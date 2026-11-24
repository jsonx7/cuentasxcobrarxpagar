# AGENTS.md

Sistema de cuentas por cobrar y pagar (Laravel 13 / PHP 8.3, MySQL, Vite 7, Tailwind 4, Alpine 3, DomPDF). UI, validaciones y mensajes en español. Las cuentas por cobrar son el espejo del módulo de préstamos, pero para **clientes externos** (no empleados). `interesmoratorio.xlsx` es la referencia tanto del plan de amortización mensual como de la fórmula de mora. **No hay importación de Excel ni reporte "Cartera por fecha"**. El sistema inicia vacío.

## Comandos

```bash
php artisan test                              # suite completa (SQLite en memoria)
php artisan test --filter=RolePermissionsTest
php artisan test tests/Feature/ReceivableManagementTest.php
composer test                                 # config:clear + php artisan test
vendor\bin\pint                               # Windows; en bash: vendor/bin/pint
npm run build                                 # obligatorio tras tocar resources/js o resources/css
php artisan migrate:fresh --seed --force      # solo local: borra y recrea la BD
```

- WAMP local: `http://localhost/cuentascyp/public`. `APP_URL` y `ASSET_URL` deben incluir esa subruta o los assets se rompen. Con `php artisan serve`, usar `http://127.0.0.1:8000` en ambas.
- WAMP no ejecuta Vite en modo dev: después de cambiar JS/CSS hay que correr `npm run build`.
- MySQL local: `127.0.0.1:3306`, base `cuentascyp`, usuario `root`, contraseña vacía.
- `APP_TIMEZONE=America/El_Salvador`; `config/app.php` lee la variable (default `UTC` si falta).
- Usuarios sembrados: `admin@cuentascyp.test / Admin123!` y `consulta@cuentascyp.test / Consulta123!` (configurables con `SEED_*`). La pantalla de login muestra esas credenciales escritas a mano: sincronizarlas si cambian.
- Los tests usan SQLite `:memory:` (`phpunit.xml`) y `RefreshDatabase`; no apuntarlos a MySQL.
- Valores por defecto de negocio en `config/cuentas.php`: `moneda=USD`, `tasa_interes_default=8`, `tasa_mora_default=5`, `dias_mora_base=30`, `unidad_material_default=unidad`.

## Dominio y trampas

- El plan de amortización sigue las tablas de `interesmoratorio.xlsx`: tasa periódica = `tasa_anual / 100 / 12`, cuota = PMT, interés = saldo pendiente × tasa periódica y vencimientos mensuales anclados al día del primer pago (`addMonthsNoOverflow`, equivalente a EDATE). Ya no se usa la tasa diaria de `plandepago.xlsx`.
- Regresión de referencia: `$1500`, 8 %, 30 meses ⇒ cuota `55.33`, interés cuota 1 `10.00`, saldo `1454.67`. Otras regresiones del Excel: `$40000`, 9 %, 36 meses ⇒ cuota `1271.99`, interés 1 `300.00`, saldo `39028.01`; `$64509`, 10 %, 60 meses ⇒ capital cuota 1 `833.05`.
- `createOptions()` solo ofrece `unico` y `mensual`. Los casos `semanal`, `bisemanal` y `quincenal` se conservan únicamente para contratos heredados; el servicio siempre genera fechas mensuales, aunque reciba una de esas frecuencias.
- `resources/js/app.js` (`addMonthsAnchored`, `receivableForm.schedule`) duplica la lógica de `AmortizationService` para la vista previa. Cualquier cambio de fechas, redondeo o última cuota debe hacerse en ambos lados y cubrirse con tests.
- Agregar una frecuencia al catálogo: `app/Enums/PaymentFrequency.php` (case, label, `daysBetween`, `addTo`), `resources/js/app.js`, los textos de `receivables/create.blade.php` y un test en `AmortizationServiceTest`. Para que aparezca en el formulario debe incluirse además en `createOptions()`; los planes generados siguen siendo mensuales. `personalizada` solo existe para planes irregulares heredados.
- Los contratos por cobrar son inmutables: solo `create`, `cancel` y `destroy`; no existen `edit/update` (intencional, igual que en préstamos). Lo mismo aplica a las obligaciones por pagar. No agregarlos sin pedirlo.
- Todo abono pasa por `ReceivableService::registerPayment` / `deletePayment` o por `PayableService::registerPayment` / `deletePayment`. Reparten con `payment_allocations` (`tipo='cuota'` o `tipo='mora'`) para poder revertir. **Nunca** modificar `monto_pagado`, `mora_pagada` ni borrar pagos directamente.
- El saldo del contrato es `total_a_pagar - total_pagado` y solo considera cuotas (capital + intereses futuros). La mora es **independiente**: se calcula y se paga aparte, nunca se capitaliza ni se suma al capital del contrato.
- Mora (`interesmoratorio.xlsx`): `mora = base × (tasa_mora_mensual / 100) / 30 × días_de_atraso`. Mientras la cuota tiene saldo, `base = capital × (saldo / monto)` con `saldo = monto - monto_pagado`. Al saldar la cuota se guarda esa base en `installments.mora_base` y los días siguen contando hasta la fecha de pago de la mora, aunque el contrato esté pagado. Cuando la mora se paga completa se guarda `installments.mora_fecha_hasta` y el conteo se detiene. Solo se calcula si `fecha_vencimiento < fecha de cálculo`. Tasa por defecto 5 % mensual (-config), sobreescrita por `receivables.tasa_mora_mensual`. Referencias: `1111.92 × 5 % / 30 × 98 = 181.61`; `833.05 × 5 % / 30 × 1 = 1.39`.
- `MoraService::pendiente()` resta `installments.mora_pagada` de la mora calculada; `MoraService::totales()` devuelve `calculada`, `pagada` y `pendiente`. Los abonos a mora crean allocations con `tipo='mora'` e incrementan `mora_pagada`. Al eliminar un abono se restituyen `mora_pagada`, `mora_base` y `mora_fecha_hasta` según corresponda. La mora es opcional e independiente: se puede pagar el préstamo completo y dejar mora pendiente; los reportes y el botón «Cobrar mora pendiente» la siguen mostrando. Los botones «Cobrar» y «Cobrar esta cuota» precargan solo el saldo de la cuota; nunca incluyen mora. La mora se cobra exclusivamente con «Cobrar mora» o «Cobrar mora pendiente», y se calcula hasta la fecha de ese pago.
- Los abonos pueden ser en dinero y/o con materiales. Catálogo de materiales con precio; cada abono lleva `payment_materials` (material, cantidad, precio unitario, monto = cantidad × precio unitario). **No se controla inventario**. Método resultante: `material` si solo hay materiales, `mixto` si además hay dinero y de lo contrario el enviado (default `efectivo`).
- Estados de cuota calculados en `Installment::getEstadoAttribute`: pagada, parcial, vencida, pendiente. El orden importa: con pago parcial se muestra `parcial` aunque la fecha ya pasó; `vencida` solo aplica a cuotas sin pagos y con fecha pasada.
- Cuentas por pagar = proveedores + obligaciones (total de la deuda, emisión, primera fecha de pago) + pagos directos con concepto. Sin PMT, sin número de cuotas, **sin mora ni intereses**. Estados: pendiente, parcial, pagado, cancelado.
- No se maneja `plazo` ni `frecuencia` en CXP: la migración `2026_11_24_000001_simplify_payables_schedule` eliminó `payable_installments` y esas columnas. `Payable::fecha_proxima_pago` calcula el recordatorio desde `fecha_primer_pago` sumando un mes por cada **abono normal** registrado (`addMonthsNoOverflow`, equivalente a EDATE); se anula al saldar o cancelar. `Payable::recordatorio_vencido` es `fecha_proxima_pago < hoy` con saldo abierto; el dashboard lo cuenta como `recordatoriosPayableVencidos`.
- Cada pago de proveedor lleva `concepto` obligatorio en `RegisterPayablePaymentRequest` y se muestra en el historial de `payables.show`. Al crear la obligación se puede registrar una **prima** (`tiene_prima`, `monto_prima`, `fecha_prima` en `StorePayableRequest`): `PayableService::create()` la convierte en un `PayablePayment` con concepto `Prima` y `es_prima=true` mediante `registerPayment`; descuenta saldo y cambia el estado, pero **no cuenta como abono** ni mueve `fecha_proxima_pago`. Se puede eliminar como cualquier pago.
- Códigos internos: `CR-{año}-{0001}` para cobrar (`ReceivableCodeGenerator`) y `CP-{año}-{0001}` para pagar (`PayableCodeGenerator`). Ambos `Store*Request` aceptan un `codigo` manual opcional (mayúsculas, único) y los servicios usan el generador solo si viene vacío.
- Los enums se persisten como varchar (`users.role`, `receivables.frecuencia`, `receivables.estado`, `payables.estado`, `payments.metodo`): agregar casos no requiere migración.
- **No existen** `app/Imports`, `app/Exports`, rutas ni vistas de importación/exportación de Excel, ni "Cartera por fecha". No reintroducirlos.

## Roles y rutas

- Roles: `admin` (todo, incluido usuarios), `operador` (clientes, materiales, proveedores, contratos, abonos y obligaciones; sin usuarios), `consulta` (solo lectura, incluidos PDF y reportes).
- Usar `User::canWrite()` para acciones operativas y `User::isAdmin()` solo para el módulo de usuarios. En Blade: `$user?->canWrite()` vs `$user?->isAdmin()`.
- En `routes/web.php` las rutas operativas usan `role:admin,operador`; el resource `usuarios` está anidado en `role:admin`. Los FormRequest operativos autorizan con `canWrite()`.
- URIs en español, nombres de ruta en inglés: `/clientes` = `clients.*`, `/materiales` = `materials.*`, `/proveedores` = `suppliers.*`, `/cuentas-por-cobrar` = `receivables.*`, `/abonos` = `payments.*`, `/cuentas-por-pagar` = `payables.*`, `/pagos` = `payable-payments.*`. Respetar el mapeo `->names()`.
- Los resources usan parámetros `{client}`, `{material}`, `{supplier}`, `{receivable}`, `{payment}`, `{payable}`, `{payablePayment}` y `{user}` (no en español), con `whereNumber` para evitar colisiones con `create`.
- Laravel 13 no tiene `app/Http/Kernel.php`: el alias `role` y el middleware se registran en `bootstrap/app.php`.

## Despliegue

- Dockerfile multi-stage: assets con Node 22, runtime `serversideup/php:8.3-fpm-nginx`, puerto **8080**.
- `AUTORUN_ENABLED=false`: tras desplegar ejecutar `php artisan migrate --force && php artisan db:seed --force` (el seeder es idempotente). `public/build` está en `.gitignore` y se genera dentro de la imagen.
- Coolify: usar `.env.production.example`; `SESSION_SECURE_COOKIE=true` con HTTPS y `false` con IP/HTTP.
- PDFs con dompdf (`barryvdh/laravel-dompdf`); las vistas PDF fuerzan `DejaVu Sans` para acentos. Consolidado y vencidas en `letter landscape`; contrato en `letter portrait`.

## Convenciones

- Texto visible al usuario, validaciones y mensajes en español; identificadores de código en inglés.
- Formatear PHP con Pint y ejecutar `npm run build` tras tocar JS/CSS antes de terminar.
- No hacer commit ni cambiar de rama sin que se solicite.
