# AGENTS.md

Sistema de cuentas por cobrar y pagar (Laravel 13 / PHP 8.3, MySQL, Vite 7, Tailwind 4, Alpine 3, DomPDF). UI, validaciones y mensajes en español. Las cuentas por cobrar son el espejo del módulo de préstamos, pero para **clientes externos** (no empleados). `plandepago.xlsx` es la referencia de cálculo del plan de amortización e `interesmoratorio.xlsx` la de la fórmula de mora. **No hay importación de Excel ni reporte "Cartera por fecha"**. El sistema inicia vacío.

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

- El plan de amortización sigue `plandepago.xlsx`: tasa periódica = `tasa_anual / 100 / 365`, cuota = PMT, interés = saldo pendiente × tasa periódica. No sustituirlo por una amortización estándar.
- Regresión de referencia: `$1500`, 8 %, 30 cuotas semanales ⇒ cuota `50.17`, interés cuota 1 `0.33`, saldo `1450.16`.
- Mensual se ancla al día del primer pago con `addMonthsNoOverflow` (`31/01 → 28/02 → 31/03`). Bisemanal = +14 días; quincenal = +15 días; no son equivalentes.
- `resources/js/app.js` (`addFrequency`, `receivableForm.schedule`) duplica la lógica de `AmortizationService` para la vista previa. Cualquier cambio de fechas, redondeo o última cuota debe hacerse en ambos lados y cubrirse con tests.
- Agregar una frecuencia: `app/Enums/PaymentFrequency.php` (case, label, `daysBetween`, `addTo`), `resources/js/app.js`, el texto "Cada X días" en `receivables/create.blade.php` y un test en `AmortizationServiceTest`. `createOptions()` excluye `personalizada`; esa frecuencia solo existe para planes irregulares heredados.
- Los contratos por cobrar son inmutables: solo `create`, `cancel` y `destroy`; no existen `edit/update` (intencional, igual que en préstamos). Lo mismo aplica a las obligaciones por pagar. No agregarlos sin pedirlo.
- Todo abono pasa por `ReceivableService::registerPayment` / `deletePayment` o por `PayableService::registerPayment` / `deletePayment`. Reparten con `payment_allocations` (`tipo='cuota'` o `tipo='mora'`) para poder revertir. **Nunca** modificar `monto_pagado`, `mora_pagada` ni borrar pagos directamente.
- El saldo del contrato es `total_a_pagar - total_pagado` y solo considera cuotas (capital + intereses futuros). La mora es **independiente**: se calcula y se paga aparte, nunca se capitaliza ni se suma al capital del contrato.
- Mora (`interesmoratorio.xlsx`): `mora = capital × (saldo / monto) × (tasa_mora_mensual / 100) / 30 × días_de_atraso`, donde `saldo = monto - monto_pagado` de la cuota. Solo si hay saldo y `fecha_vencimiento < fecha de cálculo`. Tasa por defecto 5 % mensual (-config), sobreescrita por `receivables.tasa_mora_mensual`. Referencias: `1111.92 × 5 % / 30 × 98 = 181.61`; `833.05 × 5 % / 30 × 1 = 1.39`.
- `MoraService::pendiente()` resta `installments.mora_pagada` de la mora calculada; `MoraService::totales()` devuelve `calculada`, `pagada` y `pendiente`. Los abonos a mora crean allocations con `tipo='mora'` e incrementan `mora_pagada`.
- Los abonos pueden ser en dinero y/o con materiales. Catálogo de materiales con precio; cada abono lleva `payment_materials` (material, cantidad, precio unitario, monto = cantidad × precio unitario). **No se controla inventario**. Método resultante: `material` si solo hay materiales, `mixto` si además hay dinero y de lo contrario el enviado (default `efectivo`).
- Estados de cuota calculados en `Installment::getEstadoAttribute`: pagada, parcial, vencida, pendiente. El orden importa: con pago parcial se muestra `parcial` aunque la fecha ya pasó; `vencida` solo aplica a cuotas sin pagos y con fecha pasada.
- Cuentas por pagar = proveedores + obligaciones (monto, emisión, vencimiento) + pagos directos. Sin PMT ni plan de amortización. Estados: pendiente, parcial, pagado, cancelado.
- Códigos internos: `CR-{año}-{0001}` para cobrar (`ReceivableCodeGenerator`) y `CP-{año}-{0001}` para pagar (`PayableCodeGenerator`).
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
