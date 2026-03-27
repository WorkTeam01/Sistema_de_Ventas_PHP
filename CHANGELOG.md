# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

### Agregado

- Modelo `App\Models\Role` (hereda de `App\Core\Model`) — `$table = 'tb_roles'`, `$primaryKey = 'id_rol'`; métodos CRUD heredados del base
- `App\Controllers\RoleController` con CRUD parcial (index, create, store, edit, update); sin delete por ser datos de sistema
- Vistas MVC en `views/roles/` (index, create, edit) con DataTables, breadcrumb, card info lateral y badge de total en header
- Rutas `/roles`, `/roles/create`, `/roles/edit/{id}` (GET/POST) en `routes/web.php` con middleware `auth` + `admin`
- Modelo `App\Models\Category` (hereda de `App\Core\Model`) — `$table = 'tb_categorias'`, `$primaryKey = 'id_categoria'`
- `App\Controllers\CategoryController` con CRUD parcial (index, create, store, edit, update); accesible a todos los roles autenticados
- Vistas MVC en `views/categories/` (index, create, edit) con DataTables, breadcrumb y card info lateral
- Rutas `/categories`, `/categories/create`, `/categories/edit/{id}` (GET/POST) en `routes/web.php` con middleware `auth`

- Modelo `App\Models\Supplier` (hereda de `App\Core\Model`) — `$table = 'tb_proveedores'`, `$primaryKey = 'id_proveedor'`; sobreescribe `isReferenced()` para verificar dependencias en `tb_compras`
- `App\Controllers\SupplierController` con CRUD completo (index, create, store, edit, update, destroy); accesible a roles `Administrador` y `Comprador`
- Vistas MVC en `views/suppliers/` (index, create, edit) con DataTables, breadcrumb, card info lateral y confirmación SweetAlert2 para eliminar
- Rutas `/suppliers`, `/suppliers/create`, `/suppliers/edit/{id}`, `/suppliers/delete` (GET/POST) en `routes/web.php` con middleware `auth`

### Cambiado

- `DashboardController` reemplaza `require_once listado_de_roles.php` por `Role::count()` — elimina dependencia de archivo legacy
- `DashboardController` reemplaza `require_once listado_de_categorias.php` por `Category::count()` — elimina dependencia de archivo legacy
- `DashboardController` reemplaza `require_once listado_de_proveedores.php` por `Supplier::count()` — elimina dependencia de archivo legacy

### Eliminado

- Vistas legacy `roles/index.php`, `roles/create.php`, `roles/update.php`
- Controladores legacy `app/controllers/roles/` (listado_de_roles, create, update, update_roles)
- Vista legacy `categorias/index.php`
- Controladores legacy `app/controllers/categorias/` (listado_de_categorias, registro_categorias, update_de_categorias)
- Vista legacy `proveedores/index.php`
- Controladores legacy `app/controllers/proveedores/` (listado_de_proveedores, create, update, delete)

## [1.1.0] - 2026-03-26

### Agregado

- Composer con PSR-4 autoloading y `vlucas/phpdotenv ^5.6`
- Credenciales movidas a `.env` (fuera del control de versiones); `.env.example` como plantilla
- Clases Core MVC: `App\Core\Database` (singleton PDO con `getConnection()`), `App\Core\Router`, `App\Core\Controller`, `App\Core\Model` (base abstracta), `App\Core\Config` (wrapper .env), `App\Core\Middleware` (interfaz)
- `App\Core\Auth` para centralizar sesión, usuario actual, login/logout y CSRF
- `app/Middleware/` con middlewares namespaced PSR-4: `AuthMiddleware`, `GuestMiddleware`, `AdminMiddleware`
- Modelo `App\Models\User` (hereda de `App\Core\Model`) con métodos de autenticación y CRUD de usuarios
- Entry point `public/index.php` con Router; `.htaccess` en raíz para soporte de rutas; `routes/web.php` para registro de rutas
- `App\Controllers\AuthController` consolida login y logout; directorio `auth/` reemplaza `login/`
- `App\Controllers\UserController` con CRUD completo del módulo users
- `App\Controllers\DashboardController` — ruta `GET /` con conteos de todos los módulos
- Nuevas vistas MVC en `views/auth/login.php`, `views/users/` (index, create, edit, show, delete), `views/dashboard/index.php`
- `Controller::renderWithLayout()` para renderizar vistas envueltas en `parte1`/`mensajes`/`parte2` desde el controlador
- Constante `BASE_URL` definida en `app/config.php` — disponible globalmente sin necesidad de pasar como variable

### Cambiado

- `APP_URL` en `.env` ahora incluye `/public` (`http://localhost/Sistema_de_Ventas_PHP/public`) — todas las rutas MVC apuntan al front controller
- `app/config.php`: agrega `define('BASE_URL', ...)` y mantiene `$URL = BASE_URL` como alias backward-compat para módulos legacy
- `App\Core\Model` ampliado con CRUD completo: `all/create/update/count/query/isReferenced` y aliases de compatibilidad `findAll/insert`; propiedad canonical `$db` (con `$pdo` como alias)
- `App\Models\User` actualizado para usar `$this->db` (propiedad canonical de Model)
- `App\Controllers\AuthController` usa `BASE_URL` directamente; redirige a `BASE_URL . '/'` tras login exitoso
- `App\Controllers\UserController` refactorizado: helper `sessionData()` centraliza variables de sesión para todas las vistas; todos los métodos usan `renderWithLayout()`
- `login/index.php` reemplazado por redirect a `/auth/` (backward compat)
- Root `index.php` reemplazado por redirect stub a `BASE_URL . '/'`
- Rutas de users depuradas para usar endpoints canónicos sin duplicados
- `App\Core\Router` actualizado con middlewares por ruta y soporte de parámetros dinámicos (`/users/edit/{id}`)

### Corregido

- `$(document).ready()` en DataTables init de `views/users/index.php` — prevenía `DataTable is not a function` al ejecutar el script antes de que `parte2.php` cargara la librería
- Atributos `autocomplete` añadidos en formularios de usuarios (edit: `name`, `email`, `new-password`; show: `off`) — elimina error `autofillFieldData.autoCompleteType is null` del browser

### Eliminado

- Vistas legacy del módulo `usuarios/` y controladores `app/controllers/usuarios/` reemplazados por `views/users/` y `UserController`

## [1.0.0] - 2026-03-24

### Agregado

- `database/schema.sql` con la estructura de todas las tablas
- `database/seeder.sql` con datos iniciales para todas las tablas: roles, categorías, proveedores, clientes, usuarios, productos, compras y ventas de ejemplo
- Usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`, `comprador@sistema.com` / `comprador123`
- `UNIQUE KEY` en `tb_usuarios.email` para evitar emails duplicados
- `UNIQUE KEY` en `tb_almacen.codigo` para evitar códigos de producto duplicados
- `app/config.example.php` como plantilla de configuración sin credenciales
- **Protección CSRF**: Generación global de token y validación estricta para todos los endpoints POST de mutación.
- **Validación Fuerte del Servidor**: Filtros integrados (`is_numeric`, `filter_var`) en controladores críticos de usuarios, almacén y ventas.

### Cambiado

- **Refactorización de Verbos HTTP**: Las mutaciones lógicas como la creación de ventas ahora requieren estrictamente `POST` mitigando vulnerabilidades.
- **Transacciones PDO Íntegras**: El carrito y la cabecera de la venta se procesan bajo un único bloque `$pdo->beginTransaction()` garantizando consistencia del stock.
- `fyh_creacion` ahora tiene `DEFAULT CURRENT_TIMESTAMP` en todas las tablas — ya no es necesario insertarla manualmente
- `fyh_actualizacion` ahora es `DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP` en todas las tablas — queda NULL al crear y se actualiza automáticamente al modificar
- `tb_almacen.precio_compra` y `tb_almacen.precio_venta`: `VARCHAR(255)` → `DECIMAL(10,2)`
- `tb_compras.precio_compra`: `VARCHAR(50)` → `DECIMAL(10,2)`
- `tb_ventas.total_pagado`: `INT(11)` → `DECIMAL(10,2)` (corrige pérdida de decimales)
- `tb_usuarios.token`: `NOT NULL` → `DEFAULT NULL`
- `tb_proveedores.email`: `VARCHAR(50)` → `VARCHAR(254)` (estándar RFC 5321)
- `AUTO_INCREMENT` reseteado a 1 en todas las tablas
- `app/config.php` excluido del repositorio vía `.gitignore`

### Corregido

- Advertencias en consola del navegador reparadas al forzar `autocomplete="off"` en los formularios de `login/index.php`.
- Estandarización de `layout/mensajes.php` para usar Toasts globales de SweetAlert2 en lugar del modal intrusivo.
- Campos de contraseña corregidos de `type="text"` a `type="password"` en `usuarios/create.php` y `usuarios/update.php` — la contraseña ya no se muestra en texto plano
- `htmlspecialchars()` aplicado en todas las vistas donde se muestran datos de usuarios/BD en HTML: `almacen/index.php`, `almacen/show.php`, `almacen/update.php`, `ventas/index.php`, `compras/index.php`, `proveedores/index.php` y `usuarios/update.php` — previene XSS almacenado
- Confirmaciones SweetAlert2 agregadas antes de eliminar registros en `almacen/index.php`, `ventas/index.php`, `compras/index.php` y `usuarios/index.php` — los botones de eliminar ya no navegan directamente sin confirmación
- Typo `lamppstart` → `lampp start` en README.md y CLAUDE.md
- URL de ejemplo incorrecta en CLAUDE.md (`sistemaventas` → `Sistema_de_Ventas_PHP`)
- SQL injection en `layout/sesion.php`, `app/controllers/login/ingreso.php`, `app/controllers/roles/update_roles.php`, `app/controllers/clientes/cargar_cliente.php`, `ventas/factura.php`, `ventas/show.php`, `ventas/index.php`, `ventas/delete.php` y `ventas/create.php` — se reemplazó interpolación directa de variables en SQL por placeholders `?` con `execute([$var])`
- Subida de imágenes sin validación en `app/controllers/almacen/create.php` y `update.php` — se agregó whitelist de extensiones (jpg, jpeg, png, webp), validación de MIME type real y límite de 2MB
- Variables PHP interpoladas directamente en bloques JavaScript en `ventas/create.php` — se reemplazó por `json_encode()` para prevenir errores con caracteres especiales
- **Control Sidebar:** Funcionalidad original manual reemplazada por el archivo `public/js/control_sidebar.js` 100% nativo de la API de AdminLTE, traducido al español, con guardado persistente en `localStorage`.
- **FOUC (Flash of Unstyled Content):** Parpadeos visuales al navegar con temas oscuros prevenidos mediante una pequeña inyección JS al inicio del `<body>` en `layout/parte1.php`.
- Reposicionado de la barra lateral de configuración a `position: fixed` para evitar pérdida visual al hacer scroll excesivo.
