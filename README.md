# Cuentas por Cobrar y Pagar

Sistema de administración de **cuentas por cobrar** (clientes externos) y **cuentas por pagar** (proveedores), construido con **Laravel 13**, **PHP 8.3**, **MySQL/MariaDB**, **Tailwind CSS 4**, **Alpine.js 3**, **Vite 7** y **DomPDF**.

UI, validaciones y mensajes en español. El plan de amortización mensual y la mora replican `interesmoratorio.xlsx`. El sistema inicia vacío: no trae importaciones de Excel ni el reporte "Cartera por fecha" del proyecto de préstamos.

---

## Funcionalidades

- **Clientes**: nombre, DUI (`00000000-0`), NIT, teléfono, correo, dirección, notas y estado. Se pueden inactivar; no se eliminan si tienen contratos.
- **Cuentas por cobrar (contratos)**:
  - Código `CR-{año}-{0001}` automático; también puedes escribir uno propio (se normaliza a mayúsculas y no puede repetirse).
  - Frecuencias al crear: pago único y mensual. Los vencimientos se anclan al día del primer pago sin desbordamiento de mes.
  - El detalle incluye un **historial de pagos** con saldo posterior por abono.
  - Plan de amortización generado con la lógica mensual de `interesmoratorio.xlsx`, con vista previa en vivo.
  - Tasa de mora mensual configurable por contrato (por defecto 5 %).
  - Contratos inmutables: se crean, se cancelan o se eliminan (sin editar). Se pueden descargar el plan de pagos y el **estado de cuenta** (deuda + historial de pagos) en PDF.
- **Abonos**:
  - En dinero, con materiales o mixtos. Cada material registra cantidad, precio unitario y monto; **no se controla inventario**.
  - Se aplican primero a las cuotas más antiguas (FIFO) o a una cuota específica, mediante `payment_allocations`.
  - Pago de mora separado del capital, con su propia asignación (`tipo='mora'`).
  - Se pueden eliminar y los saldos, estados y mora pagada se restituyen automáticamente.
  - Validación para no pagar más que el saldo de la cuota/contrato ni más mora de la pendiente.
- **Materiales**: catálogo con código, unidad, precio y estado. No se pueden eliminar si aparecen en un abono.
- **Proveedores y cuentas por pagar**:
  - Código `CP-{año}-{0001}` automático; también puedes escribir uno propio (se normaliza a mayúsculas y no puede repetirse).
  - Obligaciones con concepto, **total de la deuda**, emisión y **primera fecha de pago**; pagos directos (sin PMT ni número de cuotas).
  - Seguimiento **mensual**: la primera fecha indicada es el primer recordatorio y cada abono normal mueve la próxima fecha un mes (anclada al mismo día, equivalente a EDATE). La deuda saldada deja de mostrar recordatorio. Son solo recordatorios: **no generan intereses ni mora**. El detalle y el panel muestran la próxima fecha y avisan cuántos recordatorios están vencidos.
  - Cada pago lleva su **concepto** obligatorio, fecha, monto, método, referencia y notas.
  - Si al crear la obligación se abonó **prima o anticipo**, marca la opción e indica monto y fecha: se registra como un pago con concepto «Prima», descuenta el saldo y aparece en el historial (se puede eliminar como cualquier otro pago). La prima es un anticipo: **no mueve** la próxima fecha de pago.
  - Estados: pendiente, parcial, pagado y cancelado.
  - El detalle permite descargar el **estado de cuenta** en PDF: total de la deuda, pagado, saldo, próxima fecha e historial de pagos con concepto.
- **Reportes**: consolidado general (cobrar y pagar) y cuotas con mora pendiente —vencidas o ya pagadas— con días de atraso, ambos exportables a PDF. Panel con totales, próximos vencimientos y obligaciones por vencer.
- **Usuarios y roles**:
  - `admin`: acceso total, incluida la administración de usuarios.
  - `operador`: puede gestionar clientes, materiales, proveedores, contratos, abonos y obligaciones; no puede administrar usuarios.
  - `consulta`: solo lectura (incluye reportes y PDF).
- **Auditoría básica**: usuario que registra cada abono u obligación.

---

## Cálculo de la amortización (igual que `interesmoratorio.xlsx`)

- Tasa periódica mensual: `tasa_anual / 100 / 12`.
- Plazo: número de meses; cada mes genera una cuota.
- Cuota fija: `PMT(tasa_mensual, plazo, -monto)`.
- Interés de cada cuota: `saldo_pendiente × tasa_mensual`.
- Abono a capital: `cuota - interés`; saldo final: `saldo_anterior - abono_capital`.
- Vencimientos mensuales anclados al día del primer pago (`31/01 → 28/02 → 31/03`).
- La última cuota ajusta el saldo restante para cerrar el contrato.

Ejemplo de referencia (`monto = 1500`, `tasa = 8 %`, `plazo = 30 meses`):

| Concepto        | Valor     |
| --------------- | --------- |
| Cuota           | `55.33`   |
| Interés cuota 1 | `10.00`   |
| Saldo cuota 1   | `1454.67` |

---

## Cálculo de mora (igual que `interesmoratorio.xlsx`)

```
mora = base × (tasa_mora_mensual / 100) / 30 × días_de_atraso
```

- Mientras la cuota tiene saldo, `base = capital × (saldo / monto)`, donde `saldo = monto - monto_pagado`.
- Al saldar una cuota se guarda esa base en `installments.mora_base`. La mora sigue contando días hasta la fecha de pago de la mora, aunque el préstamo ya esté pagado.
- Al pagar la mora por completo se guarda `installments.mora_fecha_hasta` y el conteo se detiene.
- Los pagos parciales de mora no cambian la base; el resto sigue generando mora.
- Solo se calcula cuando `fecha_vencimiento < fecha de cálculo` (días corridos, sin capitalización).
- Tasa por defecto 5 % mensual; se puede sobreescribir en cada contrato.
- La mora **nunca** se suma al capital del contrato: se controla en `installments.mora_pagada` y en abonos con `tipo='mora'`. Se puede pagar de forma opcional incluso después de cancelar el préstamo.

Referencias de la hoja:

| Capital   | Tasa  | Días | Mora     |
| --------- | ----- | ---- | -------- |
| `1111.92` | 5 %   | 98   | `181.61` |
| `833.05`  | 5 %   | 1    | `1.39`   |

> El saldo del contrato es `total_a_pagar - total_pagado` (capital + intereses futuros) y no incluye la mora pendiente.

---

## Abonos con materiales

En la ficha del contrato, el modal **Nuevo abono** permite capturar dinero, monto para mora y varias filas de materiales (material, cantidad y precio unitario). El total de cada fila es `cantidad × precio_unitario` y el total del abono es `dinero + materiales`.

- Solo materiales ⇒ método `material`.
- Dinero y materiales ⇒ método `mixto`.
- Solo dinero ⇒ el método seleccionado (por defecto `efectivo`).

Al eliminar el abono se revierten las asignaciones a cuotas y a mora, y se devuelve el saldo.

---

## Requisitos

- PHP **8.3+** con extensiones `pdo_mysql`, `mbstring`, `gd`, `intl`, `zip`.
- Composer 2.
- Node.js 20+ y npm.
- MySQL 8 o MariaDB 10.6+.

Dependencias principales: `barryvdh/laravel-dompdf` (PDF). No se usan `phpspreadsheet` ni importaciones de Excel.

---

## Instalación local (WAMP)

1. Coloca el proyecto en `C:\wamp64\www\cuentascyp`.
2. Crea la base de datos:

   ```sql
   CREATE DATABASE cuentascyp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Copia y ajusta el entorno:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Para WAMP el `.env` debe incluir:

   ```env
   APP_URL=http://localhost/cuentascyp/public
   ASSET_URL=http://localhost/cuentascyp/public
   DB_DATABASE=cuentascyp
   ```

4. Instala dependencias y prepara la base de datos:

   ```bash
   composer install
   npm install
   php artisan migrate --seed --force
   npm run build
   ```

5. Abre `http://localhost/cuentascyp/public`.

> Con `php artisan serve`, ajusta `APP_URL` y `ASSET_URL` a `http://127.0.0.1:8000`. WAMP no ejecuta Vite en modo dev: tras cambiar JS/CSS corre `npm run build`.

### Usuarios iniciales

| Rol      | Correo                     | Contraseña    |
| -------- | -------------------------- | ------------- |
| Admin    | `admin@cuentascyp.test`    | `Admin123!`   |
| Consulta | `consulta@cuentascyp.test` | `Consulta123!` |

Se pueden cambiar con `SEED_ADMIN_*` y `SEED_CONSULTA_*` antes de sembrar. **Cambia las contraseñas en producción.** El rol `operador` se crea desde **Usuarios** (solo admin).

---

## Pruebas

```bash
php artisan test
php artisan test --filter=RolePermissionsTest
vendor/bin/pint
```

Los tests usan SQLite en memoria (`phpunit.xml`) y `RefreshDatabase`. Cubren la amortización de referencia, la mora del Excel, CRUD de clientes/materiales/proveedores, creación de contratos y su plan, abonos en dinero/materiales/mora y su reversión, validaciones, obligaciones por pagar, PDF y permisos por rol.

---

## Despliegue en Coolify

El `Dockerfile` multi-stage compila los assets con Node 22 y ejecuta la aplicación con `serversideup/php:8.3-fpm-nginx` en el puerto **8080**.

1. Crea un servicio **MySQL** con volumen persistente y una **Application** desde el repositorio (Build Pack `Dockerfile`, puerto `8080`).
2. Copia `.env.production.example` en las variables de entorno de la Application:
   - Genera `APP_KEY` con `php artisan key:generate --show`.
   - Ajusta `APP_URL` al dominio/IP público.
   - Copia las credenciales internas del servicio MySQL (`DB_HOST`, `DB_DATABASE=cuentascyp`, `DB_USERNAME`, `DB_PASSWORD`). Si falta `DB_CONNECTION`, Laravel usa SQLite efímero y la app falla tras el deploy.
   - `SESSION_SECURE_COOKIE=true` con HTTPS; `false` si publicas por IP en HTTP.
3. Despliega y ejecuta una vez en la terminal del contenedor:

   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

El seeder es idempotente. `AUTORUN_ENABLED=false` evita migraciones automáticas en cada arranque; los logs van a la salida estándar (`LOG_CHANNEL=stderr`) y los assets se compilan dentro de la imagen.

---

## Estructura relevante

```
app/
  Enums/                  Roles, frecuencias, estados y métodos de pago
  Http/Controllers/       Clientes, materiales, proveedores, cobros, abonos, pagos, reportes, usuarios
  Http/Requests/          Validaciones y normalización de DUI/montos
  Models/                 Client, Material, Supplier, Receivable, Installment, Payment, Payable, ...
  Services/
    AmortizationService   Plan mensual de interesmoratorio.xlsx
    MoraService           Mora de interesmoratorio.xlsx (5 % / 30, no capitalizada)
    ReceivableService     Contratos, abonos en dinero/materiales/mora y reversiones
    PayableService        Obligaciones y pagos directos
    ReceivableCodeGenerator / PayableCodeGenerator
database/
  migrations/             Esquema de cobros, pagos y catálogos
  seeders/                Usuarios iniciales
resources/
  js/app.js               Alpine: vista previa del plan y modales de abono/pago
  views/                  Interfaces Blade y PDF
tests/                    Unitarias y de integración (SQLite en memoria)
Dockerfile                Imagen de producción para Coolify
```

---

## Licencia

Uso interno. Basado en el esqueleto de Laravel (MIT).
