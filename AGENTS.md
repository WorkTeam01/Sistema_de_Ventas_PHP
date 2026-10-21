# AGENTS.md — Sistema de Ventas PHP

> System prompt persistente para agentes de IA y sesiones de desarrollo asistido.
> Compatible con: Claude Code · Cursor (.cursorrules) · Claude.ai (pegar al inicio) · Copilot (workspace instructions)
> Fuente única de convenciones/arquitectura/setup. `CLAUDE.md` solo la importa.

---

## Proyecto

Sistema de gestión de ventas con control de inventario, facturación, gestión de clientes y acceso por roles.
Permite registrar ventas, compras a proveedores, gestionar el almacén y emitir facturas en PDF.

**Estado actual:** 1.20.0 — incluye adaptación inicial para ferretería en Chile: unidades de medida, historial de movimientos de stock, cotizaciones y órdenes de compra internas, exportaciones y guía de operación LAN. Los documentos tributarios siguen siendo internos; no hay integración con el SII. Historial completo en [CHANGELOG.md](CHANGELOG.md); despliegue en [docs/guides/despliegue.md](docs/guides/despliegue.md).

---

## Stack Tecnológico

- **Backend:** PHP 8.2+ (sin framework — MVC custom con PSR-4 via Composer)
- **Frontend:** AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2
- **Base de datos:** MySQL / MariaDB (PDO)
- **PDF:** TCPDF (`tecnickcom/tcpdf` vía Composer)
- **Email:** PHPMailer (`phpmailer/phpmailer ^7.0` vía Composer) — SMTP Gmail con App Password
- **Control de versiones:** Git + GitHub (`WorkTeam01/Sistema_de_Ventas_PHP`)

---

## Documentación

`AGENTS.md` es la fuente operativa para el código; el detalle largo vive en `docs/` y se enlaza desde aquí (guías en `docs/guides/`, capa SDD en `docs/`):

| Documento                                                                                    | Contenido                                                                        |
| -------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| [docs/guides/arquitectura.md](docs/guides/arquitectura.md)                                   | Árbol completo de archivos y enrutamiento                                        |
| [docs/schema.md](docs/schema.md)                                                             | Tablas, campos, claves y relaciones (derivado de `database/`)                    |
| [docs/guides/autenticacion.md](docs/guides/autenticacion.md)                                 | RBAC, slugs de permisos, sesión, "recordarme", restablecimiento, rate limiting   |
| [docs/guides/frontend.md](docs/guides/frontend.md)                                           | JavaScript, vistas, layouts, accesibilidad, lecciones de Bootstrap/DataTables    |
| [docs/guides/testing.md](docs/guides/testing.md)                                             | Suites, tests contra MariaDB, singleton de BD, CI                                |
| [docs/guides/desarrollo.md](docs/guides/desarrollo.md)                                       | Puesta en marcha local, BD, `.env`, permisos de archivos                         |
| [docs/guides/despliegue.md](docs/guides/despliegue.md)                                       | Despliegue en producción                                                         |
| [docs/app-flow.md](docs/app-flow.md) · [docs/ux-brief.md](docs/ux-brief.md)                  | Recorridos de usuario y criterios de UX (derivados de las specs con `/sdd:docs`) |
| [docs/constitution.md](docs/constitution.md) · [docs/roadmap.md](docs/roadmap.md) · `specs/` | Principios, estado y especificaciones (SDD)                                      |

---

## Arquitectura

```
app/Core/         Router, Auth, Controller, Model, Database, Config, Middleware (base del MVC)
app/Controllers/  un controlador por módulo (PSR-4 App\Controllers)
app/Models/       lógica de negocio y acceso a datos con PDO (fat model)
app/Helpers/      Money, InvoicePdf, ReportPdf, ImageUpload, AuthSettings, ReportFilters…
app/Services/     EmailService (PHPMailer)
app/Middleware/   AuthMiddleware, GuestMiddleware, PermissionMiddleware (`can:permiso`)
views/            layouts/, partials y una carpeta por módulo
routes/web.php    todas las rutas: método + ruta + controller + middleware (fuente de verdad)
public/           index.php (front controller), .htaccess, css/ js/ por core, modules, lib, plugins
database/         schema.sql, seeder.sql (desarrollo), seeder_produccion.sql (solo catálogo), migrations/
```

Todo pasa por `public/index.php` → `Router` → middleware → controller → modelo. `{id}` solo admite dígitos (otro valor responde 404); `{token}` admite alfanuméricos, `-` y `_`. Si cambias el catálogo en `seeder.sql`, replícalo en `seeder_produccion.sql`. Árbol completo con el rol de cada archivo: [docs/guides/arquitectura.md](docs/guides/arquitectura.md).

---

## Base de Datos

`Database::connect()` fija `time_zone` de la sesión al desplazamiento UTC de `APP_TIMEZONE` (`+HH:MM`, no el nombre: no exige tablas de zonas en el servidor), así `NOW()`/`CURDATE()`/`fyh_creacion` usan el mismo reloj que `date()` de PHP. Al desplegar en un servidor MySQL cuya zona difiera de `APP_TIMEZONE`, las filas anteriores quedaron guardadas en la hora del servidor y se leerán desplazadas.

Estructura completa de tablas, campos, claves y relaciones: [docs/schema.md](docs/schema.md) (vista derivada de `database/schema.sql` y `database/migrations/`; se regenera con `/sdd:docs schema`).

Invariantes que el esquema no deja ver:

- `tb_roles.permisos_version` se incrementa en `Role::syncPermissions()`; `Auth::check()` la compara con `$_SESSION['permisos_version']` para invalidar la caché de permisos.
- `tb_ventas.total_pagado` se calcula en el servidor como `SUM(cantidad * precio_unitario)` de `tb_carrito`, nunca del POST; `tb_carrito.precio_unitario` es NULL mientras el carrito está en curso. La venta neta de un período es ventas menos devoluciones imputadas a ese período.
- `tb_movimientos_inventario` es el libro append-only de stock; las correcciones posteriores agregan movimientos compensatorios con usuario y fecha, nunca reescriben los anteriores. La migración 017 estima el saldo de apertura con los registros disponibles.
- `tb_almacen.unidad_medida` permite identificar la unidad del producto; en esta entrega las cantidades se mantienen enteras. `tb_documentos_comerciales` guarda cotizaciones y órdenes de compra internas, sin modificar stock; no emite DTE ni se conecta al SII.
- `tb_ventas.id_cliente` es opcional desde 1.18.1: las lecturas usan `LEFT JOIN` y las salidas (listado, detalle, delete, dashboard, factura PDF, activity log, exports) muestran "Consumidor final"/"—". `id_usuario` es NULL en ventas anteriores a 1.12.1.
- `tb_pagos`: una línea por método por venta; el vuelto nunca se persiste, se deriva con `SalePayment::vueltoFor()`. Solo los métodos `efectivo` admiten exceso.
- `tb_pos_reservas`: `id_reserva` ES el `nro_venta` del POS; las filas nunca se borran (la secuencia no debe retroceder). Tras volver temporalmente a 1.18.3 (numera con MAX+1 e ignora esta tabla) hay que reejecutar la migración 011.
- `tb_usuarios`: `email` se guarda en minúsculas; `reset_token` guarda el hash SHA-256. Las columnas `login_intentos`, `login_bloqueado_hasta`, `remember_token` y `remember_token_expiry` están obsoletas (sin uso desde la migración 013; se conservan para poder volver a 1.18.3).
- `tb_activity_log.usuario_nombre` y `tb_ajustes_stock.usuario_nombre` están desnormalizados para persistir si el usuario es eliminado. Acciones registradas: `create`, `update`, `delete`, `price_change`, `role_change`, `permission_change`, `stock_adjustment`, `export`, `login`, `login_failed`, `logout`. Entidades: `sale`, `purchase`, `product`, `user`, `client`, `supplier`, `category`, `role`, `permission`, `report`, `auth`, `sale_return`, `payment_method`, `commercial_document`. Los KPIs salen de `ActivityLog::kpis()` (COUNT/GROUP BY sobre el rango filtrado, no sobre la página).
- Roles sembrados: Administrador, Vendedor, Comprador.
- Auditoría: `fyh_creacion` es `DEFAULT CURRENT_TIMESTAMP` (no insertar a mano); `fyh_actualizacion` es `ON UPDATE CURRENT_TIMESTAMP` y queda NULL al crear.

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
- Stock: `Sale::storeWithStock()` decrementa de forma relativa dentro de la transacción y permite stock negativo según el requisito operativo; el POS advierte al agregar al carrito y al confirmar. Los ajustes manuales de salida continúan bloqueando saldos negativos.
- Totales: `Sale::storeWithStock()` congela `precio_unitario` por línea (desde `tb_almacen.precio_venta`) y calcula `total_pagado` como `SUM(cantidad * precio_unitario)` de `tb_carrito`, todo dentro de la transacción — ignorar siempre el valor del POST
- Guards en destroy: `isReferenced()` y verificación de auto-eliminación siempre server-side en `UserController::destroy()` — la verificación cliente-side (AJAX) es solo UX
- Datos para operaciones críticas: usar siempre el snapshot de BD (ej: `$snapshot['id_producto']`, no `$_POST['id_producto']`) para revertir stock u otras operaciones irreversibles

### PHP — Autenticación y permisos

```php
$router->get('/ruta', [Controller::class, 'method'], ['auth']);                // cualquier rol autenticado
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'can:permiso']); // requiere permiso
Auth::user() / Auth::role() / Auth::check()   // check() revalida sesión, timeout y que cuenta/rol sigan vigentes
Auth::can('permiso') / Auth::isAdmin()        // isAdmin() = can('is_superadmin'); caché en $_SESSION['permisos']
```

- Los permisos viven en `tb_permisos`, se asignan por rol en `tb_rol_permiso` (no hay asignación por usuario) y se gestionan desde `/permissions` y `/roles/permisos/{id}` con `manage_roles`. La `clave` es **inmutable**. Al añadir un slug, actualiza la lista de [docs/guides/autenticacion.md](docs/guides/autenticacion.md).
- En controladores usa `Auth::can()`; en vistas, el controlador pasa `$can` vía `renderWithLayout()` — nunca `Auth::` en vistas. El sidebar gatea cada enlace con su permiso real (`$can[...]`), no con los proxies `$isAdmin`/`$isSeller`/`$isBuyer`.
- **Scoping por dueño (`view_sales_all` / `view_purchases_all`):** sin el permiso `_all` se filtra por `id_usuario`. Aplica el filtro al listado **y** a `show`/`edit`/`update`/`destroy` (filtrar solo el listado deja abierto el acceso por URL directa), con `Controller::forbidden()` para "este registro no es tuyo".
- **Anti escalada:** `manage_users`/`manage_roles` no incluyen otorgarse `is_superadmin` (`UserController::blocksSuperadminEscalation()`, `Role::grantsSuperadmin()`, `Role::isLastSuperadminRole()`). Úsalos en cualquier flujo que asigne roles o permisos.
- Cada cambio de permisos de un rol incrementa `tb_roles.permisos_version`; `Auth::check()` la compara y recarga permisos sin re-login.
- Sesión: cookie `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS (también tras proxy con `X-Forwarded-Proto`); timeout `SESSION_LIFETIME` (60 min); `pw_fp` cierra las demás sesiones al cambiar la contraseña.
- Login: cada intento se registra en `tb_intentos_auth` **antes** de verificar nada; correo inexistente, contraseña incorrecta y bloqueo muestran el mismo mensaje. Umbrales ajustables por `.env` (`LOGIN_*`, `RESET_*`, `REMEMBER_LIFETIME`) vía `AuthSettings`.
- Restablecimiento: el enlace nunca se muestra en pantalla (con `APP_DEBUG=true` solo va a `error_log`); la respuesta es la misma exista o no el correo.
- Detalle de cada flujo, tabla de umbrales y "recordarme": [docs/guides/autenticacion.md](docs/guides/autenticacion.md).

### JavaScript, vistas y accesibilidad

- jQuery + DataTables sin AJAX (datos desde PHP) + SweetAlert2. Nunca `Swal.fire`/`onclick` inline: usa `AlertUtils`/`ToastUtils` de `public/js/core/sweetalert-utils.js`.
- Patrones de CRUD: página dedicada de confirmación (products, users), formulario inline (purchases) y modal + AJAX (roles, categories, suppliers, clients, payment-methods). Assets por vista con `$pageStyles`/`$pageScripts`; el JS de cada módulo va en `public/js/modules/[modulo]/`.
- Todas las vistas usan `renderWithLayout()`, llevan breadcrumb y **no contienen lógica de negocio** (totales y sumas se calculan en Controller/Model). `BASE_URL` y `APP_CURRENCY_SYMBOL` están disponibles globalmente: no hardcodear `"Bs."` ni llamar `CURRENCY_SYMBOL` a una constante (colisiona con `intl`).
- Accesibilidad: axe-core sin violaciones en claro y oscuro; título de modal `<h2 class="modal-title h5">`; `aria-label` en todo botón o enlace de solo icono; cada `<label>` con `for`/`id`.
- Cache-busting: los assets propios llevan `?v=<?= APP_VERSION ?>`; tras un fix de CSS/JS, sube `APP_VERSION` en `.env`.
- Layouts, POS (wizard, reserva del `nro_venta`), detalle de páginas y lecciones de Bootstrap/DataTables: [docs/guides/frontend.md](docs/guides/frontend.md).

---

## Testing

```bash
composer test             # todas las suites
composer test:unit        # solo Unit (rápido, ideal pre-commit)
composer test:integration # solo Integration (SQLite in-memory)
composer test:coverage    # requiere PCOV o Xdebug
```

- PHPUnit 11 con atributos (`#[DataProvider(...)]`). No se testean Controllers, Middleware, Vistas ni Router.
- Al cambiar `database/schema.sql`, actualiza `tests/fixtures/schema.sqlite.sql` (tabla de equivalencias en [docs/guides/testing.md](docs/guides/testing.md)).
- Todo test que resetee el singleton debe dejar SQLite inyectado (`Database::set(...)`), nunca solo `Database::reset()`.
- Los tests `*MariaDbTest` usan MariaDB real solo para funciones de fecha del motor; no se ejecutan en paralelo (BD de test fija). Nunca calcules fechas límite con `date()` de PHP contra `NOW()` de la BD.
- CI: un único job `test` (PHP 8.2/8.3, `mariadb:10.11`) en cada push a `master` y cada PR. Detalle: [docs/guides/testing.md](docs/guides/testing.md).

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

Features nuevas: primero spec, luego plan, tareas y solo entonces código. **La spec es el contrato: si no está ahí, no se implementa.** Principios en [docs/constitution.md](docs/constitution.md), estado en [docs/roadmap.md](docs/roadmap.md). Cada feature vive en `specs/NNN-<slug>/` con `spec.md`, `plan.md`, `tasks.md` y `validation.md` (no se cierra hasta que existen los cuatro).

Comandos: `/sdd:constitution` · `/sdd:spec` · `/sdd:clarify` · `/sdd:plan` · `/sdd:tasks` · `/sdd:implement <n>` · `/sdd:validate` · `/sdd:change <req>`; documentos derivados con `/sdd:docs`.

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
  producción debe ser `public/`). `docs/` reúne la capa SDD (constitución, roadmap y los
  documentos que genera `/sdd:docs`: `schema.md`, `app-flow.md`, `ux-brief.md`) y, en
  `docs/guides/`, las guías de desarrollo, arquitectura y despliegue.
- No modificar `specs/` fuera de su fase sin pedido explícito.
- **Formato de `validation.md`:** `## Verdict` con primera línea `Spec fulfilled…` (o `Spec NOT fulfilled`) y `**Date:** YYYY-MM-DD`; es lo que el toolkit lee para saber si una feature está cerrada.

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

_Última actualización: 2026-10-21 — 1.20.0. Historial completo en [CHANGELOG.md](CHANGELOG.md)._
