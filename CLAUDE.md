# CLAUDE.md — Guía Local para Claude Code

> Instrucciones operacionales para trabajar con Sistema de Ventas en Claude Code.
>
> Para **arquitectura, convenciones de código, stack tecnológico y prohibiciones explícitas**, ver [AGENT.md](AGENT.md).

---

## Ejecutar la Aplicación

Proyecto basado en XAMPP — Apache sirve los archivos directamente.

**Instalación de dependencias (primera vez):**

```bash
composer install
cp .env.example .env
# Editar .env con las credenciales reales
```

**Linux** — directorio del proyecto: `/opt/lampp/htdocs/Sistema_de_Ventas_PHP/`

```bash
sudo /opt/lampp/lampp start
# o servicios individuales:
sudo /opt/lampp/bin/apachectl start
sudo /opt/lampp/bin/mysql start
```

**Windows** — directorio del proyecto: `C:\xampp\htdocs\Sistema_de_Ventas_PHP\`

```bat
# Usar el panel de control XAMPP (xampp-control.exe) o desde CMD como administrador:
C:\xampp\xampp_start.exe
```

**macOS** — directorio del proyecto: `/Applications/XAMPP/htdocs/Sistema_de_Ventas_PHP/`

```bash
sudo /Applications/XAMPP/xamppfiles/xampp start
# o servicios individuales:
sudo /Applications/XAMPP/xamppfiles/bin/apachectl start
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
```

**Acceder a la app:** `http://localhost/Sistema_de_Ventas_PHP/public/`

> Ajustar `APP_URL` en `.env` si el nombre del directorio difiere. El valor debe incluir `/public`.

---

## Configuración de Base de Datos

**Linux/macOS:**

```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < database/schema.sql
mysql -u root -p sistemadeventas < database/seeder.sql
```

**Windows** (desde `C:\xampp\mysql\bin\`):

```bat
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\schema.sql
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\seeder.sql
```

El seeder crea usuarios de prueba:

- `admin@sistema.com` / `admin123`
- `vendedor@sistema.com` / `vendedor123`
- `comprador@sistema.com` / `comprador123`

---

## Configuración (.env)

```env
DB_HOST=localhost
DB_NAME=sistemadeventas
DB_USER=root
DB_PASS=root
APP_URL=http://localhost/Sistema_de_Ventas_PHP/public
APP_TIMEZONE=America/La_Paz
APP_DEBUG=false
SESSION_LIFETIME=60
REMEMBER_LIFETIME=14

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=xxxx_xxxx_xxxx_xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="Sistema de Ventas"
```

> `APP_DEBUG=true` activa el modo desarrollo: muestra el link de restablecimiento en pantalla
> en lugar de (solo) enviarlo por email. Usar `false` en producción.
>
> `MAIL_PASSWORD` debe ser una **Contraseña de Aplicación** de Google — no la contraseña de tu cuenta.

`public/index.php` carga `.env` vía phpdotenv y expone:

- `BASE_PATH` — ruta absoluta al directorio raíz
- `BASE_URL` — URL base sin trailing slash (disponible globalmente)
- `$pdo`, `$Año`, `$fechaHora` — compatibilidad

---

## Estructura de Rutas

| Método | Ruta                           | Controller                               | Middleware       |
| ------ | ------------------------------ | ---------------------------------------- | ---------------- |
| GET    | `/`                            | `DashboardController::index()`           | `auth`           |
| GET    | `/profile`                     | `UserController::profile()`              | `auth`           |
| POST   | `/profile/update`              | `UserController::updateProfile()`        | `auth`           |
| POST   | `/profile/password`            | `UserController::updatePassword()`       | `auth`           |
| GET    | `/auth`                        | `AuthController::showLogin()`            | `guest`          |
| POST   | `/auth/login`                  | `AuthController::store()`                | `guest`          |
| GET    | `/auth/logout`                 | `AuthController::logout()`               | `auth`           |
| GET    | `/auth/forgot-password`        | `AuthController::forgotPassword()`       | `guest`          |
| POST   | `/auth/forgot-password`        | `AuthController::sendResetLink()`        | `guest`          |
| GET    | `/auth/reset-password/{token}` | `AuthController::showResetForm()`        | `guest`          |
| POST   | `/auth/reset-password`         | `AuthController::resetPassword()`        | `guest`          |
| GET    | `/users`                       | `UserController::index()`                | `auth`, `admin`  |
| GET    | `/users/create`                | `UserController::create()`               | `auth`, `admin`  |
| POST   | `/users`                       | `UserController::store()`                | `auth`, `admin`  |
| GET    | `/users/edit/{id}`             | `UserController::edit()`                 | `auth`, `admin`  |
| POST   | `/users/update`                | `UserController::update()`               | `auth`, `admin`  |
| GET    | `/users/delete/{id}`           | `UserController::delete()`               | `auth`, `admin`  |
| POST   | `/users/delete`                | `UserController::destroy()`              | `auth`, `admin`  |
| GET    | `/roles`                       | `RoleController::index()`                | `auth`, `admin`  |
| POST   | `/roles/store`                 | `RoleController::store()`                | `auth`, `admin`  |
| GET    | `/roles/show/{id}`             | `RoleController::show()`                 | `auth`, `admin`  |
| POST   | `/roles/update/{id}`           | `RoleController::update()`               | `auth`, `admin`  |
| POST   | `/roles/check-nombre`          | `RoleController::checkNombre()`          | `auth`, `admin`  |
| GET    | `/categories`                  | `CategoryController::index()`            | `auth`           |
| POST   | `/categories/store`            | `CategoryController::store()`            | `auth`           |
| GET    | `/categories/show/{id}`        | `CategoryController::show()`             | `auth`           |
| POST   | `/categories/update/{id}`      | `CategoryController::update()`           | `auth`           |
| POST   | `/categories/check-nombre`     | `CategoryController::checkNombre()`      | `auth`           |
| GET    | `/suppliers`                   | `SupplierController::index()`            | `auth`           |
| POST   | `/suppliers/store`             | `SupplierController::store()`            | `auth`           |
| GET    | `/suppliers/show/{id}`         | `SupplierController::show()`             | `auth`           |
| POST   | `/suppliers/update/{id}`       | `SupplierController::update()`           | `auth`           |
| POST   | `/suppliers/check-nombre`      | `SupplierController::checkNombre()`      | `auth`           |
| POST   | `/suppliers/delete`            | `SupplierController::destroy()`          | `auth`           |
| GET    | `/clients`                     | `ClientController::index()`              | `auth`           |
| POST   | `/clients/store`               | `ClientController::store()`              | `auth`           |
| POST   | `/clients/check-nit-ci`        | `ClientController::checkNitCi()`         | `auth`           |
| POST   | `/clients/check-email`         | `ClientController::checkEmail()`         | `auth`           |
| GET    | `/clients/show/{id}`           | `ClientController::show()`               | `auth`           |
| POST   | `/clients/update/{id}`         | `ClientController::update()`             | `auth`           |
| POST   | `/clients/delete`              | `ClientController::destroy()`            | `auth`           |
| GET    | `/products`                    | `ProductController::index()`             | `auth`           |
| GET    | `/products/show/{id}`          | `ProductController::show()`              | `auth`           |
| GET    | `/products/create`             | `ProductController::create()`            | `auth`           |
| POST   | `/products`                    | `ProductController::store()`             | `auth`           |
| GET    | `/products/edit/{id}`          | `ProductController::edit()`              | `auth`           |
| POST   | `/products/update`             | `ProductController::update()`            | `auth`           |
| GET    | `/products/check/{id}`         | `ProductController::check()`             | `auth`           |
| GET    | `/products/delete/{id}`        | `ProductController::delete()`            | `auth`           |
| POST   | `/products/delete`             | `ProductController::destroy()`           | `auth`           |
| GET    | `/purchases`                   | `PurchaseController::index()`            | `auth`           |
| GET    | `/purchases/create`            | `PurchaseController::create()`           | `auth`           |
| POST   | `/purchases`                   | `PurchaseController::store()`            | `auth`           |
| GET    | `/purchases/show/{id}`         | `PurchaseController::show()`             | `auth`           |
| GET    | `/purchases/report/{id}`       | `PurchaseController::report()`           | `auth`           |
| GET    | `/purchases/edit/{id}`         | `PurchaseController::edit()`             | `auth`           |
| POST   | `/purchases/update`            | `PurchaseController::update()`           | `auth`           |
| POST   | `/purchases/delete`            | `PurchaseController::destroy()`          | `auth`           |
| GET    | `/sales`                       | `SaleController::index()`                | `auth`, `seller` |
| GET    | `/sales/create`                | `SaleController::create()`               | `auth`, `seller` |
| POST   | `/sales/cart/add`              | `SaleController::addToCart()`            | `auth`, `seller` |
| POST   | `/sales/cart/remove`           | `SaleController::removeFromCart()`       | `auth`, `seller` |
| POST   | `/sales`                       | `SaleController::store()`                | `auth`, `seller` |
| GET    | `/sales/show/{id}`             | `SaleController::show()`                 | `auth`, `seller` |
| GET    | `/sales/delete/{id}`           | `SaleController::confirmDelete()`        | `auth`, `seller` |
| GET    | `/sales/invoice/{id}`          | `SaleController::invoice()`              | `auth`, `seller` |
| POST   | `/sales/delete`                | `SaleController::destroy()`              | `auth`, `seller` |
| GET    | `/activity-log`                | `ActivityLogController::index()`         | `auth`, `admin`  |
| GET    | `/activity-log/show/{id}`      | `ActivityLogController::show()`          | `auth`, `admin`  |
| GET    | `/inventory`                   | `InventoryController::index()`           | `auth`, `admin`  |
| POST   | `/inventory/adjustments`       | `InventoryController::storeAdjustment()` | `auth`, `admin`  |
| GET    | `/reports`                     | `ReportController::index()`              | `auth`           |
| GET    | `/reports/sales`               | `ReportController::sales()`              | `auth`, `seller` |
| GET    | `/reports/purchases`           | `ReportController::purchases()`          | `auth`, `admin`  |
| GET    | `/reports/top-products`        | `ReportController::topProducts()`        | `auth`, `seller` |
| GET    | `/reports/clients`             | `ReportController::clients()`            | `auth`, `admin`  |

---

## Archivos de Referencia

| Archivo                  | Propósito                                                                 |
| ------------------------ | ------------------------------------------------------------------------- |
| [AGENT.md](AGENT.md)     | Arquitectura MVC, convenciones de código, stack, prohibiciones explícitas |
| [PROMPTS.md](PROMPTS.md) | Plantillas de prompts para migración, debugging, code review              |

---

## Estructura de Directorios

```
Sistema_de_Ventas_PHP/
├── app/
│   ├── Core/          ← Base: Router, Database, Controller, Model, Auth, Config, Middleware
│   ├── Controllers/   ← PSR-4, namespace App\Controllers
│   ├── Models/        ← PSR-4, namespace App\Models
│   ├── Middleware/    ← AuthMiddleware, AdminMiddleware, GuestMiddleware, SellerMiddleware
│   ├── Helpers/       ← PSR-4, NumberToWords, InvoicePdf, PurchaseReportPdf, ActivityLogRenderer, ReportFilters, ReportPdf
│   └── Services/      ← PSR-4, namespace App\Services (EmailService)
├── views/
│   ├── layouts/       ← header.php, footer.php, messages.php, partials/_sidebar.php
│   ├── [modulo]/      ← índice, create, edit, show (por cada módulo)
│   │   └── partial/   ← partials de modales cuando el módulo los usa (ej: suppliers/partial/_modals.php)
│   └── errors/        ← 404, 403, 500
├── routes/
│   └── web.php        ← Todas las rutas MVC
├── public/
│   ├── index.php      ← Entry point (front controller)
│   ├── .htaccess      ← Redirige al Router
│   ├── css/
│   │   ├── core/      ← Utilitarios globales
│   │   ├── modules/   ← CSS por módulo
│   │   ├── lib/       ← Vendors CSS (adminlte/, fontawesome/, bootstrap/)
│   │   └── plugins/   ← Plugins CSS (sweetalert2/, datatables/, select2/)
│   ├── js/
│   │   ├── core/      ← Utilitarios globales (sweetalert-utils.js, control_sidebar.js)
│   │   ├── modules/   ← JS por módulo
│   │   ├── lib/       ← Vendors JS (jquery/, bootstrap/, adminlte/)
│   │   └── plugins/   ← Plugins JS (sweetalert2/)
│   ├── uploads/       ← Imágenes de productos (chmod 755)
│   └── templates/     ← AdminLTE fuente completa (no modificar)
└── database/
    ├── schema.sql
    └── seeder.sql
```

---

## Permisos de Archivos

```bash
chmod 755 public/uploads/products/  # Directorio de carga de imágenes
```

---

## Tablas Principales de Base de Datos

| Tabla              | Propósito                                                                                                             |
| ------------------ | --------------------------------------------------------------------------------------------------------------------- |
| `tb_almacen`       | Productos/inventario con stock, precios e imágenes                                                                    |
| `tb_ventas`        | Encabezados de venta (vinculados a `tb_carrito` para ítems)                                                           |
| `tb_carrito`       | Ítems de venta (producto y cantidad por venta)                                                                        |
| `tb_compras`       | Registros de compras a proveedores                                                                                    |
| `tb_clientes`      | Base de datos de clientes                                                                                             |
| `tb_proveedores`   | Base de datos de proveedores                                                                                          |
| `tb_usuarios`      | Usuarios con contraseñas hasheadas, FK de rol, tokens de restablecimiento y remember_token para "Recordarme"          |
| `tb_roles`         | Definiciones de roles                                                                                                 |
| `tb_categorias`    | Categorías de productos                                                                                               |
| `tb_activity_log`  | Auditoría de operaciones sensibles (delete, price_change, role_change, stock_adjustment); FK nullable a `tb_usuarios` |
| `tb_ajustes_stock` | Historial de ajustes manuales de stock (entrada/salida); FK a `tb_almacen` y `tb_usuarios` (nullable)                 |

Convenciones:

- `fyh_creacion` — `DEFAULT CURRENT_TIMESTAMP`; no insertar manualmente
- `fyh_actualizacion` — `ON UPDATE CURRENT_TIMESTAMP`; queda `NULL` al crear
- Precios: `DECIMAL(10,2)`, no VARCHAR

---

## Testing

### Correr los tests

```bash
# Todos los tests
composer test

# Solo Unit (rápido, sin BD — ideal antes de un commit)
composer test:unit

# Solo Integration (SQLite in-memory)
composer test:integration

# Con reporte de cobertura (requiere PCOV o Xdebug)
composer test:coverage
```

### Suites

| Suite         | Directorio           | Estrategia                        |
| ------------- | -------------------- | --------------------------------- |
| `Unit`        | `tests/Unit/`        | Lógica pura, sin BD               |
| `Integration` | `tests/Integration/` | SQLite in-memory, schema completo |

### Convenciones

- Cada Integration test arranca con BD limpia via el trait `RefreshDatabase`.
- Seeders mínimos por test — solo los registros que el test necesita.
- PHPUnit 11: usar `#[\PHPUnit\Framework\Attributes\DataProvider('method')]` en lugar de `@dataProvider` en docblocks.

### Mantener el schema SQLite sincronizado

`tests/fixtures/schema.sqlite.sql` es la versión SQLite-compatible de `database/schema.sql`.
**Cada vez que se agregue una columna o tabla nueva a `schema.sql`, actualizar también `schema.sqlite.sql`** con las diferencias de sintaxis:

| MySQL                         | SQLite equivalente |
| ----------------------------- | ------------------ |
| `AUTO_INCREMENT`              | `AUTOINCREMENT`    |
| `DECIMAL(10,2)`               | `NUMERIC`          |
| `datetime`                    | `TEXT`             |
| `ON UPDATE CURRENT_TIMESTAMP` | (omitir)           |
| `ENGINE=InnoDB CHARSET=`      | (omitir)           |

### Lo que NO se testea

Controllers, Middleware, Vistas y Router quedan fuera del scope de tests automáticos.

---

## Referencia Rápida: Estado de Migración MVC

Todos los módulos están migrados a MVC. Ver [AGENT.md](AGENT.md) para detalles de cada módulo (fat model, patrones de
eliminación, validaciones).

| Módulo         | Estado          |
| -------------- | --------------- |
| `roles`        | ✅ Migrado      |
| `categories`   | ✅ Migrado      |
| `suppliers`    | ✅ Migrado      |
| `clients`      | ✅ Migrado      |
| `almacen`      | ✅ Migrado      |
| `compras`      | ✅ Migrado      |
| `ventas`       | ✅ Migrado      |
| `perfil`       | ✅ Implementado |
| `activity-log` | ✅ Implementado |
| `inventory`    | ✅ Implementado |
| `reports`      | ✅ Implementado |

---

_Última actualización: 2026-06-09 — v1.12.0_
