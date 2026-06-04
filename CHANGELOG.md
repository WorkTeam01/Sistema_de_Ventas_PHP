# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

---

## [1.10.0] - 2026-06-03

### Agregado

- **Módulo de inventario completo** — dos tabs en `/inventory` (solo Administrador): "Control de Stock" y "Ajustes de Stock"
- `database/schema.sql` y `tests/fixtures/schema.sqlite.sql` — tabla `tb_ajustes_stock` con FK a `tb_almacen` y FK nullable `ON DELETE SET NULL` a `tb_usuarios`; campos `tipo` (enum entrada/salida), `cantidad`, `stock_anterior`, `stock_posterior`, `motivo`, `usuario_nombre` (desnormalizado), `fyh_creacion`
- `app/Models/StockAdjustment.php` — `register()` con transacción atómica (UPDATE stock + INSERT ajuste + ActivityLog::record()); `history()` con filtros opcionales (desde, hasta, tipo, id_producto) y parámetro `$limit` para limitar resultados
- `app/Models/Product.php` — `inventoryList(string $estado): array` con JOIN a `tb_categorias` y filtro por estado (todos/bajo/agotado); `inventoryStats(): array` con conteos y valor total del inventario
- `app/Controllers/InventoryController.php` — `index()` con whitelist de tabs; tab stock carga productos + últimos 10 ajustes; tab ajustes aplica filtros server-side; `storeAdjustment()` con CSRF + validación completa
- `views/inventory/index.php` — tabs server-side con `?tab=`, modal de ajuste con form y CSRF fuera del card principal
- `views/inventory/partials/stock-control.php` — card "Alertas de Stock Bajo" (colapsable, danger outline); tabla "Estado del Inventario" con barras de progreso y scroll (max-height 400px); tabla "Últimos Ajustes" con link a historial completo
- `views/inventory/partials/adjustments.php` — filtros colapsables en dos filas responsivas (desde, hasta, tipo, producto); historial en card con DataTable y botón "Ajustar Stock" en card-header
- `views/inventory/partials/adjustment-form.php` — campos del modal: select2 de producto con stock visible en opción, tipo con labels descriptivos, cantidad con input-group (icono + "unidades"), motivo con maxlength; preview de stock proyectado en tiempo real
- `public/js/modules/inventory/inventory.js` — DataTable para historial; Select2 con destroy/reinit en cada apertura del modal; preview de stock proyectado; confirmación `AlertUtils.confirm()` + `ToastUtils.loadingWithMinTime()`; redirección a compras desde alertas con confirmación
- Rutas `GET /inventory` y `POST /inventory/adjustments` con middleware `auth, admin`
- Link "Inventario" en sidebar (solo rol Administrador)
- Tests de integración: `StockAdjustmentTest.php` (6 tests: entrada, salida, stock insuficiente, producto inexistente, ok+posterior, filtro por tipo) y `InventoryListFieldsTest.php` (3 tests: campos para badge, stats con producto, stats tabla vacía)

### Modificado

- `app/Models/ActivityLog.php` — acción `'stock_adjustment'` documentada en docblock de `record()`
- `views/purchases/create.php` — pre-selección de producto via `$_GET['id_producto']` para redirección rápida desde alertas de inventario
- `views/layouts/partials/_sidebar.php` — link "Inventario" en sección Administración (solo `$isAdmin`)

### Técnico

- Transacción atómica en `StockAdjustment::register()`: si falla el UPDATE de stock, el INSERT de ajuste no se ejecuta y el log de auditoría tampoco (ActivityLog tiene try/catch interno, nunca interrumpe la transacción)
- `history()` acepta `$limit` opcional para evitar cargar todo el historial en el tab de Control de Stock
- Select2 en modal: destroy antes de reinicializar en cada `shown.bs.modal` para evitar duplicación del dropdown
- Preview de stock proyectado: lee `data-stock` del option seleccionado, actualiza en tiempo real al cambiar producto, tipo o cantidad

---

## [1.9.0] - 2026-05-23

### Agregado

- **Módulo de auditoría completo** — registro de operaciones sensibles en `tb_activity_log` con filtros obligatorios de fecha (máximo 90 días), listado paginado con DataTables y vista de detalle por evento
- `database/schema.sql` y `tests/fixtures/schema.sqlite.sql` — tabla `tb_activity_log` con FK nullable `ON DELETE SET NULL` a `tb_usuarios`; `usuario_nombre` desnormalizado para persistir el actor incluso si el usuario es eliminado; índices en `fyh_creacion`, `accion` y `entidad`
- `app/Models/ActivityLog.php` — modelo con `record()` (try/catch centralizado, nunca interrumpe la operación principal), `search()` (filtros + COALESCE para usuarios eliminados), `availableEntities()`, `availableActions()`, `purgeOlderThan()`
- `app/Controllers/ActivityLogController.php` — `index()` con `resolveRange()` (máximo 90 días, default 7), `show()` con datos preparados para partials
- `app/Helpers/ActivityLogRenderer.php` — helper sin HTML: decodifica JSON del log y devuelve filas `{label, value, type}` listas para las vistas; `label()` traduce claves técnicas a español
- `views/activity-log/index.php` — filtros colapsables con select2, date inputs con `showPicker()`, DataTable con traducción manual al español
- `views/activity-log/show.php` — tabla de información del evento + partials reutilizables para datos anteriores/nuevos
- `views/activity-log/partial/_data-panel.php` — renderiza tabla key-value con HTML puro
- `views/activity-log/partial/_item-accordion.php` — acordeón Bootstrap para listas de ítems (ventas/compras con múltiples productos)
- `public/js/modules/activity-log/activity-log-index.js` — validación de rango de fechas + DataTable con traducción manual
- Rutas `GET /activity-log` y `GET /activity-log/show/{id}` con middleware `auth, admin`
- Link "Auditoría" en el sidebar (solo rol Administrador)

### Modificado

- `app/Controllers/ProductController.php` — registra `price_change` en `ActivityLog` cuando cambia `precio_venta` o `precio_compra` (snapshot antes del update, log solo si los precios difieren)
- `app/Controllers/UserController.php` — registra `role_change` al cambiar `id_rol` de un usuario; registra `delete` al eliminar un usuario con snapshot de datos
- `app/Controllers/ClientController.php` — registra `delete` al eliminar un cliente con snapshot de datos
- `app/Controllers/SupplierController.php` — registra `delete` al eliminar un proveedor con snapshot de datos
- `app/Controllers/SaleController.php` — registra `delete` al eliminar una venta con snapshot de ítems y datos de cabecera
- `app/Controllers/PurchaseController.php` — registra `delete` al eliminar una compra con snapshot de datos
- `routes/web.php` — 2 rutas nuevas para el módulo activity-log
- `views/layouts/partials/_sidebar.php` — link "Auditoría" en sección Administración

### Técnico

- Patrón snapshot: captura del estado del registro **antes** de la mutación; el log se escribe solo si la operación fue exitosa
- `ActivityLog::record()` con try/catch centralizado — un fallo en el log nunca interrumpe la operación principal
- `ActivityLogRenderer` sin HTML: separación limpia entre lógica de presentación (helper) y markup (partials PHP/HTML)
- Tests de integración en `tests/Integration/Models/ActivityLogRepositoryTest.php` — 6 casos cubriendo campos nullable, JSON, persistencia de `usuario_nombre` y orden de resultados

---

## [1.8.0] - 2026-05-22

### Agregado

- **Rate limiting en login** — bloqueo de cuenta tras 5 intentos fallidos consecutivos (15 minutos)
- `app/Models/User.php` — `isLocked(array $user): bool`, `recordFailedLogin(int $id): void`, `clearLoginAttempts(int $id): void`
- `database/schema.sql` y `tests/fixtures/schema.sqlite.sql` — columnas `login_intentos TINYINT UNSIGNED DEFAULT 0` y `login_bloqueado_hasta DATETIME NULL` en `tb_usuarios`; timestamp calculado en PHP para compatibilidad SQLite en tests
- `tests/Integration/Models/UserRepositoryTest.php` — 6 tests nuevos: `isLocked` con null/pasado/futuro, `recordFailedLogin` sin bloqueo (4 intentos) y con bloqueo (5 intentos), `clearLoginAttempts`

### Modificado

- `app/Controllers/AuthController.php` — `store()` reescrito: verifica bloqueo antes de `password_verify()`; llama `recordFailedLogin()` en fallo y `clearLoginAttempts()` en éxito; mensaje de bloqueo dirige al usuario a "¿Olvidaste tu contraseña?" sin revelar intentos restantes
- `app/Models/CartItem.php` — `purgeOrphans(int $excludeNroVenta)` elimina carritos sin venta finalizada excluyendo el `nro_venta` activo (previene borrado del carrito en construcción); `clearCart(int $nroVenta)` para cancelación explícita
- `app/Controllers/SaleController.php` — `create()` llama `nextNumber()` antes de `purgeOrphans()` para excluir el carrito activo; `cancel()` limpia el carrito del `nro_venta` activo y redirige al listado
- `routes/web.php` — ruta `POST /sales/cancel` registrada con middleware `auth, seller`
- `views/sales/create.php` — botón Cancelar usa `data-nro-venta` y `data-csrf` sin form adicional
- `public/js/modules/sales/sales-create.js` — handler de cancelación con `fetch().finally()` limpia `sessionStorage` y redirige independientemente del resultado
- `database/schema.sql` — `UNIQUE KEY nit_ci_cliente` y `UNIQUE KEY email_cliente` en `tb_clientes` como red de seguridad complementaria a las validaciones de modelo
- `tests/fixtures/schema.sqlite.sql` — `nit_ci_cliente TEXT NOT NULL UNIQUE` y `email_cliente TEXT NOT NULL UNIQUE` en `tb_clientes`

### Corregido

- **Carrito huérfano en POS** — ítems en `tb_carrito` sin venta finalizada en `tb_ventas` (sesión abandonada) se purgan al iniciar una nueva venta
- **Cancelar venta disparaba validación de cliente** — botón dentro de `#formVenta` activaba jQuery Validate; resuelto moviendo la lógica al JS con `fetch()` en lugar de un form separado

---

## [1.7.0] - 2026-05-04

### Agregado

- **PHPUnit 11.x** — suite `Unit` (lógica pura, sin BD) y suite `Integration` (SQLite in-memory); 90 tests, 149 assertions
- `tests/bootstrap.php` — carga autoload e inyecta PDO SQLite vacío en `Database::set()` para que los tests Unit puedan instanciar modelos sin MySQL
- `tests/TestCase.php` — clase base mínima que extiende `PHPUnit\Framework\TestCase`
- `tests/Concerns/RefreshDatabase.php` — trait que construye un PDO `sqlite::memory:` fresco, llama `Database::set()` y ejecuta el schema antes de cada test de integración; en `tearDown` reinyecta un PDO vacío para no romper los tests Unit que sigan
- `tests/fixtures/schema.sqlite.sql` — schema portado a SQLite (sin `AUTO_INCREMENT`, `DECIMAL→NUMERIC`, `datetime→TEXT`, sin `ON UPDATE CURRENT_TIMESTAMP`, sin `ENGINE`/`CHARSET`)
- `tests/Unit/Helpers/NumberToWordsTest.php` — 9 tests para `NumberToWords::convert()` con `#[DataProvider]` (PHPUnit 11)
- `tests/Unit/Models/ClientValidationTest.php` — 10 tests para `Client::isValidEmail()` con dataProviders de casos válidos e inválidos
- `tests/Unit/Models/SaleComputeTotalsTest.php` — 8 tests para `Sale::computeInvoiceTotals()`: carrito vacío, ítem único, múltiples ítems, precisión decimal, claves retornadas
- `tests/Integration/Models/UserRepositoryTest.php` — 11 tests: `createUser`, `updateUser` con/sin contraseña, `storeResetToken`/`clearResetToken`, `storeRememberToken`/`clearRememberToken`, `isReferenced` (falso, con productos, con compras)
- `tests/Integration/Models/ClientRepositoryTest.php` — 11 tests: CRUD completo, `isReferenced`, `nitCiExists`/`emailExists` con exclusión por ID
- `tests/Integration/Models/SupplierRepositoryTest.php` — 12 tests: `createSupplier`/`updateSupplier` con normalización de campos opcionales vacíos → `null`, `isReferenced`, `nameExists` con exclusión
- `tests/Integration/Models/ProductRepositoryTest.php` — 11 tests: `createProduct` con normalización, `findWithCategory`/`allWithCategories` (JOINs), `nextCode`, `isReferenced` con carrito y compras
- `.github/workflows/tests.yml` — CI con matrix PHP 8.2/8.3, PCOV para coverage (solo PHP 8.3), caché de Composer, pasos `Unit` e `Integration` separados; sin MySQL (SQLite in-memory)
- `phpunit.xml.dist` — configuración PHPUnit: dos suites, `executionOrder="random"`, `failOnWarning="true"`, cobertura excluye Controllers y Middleware
- `CLAUDE.md` — nueva sección "Testing" con comandos `composer test`, tabla de suites, convenciones y tabla de diferencias MySQL→SQLite para mantener el schema sincronizado

### Modificado

- `app/Core/Database.php` — agregados `set(PDO $pdo): void` y `reset(): void` (métodos estáticos de inyección para tests); `getInstance()` existente intacto — si nadie llamó `set()`, construye el PDO MySQL normal
- `composer.json` — `require-dev`: `phpunit/phpunit ^11.0`, `fakerphp/faker ^1.23`; `autoload-dev`: namespace `Tests\` → `tests/`; scripts `test`, `test:unit`, `test:integration`, `test:coverage`
- `.gitignore` — agregados `.phpunit.cache/`, `.phpunit.result.cache`, `coverage.xml`

### Eliminado

- `docs/plan-phpunit.md` — documento de planificación interno eliminado tras implementación completa

---

## [1.6.3] - 2026-04-30

### Agregado

- `app/Core/Auth.php` — `Auth::loginWithCookie()`: auto-login desde cookie `remember_token`; rota el token en cada
  uso para mitigar robo de cookie; limpia BD y cookie si el token es inválido o expirado
- `app/Core/Auth.php` — `Auth::clearRememberCookie()` (privado): borra la cookie con `expires` en el pasado,
  httponly, samesite=Strict
- `app/Models/User.php` — `storeRememberToken(int $userId, string $tokenHash, string $expiryDatetime): bool`
- `app/Models/User.php` — `findByRememberToken(int $userId, string $tokenHash): ?array` — verifica
  `remember_token_expiry > NOW()` en la misma query
- `app/Models/User.php` — `clearRememberToken(int $userId): bool`
- `database/schema.sql` — `tb_usuarios`: columnas `remember_token VARCHAR(64) NULL` y
  `remember_token_expiry DATETIME NULL`; índice `idx_usuarios_remember_token`
- `views/auth/login.php` — checkbox "Recordarme" con icheck-bootstrap (ya incluido); envía `remember=1`
- `.env` / `.env.example` — variables `SESSION_LIFETIME=60` (minutos de inactividad) y `REMEMBER_LIFETIME=14`
  (días de vida de la cookie)

### Modificado

- `app/Core/Auth.php` — `login(array $user, bool $remember = false)`: acepta parámetro `$remember`; si es
  `true` genera token plain, almacena su SHA-256 en BD y emite cookie httponly/samesite=Strict/secure según entorno;
  lifetime configurable con `REMEMBER_LIFETIME` en `.env` (default: 14 días)
- `app/Core/Auth.php` — `check()`: añade timeout de inactividad; lee `SESSION_LIFETIME` de `.env` (default: 60
  minutos); llama a `logout()` y retorna `false` si el tiempo excede el límite; actualiza `last_activity` en cada
  petición válida
- `app/Core/Auth.php` — `logout()`: antes de destruir la sesión, llama a `User::clearRememberToken()` y
  `clearRememberCookie()` para invalidar el token en BD y borrar la cookie
- `app/Core/Controller.php` — `view()` acepta tercer parámetro `bool $withMessages = false`; si es `true`
  incluye `views/layouts/messages.php` al final (mismo patrón que `renderWithLayout()`)
- `app/Controllers/AuthController.php` — `store()` lee `$_POST['remember']` y lo pasa a `Auth::login()`;
  todas las llamadas a `view()` usan `withMessages: true`; `showLogin()` elimina lectura manual de
  `$_SESSION['mensaje']`
- `app/Middleware/AuthMiddleware.php` — intenta `Auth::loginWithCookie()` antes de redirigir a `/auth`
- `views/auth/login.php` — eliminado bloque `<?php if ($respuesta): ?><script>showToast()</script><?php endif ?>`
- `views/auth/forgot-password.php` — eliminado bloque `showToast()` inline
- `views/auth/reset-password.php` — eliminado bloque `showToast()` inline

### Eliminado

- `docs/plan-auth.md` — documento de planificación interno eliminado tras implementación completa

---

## [1.6.2] - 2026-04-29

### Refactorizado

- `public/css/` y `public/js/` — reorganización de vendors y plugins en subdirectorios explícitos:
  `css/lib/` (AdminLTE, FontAwesome, Bootstrap), `css/plugins/` (SweetAlert2, DataTables, Select2),
  `js/lib/` (jQuery, Bootstrap, AdminLTE) y `js/plugins/` (SweetAlert2); los archivos copiados de
  `public/templates/AdminLTE-3.2.0/plugins/` y los sueltos `css/sweetalert2.min.css` /
  `js/sweetalert2.min.js` / `css/style.css` son reemplazados por esta nueva estructura
- `views/auth/login.php`, `forgot-password.php`, `reset-password.php`, `show-reset-link.php` —
  rutas de assets actualizadas a la nueva estructura `css/lib/`, `css/plugins/`, `js/lib/`, `js/plugins/`
- `views/errors/403.php`, `404.php`, `500.php` — rutas de assets actualizadas
- `views/layouts/header.php`, `footer.php` — rutas de assets actualizadas; eliminada referencia a
  `css/style.css` (absorbida por `css/core/ui-components.css`)

### Documentación

- `AGENT.md`, `CLAUDE.md`, `README.md` — estructura `public/` actualizada con los nuevos subdirectorios
  `lib/` y `plugins/`; prohibición extendida a `public/css/lib/` y `public/js/lib/` (no modificar vendors)

---

## [1.6.1] - 2026-04-28

### Corregido

- `app/Models/Purchase.php` — `totalsByMonth()`, `totalCurrentMonth()` y `totalPreviousMonth()` usaban `fyh_creacion`
  (fecha de inserción del registro) en lugar de `fecha_compra` (fecha real de la compra); todas las compras aparecían
  agrupadas en el mes de inserción, causando que la barra de Compras en el gráfico del dashboard se acumulara en el mes
  actual en lugar de distribuirse por mes como las Ventas

### Modificado

- `views/layouts/partials/_sidebar.php` — reescrito con detección de ruta activa basada en `REQUEST_URI`; cada ítem
  de menú y árbol treeview recibe clases `active` / `menu-open` dinámicamente según la ruta actual; nombre de usuario
  en el panel lateral enlaza a `/profile` con `htmlspecialchars()`; se eliminó la clase `active` hardcodeada en todos
  los ítems; lógica de roles centralizada en variables `$isAdmin`, `$isSeller`, `$isBuyer`
- `views/layouts/header.php` — reformateo de indentación para consistencia (sin cambio funcional)
- `database/seeder.sql` — compras de ejemplo expandidas de 5 a 18 registros distribuidos en cuatro meses
  (Enero–Abril 2026) para que el gráfico del dashboard muestre datos representativos desde el primer uso;
  datos de proveedores y clientes alineados con columnas reales del schema; ajuste en `tb_usuarios`
  (columna `reset_token` añadida al INSERT)
- `public/.htaccess` — movido desde raíz a `public/` para que Apache redirija correctamente al front controller
  desde el directorio público; eliminado `.htaccess` de la raíz del proyecto

### Eliminado

- `docs/plan-password-reset.md` — documento de planificación interno eliminado por ser contenido de trabajo temporal

---

## [1.6.0] - 2026-04-11

### Agregado

- **POS: creación de cliente inline** — nuevo modal "Nuevo cliente" en `views/sales/create.php`
  (`#modal-nuevo_cliente`, form `#formNuevoCliente`) que permite registrar un cliente sin salir del wizard;
  al crearlo se auto-selecciona y se rellenan los campos del Tab 1
- **POS: validación inline con jQuery Validate** — `#formNuevoCliente` valida en tiempo real con reglas
  `remote` para NIT/CI y email (reutiliza los endpoints `/clients/check-nit-ci` y `/clients/check-email`)
- **POS: persistencia del cliente en recarga** — `seleccionarCliente()` guarda los datos en
  `sessionStorage('pos_client')` para sobrevivir la recarga de página que dispara al agregar/eliminar
  productos del carrito; se restaura automáticamente en `$(function(){})` al volver a cargar

### Modificado

- `views/sales/create.php` — Tab 1 rediseñado: card interna colapsable "Datos del cliente",
  alerta de advertencia visible por defecto cuando no hay cliente, campos ocultos con `d-none`
  hasta seleccionar uno, alerta de éxito dismissible al seleccionarlo; botones "Nuevo cliente" (verde)
  y "Buscar cliente" (azul) en la cabecera del tab
- `public/js/modules/sales/sales-create.js` — función `seleccionarCliente()` extraída y unificada;
  handler de cambio migrado a `$(document).on('input keyup', '#total_pagado')` para mayor fiabilidad
- `app/Controllers/ClientController.php` — `store()` ahora retorna los datos del cliente creado
  (`id_cliente`, `nombre_cliente`, `nit_ci_cliente`, `celular_cliente`, `email_cliente`) en la respuesta JSON
- `app/Controllers/SaleController.php` — `create()` agrega `'validation'` a los plugins opcionales
  cargados por `renderWithLayout()` (necesario para jQuery Validate en el wizard POS)

### Corregido

- **Cambio no calculaba** — la ausencia del plugin `validation` provocaba un error JS que detenía
  la ejecución del script antes de registrar el listener `#total_pagado`; corregido al agregar
  `'validation'` en `SaleController::create()`

---

## [1.5.0] - 2026-04-10

### Agregado

- **Flujo de restablecimiento de contraseña** — implementación completa portada desde sistema-hielo-cambita
  y adaptada a las convenciones del proyecto (`tb_usuarios`, `id_usuario`, `password_user`, `fyh_*`)
- `app/Services/EmailService.php` — nuevo servicio de envío de email vía PHPMailer + Gmail SMTP;
  template HTML responsivo con tabla de presentación, botón CTA y enlace alternativo en texto plano
- `app/Controllers/AuthController.php` — métodos `forgotPassword()`, `sendResetLink()`,
  `showResetForm()`, `resetPassword()` para el flujo completo de recuperación
- `app/Models/User.php` — métodos `storeResetToken()`, `findByResetToken()`, `clearResetToken()`
  para gestión segura del token de restablecimiento en BD
- `views/auth/forgot-password.php` — formulario de solicitud de restablecimiento
- `views/auth/reset-password.php` — formulario de nueva contraseña con indicador de fortaleza en tiempo real
- `views/auth/show-reset-link.php` — vista exclusiva de modo desarrollo que muestra el link en pantalla
  e indica si el email fue enviado correctamente
- `public/js/modules/auth/forgot-password.js` — validación jQuery Validate + spinner de envío
- `public/js/modules/auth/reset-password.js` — validación jQuery Validate + strength meter (4 niveles)
- `public/css/modules/auth/reset-password.css` — estilos de la barra de fortaleza de contraseña
- `composer.json` — dependencia `phpmailer/phpmailer ^7.0`
- `.env.example` — variables `APP_DEBUG`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
  `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

### Modificado

- `database/schema.sql` — `tb_usuarios`: columna `token` reemplazada por `reset_token VARCHAR(255)`
  y `reset_token_expiracion DATETIME` para el flujo de restablecimiento con expiración de 1 hora
- `routes/web.php` — 4 rutas nuevas bajo `/auth/forgot-password` y `/auth/reset-password`
  con middleware `guest`
- `views/auth/login.php` — enlace "¿Olvidaste tu contraseña?" → `/auth/forgot-password`;
  icono del toast ahora dinámico (soporta `success` además de `error`)

### Comportamiento en modo desarrollo (`APP_DEBUG=true`)

- Email no encontrado en BD → error explícito en lugar del mensaje genérico de seguridad
- Email encontrado → envía el email Y muestra el link en pantalla con indicación de si el envío fue exitoso

---

## [1.4.1] - 2026-04-10

### Agregado

- `views/suppliers/partial/_modals.php` — modales de crear, editar y ver detalle de proveedor
  extraídos de `views/suppliers/index.php` a su propio partial para reducir tamaño de la vista principal
- Botón "Ver detalle" (`btn-show`, ícono `fa-eye`) en la tabla de proveedores que abre el modal de detalle

### Modificado

- `views/suppliers/index.php` — eliminados los bloques HTML de los modales (ahora en `_modals.php`);
  se incluye el partial al final de la vista con `<?php include ... ?>`
- `public/js/modules/suppliers/suppliers-modals.js` — añadido handler para `btn-show` que carga y muestra
  los datos del proveedor en el modal de detalle vía AJAX a `/suppliers/show/{id}`
- `views/products/show.php` — rediseño con layout de dos columnas: columna izquierda con imagen, nombre,
  código, categoría y acciones; columna derecha con tarjetas de métricas (precio venta, precio compra, stock)
  y tabla de detalles del producto
- `views/purchases/show.php` — mismo patrón de dos columnas aplicado: imagen del producto a la izquierda
  con acciones (PDF, editar, volver), métricas y datos de la compra a la derecha
- `views/sales/show.php` — rediseño: cálculo de totales (`total_cantidad`, `precio_total`, `total_productos`)
  movido a PHP antes del HTML; encabezado con número de venta y acciones (imprimir factura, volver)
- `views/sales/delete.php` — refactor menor de marcado: tabla de resumen reemplazada por `<dl class="row">`,
  ajustes de espaciado y clases CSS
- `views/users/profile.php` — eliminado ítem ID de usuario del listado de información de perfil
- `public/css/core/ui-components.css` — ajustes de estilos de apoyo para los nuevos layouts de detalle

---

## [1.4.0] - 2026-04-09

### Agregado

- `app/Helpers/PurchaseReportPdf.php` — nuevo helper que encapsula la generación del comprobante PDF
  de compra: configuración de TCPDF, sección de proveedor, tabla de producto con cantidad/precio/total,
  código QR y emisión inline; expone `PurchaseReportPdf::generate(array $purchase, string $comprador, int $id): void`
- `app/Helpers/InvoicePdf.php` — nuevo helper que encapsula toda la generación del PDF de factura:
  configuración de TCPDF, construcción del HTML con ítems y totales, código QR y emisión inline;
  expone `InvoicePdf::generate(array $sale, array $totals, string $vendedor, int $id): void`
- Ruta `GET /purchases/report/{id}` — nueva ruta para emitir el comprobante PDF de una compra
- Botón "Reporte PDF" en `views/purchases/show.php` — abre el comprobante en nueva pestaña

### Modificado

- `app/Controllers/PurchaseController.php` — añadido `report()`: obtiene la compra con
  `findWithDetails()` y delega la generación del PDF a `PurchaseReportPdf::generate()`;
  el nombre del comprador se obtiene de `nombre_usuario` del registro (usuario real que creó la compra),
  no del usuario en sesión
- `app/Models/Purchase.php` — `findWithDetails()` incluye ahora `us.nombres AS nombre_usuario`
  para exponer el nombre del creador de la compra en el reporte PDF
- `app/Helpers/InvoicePdf.php` — datos del QR reformateados con `\n` entre campos para mejor
  legibilidad al escanear; QR reposicionado y ampliado a 40×40mm
- `app/Helpers/PurchaseReportPdf.php` — info box simplificado a 2 columnas (sin columna vacía central);
  datos del QR con `\n` entre campos; QR 40×40mm; `RoundedRect` calibrado a h=30
- `app/Models/Sale.php` — fat model: añadido `computeInvoiceTotals(array $items): array` que calcula
  `precio_total`, `cantidad_total` y `total_unitarios` a partir de los ítems del carrito; lógica que
  antes vivía en `SaleController::invoice()`
- `app/Controllers/SaleController.php` — `invoice()` reducido de ~120 a ~20 líneas: obtiene datos,
  delega cálculo de totales a `Sale::computeInvoiceTotals()` y generación del PDF a `InvoicePdf::generate()`;
  eliminado `use App\Helpers\NumberToWords` (ahora lo consume `InvoicePdf` internamente);
  documentado el early-return de carrito vacío en `store()` como capa de UX, independiente de la
  verificación de integridad en `Sale::storeWithStock()`
- `app/Models/Client.php` — fat model: añadido `isValidEmail(string $email): bool` que encapsula
  `filter_var(FILTER_VALIDATE_EMAIL)`; la validación de formato de email ya no vive en el controlador
- `app/Controllers/ClientController.php` — eliminados los dos llamados directos a `filter_var()` en
  `store()` y `update()`; reemplazados por `$clientModel->isValidEmail($email_cliente)`; instanciación
  del modelo movida antes de la validación de formato para evitar duplicar `new Client()`
- `app/Models/Product.php` — fat model: añadidos `createProduct()` y `updateProduct()` que normalizan
  internamente campos opcionales (`descripcion`/`stock_minimo`/`stock_maximo` vacíos → `null`) y castean
  tipos (`(int)stock`, `(float)precio_*`); añadido `findWithCategory(int $id)` que retorna el producto
  con `nombre_categoria` en una sola query JOIN con `tb_categorias`
- `app/Controllers/ProductController.php` — `store()` y `update()` reemplazados `create()`/`update()`
  directos por `createProduct()`/`updateProduct()` (normalización y casteos eliminados del controlador);
  `show()` y `delete()` usan `findWithCategory()` en lugar de dos queries separadas (eliminado uso de
  `$categoryModel` en ambos métodos)
- `app/Models/Supplier.php` — fat model: añadidos `createSupplier()` y `updateSupplier()` como métodos
  tipados que normalizan internamente los campos opcionales `telefono` y `email` (vacío → `null`);
  sigue el mismo patrón que `User::createUser()` / `User::updateUser()`
- `app/Controllers/SupplierController.php` — eliminadas las 4 líneas de normalización manual
  (`empty → null`) duplicadas en `store()` y `update()`; reemplazados `create([...])` y `update(...)`
  por `createSupplier(...)` y `updateSupplier(...)`
- `app/Models/User.php` — patrón fat model aplicado: `createUser()`, `updateUser()` y `updatePassword()`
  ahora aceptan contraseña en texto plano y ejecutan `password_hash()` internamente; `createUser()` migrado
  de `insert()` (deprecated) a `create()`
- `app/Controllers/UserController.php` — eliminados los tres llamados a `password_hash()` (movidos al modelo);
  añadido guard `if (!$usuario)` en `profile()` (era acceso sin verificar); `new \DateTime()` reemplazado
  por `date_create()` para evitar excepción no manejada
- `app/Core/Controller.php` — `redirect()` y `json()` cambian tipo de retorno de `void` a `never`, permitiendo
  que PhpStorm infiera correctamente el flujo de control tras estos métodos
- `app/Models/Purchase.php` — fat model: añadido `validateData(array $data): bool|array` que encapsula
  todas las validaciones de datos (campos obligatorios, tipos numéricos, cantidad > 0); retorna `true`
  si válidos o array de errores con claves de campo; `validateData()` incluye validaciones exhaustivas:
  comprobante mínimo 3 caracteres, precio > 0
- `app/Controllers/PurchaseController.php` — `store()` y `update()` reemplazados validaciones manuales
  repetidas por llamada a `$purchaseModel->validateData($data)`; flujo simplificado: recopilación de datos
  → validación centralizada en modelo → operación transaccional o redirección con errores

### Eliminado

- `views/users/show.php` — vista de detalle de usuario eliminada por ser redundante con el listado
  (`index` ya expone nombre, email y rol); acciones de editar/eliminar disponibles directamente desde la tabla
- `UserController::show()` — método eliminado junto con su vista
- Ruta `GET /users/show/{id}` — removida de `routes/web.php`
- Botón "Ver detalles" (ojo) del listado de usuarios — reemplazado por acceso directo a Editar/Eliminar

---

## [1.3.5] - 2026-04-07

### Agregado

- `views/users/profile.php` — página de perfil propio accesible a todos los roles; patrón AdminLTE dos columnas:
  card izquierda con avatar de iniciales, nombre, badge de rol y datos del usuario; card derecha con 2 tabs
  (Editar perfil: nombre + email / Cambiar contraseña); colores de cards variables según rol
  (`card-danger` Administrador, `card-success` Vendedor, `card-warning` Comprador)
- `public/css/modules/users/profile.css` — estilos del avatar circular de iniciales con gradiente por rol
- `public/js/modules/users/users-profile.js` — validación jQuery Validate para ambos formularios del perfil;
  verificación AJAX de unicidad de email (reutiliza `/users/check-email`); toggle de visibilidad de contraseña

### Modificado

- `app/Controllers/UserController.php` — añadidos `profile()` (GET), `updateProfile()` (POST info),
  `updatePassword()` (POST contraseña); toda la lógica de presentación (iniciales, fecha formateada, clases CSS
  por rol, tab activo, variables HTML-safe) se computa en el controlador antes de pasar a la vista
- `app/Models/User.php` — añadidos `updateProfileInfo(id, nombres, email)` y `updatePassword(id, hash)` como
  métodos separados; `findWithRoleById()` incluye ahora `fyh_creacion` en el SELECT
- `routes/web.php` — añadidas rutas `GET /profile`, `POST /profile/update` y `POST /profile/password`
  con middleware `auth` (accesibles a cualquier rol autenticado)
- `views/layouts/partials/_sidebar.php` — enlace "Mi Perfil" añadido como primer ítem del menú, visible
  para todos los roles

---

## [1.3.4] - 2026-04-06

### Refactorizado

- `views/sales/create.php` — rediseño del POS con patrón wizard de 3 tabs numerados (1. Cliente → 2. Carrito → 3. Pago) + sidebar sticky "Resumen de venta"; barra de progreso animada "Paso X de 3"; validación entre tabs
  (no avanza sin cliente seleccionado / sin productos en carrito); tab de pago con `input-group` Bs. para total
  pagado y cambio; estado vacío en tabla del carrito con icono orientativo; modales de búsqueda fuera del
  `<form>` principal; patrón col-md-9 + col-md-3
- `public/js/modules/sales/sales-create.js` — reescrito con estado wizard (`currentStep`, `goToStep()`,
  `updateProgress()`); navegación Siguiente/Anterior con validación; sincronización de progreso al hacer click
  directo en tabs; restauración del tab activo via `sessionStorage` tras recarga por operaciones de carrito;
  actualización de resumen lateral al seleccionar cliente; migrado de `var` a `let`/`const`
- `app/Controllers/SaleController.php` — `create()` inyecta `pageStyles` con `/css/modules/sales/create.css`

### Agregado

- `public/css/modules/sales/create.css` — estilos del wizard POS: `.pos-progress` (barra de 26px), tabs dentro
  de card, sticky desactivado en `max-width: 767.98px`

---

## [1.3.3] - 2026-04-06

### Refactorizado

- `views/purchases/create.php` — rediseño con patrón two-pane: col-8 con 3 cards colapsables (Encabezado,
  Proveedor y Producto, Precio y Cantidad), col-4 con sidebar sticky de resumen en tiempo real (producto,
  proveedor, precio unitario, cantidad y total calculado); fecha con `input-group` y calendario clickable;
  precio con prefijo `$`; comprobante con icono `fa-list`
- `views/purchases/edit.php` — mismo patrón two-pane sticky sidebar aplicado; sidebar pre-poblado con los
  valores actuales al cargar; cards y badge de total en `card-success` / `badge-success` coherentes con el
  tema de edición; selects con clase `select2`
- `public/js/modules/purchases/purchases-create.js` — agregado `updateResumen()` con cálculo en tiempo real
  de total (precio × cantidad); badge cambia de `badge-secondary` → `badge-primary` cuando total > 0;
  `errorPlacement` actualizado para manejar `.input-group` y `.d-flex`
- `public/js/modules/purchases/purchases-edit.js` — mismo `updateResumen()` aplicado; se llama al cargar
  para poblar el sidebar con los valores existentes; `errorPlacement` actualizado para `.input-group`
- `app/Controllers/PurchaseController.php` — `create()` y `edit()` inyectan `pageStyles` con
  `/css/modules/purchases/create.css`; `edit()` añade dependencia `select2`

### Agregado

- `public/css/modules/purchases/create.css` — estilos del patrón two-pane: `.purchase-sidebar-sticky`
  (sticky `top: 20px`), tabla de resumen, `.resumen-total` destacada, truncado con `text-overflow: ellipsis`
  en `#resumenProducto` / `#resumenProveedor`; responsive: sticky desactivado en `max-width: 767.98px`

---

## [1.3.2] - 2026-04-06

### Refactorizado

- `views/products/create.php` — rediseño con patrón two-pane: columna izquierda (col-8) con campos del formulario
  agrupados en cards colapsables, columna derecha (col-4) con sidebar sticky de resumen (imagen, precios, margen,
  acciones); reemplaza el formulario de una sola columna anterior
- `views/products/edit.php` — mismo patrón two-pane sticky sidebar aplicado a la vista de edición; sidebar con
  imagen actual, resumen de precios y acciones (Guardar / Cancelar)
- `public/js/modules/products/products-create.js` — agregado cálculo de margen en tiempo real: actualiza
  `#resumenPrecioCompra`, `#resumenPrecioVenta`, `#resumenGanancia` y `#badgeMargen` (badge con color semántico:
  verde ≥20%, amarillo ≥10%, rojo <10%) al cambiar precio de compra o venta
- `public/js/modules/products/products-edit.js` — mismo cálculo de margen en tiempo real aplicado a la vista de
  edición; inicializado con los valores actuales del producto al cargar la página
- `app/Controllers/ProductController.php` — `create()` ahora inyecta `pageStyles` con
  `/css/modules/products/create.css`; reformateo de alineación de variables (sin cambio funcional)

### Agregado

- `public/css/modules/products/create.css` — estilos del patrón two-pane: `.product-sidebar-sticky` (sticky con
  `top: 20px`), estilos de tabla de resumen, badge de margen con ancho mínimo, preview de imagen; responsive: sticky
  desactivado en `max-width: 767.98px`

---

## [1.3.1] - 2026-04-05

### Corregido

- `views/layouts/footer.php` — eliminado `<div id="sidebar-overlay"></div>` que impedía que AdminLTE's PushMenu
  adjuntara el click handler al overlay en vista móvil (el sidebar no se cerraba al tocar fuera); restaurado `</div>`
  de cierre del `.wrapper` que había sido removido accidentalmente

### Refactorizado

- `app/Controllers/DashboardController.php` — toda la lógica auxiliar que estaba en la vista ahora se calcula en el
  controlador: preparación de `$chartData` (labels + datasets), clase de columna Bootstrap `$kpiCol`, claves de
  presentación `ventas_var_cls/ico/bar` y `compras_var_cls/ico/bar` dentro de `$kpis`, flag `stock_critico`, y flag
  `critico` por producto en `low_stock_products`
- `views/dashboard/index.php` — eliminado bloque PHP de 59 líneas en el tope de la vista y los snippets `<?php $var =
...; ?>` inline; la vista ahora solo consume variables inyectadas por el controlador; `$chartData` reemplaza las
  variables separadas `$chartLabels`/`$chartDatasets`

### Notas de Versión

- **Causa raíz del bug del sidebar móvil:** AdminLTE comprueba `if ($('#sidebar-overlay').length === 0)` antes de
  llamar a `_addOverlay()`. Como el div ya existía en el HTML, el método nunca se ejecutaba y el overlay quedaba sin
  handler. La solución es dejar que AdminLTE lo cree dinámicamente.

---

## [1.3.0] - 2026-04-04

### Agregado

- `app/Models/Sale.php` — `totalCurrentMonth(): float`, `totalPreviousMonth(): float`, `todaySummary(): array`,
  `latest(int $limit): array`, `totalsByMonth(int $months): array` para alimentar KPIs del dashboard
- `app/Models/Purchase.php` — `totalCurrentMonth(): float`, `totalPreviousMonth(): float`,
  `totalsByMonth(int $months): array`
- `app/Models/Product.php` — `countLowStock(): int`, `lowStockProducts(int $limit): array`
- `public/css/modules/dashboard/dashboard.css` — estilos del dashboard: badges de stock, thumbnail de producto,
  contenedor responsivo del gráfico; override de `white-space: normal` en `.progress-description` (AdminLTE truncaba
  los textos comparativos en las KPI cards)
- `public/js/modules/dashboard/dashboard.js` — inicialización de gráfico Chart.js (barras agrupadas ventas/compras
  últimos 6 meses); datos inyectados desde PHP vía `<script type="application/json" id="dashboard-chart-data">`

### Refactorizado

- `app/Controllers/DashboardController.php` — reemplazados 8 conteos simples (`total_user`, `total_roles`, etc.) por
  array `$kpis` con datos segmentados por rol; usa solo los modelos necesarios (`Product`, `Purchase`, `Sale`);
  `renderWithLayout()` con `pageStyles` y `pageScripts` del dashboard
- `views/dashboard/index.php` — rediseño completo: 4 KPI cards con variación porcentual vs. mes anterior (visibles
  según rol), gráfico de barras Chart.js (últimos 6 meses), tabla de últimas 5 ventas y tabla de productos con stock
  bajo; número de columnas de las KPI cards calculado dinámicamente según rol

### Notas de Versión

- **KPIs por rol:** Administrador ve ventas + compras + stock bajo; Vendedor ve ventas + stock bajo; Comprador ve
  compras + stock bajo. Las queries se ejecutan solo si el rol lo requiere.
- **Datos del gráfico via JSON embebido:** se usa `<script type="application/json">` para transferir los datos de PHP
  a JS sin interpolación directa en el script, evitando XSS y errores de escape.

---

## [1.2.6] - 2026-04-04

### Refactorizado

- `views/clients/index.php` — eliminado bloque `<script>` inline completo (DataTable + `confirmarEliminar` con
  `Swal.fire()` directo) y formulario oculto `#formEliminar`; reemplazado botón `<a href="/clients/create">` por
  `<button data-toggle="modal" data-target="#modalCreate">`; botones de acción cambiados a `btn-edit` / `btn-delete`
  con `data-*` para delegación de eventos
- `app/Controllers/ClientController.php` — métodos `store()`, `update()`, `destroy()` convertidos de
  redirect+flash a `json()`; eliminados `create()` y `edit()` (páginas separadas); `index()` agrega `pageScripts` y
  carga asset `validation`

### Agregado

- `app/Models/Client.php` — `nitCiExists(string $nitCi, ?int $excludeId = null): bool` y
  `emailExists(string $email, ?int $excludeId = null): bool` para validación de duplicados con exclusión al editar
- `app/Controllers/ClientController.php` — `show()` (AJAX, pre-llena modal de edición),
  `checkNitCi()` y `checkEmail()` (endpoints jQuery Validate remote)
- `views/clients/index.php` — modales `#modalCreate` y `#modalEdit` inline con layout `modal-lg` de 2 columnas;
  campos: `nombre_cliente`, `nit_ci_cliente`, `celular_cliente`, `email_cliente`
- `public/js/modules/clients/clients-datatable.js` — DataTable estandarizado: `exportOptions: { columns: [0,1,2,3,4] }`
  en todos los botones para excluir columna Acciones (índice 5); PDF con título/subtítulo/fecha/footer paginado;
  Excel con `messageTop`/`messageBottom`; botón ColVis = `'Columnas'`
- `public/js/modules/clients/clients-modals.js` — jQuery Validate en `#formCreate` y `#formEdit` con reglas `remote`
  para `nit_ci_cliente` (`/clients/check-nit-ci`) y `email_cliente` (`/clients/check-email`); AJAX CRUD completo con
  `ToastUtils.loadingWithMinTime()`; `AlertUtils.confirm()` para eliminación; reset de modales al cerrar

### Eliminado

- `views/clients/create.php` — reemplazada por modal `#modalCreate` en `index.php`
- `views/clients/edit.php` — reemplazada por modal `#modalEdit` en `index.php`

### Notas de Versión

- **Módulo clients alineado al patrón modal + AJAX:** replica el patrón de `suppliers` (modal + AJAX, sin páginas
  separadas create/edit, sin CSRF en endpoints AJAX, eliminación inline con `isReferenced()` → JSON error).
- **Dos campos únicos en clients:** a diferencia de suppliers (un solo campo único `empresa`), clients valida
  `nit_ci_cliente` y `email_cliente` como únicos — dos endpoints remote independientes con exclusión por ID al editar.
- **Sin `create()` / `edit()` en el controlador:** el módulo no usa páginas de formulario dedicadas. Las rutas
  `GET /clients/create` y `GET /clients/edit/{id}` han sido eliminadas.

---

## [1.2.5] - 2026-04-04

### Refactorizado

- `views/sales/index.php` — eliminado bloque `<script>` inline completo (DataTable init básico sin exportOptions)
- `views/sales/create.php` — eliminado bloque `<script>` inline completo (~100 líneas: DataTables de modales, lógica de
  carrito, selección producto/cliente, cálculo de cambio, validación pre-submit)
- `app/Controllers/SaleController.php` — `index()` agrega `pageScripts` con `sales-index.js`; `create()` agrega
  `pageScripts` con `sales-create.js`

### Agregado

- `public/js/modules/sales/sales-index.js` — DataTable estandarizado: `exportOptions: { columns: [0,1,2,3,4] }` en
  todos los botones para excluir columna Acciones (índice 5); PDF con título/subtítulo/fecha/footer paginado;
  Excel con `messageTop`/`messageBottom`; `pageLength: 10`; `lengthMenu: [[5,10,25,50]]`; botón ColVis = `'Columnas'`
- `public/js/modules/sales/sales-create.js` — JS del POS extraído: DataTables de modales `#productTable` /
  `#clientTable` (sin botones de exportación); selección de producto y cliente; agregar al carrito; cálculo de cambio;
  validación pre-submit; `Swal.fire()` directo reemplazado por `AlertUtils.warning()` en 2 lugares

### Notas de Versión

- **Módulo sales alineado al estándar de modularización JS:** replica el patrón del módulo `users` (v1.2.3) y
  `purchases` (v1.2.4) — todo el JS en archivos separados bajo `public/js/modules/sales/`, sin lógica inline en vistas.
- **Sin `confirmarEliminar` en sales-index.js:** las ventas usan página de confirmación dedicada (
  `views/sales/delete.php`);
  el modelo `Sale` no implementa `isReferenced()`, por lo que no aplica el patrón AJAX check previo.

---

## [1.2.4] - 2026-04-04

### Refactorizado

- `views/purchases/index.php` — eliminado bloque `<script>` inline completo (DataTable init + `confirmarEliminar` con
  `Swal.fire()` directo); el form oculto `#formEliminar` se mantiene intacto
- `views/purchases/create.php` — agregado `id="purchaseCreateForm"` al `<form>` para que jQuery Validate lo tome como
  selector
- `views/purchases/edit.php` — agregado `id="purchaseEditForm"` al `<form>`
- `app/Controllers/PurchaseController.php` — `index()` agrega `pageScripts` con `purchases-index.js`; `create()` y
  `edit()` agregan `pageScripts` con sus respectivos JS y pasan `['validation']` como cuarto argumento a
  `renderWithLayout()`

### Agregado

- `public/js/modules/purchases/purchases-index.js` — DataTable estandarizado (exportOptions excluye columna Acciones,
  índice 7; PDF con título/subtítulo/fecha/footer paginado; Excel con messageTop/Bottom; botón ColVis = `'Columnas'`);
  `confirmarEliminar(id, idProducto, cantidad, nombre)` reemplaza `Swal.fire()` directo por `AlertUtils.confirm()` +
  `ToastUtils.loadingWithMinTime()` + `form.submit()` (sin pre-check AJAX — purchases no tiene `isReferenced()`)
- `public/js/modules/purchases/purchases-create.js` — jQuery Validate para `#purchaseCreateForm`; reglas:
  `fecha_compra` (required), `comprobante` (required, minlength:3), `id_producto`/`id_proveedor` (required),
  `precio_compra` (number, min:0.01), `cantidad` (digits, min:1); `errorPlacement` especial para selectores con wrapper
  `.d-flex` (botón "+" adyacente); `submitHandler` con `ToastUtils.loadingWithMinTime('Guardando compra...')`
- `public/js/modules/purchases/purchases-edit.js` — mismo patrón que `purchases-create.js`; `submitHandler` con
  `ToastUtils.loadingWithMinTime('Actualizando compra...')`

### Notas de Versión

- **Flujo de eliminación de compras:** La confirmación pasa de `Swal.fire()` directo a `AlertUtils.confirm()`. No hay
  pre-check AJAX (`check()` / `isReferenced()`) porque `tb_compras` no es referenciada por otras tablas; el flujo es:
  `AlertUtils.confirm` → asignar hidden fields → `ToastUtils.loadingWithMinTime` → `form.submit()`.
- **Patrón JS modularizado:** purchases se alinea al estándar del módulo users — todo el JS en archivos separados bajo
  `public/js/modules/purchases/`, sin lógica inline en vistas.

---

## [1.2.3] - 2026-04-04

### Cambiado

- `public/js/modules/users/users-index.js` — DataTable actualizado al estándar del proyecto:
  `exportOptions: { columns: [0,1,2,3] }` en todos los botones de exportación para excluir la columna Acciones; PDF con
  título, subtítulo, fecha de generación y footer paginado; Excel con `messageTop`/`messageBottom`; Print con estilo de
  tabla; botón ColVis renombrado a `'Columnas'`; limpieza de estilo (sin comillas en claves, camelCase)
- `public/js/modules/products/products-index.js` — mismas mejoras de DataTable;
  `exportOptions: { columns: [0,2,3,4,5,6] }` excluye además la columna Imagen (índice 1) además de Acciones (índice 7)

---

## [1.2.2] - 2026-04-04

### Cambiado

- `app/Models/Supplier.php` — agregado `nameExists(string $empresa, ?int $excludeId = null): bool` para validación de
  unicidad de empresa (excluye el registro actual al editar)
- `app/Controllers/SupplierController.php` — refactorizado a AJAX puro: `index()` renderiza la página con
  `['datatable', 'validation']`; `store()`, `show()`, `update()`, `destroy()` responden JSON; `checkNombre()` endpoint
  para jQuery Validate `remote`; eliminados `create()` y `edit()` como páginas separadas
- `routes/web.php` — rutas de proveedores actualizadas: `POST /suppliers/store`, `GET /suppliers/show/{id}`,
  `POST /suppliers/update/{id}`, `POST /suppliers/check-nombre`; eliminadas rutas de `create` y `edit` como páginas
- `views/suppliers/index.php` — reescrito con patrón modal + AJAX: DataTable `#supplierTable` + `#modalCreate` (
  bg-primary, modal-lg) + `#modalEdit` (bg-success, modal-lg); botones `btn-edit` y `btn-delete` con `data-id`/
  `data-nombre`; eliminados formulario oculto `#formEliminar` y script inline
- `views/layouts/partials/_sidebar.php` — enlace de proveedores simplificado a link directo; eliminado treeview con
  sub-ítems "Lista de proveedores" / "Crear proveedor"

### Agregado

- `public/js/modules/suppliers/suppliers-datatable.js` — inicialización DataTable con botones de exportación (Copy, PDF
  personalizado, Excel, CSV, Imprimir), ColVis, anti-FOUC y `exportOptions` que excluye la columna Acciones
- `public/js/modules/suppliers/suppliers-modals.js` — jQuery Validate con regla `remote` en `empresa` para ambos
  formularios; `crearProveedor()` / `actualizarProveedor()` con `ToastUtils.loadingWithMinTime()`; carga de datos para
  modal de edición via AJAX (`GET /suppliers/show/{id}`); eliminación con `AlertUtils.confirm()` + AJAX +
  `isReferenced()` inline; flag `isSubmitting` para prevenir doble submit

### Eliminado

- `views/suppliers/create.php` — reemplazado por `#modalCreate` en `index.php`
- `views/suppliers/edit.php` — reemplazado por `#modalEdit` en `index.php`

### Notas de Versión

- **Flujo de eliminación de proveedores:** Cambiado del patrón formulario oculto + `Swal.fire()` directo a
  `AlertUtils.confirm()` + AJAX inline. El controller verifica `isReferenced()` y retorna JSON — sin página de
  confirmación separada ni ruta `check/{id}`.
- **Validación de unicidad:** Campo `empresa` validado con `remote` en jQuery Validate + `nameExists()` en el backend.
  `nombre_proveedor` no se valida como único (puede repetirse legítimamente entre distintos contactos de distintas
  empresas).
- **Campos opcionales:** `telefono` y `email` se insertan como `NULL` cuando se dejan vacíos; el JS al pre-llenar el
  modal usa `data.telefono || ''` para evitar mostrar "null" en los inputs.

---

## [1.2.1] - 2026-04-03

### Refactorizado

- `views/products/index.php` — JS extraído a `products-index.js`; eliminado wrapper `table-responsive`;
  `confirmarEliminar()` reemplazado por verificación AJAX antes de redirigir a página de confirmación; eliminado
  formulario oculto `#formEliminar` inline
- `views/products/create.php` — JS de validación e imagen-preview extraído a `products-create.js`
- `views/products/edit.php` — JS de validación extraído a `products-edit.js`
- `app/Controllers/ProductController.php` — `create()` y `edit()` ahora pasan `['select2', 'validation']` al sistema de
  assets; `index()` ya no pasa `csrf_token` (innecesario sin formulario inline)
- `views/users/index.php`, `create.php`, `edit.php` — eliminados `<script src>` hardcodeados; scripts servidos vía
  `$pageScripts`
- `app/Controllers/UserController.php` — `index()`, `create()` y `edit()` pasan `pageScripts` con sus respectivos
  módulos JS

### Agregado

- `public/js/modules/products/products-index.js` — DataTable init + `confirmarEliminar(id, nombre)` con verificación
  AJAX vía `GET /products/check/{id}` antes de redirigir a página de confirmación; bloqueo de botones durante la
  verificación
- `public/js/modules/products/products-create.js` — jQuery Validate para formulario de creación con preview de imagen
- `public/js/modules/products/products-edit.js` — jQuery Validate para formulario de edición
- `views/products/delete.php` — página de confirmación de eliminación con datos del producto (código, nombre, categoría,
  imagen) y SweetAlert2 para confirmación final
- `app/Controllers/ProductController.php::check()` — endpoint JSON `GET /products/check/{id}`; responde
  `{ referenced, carrito, compras }`; devuelve 400/404 en entradas inválidas
- `app/Controllers/ProductController.php::delete()` — renderiza la vista de confirmación de eliminación con datos del
  producto y su categoría
- `app/Models/Product.php::getReferenceCount()` — consulta desglosada de referencias (`carrito`, `compras`) para exponer
  conteos individuales al frontend
- `routes/web.php` — `GET /products/check/{id}`, `GET /products/delete/{id}`
- `views/layouts/header.php` — soporte de `$pageStyles` (array de rutas relativas a `BASE_URL`) para inyección de CSS
  específico por vista
- `views/layouts/footer.php` — soporte de `$pageScripts` (array de rutas relativas a `BASE_URL`) para inyección de JS
  específico por vista

### Notas de Versión

- **Flujo de eliminación de productos:** Cambiado del patrón modal-inline a página dedicada con pre-verificación AJAX.
  El botón Eliminar primero consulta `/products/check/{id}`; si el producto no tiene referencias lo redirige a
  `/products/delete/{id}` (página de confirmación); si tiene referencias muestra detalle de los registros bloqueantes
  vía SweetAlert2.
- **Sistema de assets por vista:** `$pageStyles` / `$pageScripts` permiten cargar CSS/JS específicos de módulo sin
  modificar el layout global. Compatible con todos los módulos MVC.

---

## [1.2.0] - 2026-04-03

### Agregado

- `CONTRIBUTING.md` — guía completa para colaboradores: flujo de contribución, convenciones de commit, estructura de
  archivos MVC, checklist de testing
- `LICENSE` — licencia MIT para uso público y open-source
- `.gitignore` — actualizado con reglas para `.claude/` (ignorar config local) y `.mcp.json` (ignorar config local);
  excepción para `.mcp.example.json` (template para equipo)
- `.claudeignore` — configuración de exclusión para contexto de Claude (vendor/, public/templates/, public/uploads/)
- `.mcp.example.json` — plantilla de configuración MCP con servidores GitHub, MySQL y ClickUp (para equipo)
- `.claude/skills/code-review/` — skill automático para revisar código PHP MVC antes de merge (checklist security,
  conventions, logic, git)
- `.claude/skills/git-commit/` — skill automático para commits con Conventional Commits, análisis de diff e inteligencia
  de scope/type

### Cambiado

- `PROMPTS.md` — actualizado con referencias claras a AGENT.md y CLAUDE.md como contexto base; plantillas refactorizadas
  con ejemplos más realistas (reportes, features genéricas); énfasis en "Spec first" approach
- `README.md` — reorganizado con tabla de documentación para desarrolladores (AGENT.md, CLAUDE.md, PROMPTS.md,
  CONTRIBUTING.md); sección de contribuciones mejorada con pasos explícitos; footer actualizado con código de conducta

### Notas de Versión

- **Primera release open-source estable:** Documentación completa, skills de desarrollo, configuración de MCP lista,
  licencia MIT
- **Infraestructura de colaboración:** A partir de v1.2.0, el proyecto está listo para recibir contribuciones externas
- Todo el código MVC es v1.1.8 — esta versión agrega capas de documentación y tooling

---

## [1.1.8] - 2026-04-03

### Cambiado

- `app/Models/Category.php` — método `nameExists(string $name, ?int $excludeId = null): bool` para detección de nombres
  de categoría duplicados (excluye el registro actual en ediciones)
- `app/Controllers/CategoryController.php` — refactorizado a AJAX puro: `index()` renderiza la página; `store()`,
  `show()`, `update()` responden JSON; `checkNombre()` endpoint para jQuery Validate `remote`; eliminados `create()` y
  `edit()` como páginas separadas
- `routes/web.php` — rutas de categorías actualizadas: `POST /categories/store`, `GET /categories/show/{id}`,
  `POST /categories/update/{id}`, `POST /categories/check-nombre`; eliminadas rutas de `create` y `edit` como páginas
- `views/categories/index.php` — reescrito con patrón modal + AJAX: DataTable `#categoryTable` + `#modalCreate` (
  bg-primary) + `#modalEdit` (bg-success); sin páginas separadas de creación y edición
- `views/layouts/partials/_sidebar.php` — enlace de categorías simplificado a link directo; eliminado treeview con
  sub-ítems "Lista" / "Crear"

### Agregado

- `public/js/modules/categories/categories-datatable.js` — inicialización DataTable con botones de exportación (Copy,
  PDF, Excel, CSV, Imprimir), ColVis y anti-FOUC
- `public/js/modules/categories/categories-modals.js` — jQuery Validate con regla `remote` en ambos formularios;
  `crearCategoria()` / `actualizarCategoria()` con `ToastUtils.loadingWithMinTime()`; carga de datos para modal de
  edición via AJAX; flag `isSubmitting` para prevenir doble submit

### Eliminado

- `views/categories/create.php` — reemplazado por `#modalCreate` en `index.php`
- `views/categories/edit.php` — reemplazado por `#modalEdit` en `index.php`

---

## [1.1.7] - 2026-04-02

### Cambiado

- `app/Models/Role.php` — método `nameExists(string $name, ?int $excludeId = null): bool` para detección de nombres
  duplicados (excluye el registro actual en ediciones)
- `app/Controllers/RoleController.php` — refactorizado a AJAX puro: `index()` renderiza la página; `store()`, `show()`,
  `update()` responden JSON; `checkNombre()` endpoint para jQuery Validate `remote`
- `routes/web.php` — rutas de roles actualizadas: `POST /roles/store`, `GET /roles/show/{id}`,
  `POST /roles/update/{id}`, `POST /roles/check-nombre`; eliminadas rutas de `create` y `edit` como páginas
- `views/roles/index.php` — reescrito con patrón modal + AJAX: DataTable `#roleTable` + `#modalCreate` (bg-primary) +
  `#modalEdit` (bg-success); sin páginas separadas de creación y edición
- `views/layouts/partials/_sidebar.php` — enlace de roles simplificado a link directo; eliminado treeview con
  sub-ítems "Lista de roles" / "Crear rol"

### Agregado

- `public/js/modules/roles/roles-datatable.js` — inicialización DataTable con botones de exportación (Copy, PDF
  personalizado, Excel, CSV, Imprimir), colvis y anti-FOUC
- `public/js/modules/roles/roles-modals.js` — jQuery Validate con regla `remote` en ambos formularios; `crearRol()` /
  `actualizarRol()` con `ToastUtils.loadingWithMinTime()`; carga de datos para modal de edición via AJAX; flag
  `isSubmitting` para prevenir doble submit

### Eliminado

- `views/roles/create.php` — reemplazado por `#modalCreate` en `index.php`
- `views/roles/edit.php` — reemplazado por `#modalEdit` en `index.php`

---

## [1.1.6] - 2026-04-02

### Cambiado

- `renderWithLayout()` en `app/Core/Controller.php` — nuevo 4° parámetro `array $assets = []`; los plugins (`datatable`,
  `select2`, `validation`) solo se cargan en las páginas que los declaran explícitamente
- `views/layouts/header.php` — CSS de DataTables y Select2 envueltos en bloques `in_array()` condicionales
- `views/layouts/footer.php` — JS de DataTables (11 archivos), Select2 y jQuery Validate envueltos en bloques
  `in_array()` condicionales
- Controllers actualizados con `$assets` apropiados: `['datatable']` en todos los `index()`; `['select2', 'validation']`
  en `UserController::create()` y `edit()`; `['datatable']` en `SaleController::create()` por las tablas del modal POS

---

## [1.1.5] - 2026-04-01

### Cambiado

- Directorio `views/layout/` renombrado a `views/layouts/` — convención plural consistente con frameworks PHP modernos
- `views/layout/parte1.php` → `views/layouts/header.php`
- `views/layout/parte2.php` → `views/layouts/footer.php`
- `views/layout/mensajes.php` → `views/layouts/messages.php`
- Sidebar extraído de `header.php` a `views/layouts/partials/_sidebar.php` — partial independiente incluido desde
  `header.php`
- `app/Core/Controller.php` — 3 rutas de `require` actualizadas a `views/layouts/`
- `CLAUDE.md` y `AGENT.md` — referencias a archivos de layout actualizadas

### Eliminado

- `views/layout/sesion.php` — código muerto; la sesión se gestiona enteramente via `Auth` + `sessionData()`

---

## [1.1.4] - 2026-04-01

### Agregado

- `UserController::checkEmail()` en `app/Controllers/UserController.php` — endpoint `POST /users/check-email` que
  responde `true` (email libre) o string de error (ya registrado); utilizado por jquery.validate `remote` en create y
  edit
- Ruta `POST /users/check-email` en `routes/web.php` con middleware `auth, admin`
- Validación AJAX de email en tiempo real en `users-create.js` — regla `remote` que consulta `/users/check-email`;
  impide submit si el correo ya existe en el sistema sin necesidad de reload
- Validación AJAX de email en tiempo real en `users-edit.js` — igual que create pero envía `id_usuario` para excluir al
  usuario actual de la comprobación de duplicados
- Card "Vista previa" en sidebar de `views/users/create.php` — muestra nombre, email y rol actualizándose en tiempo real
  mientras el usuario completa el formulario (via listeners JS en `users-create.js`)
- Loading toast "Redirigiendo..." en `users-index.js` — aparece tras confirmar que el usuario no tiene referencias,
  antes de navegar a la vista de eliminación

### Cambiado

- `views/users/create.php` — formulario dividido en dos cards colapsables `card-primary card-outline` ("Datos del
  usuario" y "Credenciales de acceso"); `id="btnCreateUser"` en botón submit para control de spinner
- `views/users/edit.php` — formulario dividido en dos cards colapsables `card-success card-outline` ("Información de la
  cuenta" y "Seguridad"); `id="id_usuario"` agregado al input hidden para que el validador remote lo referencie;
  `id="btnEditUser"` en botón submit
- `users-index.js` — `confirmarEliminar()` ahora usa `ToastUtils.loadingWithMinTime('Verificando usuario...')` durante
  el AJAX y deshabilita todos los botones de eliminar mientras la verificación está en curso

---

## [1.1.3] - 2026-03-31

### Agregado

- `public/js/modules/users/users-create.js` — jQuery Validate para el formulario de creación: nombres (requerido, mín. 3
  chars), email (requerido, formato), rol (requerido), contraseña (requerida, mín. 6 chars), confirmación con `equalTo`;
  toggle de visibilidad de contraseña; spinner en submit con `ToastUtils.loadingWithMinTime()`
- `public/js/modules/users/users-edit.js` — jQuery Validate para el formulario de edición: mismas reglas que create pero
  contraseña opcional (si se llena, mín. 6 chars y repeat debe coincidir); spinner con "Actualizando..."
- `User::isReferenced(int $id)` en `app/Models/User.php` — verifica si el usuario tiene productos (`tb_almacen`) o
  compras (`tb_compras`) asociadas antes de permitir la eliminación
- `User::getReferenceCount(int $id)` en `app/Models/User.php` — devuelve desglose `{productos, compras}` para mostrar en
  el mensaje de error
- `UserController::check()` en `app/Controllers/UserController.php` — endpoint JSON `GET /users/check/{id}` que responde
  `{referenced, productos, compras}`; usado por AJAX antes de redirigir a la página de eliminación
- Ruta `GET /users/check/{id}` en `routes/web.php` con middleware `auth, admin`
- Constante JS `BASE_URL` en `views/layout/parte1.php` — disponible globalmente en todos los módulos JS que necesiten
  construir URLs dinámicas

### Cambiado

- `views/users/index.php` — botón Eliminar ahora hace verificación AJAX (`/users/check/{id}`) antes de redirigir; si el
  usuario tiene referencias muestra alerta de error con detalle de productos/compras; si está libre redirige a
  `/users/delete/{id}`
- `views/users/create.php` — reestructurado a un solo `card-primary`; campos con `input-group` e íconos FontAwesome;
  `autocomplete="off"`; botones toggle de contraseña; carga `users-create.js`
- `views/users/edit.php` — eliminado el bloque `widget-user-2` (header con fondo verde e imagen); reemplazado por
  `card-success` simple con badge de ID (patrón estándar del sistema); un solo card unificado datos+contraseña; carga
  `users-edit.js`
- `views/users/delete.php` — botón "Eliminar usuario" ahora abre SweetAlert2 de confirmación con nombre del usuario
  antes de hacer submit; corregido `$URL` → `BASE_URL` en todos los enlaces y acciones
- `views/users/show.php` — agregado `container-fluid` faltante que causaba desbordamiento del layout

### Corregido

- `views/users/delete.php` — variable `$URL` indefinida reemplazada por constante `BASE_URL`; enlace de inicio apuntaba
  a `/dashboard` en lugar de `/`
- `views/users/index.php` — `json_encode()` en `onclick` generaba comillas dobles dentro de atributo HTML con comillas
  dobles, rompiendo el JS; corregido con `htmlspecialchars(..., ENT_QUOTES)`

---

## [1.1.2] - 2026-03-31

### Agregado

- `public/css/core/ui-components.css` y `public/js/core/sweetalert-utils.js` — utilitarios compartidos con
  `sistema-hielo-cambita`; `sweetalert-utils.js` provee `ToastUtils`, `AlertUtils` y funciones legacy (`showToast()`,
  `confirmDelete()`, etc.); se carga en `<head>` (parte1) para que `showToast()` esté disponible antes de que ejecute
  `mensajes.php`
- `public/js/modules/users/users-index.js` — DataTable y `confirmarEliminar()` del módulo usuarios extraídos de JS
  inline en la vista
- `public/css/modules/auth/login.css` — estilos personalizados del login: gradiente de fondo, card con hover/sombra,
  inputs redondeados, `btn-custom` azul
- `public/js/modules/auth/login.js` — toggle de visibilidad de contraseña, jQuery Validate con mensajes en español,
  spinner de submit con `ToastUtils.loadingWithMinTime()`
- `define('BASE_PATH', dirname(__DIR__))` en `public/index.php` — constante de ruta absoluta para referencias internas
- `$_SESSION['welcome_user']` en `AuthController::store()` — activa el popup de bienvenida al llegar al dashboard tras
  login exitoso

### Cambiado

- `public/index.php` — inicialización bootstrappeada inline (antes delegada a `app/config.php`); agrega `BASE_PATH`
- `views/layout/mensajes.php` — simplificado con `showToast()` de `sweetalert-utils.js`; agrega manejo de
  `$_SESSION['welcome_user']` con `AlertUtils.welcome()`
- `views/layout/parte1.php` — agrega `ui-components.css` y `sweetalert-utils.js` en `<head>`
- `views/layout/parte2.php` — ruta de `control_sidebar.js` actualizada a `js/core/control_sidebar.js`
- `views/auth/login.php` — rediseño con patrón de `sistema-hielo-cambita`: logo local, toggle contraseña, jQuery
  Validate, `btn-custom`, toast via `showToast()`; sin JS inline
- `views/users/index.php` — JS inline extraído a `users-index.js`
- `views/users/create.php` — CSRF al inicio del form; formulario dividido en dos tarjetas ("Información de la cuenta"
  y "Seguridad")

### Eliminado

- `app/config.php` — lógica inlineada en `public/index.php`
- `app/config.example.php` — archivo legacy de credenciales pre-MVC
- `public/js/control_sidebar.js` — movido a `public/js/core/control_sidebar.js`

---

## [1.1.1] - 2026-03-30

### Agregado

- Vistas de error dedicadas en `views/errors/`: `404.php` (headline amarillo), `403.php` (headline rojo + SweetAlert2
  con flash message), `500.php` (headline rojo) — páginas standalone con contenido completamente centrado, sin header ni
  footer
- Ruta `GET /errors/403` en `routes/web.php` como closure sin middleware para servir la página 403

### Corregido

- Redirect 403 de `AdminMiddleware` y `SellerMiddleware` apuntaba a `APP_URL . '/error/error.php'` — URL inválida porque
  `APP_URL` termina en `/public` y el archivo estaba fuera de ese directorio; corregido a `APP_URL . '/errors/403'`
- `Router::dispatch()` ahora incluye `views/errors/404.php` en lugar del archivo legacy `error/error.php`

### Cambiado

- TCPDF gestionado vía Composer (`tecnickcom/tcpdf ^6.7`, instalado como `6.11.2`) en lugar de la copia manual en
  `app/TCPDF-main/`; eliminada la línea `require_once` en `SaleController::invoice()`

### Eliminado

- Directorio `error/` con el archivo legacy `error/error.php`
- Directorio `app/TCPDF-main/` reemplazado por `vendor/tecnickcom/tcpdf/`

---

## [1.1.0] - 2026-03-30

### Agregado

- Composer con PSR-4 autoloading y `vlucas/phpdotenv ^5.6`
- Credenciales movidas a `.env` (fuera del control de versiones); `.env.example` como plantilla
- Clases Core MVC: `App\Core\Database` (singleton PDO con `getConnection()`), `App\Core\Router`, `App\Core\Controller`,
  `App\Core\Model` (base abstracta), `App\Core\Config` (wrapper .env), `App\Core\Middleware` (interfaz)
- `App\Core\Auth` para centralizar sesión, usuario actual, login/logout y CSRF
- `app/Middleware/` con middlewares namespaced PSR-4: `AuthMiddleware`, `GuestMiddleware`, `AdminMiddleware`,
  `SellerMiddleware` (permite `Administrador` y `Vendedor`; registrado como `'seller'` en Router)
- Modelo `App\Models\User` (hereda de `App\Core\Model`) con métodos de autenticación y CRUD de usuarios
- Entry point `public/index.php` con Router; `.htaccess` en raíz para soporte de rutas; `routes/web.php` para registro
  de rutas
- `App\Controllers\AuthController` consolida login y logout; directorio `auth/` reemplaza `login/`
- `App\Controllers\UserController` con CRUD completo del módulo users
- `App\Controllers\DashboardController` — ruta `GET /` con conteos de todos los módulos
- Nuevas vistas MVC en `views/auth/login.php`, `views/users/` (index, create, edit, show, delete),
  `views/dashboard/index.php`
- `Controller::renderWithLayout()` para renderizar vistas envueltas en `parte1`/`mensajes`/`parte2` desde el controlador
- Constante `BASE_URL` definida en `app/config.php` — disponible globalmente sin necesidad de pasar como variable
- Modelo `App\Models\Role` — `$table = 'tb_roles'`; CRUD heredado; sin delete por ser datos de sistema
- `App\Controllers\RoleController` (index, create, store, edit, update); vistas `views/roles/` con DataTables y badge de
  total
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
- Modelo `App\Models\Product` — `$table = 'tb_almacen'`; `allWithCategories()` con JOIN; `nextCode()` genera código
  `P-XXXXX`; `isReferenced()` verifica `tb_carrito` y `tb_compras`
- `App\Controllers\ProductController` con CRUD completo más `show()`; `handleImageUpload()` con validación MIME,
  extensión whitelist y límite 2MB
- Vistas `views/products/` con alerta visual de stock por colores; vista `show.php` con auditoría
- Rutas `/products` en `routes/web.php` con middleware `auth`
- Directorio `public/uploads/products/` para imágenes; `producto_default.png` y `.gitkeep` trackeados; resto ignorado en
  `.gitignore`
- Modelo `App\Models\Purchase` — `$table = 'tb_compras'`; `storeWithStock()`, `updateWithStock()`, `destroyWithStock()`
  transaccionales; `allWithDetails()`, `findWithDetails()`, `nextNumber()`
- `App\Controllers\PurchaseController` con CRUD completo; `id_usuario` de `Auth::user()` — nunca del POST
- Vistas `views/purchases/` con campos hidden `old_id_producto`/`old_cantidad` para ajuste de stock en edición
- Rutas `/purchases` en `routes/web.php` con middleware `auth`
- Helper estático `App\Helpers\NumberToWords::convert(float)` — convierte número a palabras en español para facturas PDF
- Modelo `App\Models\Sale` — `$table = 'tb_ventas'`; `allWithDetails()`, `findWithDetails()`, `nextNumber()` con
  `MAX()+1`; `storeWithStock()` y `destroyWithStock()` transaccionales (DELETE `tb_ventas` antes que `tb_carrito` por
  FK)
- Modelo `App\Models\CartItem` — `$table = 'tb_carrito'`; `addItem()` con validación de stock, `getByNroVenta()`,
  `removeItem()`, `countByNroVenta()`
- `App\Controllers\SaleController` con 9 métodos: `index`, `create`, `addToCart`, `removeFromCart`, `store`, `show`,
  `confirmDelete`, `invoice` (TCPDF inline), `destroy`
- Vistas `views/sales/`: `index.php` (DataTables), `create.php` (POS: carrito + modales de producto y cliente + panel
  pago), `show.php`, `delete.php` (confirmación SweetAlert2)
- Rutas `/sales` en `routes/web.php` con middleware `['auth', 'seller']`

### Cambiado

- `APP_URL` en `.env` ahora incluye `/public` (`http://localhost/Sistema_de_Ventas_PHP/public`) — todas las rutas MVC
  apuntan al front controller
- `app/config.php`: agrega `define('BASE_URL', ...)` y mantiene `$URL = BASE_URL` como alias backward-compat para
  módulos legacy
- `App\Core\Model` ampliado con CRUD completo: `all/create/update/count/query/isReferenced` y aliases de compatibilidad
  `findAll/insert`; propiedad canonical `$db` (con `$pdo` como alias)
- `App\Models\User` actualizado para usar `$this->db` (propiedad canonical de Model)
- `App\Controllers\AuthController` usa `BASE_URL` directamente; redirige a `BASE_URL . '/'` tras login exitoso
- `App\Controllers\UserController` refactorizado: helper `sessionData()` centraliza variables de sesión para todas las
  vistas; todos los métodos usan `renderWithLayout()`
- `login/index.php` reemplazado por redirect a `/auth/` (backward compat)
- Root `index.php` reemplazado por redirect stub a `BASE_URL . '/'`
- Rutas de users depuradas para usar endpoints canónicos sin duplicados
- `App\Core\Router` actualizado con middlewares por ruta y soporte de parámetros dinámicos (`/users/edit/{id}`)
- `DashboardController` reemplaza todos los `require_once listado_de_*.php` por llamadas a `Model::count()` (roles,
  categories, suppliers, clients, products, purchases, sales)
- Sidebar `views/layout/parte1.php` actualizado con bloques MVC para todos los módulos migrados
- Dashboard `views/dashboard/index.php` actualiza links a rutas MVC (`/sales`, `/sales/create`, etc.)
- `.gitignore` corregido para trackear solo `producto_default.png` y `.gitkeep` en `public/uploads/products/`

### Corregido

- `$(document).ready()` en DataTables init de `views/users/index.php` — prevenía `DataTable is not a function` al
  ejecutar el script antes de que `parte2.php` cargara la librería
- Atributos `autocomplete` añadidos en formularios de usuarios (edit: `name`, `email`, `new-password`; show: `off`) —
  elimina error `autofillFieldData.autoCompleteType is null` del browser

### Eliminado

- Vistas legacy del módulo `usuarios/` y controladores `app/controllers/usuarios/` reemplazados por `views/users/` y
  `UserController`
- Vistas legacy `roles/`, `categorias/`, `proveedores/`, `clientes/` y sus controladores en `app/controllers/`
- Vistas legacy `almacen/` y controladores `app/controllers/almacen/`
- Vistas legacy `compras/` y controladores `app/controllers/compras/`
- Vistas legacy `ventas/` y controladores `app/controllers/ventas/` (incluido `literal.php`)
- Directorio `app/controllers/` completo — incluyendo `middleware/AuthMiddleware.php` legacy; no queda ningún archivo
  fuera de la arquitectura MVC

## [1.0.0] - 2026-03-24

### Agregado

- `database/schema.sql` con la estructura de todas las tablas
- `database/seeder.sql` con datos iniciales para todas las tablas: roles, categorías, proveedores, clientes, usuarios,
  productos, compras y ventas de ejemplo
- Usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`,
  `comprador@sistema.com` / `comprador123`
- `UNIQUE KEY` en `tb_usuarios.email` para evitar emails duplicados
- `UNIQUE KEY` en `tb_almacen.codigo` para evitar códigos de producto duplicados
- `app/config.example.php` como plantilla de configuración sin credenciales
- **Protección CSRF**: Generación global de token y validación estricta para todos los endpoints POST de mutación.
- **Validación Fuerte del Servidor**: Filtros integrados (`is_numeric`, `filter_var`) en controladores críticos de
  usuarios, almacén y ventas.

### Cambiado

- **Refactorización de Verbos HTTP**: Las mutaciones lógicas como la creación de ventas ahora requieren estrictamente
  `POST` mitigando vulnerabilidades.
- **Transacciones PDO Íntegras**: El carrito y la cabecera de la venta se procesan bajo un único bloque
  `$pdo->beginTransaction()` garantizando consistencia del stock.
- `fyh_creacion` ahora tiene `DEFAULT CURRENT_TIMESTAMP` en todas las tablas — ya no es necesario insertarla manualmente
- `fyh_actualizacion` ahora es `DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP` en todas las tablas — queda NULL al crear y se
  actualiza automáticamente al modificar
- `tb_almacen.precio_compra` y `tb_almacen.precio_venta`: `VARCHAR(255)` → `DECIMAL(10,2)`
- `tb_compras.precio_compra`: `VARCHAR(50)` → `DECIMAL(10,2)`
- `tb_ventas.total_pagado`: `INT(11)` → `DECIMAL(10,2)` (corrige pérdida de decimales)
- `tb_usuarios.token`: `NOT NULL` → `DEFAULT NULL`
- `tb_proveedores.email`: `VARCHAR(50)` → `VARCHAR(254)` (estándar RFC 5321)
- `AUTO_INCREMENT` reseteado a 1 en todas las tablas
- `app/config.php` excluido del repositorio vía `.gitignore`

### Corregido

- Advertencias en consola del navegador reparadas al forzar `autocomplete="off"` en los formularios de
  `login/index.php`.
- Estandarización de `layout/mensajes.php` para usar Toasts globales de SweetAlert2 en lugar del modal intrusivo.
- Campos de contraseña corregidos de `type="text"` a `type="password"` en `usuarios/create.php` y
  `usuarios/update.php` — la contraseña ya no se muestra en texto plano
- `htmlspecialchars()` aplicado en todas las vistas donde se muestran datos de usuarios/BD en HTML: `almacen/index.php`,
  `almacen/show.php`, `almacen/update.php`, `ventas/index.php`, `compras/index.php`, `proveedores/index.php` y
  `usuarios/update.php` — previene XSS almacenado
- Confirmaciones SweetAlert2 agregadas antes de eliminar registros en `almacen/index.php`, `ventas/index.php`,
  `compras/index.php` y `usuarios/index.php` — los botones de eliminar ya no navegan directamente sin confirmación
- Typo `lamppstart` → `lampp start` en README.md y CLAUDE.md
- URL de ejemplo incorrecta en CLAUDE.md (`sistemaventas` → `Sistema_de_Ventas_PHP`)
- SQL injection en `layout/sesion.php`, `app/controllers/login/ingreso.php`, `app/controllers/roles/update_roles.php`,
  `app/controllers/clientes/cargar_cliente.php`, `ventas/factura.php`, `ventas/show.php`, `ventas/index.php`,
  `ventas/delete.php` y `ventas/create.php` — se reemplazó interpolación directa de variables en SQL por placeholders
  `?` con `execute([$var])`
- Subida de imágenes sin validación en `app/controllers/almacen/create.php` y `update.php` — se agregó whitelist de
  extensiones (jpg, jpeg, png, webp), validación de MIME type real y límite de 2MB
- Variables PHP interpoladas directamente en bloques JavaScript en `ventas/create.php` — se reemplazó por
  `json_encode()` para prevenir errores con caracteres especiales
- **Control Sidebar:** Funcionalidad original manual reemplazada por el archivo `public/js/control_sidebar.js` 100%
  nativo de la API de AdminLTE, traducido al español, con guardado persistente en `localStorage`.
- **FOUC (Flash of Unstyled Content):** Parpadeos visuales al navegar con temas oscuros prevenidos mediante una pequeña
  inyección JS al inicio del `<body>` en `layout/parte1.php`.
- Reposicionado de la barra lateral de configuración a `position: fixed` para evitar pérdida visual al hacer scroll
  excesivo.
