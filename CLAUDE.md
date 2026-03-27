# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Archivos de referencia del equipo

| Archivo | Propósito |
| ------- | --------- |
| [AGENT.md](AGENT.md) | Context persistente para agentes IA — arquitectura completa, convenciones, prohibiciones |
| [PROMPTS.md](PROMPTS.md) | Plantillas de prompts para el equipo — migración, debugging, code review, arquitectura |

## Descripción del Proyecto

Sistema de Ventas es un sistema de gestión de ventas en PHP/MySQL con control de inventario, facturación, gestión de clientes y acceso por roles. La interfaz usa AdminLTE 3.2.0 (Bootstrap 4).

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

El seeder crea usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`, `comprador@sistema.com` / `comprador123`.

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

`app/config.php` carga `.env` vía phpdotenv y expone:

- `BASE_URL` — constante PHP global con la URL base (sin trailing slash), disponible en cualquier archivo sin necesidad de pasarla como variable
- `$URL = BASE_URL` — alias backward-compat para módulos legacy
- `$pdo`, `$Año`, `$fechaHora` — compatibilidad con módulos existentes

## Arquitectura

### Enrutamiento

El ruteo es **híbrido**:

- **Módulos existentes** — ruteo implícito (archivo directo): `GET /almacen/` → `almacen/index.php`
- **Nuevas rutas** — pasan por `public/index.php` vía `.htaccess` → `App\Core\Router` → Controller

Rutas activas en `routes/web.php`:

| Método | Ruta                    | Controller                      | Middleware      |
| ------ | ----------------------- | ------------------------------- | --------------- |
| GET    | `/`                     | `DashboardController::index()`  | `auth`          |
| GET    | `/auth`                 | `AuthController::showLogin()`   | `guest`         |
| POST   | `/auth/login`           | `AuthController::store()`       | `guest`         |
| GET    | `/auth/logout`          | `AuthController::logout()`      | `auth`          |
| GET    | `/users`                | `UserController::index()`       | `auth`, `admin` |
| GET    | `/users/create`         | `UserController::create()`      | `auth`, `admin` |
| POST   | `/users`                | `UserController::store()`       | `auth`, `admin` |
| GET    | `/users/show/{id}`      | `UserController::show()`        | `auth`, `admin` |
| GET    | `/users/edit/{id}`      | `UserController::edit()`        | `auth`, `admin` |
| POST   | `/users/update`         | `UserController::update()`      | `auth`, `admin` |
| GET    | `/users/delete/{id}`    | `UserController::delete()`      | `auth`, `admin` |
| POST   | `/users/delete`         | `UserController::destroy()`     | `auth`, `admin` |
| GET    | `/roles`                | `RoleController::index()`       | `auth`, `admin` |
| GET    | `/roles/create`         | `RoleController::create()`      | `auth`, `admin` |
| POST   | `/roles`                | `RoleController::store()`       | `auth`, `admin` |
| GET    | `/roles/edit/{id}`      | `RoleController::edit()`        | `auth`, `admin` |
| POST   | `/roles/update`         | `RoleController::update()`      | `auth`, `admin` |
| GET    | `/categories`           | `CategoryController::index()`   | `auth`          |
| GET    | `/categories/create`    | `CategoryController::create()`  | `auth`          |
| POST   | `/categories`           | `CategoryController::store()`   | `auth`          |
| GET    | `/categories/edit/{id}` | `CategoryController::edit()`    | `auth`          |
| POST   | `/categories/update`    | `CategoryController::update()`  | `auth`          |
| GET    | `/suppliers`            | `SupplierController::index()`   | `auth`          |
| GET    | `/suppliers/create`     | `SupplierController::create()`  | `auth`          |
| POST   | `/suppliers`            | `SupplierController::store()`   | `auth`          |
| GET    | `/suppliers/edit/{id}`  | `SupplierController::edit()`    | `auth`          |
| POST   | `/suppliers/update`     | `SupplierController::update()`  | `auth`          |
| POST   | `/suppliers/delete`     | `SupplierController::destroy()` | `auth`          |
| GET    | `/clients`              | `ClientController::index()`     | `auth`          |
| GET    | `/clients/create`       | `ClientController::create()`    | `auth`          |
| POST   | `/clients`              | `ClientController::store()`     | `auth`          |
| GET    | `/clients/edit/{id}`    | `ClientController::edit()`      | `auth`          |
| POST   | `/clients/update`       | `ClientController::update()`    | `auth`          |
| POST   | `/clients/delete`       | `ClientController::destroy()`   | `auth`          |

### Clases Core MVC (`app/Core/`)

| Clase                 | Descripción                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------- |
| `App\Core\Database`   | Singleton PDO — `Database::getInstance()->getConnection()`                                  |
| `App\Core\Router`     | Registra y despacha rutas GET/POST con middleware                                           |
| `App\Core\Controller` | Base: `view()`, `renderWithLayout()`, `redirect()`, `json()`, `input()`, `validate()`       |
| `App\Core\Model`      | Base abstracta: `all()`, `find()`, `create()`, `update()`, `delete()`, `count()`, `query()` |
| `App\Core\Auth`       | Sesión y CSRF: `check()`, `user()`, `role()`, `login()`, `logout()`, `generateCsrfToken()`  |
| `App\Core\Config`     | Wrapper de `.env`: `Config::get('KEY', $default)`                                           |
| `App\Core\Middleware` | Interfaz: `handle(): bool`                                                                  |

Nuevos controladores van en `app/Controllers/` (PSR-4, namespace `App\Controllers`): `AuthController`, `DashboardController`, `UserController`, `RoleController`, `CategoryController`, `SupplierController`, `ClientController`.
Nuevos modelos van en `app/Models/` (PSR-4, namespace `App\Models`): `User`, `Role`, `Category`, `Supplier`, `Client`.

### Estado de Migración MVC

| Módulo | Estado | Notas |
| ------ | ------ | ----- |
| `roles` | ✅ Migrado | `RoleController`, `Role` |
| `categories` | ✅ Migrado | `CategoryController`, `Category` |
| `suppliers` | ✅ Migrado | `SupplierController`, `Supplier` — `isReferenced()` → `tb_compras` |
| `clients` | ✅ Migrado | `ClientController`, `Client` — `isReferenced()` → `tb_ventas` |
| `almacen` | ✅ Migrado | `ProductController`, `Product` — imágenes en `public/uploads/products/`; incluye vista `show` |
| `compras` | ⏳ Pendiente | Siguiente en migrar |
| `ventas` | ⏳ Pendiente | Más complejo — incluye carrito y TCPDF |

### Workflow de Migración MVC

Al migrar un módulo legacy, seguir este orden exacto:

1. Crear `app/Models/[Nombre].php` — extender `Model`, definir `$table` y `$primaryKey`, sobreescribir `isReferenced()` si la tabla tiene FKs en otras tablas
2. Crear `app/Controllers/[Nombre]Controller.php` — 6 métodos: `index`, `create`, `store`, `edit`, `update`, `destroy`
3. Crear `views/[modulo]/index.php`, `create.php`, `edit.php`
4. Registrar 6 rutas en `routes/web.php`
5. Actualizar sidebar en `views/layout/parte1.php` con control de rol
6. Actualizar `DashboardController` para usar el nuevo Model en lugar del `require_once` legacy
7. Eliminar archivos legacy del módulo
8. Actualizar `CHANGELOG.md`, `README.md`, `CLAUDE.md`

Los commits van separados: `feat(modulo)` para archivos MVC + `chore(modulo)` para eliminación de legacy.

### Patrón MVC simplificado (módulos existentes)

- **Vistas**: Directorios de módulos en la raíz (`almacen/`, `ventas/`, `compras/`, etc.)
- **Controladores**: Lógica de negocio en `app/controllers/[modulo]/` — son incluidos/requeridos por las vistas
- **Sin capa de modelos**: Las consultas SQL se escriben directamente en los controladores usando PDO

### Autenticación y Autorización

El login usa `App\Controllers\AuthController` (vía Router). La vista está en `views/auth/login.php`.

**Módulos MVC** — las rutas se protegen con middleware en `routes/web.php`:

```php
$router->get('/ruta', [Controller::class, 'method'], ['auth']);         // cualquier rol
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'admin']); // solo Administrador
```

Los middlewares PSR-4 viven en `app/Middleware/`: `AuthMiddleware`, `AdminMiddleware`, `GuestMiddleware`.

**Módulos legacy** — cada página protegida sigue usando [app/controllers/middleware/AuthMiddleware.php](app/controllers/middleware/AuthMiddleware.php):

```php
require_once('../app/config.php');
require_once('../app/controllers/middleware/AuthMiddleware.php');

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Vendedor']); // múltiples roles
$usuario = $auth->verificarPermiso('Administrador');             // un solo rol
```

Para obtener datos del usuario en sesión en cualquier contexto MVC: `Auth::user()`.

Roles disponibles (almacenados en `tb_roles`): `Administrador`, `Vendedor`, `Comprador`.

### Sistema de Layout

Cada página incluye plantillas compartidas:

- [views/layout/sesion.php](views/layout/sesion.php) — valida que exista sesión activa, redirige a `/auth` si no
- [views/layout/parte1.php](views/layout/parte1.php) — head HTML, navbar, sidebar
- [views/layout/parte2.php](views/layout/parte2.php) — scripts de cierre, footer

### Acceso a Base de Datos

La conexión PDO se inicializa en `app/config.php` y está disponible como `$pdo`. Todas las consultas deben usar **sentencias preparadas** con placeholders `?` — nunca interpolar variables directamente en el string SQL:

```php
// CORRECTO
$query = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = ?");
$query->execute([$email]);

// INCORRECTO — vulnerable a SQL injection
$query = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = '$email'");
$query->execute();
```

### Generación de PDF

Las facturas de ventas usan TCPDF en [app/TCPDF-main/](app/TCPDF-main/). El controlador de factura está en [app/controllers/ventas/factura_venta.php](app/controllers/ventas/factura_venta.php).

## Estructura de Módulos

Cada módulo sigue el mismo patrón CRUD:

```
[modulo]/
├── index.php      # Vista de listado (DataTables)
├── create.php     # Formulario de creación
├── update.php     # Formulario de edición
├── delete.php     # Manejador de eliminación
└── show.php       # Vista de detalle (algunos módulos)

app/controllers/[modulo]/
├── listado_de_[modulo].php   # Obtener datos del listado
├── registro_de_[modulo].php  # Lógica de INSERT
├── actualizar_[modulo].php   # Lógica de UPDATE
└── eliminar_[modulo].php     # Lógica de DELETE
```

## Tablas Principales de Base de Datos

| Tabla            | Propósito                                                   |
| ---------------- | ----------------------------------------------------------- |
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
- **Subida de archivos**: Validar extensión (whitelist: jpg, jpeg, png, webp), MIME type real con `mime_content_type()` y tamaño máximo (2MB) antes de `move_uploaded_file()`.
- **JavaScript**: Usar `json_encode()` para pasar strings PHP a variables JS — nunca interpolación directa con comillas simples.
- **Output HTML**: Usar `htmlspecialchars()` al mostrar datos de usuario en HTML para prevenir XSS.

## Convenciones de Frontend

- **DataTables** se inicializa en cada página de listado para búsqueda, ordenamiento y exportación (PDF/Excel/CSV/Imprimir)
- **SweetAlert2** se usa para todas las confirmaciones de eliminación y alertas de éxito/error (Toast mixin para notificaciones globales)
- **Control Sidebar:** La lógica de tema y colores usa `public/js/control_sidebar.js` (basado en API nativa de AdminLTE). Las selecciones se guardan en `localStorage` y se aplica un script Anti-FOUC directamente en `layout/parte1.php`.
- **jQuery** es requerido y se carga vía la plantilla AdminLTE
- CSS personalizado en [public/css/](public/css/), JS personalizado en [public/js/](public/js/)
- Assets de AdminLTE servidos desde [public/templates/](public/templates/) — no modificar estos archivos

## Permisos de Archivos

```bash
chmod 755 almacen/img_productos/   # Directorio de carga de imágenes de productos
chmod 644 app/config.php
```

## Prohibiciones Explícitas

- **SQL**: Nunca concatenar variables en queries — siempre `?` con `execute([$var])`
- **Borrado**: Este proyecto usa borrado **físico** con `isReferenced()` — nunca borrado lógico con `is_active`
- **Controladores**: Solo los 6 métodos estándar (`index`, `create`, `store`, `edit`, `update`, `destroy`) — no inventar `toggle()`, `activate()`, `complete()`, etc.
- **JavaScript**: Nunca `alert()` nativo — usar SweetAlert2 con el patrón de formulario oculto `#formEliminar`
- **PHP → JS**: Nunca interpolar strings PHP en JS con comillas simples — usar `json_encode()`
- **Templates**: No modificar archivos en `public/templates/` (AdminLTE)
- **Vistas**: No llamar `Auth::` directamente en vistas — pasar los datos desde el controlador vía `renderWithLayout()`
