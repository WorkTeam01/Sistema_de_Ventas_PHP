# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [1.1.1] - 2026-03-30

### Agregado

- Vistas de error dedicadas en `views/errors/`: `404.php` (headline amarillo), `403.php` (headline rojo + SweetAlert2 con flash message), `500.php` (headline rojo) — páginas standalone con contenido completamente centrado, sin header ni footer
- Ruta `GET /errors/403` en `routes/web.php` como closure sin middleware para servir la página 403

### Corregido

- Redirect 403 de `AdminMiddleware` y `SellerMiddleware` apuntaba a `APP_URL . '/error/error.php'` — URL inválida porque `APP_URL` termina en `/public` y el archivo estaba fuera de ese directorio; corregido a `APP_URL . '/errors/403'`
- `Router::dispatch()` ahora incluye `views/errors/404.php` en lugar del archivo legacy `error/error.php`

### Cambiado

- TCPDF gestionado vía Composer (`tecnickcom/tcpdf ^6.7`, instalado como `6.11.2`) en lugar de la copia manual en `app/TCPDF-main/`; eliminada la línea `require_once` en `SaleController::invoice()`

### Eliminado

- Directorio `error/` con el archivo legacy `error/error.php`
- Directorio `app/TCPDF-main/` reemplazado por `vendor/tecnickcom/tcpdf/`

---

## [1.1.0] - 2026-03-30

### Agregado

- Composer con PSR-4 autoloading y `vlucas/phpdotenv ^5.6`
- Credenciales movidas a `.env` (fuera del control de versiones); `.env.example` como plantilla
- Clases Core MVC: `App\Core\Database` (singleton PDO con `getConnection()`), `App\Core\Router`, `App\Core\Controller`, `App\Core\Model` (base abstracta), `App\Core\Config` (wrapper .env), `App\Core\Middleware` (interfaz)
- `App\Core\Auth` para centralizar sesión, usuario actual, login/logout y CSRF
- `app/Middleware/` con middlewares namespaced PSR-4: `AuthMiddleware`, `GuestMiddleware`, `AdminMiddleware`, `SellerMiddleware` (permite `Administrador` y `Vendedor`; registrado como `'seller'` en Router)
- Modelo `App\Models\User` (hereda de `App\Core\Model`) con métodos de autenticación y CRUD de usuarios
- Entry point `public/index.php` con Router; `.htaccess` en raíz para soporte de rutas; `routes/web.php` para registro de rutas
- `App\Controllers\AuthController` consolida login y logout; directorio `auth/` reemplaza `login/`
- `App\Controllers\UserController` con CRUD completo del módulo users
- `App\Controllers\DashboardController` — ruta `GET /` con conteos de todos los módulos
- Nuevas vistas MVC en `views/auth/login.php`, `views/users/` (index, create, edit, show, delete), `views/dashboard/index.php`
- `Controller::renderWithLayout()` para renderizar vistas envueltas en `parte1`/`mensajes`/`parte2` desde el controlador
- Constante `BASE_URL` definida en `app/config.php` — disponible globalmente sin necesidad de pasar como variable
- Modelo `App\Models\Role` — `$table = 'tb_roles'`; CRUD heredado; sin delete por ser datos de sistema
- `App\Controllers\RoleController` (index, create, store, edit, update); vistas `views/roles/` con DataTables y badge de total
- Rutas `/roles` en `routes/web.php` con middleware `['auth', 'admin']`
- Modelo `App\Models\Category` — `$table = 'tb_categorias'`
- `App\Controllers\CategoryController` (index, create, store, edit, update); vistas `views/categories/`
- Rutas `/categories` en `routes/web.php` con middleware `auth`
- Modelo `App\Models\Supplier` — `$table = 'tb_proveedores'`; `isReferenced()` verifica `tb_compras`
- `App\Controllers\SupplierController` con CRUD completo; vistas `views/suppliers/` con SweetAlert2 para eliminar
- Rutas `/suppliers` en `routes/web.php` con middleware `auth`
- Modelo `App\Models\Client` — `$table = 'tb_clientes'`; `isReferenced()` verifica `tb_ventas`
- `App\Controllers\ClientController` con CRUD completo; vistas `views/clients/` con SweetAlert2 para eliminar
- Rutas `/clients` en `routes/web.php` con middleware `auth`
- Modelo `App\Models\Product` — `$table = 'tb_almacen'`; `allWithCategories()` con JOIN; `nextCode()` genera código `P-XXXXX`; `isReferenced()` verifica `tb_carrito` y `tb_compras`
- `App\Controllers\ProductController` con CRUD completo más `show()`; `handleImageUpload()` con validación MIME, extensión whitelist y límite 2MB
- Vistas `views/products/` con alerta visual de stock por colores; vista `show.php` con auditoría
- Rutas `/products` en `routes/web.php` con middleware `auth`
- Directorio `public/uploads/products/` para imágenes; `producto_default.png` y `.gitkeep` trackeados; resto ignorado en `.gitignore`
- Modelo `App\Models\Purchase` — `$table = 'tb_compras'`; `storeWithStock()`, `updateWithStock()`, `destroyWithStock()` transaccionales; `allWithDetails()`, `findWithDetails()`, `nextNumber()`
- `App\Controllers\PurchaseController` con CRUD completo; `id_usuario` de `Auth::user()` — nunca del POST
- Vistas `views/purchases/` con campos hidden `old_id_producto`/`old_cantidad` para ajuste de stock en edición
- Rutas `/purchases` en `routes/web.php` con middleware `auth`
- Helper estático `App\Helpers\NumberToWords::convert(float)` — convierte número a palabras en español para facturas PDF
- Modelo `App\Models\Sale` — `$table = 'tb_ventas'`; `allWithDetails()`, `findWithDetails()`, `nextNumber()` con `MAX()+1`; `storeWithStock()` y `destroyWithStock()` transaccionales (DELETE `tb_ventas` antes que `tb_carrito` por FK)
- Modelo `App\Models\CartItem` — `$table = 'tb_carrito'`; `addItem()` con validación de stock, `getByNroVenta()`, `removeItem()`, `countByNroVenta()`
- `App\Controllers\SaleController` con 9 métodos: `index`, `create`, `addToCart`, `removeFromCart`, `store`, `show`, `confirmDelete`, `invoice` (TCPDF inline), `destroy`
- Vistas `views/sales/`: `index.php` (DataTables), `create.php` (POS: carrito + modales de producto y cliente + panel pago), `show.php`, `delete.php` (confirmación SweetAlert2)
- Rutas `/sales` en `routes/web.php` con middleware `['auth', 'seller']`

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
- `DashboardController` reemplaza todos los `require_once listado_de_*.php` por llamadas a `Model::count()` (roles, categories, suppliers, clients, products, purchases, sales)
- Sidebar `views/layout/parte1.php` actualizado con bloques MVC para todos los módulos migrados
- Dashboard `views/dashboard/index.php` actualiza links a rutas MVC (`/sales`, `/sales/create`, etc.)
- `.gitignore` corregido para trackear solo `producto_default.png` y `.gitkeep` en `public/uploads/products/`

### Corregido

- `$(document).ready()` en DataTables init de `views/users/index.php` — prevenía `DataTable is not a function` al ejecutar el script antes de que `parte2.php` cargara la librería
- Atributos `autocomplete` añadidos en formularios de usuarios (edit: `name`, `email`, `new-password`; show: `off`) — elimina error `autofillFieldData.autoCompleteType is null` del browser

### Eliminado

- Vistas legacy del módulo `usuarios/` y controladores `app/controllers/usuarios/` reemplazados por `views/users/` y `UserController`
- Vistas legacy `roles/`, `categorias/`, `proveedores/`, `clientes/` y sus controladores en `app/controllers/`
- Vistas legacy `almacen/` y controladores `app/controllers/almacen/`
- Vistas legacy `compras/` y controladores `app/controllers/compras/`
- Vistas legacy `ventas/` y controladores `app/controllers/ventas/` (incluido `literal.php`)
- Directorio `app/controllers/` completo — incluyendo `middleware/AuthMiddleware.php` legacy; no queda ningún archivo fuera de la arquitectura MVC

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
