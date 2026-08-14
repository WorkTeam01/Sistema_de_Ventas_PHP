# AGENT.md — Sistema de Ventas PHP

> System prompt persistente para agentes de IA y sesiones de desarrollo asistido.
> Compatible con: Claude Code · Cursor (.cursorrules) · Claude.ai (pegar al inicio) · Copilot (workspace instructions)

---

## Proyecto

Sistema de gestión de ventas con control de inventario, facturación, gestión de clientes y acceso por roles.
Permite registrar ventas, compras a proveedores, gestionar el almacén y emitir facturas en PDF.

**Estado actual:** 1.16.1 — migración MVC completada (sin módulos legacy pendientes), RBAC granular con gestión de permisos vía UI, dashboard y módulos de ventas/compras scopeados por permisos reales y por usuario (`view_sales_all`/`view_purchases_all`, sin proxies de rol hardcodeados), audit log con cobertura completa y KPIs, hardening de seguridad (cabeceras HTTP, detección de HTTPS tras proxy, saneo de HTML en SweetAlert2, prevención de IDOR en compras), eliminación de `Swal.fire`/`onclick` inline en vistas, hardening de accesibilidad/UX en el flujo de autenticación (login, forgot-password, reset-password), moneda configurable vía `.env`, auditoría de accesibilidad del módulo de ventas/POS y del layout global (header/sidebar), y auditoría de accesibilidad del módulo Productos con selector de fecha accesible reutilizado en toda la app. Historial completo de versiones en [CHANGELOG.md](CHANGELOG.md).

---

## Stack Tecnológico

- **Backend:** PHP 8.x (sin framework — MVC custom con PSR-4 via Composer)
- **Frontend:** AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2
- **Base de datos:** MySQL / MariaDB (PDO)
- **PDF:** TCPDF (`tecnickcom/tcpdf` vía Composer)
- **Email:** PHPMailer (`phpmailer/phpmailer ^7.0` vía Composer) — SMTP Gmail con App Password
- **Control de versiones:** Git + GitHub (`WorkTeam01/Sistema_de_Ventas_PHP`)

---

## Arquitectura del Proyecto

```
Sistema_de_Ventas_PHP/
├── app/
│   ├── Core/
│   │   ├── Router.php        ← Enrutamiento centralizado (rutas MVC)
│   │   ├── Database.php      ← Singleton PDO
│   │   ├── Model.php         ← CRUD genérico base
│   │   ├── Controller.php    ← renderWithLayout(), redirect(), flash(), validate()
│   │   ├── Auth.php          ← Sesiones, CSRF, login/logout
│   │   ├── Config.php        ← Wrapper .env
│   │   └── Middleware.php    ← Interfaz handle(): bool
│   ├── Controllers/          ← PSR-4, namespace App\Controllers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── UserController.php
│   │   ├── RoleController.php
│   │   ├── CategoryController.php
│   │   ├── SupplierController.php
│   │   ├── ClientController.php
│   │   ├── ProductController.php
│   │   ├── PurchaseController.php
│   │   ├── SaleController.php
│   │   ├── ActivityLogController.php
│   │   ├── InventoryController.php
│   │   ├── ReportController.php     ← index(), sales(), purchases(), topProducts(), clients(); export PDF/CSV/Excel
│   │   └── PermissionController.php ← index(), store(), show() (JSON), update(), checkClave() — catálogo de permisos
│   ├── Helpers/              ← PSR-4, namespace App\Helpers
│   │   ├── NumberToWords.php
│   │   ├── InvoicePdf.php
│   │   ├── PurchaseReportPdf.php
│   │   ├── ActivityLogRenderer.php  ← decodifica JSON del log y prepara filas para las vistas
│   │   ├── ReportFilters.php        ← parseDateRange() — normaliza rango GET; default = mes actual
│   │   └── ReportPdf.php            ← generate() estático — tabla TCPDF landscape con totalizadores y emisor
│   ├── Services/             ← PSR-4, namespace App\Services
│   │   └── EmailService.php  ← PHPMailer SMTP — sendResetLink()
│   ├── Models/               ← PSR-4, namespace App\Models
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Category.php
│   │   ├── Supplier.php
│   │   ├── Client.php
│   │   ├── Product.php
│   │   ├── Purchase.php
│   │   ├── Sale.php
│   │   ├── CartItem.php
│   │   ├── ActivityLog.php
│   │   ├── StockAdjustment.php
│   │   ├── Report.php               ← queries agregadas: salesByPeriod, salesTotals, purchasesByPeriod, purchasesTotals, topProducts, clientsByPeriod, salesSummary
│   │   └── Permission.php           ← claveExists(), allGroupedByModulo(); Role.php agrega getAssignedPermissionIds()/syncPermissions()
│   └── Middleware/           ← AuthMiddleware, GuestMiddleware, PermissionMiddleware
├── views/
│   ├── layouts/
│   │   ├── header.php        ← Head HTML, navbar; incluye sidebar partial
│   │   ├── footer.php        ← Scripts de cierre, footer
│   │   ├── messages.php      ← Mensajes flash (welcome y toasts CRUD)
│   │   └── partials/
│   │       └── _sidebar.php  ← Sidebar con control de rol
│   ├── errors/               ← Páginas de error standalone (404, 403, 500)
│   ├── auth/                 ← login.php, forgot-password.php, reset-password.php, show-reset-link.php
│   ├── dashboard/
│   ├── users/
│   ├── roles/                ← index.php, permisos.php (asignación de permisos por rol)
│   ├── permissions/          ← index.php; partial/_modals.php (crear/editar catálogo)
│   ├── categories/
│   ├── suppliers/
│   ├── clients/
│   ├── products/
│   ├── purchases/
│   ├── sales/
│   ├── activity-log/         ← index.php, show.php; partial/_data-panel.php, _item-accordion.php
│   └── reports/              ← index.php, sales.php, purchases.php, top-products.php, clients.php; partial/_date_filter.php
├── routes/
│   └── web.php               ← Todas las rutas MVC registradas
├── public/
│   ├── index.php             ← Entry point único (front controller MVC)
│   ├── .htaccess             ← Redirige al Router
│   ├── css/
│   │   ├── core/             ← Utilitarios globales (ui-components.css)
│   │   ├── modules/          ← CSS por módulo (auth/login.css, …)
│   │   ├── lib/              ← Vendors CSS (adminlte/, fontawesome/, bootstrap/)
│   │   └── plugins/          ← Plugins CSS (sweetalert2/, datatables/, select2/)
│   ├── js/
│   │   ├── core/             ← Utilitarios globales (sweetalert-utils.js, control_sidebar.js)
│   │   ├── modules/          ← JS por módulo (auth/login.js, users/users-index.js, …)
│   │   ├── lib/              ← Vendors JS (jquery/, bootstrap/, adminlte/)
│   │   └── plugins/          ← Plugins JS (sweetalert2/)
│   └── templates/            ← AdminLTE fuente completa (no modificar)
└── database/
    ├── schema.sql
    └── seeder.sql
```

---

## Enrutamiento Híbrido

El proyecto usa **dos sistemas de ruteo en paralelo**:

| Tipo    | Cómo funciona                              | Módulos                                                                                                                             |
| ------- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------- |
| **MVC** | `public/index.php` → `Router` → Controller | auth, dashboard, users, roles, categories, suppliers, clients, products, purchases, sales, activity-log, inventory, reports (todos) |

Todos los módulos están migrados. No quedan módulos legacy.

> El detalle exacto de método + ruta + controller + middleware por endpoint vive en `routes/web.php` — es la fuente
> de verdad; no se replica aquí para evitar desincronización. Los middlewares usan la sintaxis `can:permiso` (ver
> slugs en §Autenticación).

---

## Base de Datos

```sql
-- Tablas principales
tb_usuarios
(id_usuario, nombre, apellido, email, password, id_rol, fyh_creacion, fyh_actualizacion)
    tb_roles
    (id_rol, rol, permisos_version, fyh_creacion, fyh_actualizacion)
    -- permisos_version se incrementa en Role::syncPermissions(); Auth::check() la compara contra
    -- $_SESSION['permisos_version'] para invalidar la caché de permisos sin requerir re-login
    tb_categorias
    (id_categoria, nombre_categoria, fyh_creacion, fyh_actualizacion)
    tb_proveedores
(id_proveedor, nombre_proveedor, nit_ci_proveedor, celular_proveedor,
    email_proveedor, nombre_empresa, fyh_creacion, fyh_actualizacion)
tb_clientes
(id_cliente, nombre_cliente, nit_ci_cliente, celular_cliente,
    email_cliente, fyh_creacion, fyh_actualizacion)
tb_almacen
(id_almacen, nombre_almacen, descripcion, precio_compra, precio_venta,
    stock, imagen, id_categoria, fyh_creacion, fyh_actualizacion)
tb_ventas
    (id_venta, nro_venta, id_cliente, id_usuario [FK NULL → ON DELETE SET NULL], total_pagado, fyh_creacion, fyh_actualizacion)
    -- id_usuario registra al vendedor; NULL en registros anteriores a v1.12.1
    -- total_pagado calculado server-side (precio_venta × cantidad desde tb_almacen), nunca del POST
    tb_carrito
    (id_carrito, nro_venta, id_producto, cantidad)
    tb_compras
(id_compra, id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad, fyh_creacion)
    tb_activity_log
(id_log, id_usuario [FK NULL → ON DELETE SET NULL], usuario_nombre, accion, entidad, entidad_id,
    descripcion, datos_anteriores [JSON], datos_nuevos [JSON], ip_address, fyh_creacion)
    -- acciones registradas: 'create', 'update', 'delete', 'price_change', 'role_change', 'permission_change',
    --   'stock_adjustment', 'export', 'login', 'login_failed', 'logout'
    -- entidades: 'sale', 'purchase', 'product', 'user', 'client', 'supplier', 'category', 'role', 'permission',
    --   'report', 'auth'
    -- KPIs agregados (total, usuarios distintos, eliminaciones, cambios sensibles) vía ActivityLog::kpis(),
    --   calculados por COUNT/GROUP BY sobre el rango filtrado, no sobre la página ya paginada
    -- usuario_nombre desnormalizado para persistir incluso si el usuario es eliminado
    tb_ajustes_stock
(id_ajuste, id_producto [FK → tb_almacen], tipo [enum: entrada|salida], cantidad,
    stock_anterior, stock_posterior, motivo, id_usuario [FK NULL → ON DELETE SET NULL],
    usuario_nombre, fyh_creacion)
    -- registrado atómicamente junto con UPDATE tb_almacen en StockAdjustment::register()
    tb_permisos
    (id_permiso, clave [VARCHAR unique], descripcion, modulo, fyh_creacion)
    -- clave es el slug del permiso (ej: 'manage_users', 'view_sales'); modulo agrupa el catálogo en la UI; sin columna status
    tb_rol_permiso
    (id_rol [FK → tb_roles CASCADE DELETE], id_permiso [FK → tb_permisos CASCADE DELETE], PRIMARY KEY compuesta)
    -- tabla pivote muchos-a-muchos entre roles y permisos; reemplazada por completo en cada Role::syncPermissions()

-- Roles de usuario (almacenados en tb_roles)
Administrador
· Vendedor
· Comprador

-- Convenciones de auditoría
fyh_creacion      DEFAULT CURRENT_TIMESTAMP
← no insertar manualmente
fyh_actualizacion ON
UPDATE CURRENT_TIMESTAMP ← queda NULL al crear
```

---

## Convenciones de Código

- **Identificadores en inglés**: variables, métodos, funciones y clases nuevas se nombran en inglés (`$previousRole`,
  `$newId`), incluso en archivos donde identificadores preexistentes usan español (`$rol`, `$permisos_datos`) por
  convención histórica del dominio (ventas, roles, etc.). Los strings/mensajes de usuario siguen en español. No
  renombrar identificadores preexistentes salvo pedido explícito.

### PHP — Controladores MVC

- Un controlador por módulo: `SupplierController`, `ClientController`, etc.
- Métodos estándar del proyecto: `index()`, `create()`, `store()`, `edit(?int $id)`, `update()`, `destroy()`
- Métodos auxiliares permitidos (cuando el módulo lo requiere): `check()` (endpoint JSON de verificación de
  referencias), `delete()` (página de confirmación de eliminación), `show()` (retorna JSON con datos del registro para
  pre-llenar modal de edición), `checkNombre()` (endpoint `remote` para jQuery Validate),
  `profile()` / `updateProfile()` / `updatePassword()` (perfil propio del usuario autenticado — solo en
  `UserController`)
- `destroy()` llama a `$model->isReferenced($id)` antes de eliminar — si hay FK activa: flash + redirect (patrón
  clásico) o JSON error (patrón modal+AJAX)
- **PROHIBIDO** inventar métodos fuera del estándar sin justificación (toggle, activate, complete, etc.)

### PHP — Modelos MVC

- Heredan de `App\Core\Model` — métodos disponibles: `all()`, `find()`, `create()`, `update()`, `delete()`, `count()`,
  `query()` — `insert()` y `findAll()` están marcados `@deprecated`, usar `create()` y `all()`
- Sobreescribir `isReferenced(int|string $id): bool` en modelos con FKs en otras tablas
- Usar PDO con prepared statements siempre — nunca concatenar variables en SQL
- **Borrado físico** (no lógico) — protegido por `isReferenced()` antes de ejecutar DELETE
- **Fat model:** la lógica de negocio vive en el modelo (hashing de contraseñas, validación de formato, normalización
  de campos, cálculos, reglas de integridad, transacciones); el controlador solo orquesta (leer input → llamar
  modelo → responder). Ejemplos representativos: `User::createUser()` encapsula `password_hash()`;
  `Sale::computeInvoiceTotals()` calcula totales de factura y delega el PDF a `InvoicePdf::generate()` en
  `app/Helpers/`, manteniendo `SaleController::invoice()` en ~20 líneas; `Role::syncPermissions()` reemplaza el set
  de permisos de un rol dentro de una transacción (DELETE + INSERT + incremento de `permisos_version`), con rollback
  ante error. Los modelos también exponen queries enriquecidas para evitar joins manuales en el controlador
  (`Product::findWithCategory()`, `Permission::allGroupedByModulo()`).

### PHP — Seguridad

- Passwords: `password_hash()` al guardar, `password_verify()` al validar; mínimo 6 caracteres validado server-side en todos los flujos (create, update, reset)
- Inputs: `htmlspecialchars()` en outputs HTML, validación server-side obligatoria
- CSRF: token en todos los formularios POST (`Auth::generateCsrfToken()` / `validateCsrfOrFail()`)
- SQL: siempre placeholders `?` con `execute([$var])` — nunca interpolar en el string SQL; `$interval` y `$limit` siempre con cast `(int)` explícito antes de interpolación
- Stock: el decremento en `Sale::storeWithStock()` usa `AND stock >= ?` y verifica `rowCount() === 0` para rollback — nunca produce stock negativo
- Totales: `Sale::storeWithStock()` calcula `total_pagado` desde `precio_venta × cantidad` de la BD dentro de la transacción — ignorar siempre el valor del POST
- Guards en destroy: `isReferenced()` y verificación de auto-eliminación siempre server-side en `UserController::destroy()` — la verificación cliente-side (AJAX) es solo UX
- Datos para operaciones críticas: usar siempre el snapshot de BD (ej: `$snapshot['id_producto']`, no `$_POST['id_producto']`) para revertir stock u otras operaciones irreversibles

### PHP — Autenticación

```php
// En módulos MVC (via middleware en routes/web.php):
$router->get('/ruta', [Controller::class, 'method'], ['auth']);                    // cualquier rol autenticado
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'can:permiso']);     // requiere permiso específico

// Datos del usuario en sesión:
Auth::user()            // array con datos del usuario
Auth::role()            // nombre del rol
Auth::check()           // bool — verifica sesión y timeout de inactividad
Auth::can('permiso')    // bool — verifica si el usuario tiene el permiso; usa caché en $_SESSION['permisos']
Auth::isAdmin()         // bool — alias de Auth::can('is_superadmin')
Auth::refreshPermissions() // recarga permisos desde BD (llamar tras cambiar rol o permisos)
Auth::login($user, $remember) // inicia sesión; $remember=true emite cookie de 14 días
Auth::loginWithCookie() // auto-login desde cookie remember_token; rota el token
Auth::logout()          // limpia sesión, BD y cookie
```

**Permisos granulares (RBAC):**

- Los permisos se almacenan en `tb_permisos` y se asignan a roles en `tb_rol_permiso`. Se gestionan solo a nivel de
  rol — no hay asignación individual por usuario.
- Al hacer login, `Auth::loadPermissions()` carga todos los slugs de permisos del rol en `$_SESSION['permisos']`.
- `PermissionMiddleware` resuelve el prefijo `can:` en rutas — redirige a `/errors/403` si el permiso falta.
- En controladores, usar `Auth::can('permiso')` para scoping de datos o restricciones inline.
- En vistas, el controlador pasa `$can` (array) con los permisos necesarios via `renderWithLayout()` — nunca llamar `Auth::` directamente en vistas.
- Slugs de permisos en uso: `view_dashboard`, `manage_users`, `manage_roles`, `view_categories`, `manage_categories`, `view_suppliers`, `manage_suppliers`, `view_clients`, `manage_clients`, `view_products`, `manage_products`, `view_purchases`, `manage_purchases`, `view_sales`, `manage_sales`, `view_sales_all`, `view_purchases_all`, `view_activity_log`, `manage_inventory`, `view_reports`, `view_sales_report`, `view_purchases_report`, `view_top_products_report`, `view_clients_report`, `is_superadmin`.
- **Scoping de datos por usuario (`*_all`):** `view_sales_all` y `view_purchases_all` distinguen "ver todos los
  registros" de "ver solo los propios". Sin el permiso `_all`, los métodos de listado/agregado filtran por
  `id_usuario` (ver `Sale`/`Purchase`/`Product::getTopSelling()`, que aceptan `?int $userId` opcional). Patrón usado
  en `DashboardController` (KPIs y gráficos), `SaleController::index/show/edit/destroy` y
  `PurchaseController::index/show/edit/update/report/destroy` (todos con el chequeo
  `!Auth::can('view_*_all') && (int)$registro['id_usuario'] !== (int)Auth::user()['id_usuario']` antes de
  mostrar/modificar). Solo Administrador tiene ambos `_all` por defecto — un rol nuevo sin ellos automáticamente ve
  y gestiona solo sus propios registros, sin tocar código. **Al agregar un módulo con este patrón, aplicar el
  filtro tanto al listado (`index`) como al detalle/edición/borrado (`show`/`edit`/`update`/`destroy`) — filtrar
  solo el listado deja abierto el acceso a registros ajenos por URL directa.**
- **`Controller::forbidden(string $mensaje)`:** helper para el chequeo de dueño de arriba — hace flash + redirect a
  `/errors/403` (mismo mecanismo de toast que `PermissionMiddleware` usa para rutas sin permiso). Usarlo en vez de
  `flash()` + `redirect()` al listado, así "este registro no es tuyo" se ve igual que "no tienes acceso a esta
  ruta" en toda la app.
- **El sidebar (`views/layouts/partials/_sidebar.php`) debe gatear cada enlace con su permiso real vía `$can[...]`,
  nunca con los proxies de rol `$isAdmin`/`$isSeller`/`$isBuyer`.** Esos proxies solo aproximan "tiene view_sales" /
  "tiene view_purchases" — usarlos para secciones con permisos más granulares (ej. Reportes, gateado antes por
  `$isSeller` en vez de `$can['view_reports']`) hace que el menú muestre enlaces a los que el usuario en realidad no
  tiene acceso (la ruta responde 403, pero la UI confunde). Está bien usarlos para las secciones cuyo único gate es
  precisamente `view_sales`/`view_purchases` (Ventas, Compras, Inventario).
- **Gestión vía UI:** catálogo de permisos en `/permissions` (CRUD de clave/descripción/módulo, `PermissionController`,
  `views/permissions/`) y asignación por rol en `/roles/permisos/{id}` (checkboxes agrupados por `modulo`,
  `RoleController::permisos()`/`syncPermisos()`, `views/roles/permisos.php`). Ambas rutas reutilizan el permiso
  `manage_roles` — no se sembró un slug nuevo.
- **Invalidación de caché por versión:** `tb_roles.permisos_version` se incrementa en cada `Role::syncPermissions()`.
  `Auth::check()` compara la versión en sesión contra la de BD (una query barata por request) y llama
  `refreshPermissions()` si difiere — así los usuarios activos del rol ven el cambio en su siguiente request, sin
  re-login. El propio admin que edita su rol activo se refresca de inmediato vía `Auth::refreshPermissions()` en
  `syncPermisos()`.

**Timeout de sesión:**

- Controlado por `SESSION_LIFETIME` en `.env` (minutos de inactividad). Default: `60`.
- `Auth::check()` compara `$_SESSION['last_activity']` con `time()` y llama a `logout()` si expiró.

**"Recordarme":**

- Cookie `remember_token` = `"{id_usuario}:{plain_token}"` — httponly, samesite=Strict, 14 días.
- BD almacena `hash('sha256', $plain)` en `remember_token` + fecha de expiración en `remember_token_expiry`.
- Lifetime configurable con `REMEMBER_LIFETIME` en `.env` (días). Default: `14`.
- `AuthMiddleware` intenta `Auth::loginWithCookie()` antes de redirigir a `/auth`.
- El token rota en cada auto-login para mitigar robo de cookie.
- Columnas en `tb_usuarios`: `remember_token VARCHAR(64) NULL`, `remember_token_expiry DATETIME NULL`.
- `User::storeRememberToken()` / `findByRememberToken()` / `clearRememberToken()`.

**Flujo de restablecimiento de contraseña:**

- Token: `bin2hex(random_bytes(32))` — 64 caracteres hex, expiración 1 hora.
- Columnas en `tb_usuarios`: `reset_token VARCHAR(255) NULL`, `reset_token_expiracion DATETIME NULL`.
- `User::storeResetToken()` / `findByResetToken()` / `clearResetToken()` — gestión del token.
- `App\Services\EmailService::sendResetLink()` — envío vía PHPMailer + Gmail SMTP.
- `APP_DEBUG=true` en `.env` → muestra el link en pantalla + envía email (modo desarrollo).
- `APP_DEBUG=false` → solo envía email con mensaje genérico (modo producción).

**Rate limiting en login:**

- 5 intentos fallidos consecutivos → cuenta bloqueada 15 minutos.
- Columnas en `tb_usuarios`: `login_intentos TINYINT UNSIGNED DEFAULT 0`, `login_bloqueado_hasta DATETIME NULL`.
- `User::recordFailedLogin(int $id)` — incrementa contador y activa bloqueo al llegar a 5 (timestamp calculado en PHP, no `NOW() + INTERVAL`, para compatibilidad con SQLite en tests).
- `User::isLocked(array $user): bool` — compara `login_bloqueado_hasta` con `time()`.
- `User::clearLoginAttempts(int $id)` — resetea contador y timestamp al login exitoso.
- `AuthController::store()` verifica bloqueo **antes** de `password_verify()` — sin revelar intentos restantes.
- Mensaje de bloqueo dirige al usuario a "¿Olvidaste tu contraseña?" como salida de emergencia.

**Toasts en vistas de auth:**

- Las vistas `login.php`, `forgot-password.php` y `reset-password.php` **no** contienen `<script>showToast()</script>`
  inline.
- `AuthController` llama a `$this->view(..., withMessages: true)` — `Controller::view()` incluye `messages.php` al
  final, que consume `$_SESSION['mensaje']` / `$_SESSION['icono']` y dispara `ToastUtils` desde JS.

### JavaScript / Frontend

- jQuery para DOM y eventos
- **DataTables** sin AJAX: datos cargados desde PHP en la vista, sin filtros server-side
- **SweetAlert2** para confirmaciones de eliminación. Dos patrones según el módulo:
  - **Patrón página dedicada** (products): botón llama `confirmarEliminar(id, nombre)` → verificación AJAX
    `GET /[modulo]/check/{id}` → si no referenciado redirige a `/[modulo]/delete/{id}` (página de confirmación con
    formulario oculto `#formEliminar` + CSRF); si referenciado muestra detalle de bloqueo vía SweetAlert2
  - **Patrón formulario inline** (purchases, sales, users): formulario oculto `#formEliminar` en `index.php`
    con CSRF + campos hidden del ID; confirmación con `AlertUtils.confirm()` + `ToastUtils.loadingWithMinTime()` →
    `form.submit()`; el JS vive en un archivo separado bajo `public/js/modules/[modulo]/`
- Anti-FOUC del sidebar/tema: script inline en `layouts/header.php`, preferencias en `localStorage`
- **Patrón modal + AJAX** (roles, categories, suppliers, clients): CRUD completo en `index.php` via modales Bootstrap;
  endpoints JSON en el controlador (`store`, `show`, `update`, `checkNombre`/`checkNitCi`/`checkEmail`); eliminación con
  `AlertUtils.confirm()` + AJAX (sin CSRF, `isReferenced()` retorna JSON error); jQuery Validate con regla `remote` para
  validación de duplicados en tiempo real; `ToastUtils.loadingWithMinTime()` durante operaciones asíncronas
- **Assets por vista** (`$pageStyles` / `$pageScripts`): arrays pasados a `renderWithLayout()` que el layout inyecta en
  `<head>` y antes de `</body>` respectivamente; las rutas son relativas a `BASE_URL` (e.g.
  `/js/modules/products/products-index.js`)

### Vistas MVC

- Todas usan `renderWithLayout()` del Controller base (compone header + contenido + footer)
- Constante `BASE_URL` disponible globalmente — usar para construir URLs en PHP y JS
- Constante `APP_CURRENCY_SYMBOL` disponible globalmente (definida en `public/index.php` desde `.env`, default `"Bs."`) —
  usar siempre esta constante para mostrar montos en vistas, PDFs (`InvoicePdf`, `PurchaseReportPdf`), controllers y
  `NumberToWords::convert()` (recibe `$currencyLabel` opcional, cae a la constante). **No hardcodear `"Bs."`** en
  código nuevo. **No usar el nombre `CURRENCY_SYMBOL`** — colisiona con una constante nativa de PHP (extensión intl).
- **Sin lógica de negocio en las vistas**: cálculos (totales, subtotales, sumas, agregaciones) se resuelven en el
  Controller o el Model y se pasan ya calculados a la vista (ej. `Sale::computeInvoiceTotals()`, `Sale::withSubtotals()`).
  Las vistas solo formatean/muestran valores recibidos — no hacen `foreach` acumulando `+=` sobre `$items`/`$rows`.
- Layout de páginas de listado: full width, DataTables con export (PDF/Excel/CSV/Imprimir)
- Layout de formularios CRUD: col-md-8 (form) + col-md-4 (tarjeta informativa)
- Layout de formularios POS (ventas/create): patrón wizard — col-md-9 con 3 tabs numerados (steps) + barra de
  progreso animada + validación entre pasos; col-md-3 sidebar sticky "Resumen de venta"; Tab 1 incluye card
  colapsable "Datos del cliente" con alerta de advertencia por defecto (sin cliente) y campos ocultos hasta
  selección; botones "Nuevo cliente" (modal inline) y "Buscar cliente" (modal tabla); estado del cliente
  persiste en `sessionStorage('pos_client')` para sobrevivir recargas por operaciones de carrito;
  `$_SESSION['pos_nro_venta']` persiste el número de venta activo en el POS — se asigna al entrar a `create()` y
  se limpia en `store()` (venta finalizada) y `cancel()` (venta cancelada); previene colisión de nro_venta entre
  vendedores concurrentes junto con `Sale::nextNumber()` que considera `MAX(tb_ventas UNION tb_carrito)`
- Layout de páginas de detalle (`show`): dos columnas — `col-md-4 col-lg-3` izquierda con imagen, nombre, código,
  categoría y acciones (editar, volver, PDF si aplica); `col-md-8 col-lg-9` derecha con tarjetas de métricas
  (`info-box`) y tabla/sección de datos; aplicado en `products/show.php`, `purchases/show.php` y `sales/show.php`
- **Partial de modales** (`views/[modulo]/partial/_modals.php`): cuando una vista acumula múltiples modales
  (crear, editar, ver detalle), extraerlos a un partial e incluirlos con `<?php include ... ?>` al final de la vista
  principal; aplicado en `suppliers/partial/_modals.php` incluido desde `suppliers/index.php`
- Breadcrumb obligatorio en cada vista (`<section class="content-header">`)

---

Migración MVC completada. No quedan módulos legacy pendientes.

---

## Testing

### Suites y estrategia

| Suite         | Directorio           | Estrategia                                           |
| ------------- | -------------------- | ---------------------------------------------------- |
| `Unit`        | `tests/Unit/`        | Lógica pura sin BD — Helpers, validaciones, cálculos |
| `Integration` | `tests/Integration/` | SQLite in-memory con schema completo                 |

```bash
composer test             # todas las suites
composer test:unit        # solo Unit (rápido, ideal pre-commit)
composer test:integration # solo Integration
composer test:coverage    # con reporte de cobertura (requiere PCOV)
```

### Convenciones de testing

- PHPUnit 11: usar `#[\PHPUnit\Framework\Attributes\DataProvider('method')]` — `@dataProvider` en docblock está deprecado
- Trait `RefreshDatabase`: BD limpia por test via `setUp as setUpDatabase` (trait aliasing — ver `UserRepositoryTest` como referencia)
- Seeders mínimos por test — solo los registros que el test necesita
- No testear Controllers, Middleware, Vistas ni Router
- Al agregar columnas o tablas a `database/schema.sql`, actualizar también `tests/fixtures/schema.sqlite.sql` con las diferencias de sintaxis:

  | MySQL                         | SQLite equivalente |
  | ----------------------------- | ------------------ |
  | `AUTO_INCREMENT`              | `AUTOINCREMENT`    |
  | `DECIMAL(10,2)`               | `NUMERIC`          |
  | `datetime`                    | `TEXT`             |
  | `INT(11)`                     | `INTEGER`          |
  | `ON UPDATE CURRENT_TIMESTAMP` | (omitir)           |
  | `ENGINE=InnoDB CHARSET=`      | (omitir)           |

### Inyección del singleton para tests

`Database::set(PDO $pdo)` sobrescribe el singleton para inyectar SQLite en tests. `Database::reset()` lo limpia. `getInstance()` queda intacto para producción.

---

## Flujo de Trabajo Git

```
master  ← rama principal (producción)
```

**Mensajes de commit (Conventional Commits, una línea, sin co-autor):**

```
feat(modulo): descripción breve en español
fix(modulo): descripción del bug corregido
chore(modulo): tarea de mantenimiento
docs: descripción del cambio de documentación
refactor(modulo): descripción del cambio
```

---

## Prohibiciones Explícitas

- **NO** concatenar variables directamente en queries SQL — siempre `?` con `execute()`
- **NO** usar borrado lógico con `is_active` — este proyecto usa borrado físico con `isReferenced()`
- **NO** inventar métodos de controlador fuera del estándar (`index`, `create`, `store`, `edit`, `update`, `destroy`)
- **NO** usar `alert()` nativo — usar SweetAlert2 con el patrón de formulario oculto para eliminaciones
- **NO** interpolar variables PHP directamente en strings JS — usar `json_encode()`
- **NO** modificar archivos dentro de `public/templates/` (fuente AdminLTE) ni los vendors copiados en `public/css/lib/`
  y `public/js/lib/`
- **NO** acceder a `Auth::` directamente en vistas — calcular datos en el controlador y pasarlos via
  `renderWithLayout()`

---

_Última actualización: 2026-08-14 — 1.16.1. Historial completo en [CHANGELOG.md](CHANGELOG.md)._
