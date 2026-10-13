# AGENTS.md — Sistema de Ventas PHP

> System prompt persistente para agentes de IA y sesiones de desarrollo asistido.
> Compatible con: Claude Code · Cursor (.cursorrules) · Claude.ai (pegar al inicio) · Copilot (workspace instructions)
> Fuente única de convenciones/arquitectura/setup. `CLAUDE.md` solo la importa.

---

## Proyecto

Sistema de gestión de ventas con control de inventario, facturación, gestión de clientes y acceso por roles.
Permite registrar ventas, compras a proveedores, gestionar el almacén y emitir facturas en PDF.

**Estado actual:** 1.19.2 — migración MVC completada (sin módulos legacy pendientes), RBAC granular con gestión de permisos vía UI, dashboard y módulos de ventas/compras scopeados por permisos reales y por usuario (`view_sales_all`/`view_purchases_all`, sin proxies de rol hardcodeados), devoluciones de ventas parciales con reingreso atómico de stock y venta neta en dashboard/reportes, formas de pago + pago mixto (catálogo `tb_metodos_pago`, líneas `tb_pagos`, vuelto, desglose en detalle/PDF, backfill idempotente), audit log con cobertura completa y KPIs, hardening de seguridad (cabeceras HTTP, detección de HTTPS tras proxy, saneo de HTML en SweetAlert2, prevención de IDOR en compras, guards anti escalada de privilegios en usuarios/roles y revalidación de cuenta y rol en cada petición), eliminación de `Swal.fire`/`onclick` inline en vistas, hardening de accesibilidad/UX en el flujo de autenticación y en los módulos de ventas/POS, Productos, Compras, Registro de actividad, Categorías, Clientes, Inventario, Permisos, Roles y Proveedores (auditorías de Clientes y Ventas cerradas con fixes globales de contraste WCAG AA en modo claro y oscuro y orden de encabezados en toda la app; paleta contextual de AdminLTE —badges, alerts, `btn-info`, cabeceras de modal `bg-*`, Select2 oscuro— corregida app-wide y verificada con axe-core en ambos temas), cache-busting de assets propios vía `APP_VERSION`, moneda configurable vía `.env`, borrado/edición de compras e inventario idempotentes ante doble envío, texto guardado sin escape HTML (se escapa al mostrar), cierre de las demás sesiones al cambiar la contraseña y zona horaria de `APP_TIMEZONE` aplicada también a la sesión de MySQL, exportaciones CSV a prueba de fórmulas, portada de reportes con permisos propios por bloque, guard del último rol superadmin, seeder de producción y guía de despliegue (`docs/despliegue.md`). Historial completo de versiones en [CHANGELOG.md](CHANGELOG.md).

---

## Stack Tecnológico

- **Backend:** PHP 8.2+ (sin framework — MVC custom con PSR-4 via Composer)
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
│   │   ├── PaymentMethodController.php  ← catálogo de formas de pago (modal+AJAX)
│   │   ├── SaleReturnController.php     ← devoluciones parciales de ventas
│   │   ├── InventoryController.php
│   │   ├── ReportController.php     ← index(), sales(), purchases(), topProducts(), clients(); export PDF/CSV/Excel
│   │   └── PermissionController.php ← index(), store(), show() (JSON), update(), checkClave() — catálogo de permisos
│   ├── Helpers/              ← PSR-4, namespace App\Helpers
│   │   ├── NumberToWords.php
│   │   ├── InvoicePdf.php
│   │   ├── PurchaseReportPdf.php
│   │   ├── ActivityLogRenderer.php  ← decodifica JSON del log y prepara filas para las vistas
│   │   ├── ReportFilters.php        ← parseDateRange() — normaliza rango GET (default = mes actual); parseTop() — 5/10/20/50, default 10
│   │   ├── Money.php                ← toCents()/fromCents(): montos en céntimos (mitad hacia arriba, coma o punto)
│   │   ├── MonthSeries.php          ← lastMonths(n): serie de meses consecutivos sin saltarse febrero
│   │   ├── ImageUpload.php          ← validate(): código de error de subida, extensión, MIME real, 2 MB
│   │   ├── AuthSettings.php         ← umbrales de login/reset/"recordarme" leídos de .env con default y rango
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
│   │   ├── PosReservation.php       ← reserva del nro_venta del POS por usuario (openFor/isOpenFor/release)
│   │   ├── AuthAttempt.php          ← intentos de login y de restablecimiento por correo e IP
│   │   ├── RememberToken.php        ← sesiones "recordarme", una fila por dispositivo
│   │   ├── ActivityLog.php
│   │   ├── StockAdjustment.php
│   │   ├── SaleReturn.php           ← devoluciones: reingreso atómico de stock, idempotencia por token
│   │   ├── PaymentMethod.php        ← catálogo tb_metodos_pago
│   │   ├── SalePayment.php          ← líneas de pago de una venta, vuelto (vueltoFor)
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
│   ├── auth/                 ← login.php, forgot-password.php, reset-password.php
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
│   ├── returns/               ← select-sale.php, create.php, index.php (devoluciones)
│   ├── payment-methods/       ← index.php; partial/_modals.php (catálogo modal+AJAX)
│   └── reports/               ← index.php, sales.php, purchases.php, top-products.php, clients.php; partial/_date_filter.php
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
    ├── seeder.sql            ← desarrollo: catálogo + datos demo + usuarios de prueba
    └── seeder_produccion.sql ← solo catálogo (roles, permisos, métodos de pago); al cambiar el catálogo en seeder.sql, replicarlo aquí
```

---

## Enrutamiento Híbrido

El proyecto usa **dos sistemas de ruteo en paralelo**:

| Tipo    | Cómo funciona                              | Módulos                                                                                                                             |
| ------- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------- |
| **MVC** | `public/index.php` → `Router` → Controller | auth, dashboard, users, roles, categories, suppliers, clients, products, purchases, sales, activity-log, inventory, reports (todos) |

Todos los módulos están migrados. No quedan módulos legacy.

El parámetro `{id}` de una ruta solo admite dígitos (`/products/abc` responde 404); `{token}` admite alfanuméricos, `-` y `_`.

> El detalle exacto de método + ruta + controller + middleware por endpoint vive en `routes/web.php` — es la fuente
> de verdad; no se replica aquí para evitar desincronización. Los middlewares usan la sintaxis `can:permiso` (ver
> slugs en §Autenticación).

---

## Base de Datos

`Database::connect()` fija `time_zone` de la sesión al desplazamiento UTC de `APP_TIMEZONE` (`+HH:MM`, no el nombre: no exige tablas de zonas en el servidor), así `NOW()`/`CURDATE()`/`fyh_creacion` usan el mismo reloj que `date()` de PHP. Al desplegar en un servidor MySQL cuya zona difiera de `APP_TIMEZONE`, las filas anteriores quedaron guardadas en la hora del servidor y se leerán desplazadas.

```sql
-- Tablas principales
tb_usuarios
(id_usuario, nombres, email [UNIQUE; se guarda normalizado a minúsculas], password_user, id_rol,
    fyh_creacion, fyh_actualizacion)
    -- Columnas OBSOLETAS, sin uso desde la 004 (se conservan para poder volver a 1.18.3; se eliminarán
    --   en una migración posterior): login_intentos, login_bloqueado_hasta, remember_token, remember_token_expiry.
    --   reset_token/reset_token_expiracion SÍ se usan, pero el token se guarda como hash SHA-256, nunca en claro.
    tb_intentos_auth
    (id_intento, tipo ['login'|'reset'], ip, correo [VARCHAR(250), igual que tb_usuarios.email], id_usuario [NULL si el correo no existe], fyh_creacion)
    -- una fila por intento, registrada ANTES de verificar nada; los umbrales y la ventana salen de AuthSettings
    tb_sesiones_recordadas
    (id_sesion, id_usuario [FK CASCADE], selector UNIQUE, token_hash [SHA-256 del validador], fyh_expira,
        ip, user_agent, fyh_creacion, fyh_ultimo_uso)
    -- una fila por dispositivo; la cookie lleva `selector:validador`
    tb_pos_reservas
    (id_reserva [ES el nro_venta del POS], id_usuario [FK NULL → ON DELETE SET NULL], fyh_creacion, fyh_liberacion [NULL = abierta])
    -- las filas nunca se borran (la secuencia no debe retroceder): borrar al usuario solo anula id_usuario.
    --   Tras volver temporalmente a 1.18.3 (numera con MAX+1 e ignora esta tabla) hay que reejecutar 011
    --   para subir la secuencia por encima de las ventas hechas con esa versión
    tb_roles
    (id_rol, rol, permisos_version, fyh_creacion, fyh_actualizacion)
    -- permisos_version se incrementa en Role::syncPermissions(); Auth::check() la compara contra
    -- $_SESSION['permisos_version'] para invalidar la caché de permisos sin requerir re-login
    tb_categorias
    (id_categoria, nombre_categoria, fyh_creacion, fyh_actualizacion)
    tb_proveedores
(id_proveedor, nombre_proveedor, celular, telefono [NULL], empresa, email [NULL], direccion,
    fyh_creacion, fyh_actualizacion)
tb_clientes
(id_cliente, nombre_cliente, nit_ci_cliente, celular_cliente,
    email_cliente, fyh_creacion, fyh_actualizacion)
tb_almacen
(id_producto, codigo, nombre, descripcion, stock, stock_minimo [NULL], stock_maximo [NULL],
    precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria,
    fyh_creacion, fyh_actualizacion)
tb_ventas
    (id_venta, nro_venta [UNIQUE uq_venta_nro; ya no hay FK hacia tb_carrito], id_cliente [NULL → venta sin cliente, se muestra como "Consumidor final"], id_usuario [FK NULL → ON DELETE SET NULL], total_pagado, fyh_creacion, fyh_actualizacion)
    -- id_usuario registra al vendedor; NULL en registros anteriores a v1.12.1
    -- id_cliente opcional desde 1.18.1: las lecturas usan LEFT JOIN y las salidas
    -- (listado, detalle, delete, dashboard, factura PDF, activity log, exports)
    -- muestran "Consumidor final"/"—" cuando es NULL
    -- total_pagado calculado server-side como SUM(cantidad * precio_unitario) desde tb_carrito, nunca del POST;
    -- la venta neta de un período es ventas menos devoluciones imputadas a ese período
    tb_carrito
    (id_carrito, nro_venta, id_producto, cantidad, precio_unitario [DECIMAL(10,2) NULL → fijado por
        Sale::storeWithStock desde tb_almacen.precio_venta al finalizar la venta; NULL = carrito en curso])
    tb_devoluciones
    (id_devolucion, nro_devolucion UNIQUE, id_venta [FK → tb_ventas], id_usuario [FK NULL → tb_usuarios],
        motivo, monto, token_formulario [CHAR(32) UNIQUE NULL: idempotencia del reenvío], fyh_creacion)
    tb_devolucion_items
    (id_detalle, id_devolucion [FK → tb_devoluciones CASCADE], id_producto [FK → tb_almacen],
        cantidad, precio_unitario)
    -- devoluciones parciales acumulativas; monto calculado desde el precio histórico de cada línea
    tb_metodos_pago
    (id_metodo_pago, nombre UNIQUE, tipo [enum: efectivo|no_efectivo], activo, fyh_creacion, fyh_actualizacion)
    -- catálogo configurable de métodos de cobro; solo `efectivo` admite exceso (vuelto); se gestiona
    -- desde /payment-methods con el permiso manage_payment_methods
    tb_pagos
    (id_pago, id_venta [FK → tb_ventas CASCADE], id_metodo_pago [FK → tb_metodos_pago NO ACTION],
        monto, fyh_creacion)
    -- una línea por método por venta (UNIQUE venta+metodo); el vuelto NUNCA se persiste como línea,
    -- se deriva con SalePayment::vueltoFor(float $total, array $payments)
    tb_compras
(id_compra, id_producto, nro_compra [UNIQUE uq_compra_nro], fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad, fyh_creacion, fyh_actualizacion)
    tb_activity_log
(id_log, id_usuario [FK NULL → ON DELETE SET NULL], usuario_nombre, accion, entidad, entidad_id,
    descripcion, datos_anteriores [text con JSON], datos_nuevos [text con JSON], ip_address, fyh_creacion)
    -- acciones registradas: 'create', 'update', 'delete', 'price_change', 'role_change', 'permission_change',
    --   'stock_adjustment', 'export', 'login', 'login_failed', 'logout'
    -- entidades: 'sale', 'purchase', 'product', 'user', 'client', 'supplier', 'category', 'role', 'permission',
    --   'report', 'auth', 'sale_return', 'payment_method'
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

- Passwords: `password_hash()` al guardar, `password_verify()` al validar; mínimo `User::MIN_PASSWORD_LENGTH` (8) caracteres validado server-side en alta, perfil, administrador y reset; el login no aplica el mínimo (las contraseñas anteriores siguen valiendo)
- Inputs: `htmlspecialchars()` en outputs HTML, validación server-side obligatoria; `Controller::input()` solo hace `trim` (el escape se hace al mostrar, nunca al guardar)
- CSRF: token en todos los formularios POST (`Auth::generateCsrfToken()` / `validateCsrfOrFail()`)
- SQL: siempre placeholders `?` con `execute([$var])` — nunca interpolar en el string SQL; `$interval` y `$limit` siempre con cast `(int)` explícito antes de interpolación
- Subidas de imágenes: `ImageUpload::validate()` comprueba el código de error, la extensión, el MIME real y 2 MB;
  `public/uploads/products/.htaccess` impide ejecutar PHP en ese directorio y debe estar versionado. **En un servidor
  que ignore `.htaccess` (Nginx, por ejemplo) hay que replicar la regla** (denegar `*.php` allí y desactivar el motor).
- Stock: solo escriben `tb_almacen.stock` `Sale`, `SaleReturn`, `Purchase`, `StockAdjustment` y
  `Product::createProduct` (lo vigila `StockWritesAuditTest`); la ficha de producto lo muestra solo lectura y se
  cambia desde Inventario. Los cambios sobre un producto existente son relativos (`stock = stock ± ?`), nunca un valor leído antes.
- Formularios que no deben procesarse dos veces y no tienen un identificador natural (ajuste de stock): token de un solo uso en sesión, `Auth::issueFormToken($scope)` al renderizar y `Auth::consumeFormToken($scope, $token)` justo antes de escribir; PHP serializa las peticiones de una misma sesión, así que no hay carrera. Si la idempotencia debe sobrevivir a la sesión, usar `token_formulario` UNIQUE como en devoluciones.
- Stock: el decremento en `Sale::storeWithStock()` usa `AND stock >= ?` y verifica `rowCount() === 0` para rollback — nunca produce stock negativo
- Totales: `Sale::storeWithStock()` congela `precio_unitario` por línea (desde `tb_almacen.precio_venta`) y calcula `total_pagado` como `SUM(cantidad * precio_unitario)` de `tb_carrito`, todo dentro de la transacción — ignorar siempre el valor del POST
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
Auth::check()           // bool — revalida sesión, timeout de inactividad y que la cuenta/rol sigan vigentes en BD
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
- Slugs de permisos en uso: `view_dashboard`, `manage_users`, `manage_roles`, `view_categories`, `manage_categories`, `view_suppliers`, `manage_suppliers`, `view_clients`, `manage_clients`, `view_products`, `manage_products`, `view_purchases`, `manage_purchases`, `view_sales`, `manage_sales`, `manage_returns`, `view_sales_all`, `view_purchases_all`, `view_activity_log`, `manage_inventory`, `manage_payment_methods`, `view_reports`, `view_sales_report`, `view_purchases_report`, `view_top_products_report`, `view_clients_report`, `is_superadmin`.
- **Scoping de datos por usuario (`*_all`):** `view_sales_all` y `view_purchases_all` distinguen "ver todos los
  registros" de "ver solo los propios". Sin el permiso `_all`, los métodos de listado/agregado filtran por
  `id_usuario` (ver `Sale`/`Purchase`/`Product::getTopSelling()`, que aceptan `?int $userId` opcional). Patrón usado
  en `DashboardController` (KPIs y gráficos), `SaleController::index/show/destroy` y
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
- **Portada de reportes (`/reports`):** cada bloque del resumen se carga y se muestra con su propio permiso
  (`view_sales_report`, `view_purchases_report`, `view_clients_report`, `view_top_products_report`), no con
  `view_reports`; el margen solo se calcula si ventas y compras cubren el mismo alcance (`*_all`).
- **El sidebar (`views/layouts/partials/_sidebar.php`) debe gatear cada enlace con su permiso real vía `$can[...]`,
  nunca con los proxies de rol `$isAdmin`/`$isSeller`/`$isBuyer`.** Esos proxies solo aproximan "tiene view_sales" /
  "tiene view_purchases" — usarlos para secciones con permisos más granulares (ej. Reportes, gateado antes por
  `$isSeller` en vez de `$can['view_reports']`) hace que el menú muestre enlaces a los que el usuario en realidad no
  tiene acceso (la ruta responde 403, pero la UI confunde). Está bien usarlos para las secciones cuyo único gate es
  precisamente `view_sales`/`view_purchases` (Ventas, Compras, Inventario).
- **Gestión vía UI:** catálogo de permisos en `/permissions` (alta con clave/descripción/módulo; la `clave` es **inmutable** tras crearla — los guards de escalada y las rutas `can:` la usan como identificador, renombrarla reasignaría su efecto; `PermissionController`,
  `views/permissions/`) y asignación por rol en `/roles/permisos/{id}` (checkboxes agrupados por `modulo`,
  `RoleController::permisos()`/`syncPermisos()`, `views/roles/permisos.php`). Ambas rutas reutilizan el permiso
  `manage_roles` — no se sembró un slug nuevo.
- **Invalidación de caché por versión:** `tb_roles.permisos_version` se incrementa en cada `Role::syncPermissions()`.
  `Auth::check()` compara la versión en sesión contra la de BD (una query barata por request) y llama
  `refreshPermissions()` si difiere — así los usuarios activos del rol ven el cambio en su siguiente request, sin
  re-login. El propio admin que edita su rol activo se refresca de inmediato vía `Auth::refreshPermissions()` en
  `syncPermisos()`.
- **Guard anti escalada de privilegios:** poder crear/editar usuarios (`manage_users`) o asignar permisos a roles
  (`manage_roles`) **no** incluye poder otorgarse `is_superadmin`. `UserController::blocksSuperadminEscalation()` y
  `Role::grantsSuperadmin()` + el guard de `RoleController::syncPermisos()` exigen `Auth::isAdmin()` cuando el rol
  destino es superusuario o cuando el usuario/rol afectado ya lo es. Usar este helper en cualquier flujo que asigne
  roles o permisos — gatear el CRUD no gatea la escalada. Además, `Role::isLastSuperadminRole()` impide quitar
  `is_superadmin` al único rol que lo otorga: los guards anteriores impedirían volver a concedérselo desde la UI.

**Sesión y CSRF:**

- La cookie de sesión es `HttpOnly`, `SameSite=Lax` y `Secure` cuando la petición es HTTPS (también tras un
  proxy que envía `X-Forwarded-Proto: https`). `Auth::login()` regenera el id de sesión y el token CSRF.

**Timeout de sesión:**

- Controlado por `SESSION_LIFETIME` en `.env` (minutos de inactividad). Default: `60`.
- `Auth::check()` compara `$_SESSION['last_activity']` con `time()`; si expiró destruye solo la sesión de PHP (no revoca el
  dispositivo "recordarme", que `AuthMiddleware` reabre con la cookie). `Auth::startSession()` sube `session.gc_maxlifetime` para
  que el gc de PHP no borre la sesión antes del timeout.
- `$_SESSION['pw_fp']` guarda la huella SHA-256 del hash de contraseña; `Auth::check()` cierra la sesión si ya no coincide, así
  cambiar o restablecer la contraseña cierra las demás sesiones abiertas. Quien cambia su propia contraseña llama a
  `Auth::syncPasswordFingerprint()` para conservar la suya.

**Umbrales de autenticación (`.env`, leídos por `App\Helpers\AuthSettings`):** un valor ausente, no entero o
fuera de rango se ignora y se usa el default. Los 7 ajustables:

| Variable                 | Default | Rango  | Significado                                         |
| ------------------------ | ------- | ------ | --------------------------------------------------- |
| `LOGIN_MAX_PER_EMAIL`    | 5       | 1–50   | intentos de login por correo dentro de la ventana   |
| `LOGIN_MAX_PER_IP`       | 20      | 1–500  | intentos de login por IP dentro de la ventana       |
| `LOGIN_WINDOW_MINUTES`   | 15      | 1–1440 | ventana de conteo de intentos                       |
| `RESET_TOKEN_MINUTES`    | 60      | 5–1440 | vigencia del enlace de restablecimiento             |
| `RESET_COOLDOWN_MINUTES` | 5       | 1–60   | espera entre solicitudes de reset del mismo usuario |
| `RESET_MAX_PER_IP_HOUR`  | 5       | 1–100  | solicitudes de reset por IP y hora                  |
| `REMEMBER_LIFETIME`      | 14      | 1–90   | días de vigencia de "recordarme"                    |

**"Recordarme":**

- Cookie `remember_token` = `"{selector}:{validador}"` — httponly, samesite=Strict, vigencia `REMEMBER_LIFETIME` días.
- Una fila por dispositivo en `tb_sesiones_recordadas`; la BD guarda solo `hash('sha256', $validador)`.
- `AuthMiddleware` intenta `Auth::loginWithCookie()` antes de redirigir a `/auth`.
- `RememberToken::consume()` rota el validador solo de esa fila; un token inválido no modifica ninguna fila y
  únicamente borra la cookie del cliente. Un cambio de IP se registra en el log de actividad sin rechazar el acceso.
- `logout()` revoca solo el dispositivo actual. Cambiar una contraseña (`User::updatePassword()`, `updateUser()` con
  contraseña, restablecimiento) llama a `RememberToken::revokeAllFor()` y cierra todos los dispositivos.
- Las columnas `remember_token*` de `tb_usuarios` están obsoletas y sin uso.

**Flujo de restablecimiento de contraseña:**

- Token: `bin2hex(random_bytes(32))`; en `tb_usuarios.reset_token` se guarda su hash SHA-256 (`User::storeResetToken()` /
  `findByResetToken()` / `clearResetToken()`), con vigencia `RESET_TOKEN_MINUTES`.
- **El enlace nunca se muestra en pantalla**, ni con `APP_DEBUG=true` ni con `APP_ENV=local`. Con `APP_DEBUG=true`
  solo se escribe en `error_log` (para desarrollo sin SMTP); la vista `show-reset-link.php` ya no existe.
- La respuesta es la misma exista o no el correo. El envío SMTP se hace después de cerrar la respuesta
  (`Controller::redirectThenRun()`), para que el tiempo de respuesta no delate si el correo está registrado.
- Límites con `AuthAttempt::resetThrottled()`: espera `RESET_COOLDOWN_MINUTES` por usuario y `RESET_MAX_PER_IP_HOUR`
  por IP; las solicitudes sobre el límite se ignoran en silencio.
- `App\Services\EmailService::sendResetLink()` — PHPMailer + Gmail SMTP; escapa los datos en el HTML del correo y
  el plazo mostrado sale de `AuthSettings::resetTokenMinutes()`.
- Cambiar la propia contraseña desde el perfil exige la contraseña actual (`User::verifyCurrentPassword()`);
  un administrador cambia la de otro usuario sin pedirla.

**Rate limiting en login:**

- `AuthController::store()` registra cada intento en `tb_intentos_auth` **antes** de verificar nada
  (`AuthAttempt::register()`) y cuenta solo los intentos con id menor o igual al propio (`isBlocked()`): en una
  ráfaga paralela solo los primeros por id llegan a verificar la contraseña. El reloj es el de la BD.
- Más de `LOGIN_MAX_PER_EMAIL` intentos por correo o `LOGIN_MAX_PER_IP` por IP dentro de `LOGIN_WINDOW_MINUTES`
  bloquean sin verificar la contraseña. Un correo inexistente cuenta igual.
- Correo inexistente, contraseña incorrecta y bloqueo muestran el mismo mensaje, con salida a «¿Olvidaste tu
  contraseña?»; para los correos inexistentes se verifica contra un hash ficticio (`User::passwordMatches()`).
- Un login correcto borra los intentos de ese correo (`AuthAttempt::clear()`).
- **Limitación conocida:** quien conozca un correo puede bloquear su login durante la ventana enviando intentos
  fallidos; la salida del usuario legítimo es «¿Olvidaste tu contraseña?». Se aceptó a cambio de no filtrar qué
  correos existen.
- Las columnas `login_intentos` y `login_bloqueado_hasta` están obsoletas y sin uso.

**Toasts en vistas de auth:**

- Las vistas `login.php`, `forgot-password.php` y `reset-password.php` **no** contienen `<script>showToast()</script>`
  inline.
- `AuthController` llama a `$this->view(..., withMessages: true)` — `Controller::view()` incluye `messages.php` al
  final, que consume `$_SESSION['mensaje']` / `$_SESSION['icono']` y dispara `ToastUtils` desde JS.

### JavaScript / Frontend

- jQuery para DOM y eventos
- **DataTables** sin AJAX: datos cargados desde PHP en la vista, sin filtros server-side
- **SweetAlert2** para confirmaciones de eliminación. Dos patrones según el módulo:
  - **Patrón página dedicada** (products, users): botón llama `confirmarEliminar(id, nombre)` → verificación AJAX
    `GET /[modulo]/check/{id}` → si no referenciado redirige a `/[modulo]/delete/{id}` (página de confirmación con
    formulario oculto `#formEliminar` + CSRF); si referenciado muestra detalle de bloqueo vía SweetAlert2.
    `sales` va directo a `/sales/delete/{id}` (sin `check`): `Sale::destroyWithStock()` rechaza con devoluciones
  - **Patrón formulario inline** (purchases): formulario oculto `#formEliminar` en `index.php`
    con CSRF + campos hidden del ID; confirmación con `AlertUtils.confirm()` + `ToastUtils.loadingWithMinTime()` →
    `form.submit()`; el JS vive en un archivo separado bajo `public/js/modules/[modulo]/`
- Anti-FOUC del sidebar/tema: script inline en `layouts/header.php`, preferencias en `localStorage`
- **Patrón modal + AJAX** (roles, categories, suppliers, clients, payment-methods): CRUD completo en `index.php` via modales Bootstrap;
  endpoints JSON en el controlador (`store`, `show`, `update`, `checkNombre`/`checkNitCi`/`checkEmail`); eliminación (clientes,
  proveedores y métodos de pago; roles y categorías no tienen ruta de borrado) con `AlertUtils.confirm()` + AJAX, validando CSRF
  con `validateCsrfOrFailJson` (`isReferenced()` retorna JSON error); jQuery Validate con regla `remote` para
  validación de duplicados en tiempo real; `ToastUtils.loadingWithMinTime()` durante operaciones asíncronas
- **Assets por vista** (`$pageStyles` / `$pageScripts`): arrays pasados a `renderWithLayout()` que el layout inyecta en
  `<head>` y antes de `</body>` respectivamente; las rutas son relativas a `BASE_URL` (e.g.
  `/js/modules/products/products-index.js`)
- **Accesibilidad (axe-core en claro y oscuro, incluidos tabs y modales):** el título de un modal es
  `<h2 class="modal-title h5">` (nunca `h4`/`h5` sueltos: rompen `heading-order`); todo botón o enlace de solo icono
  lleva `aria-label` (también el de colapsar `btn-tool`); cada `<label>` apunta a su campo con `for`/`id`; los `<li>`
  de las pestañas llevan `role="presentation"`. Al auditar con axe, desactivar las transiciones CSS antes de
  alternar `body.dark-mode`, o lee colores a medio animar.
- **Cache-busting**: los layouts anexan `?v=<?= APP_VERSION ?>` a todos los assets propios (CSS/JS de `core/` y
  cada entrada de `$pageStyles` / `$pageScripts`). Tras un fix de CSS/JS, subir `APP_VERSION` en `.env` para forzar
  la recarga en navegadores de usuarios. Los vendors (`lib/`, `plugins/`) no llevan query string.

### Vistas MVC

- Todas usan `renderWithLayout()` del Controller base (compone header + contenido + footer)
- Constante `BASE_URL` disponible globalmente — usar para construir URLs en PHP y JS
- Constante `APP_CURRENCY_SYMBOL` disponible globalmente (definida en `public/index.php` desde `.env`, default `"Bs."`) —
  usar siempre esta constante para mostrar montos en vistas, PDFs (`InvoicePdf`, `PurchaseReportPdf`), controllers y
  `NumberToWords::convert()` (recibe `$currencyLabel` opcional, cae a la constante). **No hardcodear `"Bs."`** en
  código nuevo. **No usar el nombre `CURRENCY_SYMBOL`** — colisiona con una constante nativa de PHP (extensión intl).
- **Sin lógica de negocio en las vistas**: cálculos (totales, subtotales, sumas, agregaciones) se resuelven en el
  Controller o el Model y se pasan ya calculados a la vista (ej. `Sale::computeInvoiceTotals()`, `Sale::withSubtotals()`, `Product::withStockBars()`).
  Las vistas solo formatean/muestran valores recibidos — no hacen `foreach` acumulando `+=` sobre `$items`/`$rows`.
- Layout de páginas de listado: full width, DataTables con export (PDF/Excel/CSV/Imprimir)
- Layout de formularios CRUD: col-md-8 (form) + col-md-4 (tarjeta informativa)
- Layout de formularios POS (ventas/create): patrón wizard — col-md-9 con 3 tabs numerados (steps) + barra de
  progreso animada + validación entre pasos; col-md-3 sidebar sticky "Resumen de venta"; Tab 1 incluye card
  colapsable "Datos del cliente" con alerta de advertencia por defecto (sin cliente) y campos ocultos hasta
  selección; botones "Nuevo cliente" (modal inline) y "Buscar cliente" (modal tabla); estado del cliente
  persiste en `sessionStorage('pos_client')` para sobrevivir recargas por operaciones de carrito;
  el número de venta activo lo reserva `PosReservation::openFor()` (una reserva abierta por vendedor; su id
  autoincremental ES el `nro_venta`, así dos vendedores nunca coinciden) y no se guarda en sesión; el carrito solo
  lo opera su dueño (`CartItem` devuelve `forbidden`) y una venta ya cobrada no admite más cambios;
  `Sale::storeWithStock()` es idempotente: reenviar el mismo cobro devuelve la venta original (`replayed`) sin tocar
  stock y libera la reserva al cobrar
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

| Suite                   | Directorio                                  | Estrategia                                                                                                                                                     |
| ----------------------- | ------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Unit`                  | `tests/Unit/`                               | Lógica pura sin BD — Helpers, validaciones, cálculos                                                                                                           |
| `Integration`           | `tests/Integration/`                        | SQLite in-memory con schema completo                                                                                                                           |
| `Integration` (MariaDB) | `tests/Integration/Models/*MariaDbTest.php` | MariaDB real vía trait `RefreshMariaDatabase` — solo para funciones de fecha del motor (`CURDATE()`, `NOW()`, `YEAR()`, `DATE_FORMAT()`) que SQLite no soporta |

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

### Tests contra MariaDB real (`RefreshMariaDatabase`)

- Solo para casos que dependen de funciones de fecha del motor MySQL/MariaDB (`CURDATE()`, `NOW()`, `YEAR()`,
  `DATE_FORMAT()`, etc.) que SQLite no soporta o soporta con semántica distinta.
- Local: copiar `.env.testing.example` a `.env.testing` y ajustar credenciales; `tests/bootstrap.php` la carga
  automáticamente si existe. Sin ese archivo (o si el servidor no responde), el test se salta con
  `markTestSkipped()` en vez de fallar — la suite completa sigue corriendo sin exigir MariaDB instalada.
- El trait recrea la BD de test (`DROP`/`CREATE DATABASE` + `database/schema.sql`) en cada `setUp()`/`tearDown()`
  sobre **una sola BD de nombre fijo** (`DB_TEST_NAME`, p. ej. `sistemadeventas_test`), nunca la de desarrollo: el
  trait falla si el nombre no contiene `test`. Por eso la suite no se ejecuta en paralelo contra la misma BD; una
  corrida interrumpida deja esa BD, que la siguiente recrea.
- **Nunca calcular la fecha límite con `date()`/`strtotime()` de PHP y comparar contra `NOW()`/`CURDATE()` de la
  BD** — el reloj/timezone del proceso PHP y el del servidor MariaDB pueden diferir. Calcular la fecha límite con
  una expresión SQL evaluada por el propio motor (`NOW() - INTERVAL 1 HOUR`, etc.).
- En CI (`tests.yml`), el job `test` levanta un servicio `mariadb:10.11` con las env vars `DB_TEST_*` ya
  configuradas — no requiere `.env.testing` en ese entorno.

### Inyección del singleton para tests

`Database::set(PDO $pdo)` sobrescribe el singleton para inyectar SQLite en tests. `Database::reset()` lo limpia. `getInstance()` queda intacto para producción.

**Todo test que resetee el singleton debe dejar SQLite inyectado al terminar** (`Database::set(...)`, nunca solo `Database::reset()`): con `executionOrder="random"`, los tests Unit que hacen `new Model()` y corren después intentan conectar a MySQL. Localmente pasa porque el `.env` apunta a una BD real; en CI no existe y fallan. Para reproducir el escenario de CI, correr `vendor/bin/phpunit --testsuite Unit` sin `.env` ni `.env.testing`.

### CI (`.github/workflows/tests.yml`)

Un único job `test`, matrix PHP 8.2/8.3, que corre en cada push a `master` y cada PR:

- Ejecuta `vendor/bin/phpunit` (suites `Unit` + `Integration` en un solo proceso). La suite completa
  son 880 tests y tarda ~2 min en local con MariaDB real; no se filtra por diff ni se paraleliza — el coste real del pipeline es el provisioning del
  runner, no los tests, así que filtrar salía más caro que correr todo.
- `concurrency` con `cancel-in-progress` descarta runs obsoletos cuando llegan pushes seguidos.
- Levanta el servicio `mariadb:10.11` para los tests `*MariaDbTest.php`; el resto corre contra SQLite.

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

## Planificación de features (Spec-Driven Development)

Este proyecto usa SDD para features nuevas: primero spec, luego plan, luego
tareas, y solo entonces código. **La spec es el contrato: si no está ahí, no se
implementa.** El detalle del flujo y sus principios viven en
[docs/constitution.md](docs/constitution.md) (principios no negociables) y
[docs/roadmap.md](docs/roadmap.md) (hecho / en curso / backlog).

### Artefactos

```
docs/constitution.md      ← principios del proyecto (nivel proyecto)
docs/roadmap.md           ← qué está hecho / en curso / backlog (nivel proyecto)
specs/NNN-<slug>/
├── spec.md               ← QUÉ y POR QUÉ (FRs en notación EARS) + § Clarificaciones
├── plan.md               ← CÓMO (módulos, datos, decisiones, riesgos)
├── tasks.md              ← tareas <30 min + bloque de cierre
└── validation.md         ← recorrido FR por FR + veredicto (lo deja la fase 7)
```

Una feature no se cierra hasta que existen los cuatro archivos.

### Fases

| #   | Fase           | Comando              |
| --- | -------------- | -------------------- |
| 1   | Constitución   | `/sdd:constitution`  |
| 2   | Especificación | `/sdd:spec`          |
| 3   | Clarificación  | `/sdd:clarify`       |
| 4   | Planificación  | `/sdd:plan`          |
| 5   | Tareas         | `/sdd:tasks`         |
| 6   | Implementación | `/sdd:implement <n>` |
| 7   | Validación     | `/sdd:validate`      |
| 8   | Cambio         | `/sdd:change <req>`  |

### Reglas

- **Cuándo lleva spec**: si el cambio toca el esquema de BD, introduce un slug de
  permiso nuevo, o introduce una regla de negocio nueva (dinero, stock,
  integridad) → flujo SDD completo en `specs/NNN-<slug>/`. Módulo nuevo completo
  (controller + model + vistas + rutas + permiso) → siempre SDD.
- **Cuándo NO**: fixes de contraste/CSS/dark-mode, auditorías a11y por módulo
  (usan la skill `impeccable` + axe-core, tienen su propio ciclo), bumps de
  CI/dependencias, actualizaciones de docs/CHANGELOG, bugs de una línea,
  renombres de identificadores. Van por flujo directo + skill `git-commit`.
- **Numeración `specs/NNN`**: contador propio de `specs/`, tres dígitos,
  monótono, sin reutilizar números borrados. **Empieza en `001`** (no había
  serie previa). No es el ordinal del roadmap ni un FR histórico.
- **Verificación como puerta**: ninguna tarea se marca `[x]` sin `composer test`
  verde; si toca UI, además axe-core sin violaciones en modo claro y oscuro.
- **Precedencia**: `docs/constitution.md` manda sobre specs y planes. Ante
  conflicto entre `docs/`/`specs/` y este archivo, este archivo es la fuente
  operativa para **código** y `docs/`/`specs/` es la capa de **planificación**.
- **`docs/` y `specs/` se versionan pero no se despliegan**: excluidos del
  artefacto de deploy y del docroot (ver `.gitattributes`; el docroot de
  producción debe ser `public/`).
- No modificar `specs/` fuera de su fase sin pedido explícito.

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

### Lecciones de frontend (Bootstrap / DataTables)

- Un `<button>` dentro de un `.input-group` (flex `align-items:stretch`) no se encoge igual que un `<div>` hermano. Envuélvelo en un `<div>` tipo `.input-group-text` y resetea el botón (`all:unset; display:flex; align-items:center; justify-content:center; width:100%; height:100%`). Para el área táctil de 44px no pongas `min-height`/`min-width` en la caja visible: usa un `::before` absoluto con `width/height: max(100%, 44px)`.
- No parches una regla de un selector base compartido (`.form-control`, `.btn`) para un síntoma local. Compara `getComputedStyle` con un hermano que sí se vea bien y acota el fix al selector del elemento roto.
- Los tooltips de Bootstrap se pierden al paginar/ordenar/buscar en DataTables (las filas se reemplazan). Inicializa en un handler delegado `$(document).on('draw.dt', …)` **sin** `tooltip('dispose')` previo, comprobando `!$(this).data('bs.tooltip')`; sube la versión de cache-busting tras cambiar JS core.
- Una plantilla HTML oculta con la misma clase que los items clonados rompe cualquier selector global por esa clase (conteos, guards): escopa los selectores al contenedor real (`'#contenedor .item'`).

---

_Última actualización: 2026-10-13 — 1.19.2. Historial completo en [CHANGELOG.md](CHANGELOG.md)._
