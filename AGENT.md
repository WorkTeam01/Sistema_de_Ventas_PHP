# AGENT.md — Sistema de Ventas PHP

> System prompt persistente para agentes de IA y sesiones de desarrollo asistido.
> Compatible con: Claude Code · Cursor (.cursorrules) · Claude.ai (pegar al inicio) · Copilot (workspace instructions)

---

## Proyecto

Sistema de gestión de ventas con control de inventario, facturación, gestión de clientes y acceso por roles.
Permite registrar ventas, compras a proveedores, gestionar el almacén y emitir facturas en PDF.

**Estado actual:** Migración MVC completada — todos los módulos migrados a MVC.

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
│   │   └── SaleController.php
│   ├── Helpers/              ← PSR-4, namespace App\Helpers
│   │   ├── NumberToWords.php
│   │   ├── InvoicePdf.php
│   │   └── PurchaseReportPdf.php
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
│   │   └── CartItem.php
│   └── Middleware/           ← AuthMiddleware, AdminMiddleware, GuestMiddleware, SellerMiddleware
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
│   ├── roles/
│   ├── categories/
│   ├── suppliers/
│   ├── clients/
│   ├── products/
│   ├── purchases/
│   └── sales/
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

| Tipo    | Cómo funciona                              | Módulos                                                                                           |
|---------|--------------------------------------------|---------------------------------------------------------------------------------------------------|
| **MVC** | `public/index.php` → `Router` → Controller | auth, dashboard, users, roles, categories, suppliers, clients, products, purchases, sales (todos) |

Todos los módulos están migrados. No quedan módulos legacy.

---

## Base de Datos

```sql
-- Tablas principales
tb_usuarios
(id_usuario, nombre, apellido, email, password, id_rol, fyh_creacion, fyh_actualizacion)
    tb_roles
    (id_rol, nombre_rol, fyh_creacion, fyh_actualizacion)
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
    (id_venta, id_cliente, total, fyh_creacion)
    tb_carrito
    (id_carrito, id_venta, id_almacen, cantidad, precio)
    tb_compras
(id_compra, id_proveedor, id_almacen, cantidad, precio_compra, precio_total, fecha_compra, fyh_creacion)

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
  de campos, cálculos, reglas de integridad); el controlador solo orquesta (leer input → llamar modelo → responder).
  Ejemplos: `User::createUser()` / `updateUser()` encapsulan `password_hash()`; `Client::isValidEmail()` encapsula
  `filter_var()`; `Supplier::createSupplier()` / `updateSupplier()` normalizan campos opcionales (vacío → `null`);
  `Product::createProduct()` / `updateProduct()` normalizan campos opcionales y castean tipos; los modelos también
  pueden exponer queries enriquecidas (`Product::findWithCategory()`, `Product::allWithCategories()`) para evitar
  joins manuales en el controlador; `Sale::computeInvoiceTotals(array $items)` encapsula el cálculo de totales de
  factura (precio_total, cantidad_total, total_unitarios) — la generación del PDF se delega al helper
  `InvoicePdf::generate()` en `app/Helpers/`, manteniendo `SaleController::invoice()` en ~20 líneas;
  el comprobante de compras sigue el mismo patrón: `PurchaseReportPdf::generate()` invocado desde
  `PurchaseController::report()` vía `GET /purchases/report/{id}`

### PHP — Seguridad

- Passwords: `password_hash()` al guardar, `password_verify()` al validar
- Inputs: `htmlspecialchars()` en outputs HTML, validación server-side obligatoria
- CSRF: token en todos los formularios POST (`Auth::generateCsrfToken()` / `validateCsrfOrFail()`)
- SQL: siempre placeholders `?` con `execute([$var])` — nunca interpolar en el string SQL

### PHP — Autenticación

```php
// En módulos MVC (via middleware en routes/web.php):
$router->get('/ruta', [Controller::class, 'method'], ['auth']);          // cualquier rol
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'admin']); // solo Administrador

// Datos del usuario en sesión:
Auth::user()            // array con datos del usuario
Auth::role()            // nombre del rol
Auth::check()           // bool — verifica sesión y timeout de inactividad
Auth::login($user, $remember) // inicia sesión; $remember=true emite cookie de 14 días
Auth::loginWithCookie() // auto-login desde cookie remember_token; rota el token
Auth::logout()          // limpia sesión, BD y cookie
```

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
- Layout de páginas de listado: full width, DataTables con export (PDF/Excel/CSV/Imprimir)
- Layout de formularios CRUD: col-md-8 (form) + col-md-4 (tarjeta informativa)
- Layout de formularios POS (ventas/create): patrón wizard — col-md-9 con 3 tabs numerados (steps) + barra de
  progreso animada + validación entre pasos; col-md-3 sidebar sticky "Resumen de venta"; Tab 1 incluye card
  colapsable "Datos del cliente" con alerta de advertencia por defecto (sin cliente) y campos ocultos hasta
  selección; botones "Nuevo cliente" (modal inline) y "Buscar cliente" (modal tabla); estado del cliente
  persiste en `sessionStorage('pos_client')` para sobrevivir recargas por operaciones de carrito
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
|---------------|----------------------|------------------------------------------------------|
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
- No testear Controllers, Middleware, Vistas ni Router (ver CLAUDE.md §Lo que NO se testea)
- Al agregar columnas o tablas a `database/schema.sql`, actualizar también `tests/fixtures/schema.sqlite.sql`

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

_Última actualización: 2026-05-04 — v1.7.0 (PHPUnit 11 + GitHub Actions CI, suites Unit e Integration con SQLite in-memory)_
