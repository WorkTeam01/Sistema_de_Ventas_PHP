# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Archivos de referencia del equipo

| Archivo                  | Propósito                                                                                |
|--------------------------|------------------------------------------------------------------------------------------|
| [AGENT.md](AGENT.md)     | Context persistente para agentes IA — arquitectura completa, convenciones, prohibiciones |
| [PROMPTS.md](PROMPTS.md) | Plantillas de prompts para el equipo — migración, debugging, code review, arquitectura   |

## Descripción del Proyecto

Sistema de Ventas es un sistema de gestión de ventas en PHP/MySQL con control de inventario, facturación, gestión de
clientes y acceso por roles. La interfaz usa AdminLTE 3.2.0 (Bootstrap 4).

## Ejecutar la Aplicación

Proyecto basado en XAMPP. Apache sirve los archivos directamente. Requiere Composer para PSR-4 autoloading y phpdotenv.

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

**Configuración de base de datos (primera vez):**

Linux/macOS:

```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < database/schema.sql
mysql -u root -p sistemadeventas < database/seeder.sql
```

El seeder crea usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`,
`comprador@sistema.com` / `comprador123`.

Windows (desde CMD en `C:\xampp\mysql\bin\`):

```bat
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\schema.sql
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\seeders\roles.sql
```

## Configuración

Las credenciales están en `.env` (no se commitea — copiar de `.env.example`):

```
DB_HOST=localhost
DB_NAME=sistemadeventas
DB_USER=root
DB_PASS=root
APP_URL=http://localhost/Sistema_de_Ventas_PHP/public
APP_TIMEZONE=America/La_Paz
```

`public/index.php` carga `.env` vía phpdotenv y expone:

- `BASE_PATH` — constante PHP con la ruta absoluta al directorio raíz del proyecto
- `BASE_URL` — constante PHP global con la URL base (sin trailing slash), disponible en cualquier archivo sin necesidad
  de pasarla como variable
- `$URL = BASE_URL` — alias backward-compat
- `$pdo`, `$Año`, `$fechaHora` — compatibilidad con módulos existentes

## Arquitectura

### Enrutamiento

Todas las rutas pasan por `public/index.php` vía `.htaccess` → `App\Core\Router` → Controller.

Rutas activas en `routes/web.php`:

| Método | Ruta                       | Controller                          | Middleware       |
|--------|----------------------------|-------------------------------------|------------------|
| GET    | `/`                        | `DashboardController::index()`      | `auth`           |
| GET    | `/profile`                 | `UserController::profile()`         | `auth`           |
| POST   | `/profile/update`          | `UserController::updateProfile()`   | `auth`           |
| POST   | `/profile/password`        | `UserController::updatePassword()`  | `auth`           |
| GET    | `/auth`                    | `AuthController::showLogin()`       | `guest`          |
| POST   | `/auth/login`              | `AuthController::store()`           | `guest`          |
| GET    | `/auth/logout`             | `AuthController::logout()`          | `auth`           |
| GET    | `/users`                   | `UserController::index()`           | `auth`, `admin`  |
| GET    | `/users/create`            | `UserController::create()`          | `auth`, `admin`  |
| POST   | `/users`                   | `UserController::store()`           | `auth`, `admin`  |
| GET    | `/users/edit/{id}`         | `UserController::edit()`            | `auth`, `admin`  |
| POST   | `/users/update`            | `UserController::update()`          | `auth`, `admin`  |
| GET    | `/users/delete/{id}`       | `UserController::delete()`          | `auth`, `admin`  |
| POST   | `/users/delete`            | `UserController::destroy()`         | `auth`, `admin`  |
| GET    | `/roles`                   | `RoleController::index()`           | `auth`, `admin`  |
| POST   | `/roles/store`             | `RoleController::store()`           | `auth`, `admin`  |
| GET    | `/roles/show/{id}`         | `RoleController::show()`            | `auth`, `admin`  |
| POST   | `/roles/update/{id}`       | `RoleController::update()`          | `auth`, `admin`  |
| POST   | `/roles/check-nombre`      | `RoleController::checkNombre()`     | `auth`, `admin`  |
| GET    | `/categories`              | `CategoryController::index()`       | `auth`           |
| POST   | `/categories/store`        | `CategoryController::store()`       | `auth`           |
| GET    | `/categories/show/{id}`    | `CategoryController::show()`        | `auth`           |
| POST   | `/categories/update/{id}`  | `CategoryController::update()`      | `auth`           |
| POST   | `/categories/check-nombre` | `CategoryController::checkNombre()` | `auth`           |
| GET    | `/suppliers`               | `SupplierController::index()`       | `auth`           |
| POST   | `/suppliers/store`         | `SupplierController::store()`       | `auth`           |
| GET    | `/suppliers/show/{id}`     | `SupplierController::show()`        | `auth`           |
| POST   | `/suppliers/update/{id}`   | `SupplierController::update()`      | `auth`           |
| POST   | `/suppliers/check-nombre`  | `SupplierController::checkNombre()` | `auth`           |
| POST   | `/suppliers/delete`        | `SupplierController::destroy()`     | `auth`           |
| GET    | `/clients`                 | `ClientController::index()`         | `auth`           |
| POST   | `/clients/store`           | `ClientController::store()`         | `auth`           |
| POST   | `/clients/check-nit-ci`    | `ClientController::checkNitCi()`    | `auth`           |
| POST   | `/clients/check-email`     | `ClientController::checkEmail()`    | `auth`           |
| GET    | `/clients/show/{id}`       | `ClientController::show()`          | `auth`           |
| POST   | `/clients/update/{id}`     | `ClientController::update()`        | `auth`           |
| POST   | `/clients/delete`          | `ClientController::destroy()`       | `auth`           |
| GET    | `/products`                | `ProductController::index()`        | `auth`           |
| GET    | `/products/show/{id}`      | `ProductController::show()`         | `auth`           |
| GET    | `/products/create`         | `ProductController::create()`       | `auth`           |
| POST   | `/products`                | `ProductController::store()`        | `auth`           |
| GET    | `/products/edit/{id}`      | `ProductController::edit()`         | `auth`           |
| POST   | `/products/update`         | `ProductController::update()`       | `auth`           |
| GET    | `/products/check/{id}`     | `ProductController::check()`        | `auth`           |
| GET    | `/products/delete/{id}`    | `ProductController::delete()`       | `auth`           |
| POST   | `/products/delete`         | `ProductController::destroy()`      | `auth`           |
| GET    | `/purchases`               | `PurchaseController::index()`       | `auth`           |
| GET    | `/purchases/create`        | `PurchaseController::create()`      | `auth`           |
| POST   | `/purchases`               | `PurchaseController::store()`       | `auth`           |
| GET    | `/purchases/show/{id}`     | `PurchaseController::show()`        | `auth`           |
| GET    | `/purchases/edit/{id}`     | `PurchaseController::edit()`        | `auth`           |
| POST   | `/purchases/update`        | `PurchaseController::update()`      | `auth`           |
| POST   | `/purchases/delete`        | `PurchaseController::destroy()`     | `auth`           |
| GET    | `/sales`                   | `SaleController::index()`           | `auth`, `seller` |
| GET    | `/sales/create`            | `SaleController::create()`          | `auth`, `seller` |
| POST   | `/sales/cart/add`          | `SaleController::addToCart()`       | `auth`, `seller` |
| POST   | `/sales/cart/remove`       | `SaleController::removeFromCart()`  | `auth`, `seller` |
| POST   | `/sales`                   | `SaleController::store()`           | `auth`, `seller` |
| GET    | `/sales/show/{id}`         | `SaleController::show()`            | `auth`, `seller` |
| GET    | `/sales/delete/{id}`       | `SaleController::confirmDelete()`   | `auth`, `seller` |
| GET    | `/sales/invoice/{id}`      | `SaleController::invoice()`         | `auth`, `seller` |
| POST   | `/sales/delete`            | `SaleController::destroy()`         | `auth`, `seller` |

### Clases Core MVC (`app/Core/`)

| Clase                 | Descripción                                                                                                                            |
|-----------------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `App\Core\Database`   | Singleton PDO — `Database::getInstance()->getConnection()`                                                                             |
| `App\Core\Router`     | Registra y despacha rutas GET/POST con middleware                                                                                      |
| `App\Core\Controller` | Base: `view()`, `renderWithLayout()`, `redirect(): never`, `json(): never`, `input()`, `validate()`                                    |
| `App\Core\Model`      | Base abstracta: `all()`, `find()`, `create()`, `update()`, `delete()`, `count()`, `query()` — `insert()`/`findAll()` son `@deprecated` |
| `App\Core\Auth`       | Sesión y CSRF: `check()`, `user()`, `role()`, `login()`, `logout()`, `generateCsrfToken()`                                             |
| `App\Core\Config`     | Wrapper de `.env`: `Config::get('KEY', $default)`                                                                                      |
| `App\Core\Middleware` | Interfaz: `handle(): bool`                                                                                                             |

Controladores en `app/Controllers/` (PSR-4, namespace `App\Controllers`): `AuthController`, `DashboardController`,
`UserController`, `RoleController`, `CategoryController`, `SupplierController`, `ClientController`, `ProductController`,
`PurchaseController`, `SaleController`.
Modelos en `app/Models/` (PSR-4, namespace `App\Models`): `User`, `Role`, `Category`, `Supplier`, `Client`, `Product`,
`Purchase`, `Sale`, `CartItem`.
Helper en `app/Helpers/` (PSR-4, namespace `App\Helpers`): `NumberToWords`.

### Estado de Migración MVC

| Módulo       | Estado         | Notas                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------|----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `roles`      | ✅ Migrado      | `RoleController`, `Role`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `categories` | ✅ Migrado      | `CategoryController`, `Category`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         || `suppliers`  | ✅ Migrado | `SupplierController`, `Supplier` — modal + AJAX; `nameExists()` sobre `empresa`; `isReferenced()` → `tb_compras`; eliminación inline con JSON            |
| `clients`    | ✅ Migrado      | `ClientController`, `Client` — modal + AJAX; `nitCiExists()` + `emailExists()` para duplicados; `isReferenced()` → `tb_ventas`; eliminación inline con JSON; JS modularizado en `clients-datatable.js` / `clients-modals.js`                                                                                                                                                                                                                                                                                                                                                                                                             |
| `almacen`    | ✅ Migrado      | `ProductController`, `Product` — imágenes en `public/uploads/products/`; incluye vista `show`; `create` y `edit` usan patrón two-pane sticky sidebar (col-8 formulario / col-4 sidebar resumen con margen en tiempo real); CSS en `public/css/modules/products/create.css`                                                                                                                                                                                                                                                                                                                                                               |
| `compras`    | ✅ Migrado      | `PurchaseController`, `Purchase` — operaciones transaccionales con stock; JS modularizado en `purchases-index.js` / `purchases-create.js` / `purchases-edit.js`; `AlertUtils.confirm()` para eliminación (sin `isReferenced()` — `tb_compras` no es referenciada); incluye vista `show`; `create` y `edit` usan patrón two-pane sticky sidebar (col-8 formulario / col-4 sidebar resumen con total en tiempo real); CSS en `public/css/modules/purchases/create.css`                                                                                                                                                                     |
| `ventas`     | ✅ Migrado      | `SaleController`, `Sale`, `CartItem` — carrito en BD, TCPDF inline (`tecnickcom/tcpdf`), `SellerMiddleware`; JS modularizado en `sales-index.js` / `sales-create.js`; `AlertUtils.warning()` para validaciones POS; incluye vistas `show`, `delete`, `invoice`; `create` usa patrón wizard de 3 tabs numerados (1. Cliente → 2. Carrito → 3. Pago) con barra de progreso animada, validación entre pasos y sidebar sticky "Resumen de venta" (col-md-9 + col-md-3); CSS en `public/css/modules/sales/create.css`                                                                                                                         |
| `perfil`     | ✅ Implementado | `UserController::profile/updateProfile/updatePassword` — perfil propio accesible a todos los roles; vista `views/users/profile.php` con 2 tabs AdminLTE (Editar perfil / Cambiar contraseña); card izquierda con avatar de iniciales, nombre, badge de rol (color por rol: `card-danger` Admin, `card-success` Vendedor, `card-warning` Comprador); toda la lógica de presentación se computa en el controlador (sin PHP en la vista); JS en `users-profile.js`; CSS en `profile.css`; `User` sigue patrón fat model: hashing de contraseñas encapsulado en el modelo (`createUser`, `updateUser`, `updatePassword` aceptan texto plano) |

### Workflow para nuevos módulos MVC

Para agregar un nuevo módulo, seguir este orden:

1. Crear `app/Models/[Nombre].php` — extender `Model`, definir `$table` y `$primaryKey`, sobreescribir `isReferenced()`
   si la tabla tiene FKs en otras tablas
2. Crear `app/Controllers/[Nombre]Controller.php` — 6 métodos: `index`, `create`, `store`, `edit`, `update`, `destroy`
3. Crear `views/[modulo]/index.php`, `create.php`, `edit.php`
4. Registrar rutas en `routes/web.php`
5. Actualizar sidebar en `views/layouts/partials/_sidebar.php` con control de rol
6. Actualizar `CHANGELOG.md`, `README.md`, `CLAUDE.md`

### Autenticación y Autorización

El login usa `App\Controllers\AuthController` (vía Router). La vista está en `views/auth/login.php`.

Las rutas se protegen con middleware en `routes/web.php`:

```php
$router->get('/ruta', [Controller::class, 'method'], ['auth']);          // cualquier rol
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'admin']);  // solo Administrador
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'seller']); // Administrador o Vendedor
```

Los middlewares PSR-4 viven en `app/Middleware/`: `AuthMiddleware`, `AdminMiddleware`, `GuestMiddleware`,
`SellerMiddleware`.

Para obtener datos del usuario en sesión: `Auth::user()`.

Roles disponibles (almacenados en `tb_roles`): `Administrador`, `Vendedor`, `Comprador`.

### Sistema de Layout

Cada página incluye plantillas compartidas:

- [views/layouts/header.php](views/layouts/header.php) — head HTML, navbar; incluye el sidebar partial
- [views/layouts/partials/_sidebar.php](views/layouts/partials/_sidebar.php) — sidebar con control de rol
- [views/layouts/footer.php](views/layouts/footer.php) — scripts de cierre, footer
- [views/layouts/messages.php](views/layouts/messages.php) — mensajes flash (welcome y toasts CRUD)

### Acceso a Base de Datos

La conexión PDO se inicializa en `public/index.php` y está disponible como `$pdo`. Todas las consultas deben usar *
*sentencias preparadas** con placeholders `?` — nunca interpolar variables directamente en el string SQL:

```php
// CORRECTO
$query = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = ?");
$query->execute([$email]);

// INCORRECTO — vulnerable a SQL injection
$query = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = '$email'");
$query->execute();
```

### Generación de PDF

Las facturas de ventas usan TCPDF (`tecnickcom/tcpdf` vía Composer). El método `SaleController::invoice()` emite el PDF
directamente (inline) sin pasar por `renderWithLayout()`.

## Estructura de Módulos MVC

Cada módulo sigue el patrón CRUD estándar:

```
views/[modulo]/
├── index.php      # Vista de listado (DataTables)
├── create.php     # Formulario de creación
├── edit.php       # Formulario de edición
└── show.php       # Vista de detalle (donde aplique)

app/Controllers/[Nombre]Controller.php   # 6 métodos: index, create, store, edit, update, destroy
app/Models/[Nombre].php                  # Extiende App\Core\Model
```

## Tablas Principales de Base de Datos

| Tabla            | Propósito                                                   |
|------------------|-------------------------------------------------------------|
| `tb_almacen`     | Productos/inventario con stock, precios e imágenes          |
| `tb_ventas`      | Encabezados de venta (vinculados a `tb_carrito` para ítems) |
| `tb_carrito`     | Ítems de venta (producto y cantidad por venta)              |
| `tb_compras`     | Registros de compras a proveedores                          |
| `tb_clientes`    | Base de datos de clientes                                   |
| `tb_proveedores` | Base de datos de proveedores                                |
| `tb_usuarios`    | Usuarios con contraseñas hasheadas y FK de rol              |
| `tb_roles`       | Definiciones de roles                                       |
| `tb_categorias`  | Categorías de productos                                     |

El stock se actualiza automáticamente al crear/eliminar ventas y al registrar compras.

Convenciones de columnas de auditoría:

- `fyh_creacion` — `DEFAULT CURRENT_TIMESTAMP`; no insertar manualmente en los controllers
- `fyh_actualizacion` — queda `NULL` al crear; se actualiza sola con `ON UPDATE CURRENT_TIMESTAMP`
- Los precios se almacenan como `DECIMAL(10,2)`, no como VARCHAR

## Convenciones de Seguridad

- **SQL**: Siempre usar placeholders `?` con `execute([$var])`. Nunca interpolar variables en el string SQL.
- **Subida de archivos**: Validar extensión (whitelist: jpg, jpeg, png, webp), MIME type real con `mime_content_type()`
  y tamaño máximo (2MB) antes de `move_uploaded_file()`.
- **JavaScript**: Usar `json_encode()` para pasar strings PHP a variables JS — nunca interpolación directa con comillas
  simples.
- **Output HTML**: Usar `htmlspecialchars()` al mostrar datos de usuario en HTML para prevenir XSS.

## Convenciones de Frontend

- **DataTables** se inicializa en cada página de listado para búsqueda, ordenamiento y exportación (
  PDF/Excel/CSV/Imprimir)
- **SweetAlert2** se usa para todas las confirmaciones de eliminación y alertas de éxito/error
- `public/js/core/sweetalert-utils.js` — provee `ToastUtils`, `AlertUtils` y `showToast()` (legacy); se carga en
  `<head>` de `layouts/header.php` y del layout del login para que esté disponible antes de `messages.php`
- `views/layouts/messages.php` — muestra `$_SESSION['welcome_user']` con `AlertUtils.welcome()` tras login, y toasts
  estándar con `showToast()` para CRUD
- **Control Sidebar:** La lógica de tema y colores usa `public/js/core/control_sidebar.js` (basado en API nativa de
  AdminLTE). Las selecciones se guardan en `localStorage` y se aplica un script Anti-FOUC directamente en
  `layouts/header.php`.
- **jQuery** es requerido y se carga vía la plantilla AdminLTE
- CSS personalizado en [public/css/](public/css/) — estructura: `core/` (utilitarios globales), `modules/[modulo]/` (
  estilos por módulo)
- JS personalizado en [public/js/](public/js/) — estructura: `core/` (utilitarios globales), `modules/[modulo]/` (JS por
  módulo)
- Assets de AdminLTE servidos desde [public/templates/](public/templates/) — no modificar estos archivos
- **Assets por vista** (`$pageStyles` / `$pageScripts`): arrays de rutas relativas a `BASE_URL` pasados como
  cuarto/quinto argumento a `renderWithLayout()`; el layout los inyecta en `<head>` y al final del `<body>`
  respectivamente. Usar para CSS/JS específicos de módulo que no deben cargarse globalmente.

## Permisos de Archivos

```bash
chmod 755 public/uploads/products/  # Directorio de carga de imágenes de productos
```

## Prohibiciones Explícitas

- **SQL**: Nunca concatenar variables en queries — siempre `?` con `execute([$var])`
- **Borrado**: Este proyecto usa borrado **físico** con `isReferenced()` — nunca borrado lógico con `is_active`
- **Controladores**: Solo los 6 métodos estándar (`index`, `create`, `store`, `edit`, `update`, `destroy`) más los
  auxiliares permitidos: `check()` (endpoint JSON de verificación de referencias) y `delete()` (página de
  confirmación) — no inventar `toggle()`, `activate()`, `complete()`, etc.
- **JavaScript**: Nunca `alert()` nativo — usar SweetAlert2; para eliminación usar el patrón de página dedicada (
  products) o formulario oculto `#formEliminar` inline según el módulo
- **PHP → JS**: Nunca interpolar strings PHP en JS con comillas simples — usar `json_encode()`
- **Templates**: No modificar archivos en `public/templates/` (AdminLTE)
- **Vistas**: No llamar `Auth::` directamente en vistas — pasar los datos desde el controlador vía `renderWithLayout()`
