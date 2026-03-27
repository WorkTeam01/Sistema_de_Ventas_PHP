# AGENT.md — Sistema de Ventas PHP

> System prompt persistente para agentes de IA y sesiones de desarrollo asistido.
> Compatible con: Claude Code · Cursor (.cursorrules) · Claude.ai (pegar al inicio) · Copilot (workspace instructions)

---

## Proyecto

Sistema de gestión de ventas con control de inventario, facturación, gestión de clientes y acceso por roles.
Permite registrar ventas, compras a proveedores, gestionar el almacén y emitir facturas en PDF.

**Estado actual:** Migración MVC en curso — módulos `roles`, `categories`, `suppliers`, `clients` migrados.
Pendientes: `almacen` → `compras` → `ventas`.

---

## Stack Tecnológico

- **Backend:** PHP 8.x (sin framework — MVC custom con PSR-4 via Composer)
- **Frontend:** AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2
- **Base de datos:** MySQL / MariaDB (PDO)
- **PDF:** TCPDF (`app/TCPDF-main/`)
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
│   │   └── ClientController.php
│   ├── Models/               ← PSR-4, namespace App\Models
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Category.php
│   │   ├── Supplier.php
│   │   └── Client.php
│   ├── Middleware/           ← AuthMiddleware, AdminMiddleware, GuestMiddleware
│   ├── controllers/          ← Controladores legacy (módulos no migrados)
│   │   ├── middleware/AuthMiddleware.php  ← Middleware legacy
│   │   ├── almacen/
│   │   ├── compras/
│   │   └── ventas/
│   ├── TCPDF-main/           ← Generación de facturas PDF
│   └── config.php            ← Carga .env, expone $pdo, BASE_URL, $URL
├── views/
│   ├── layout/
│   │   ├── parte1.php        ← Head HTML, navbar, sidebar
│   │   ├── parte2.php        ← Scripts de cierre, footer
│   │   └── sesion.php        ← Valida sesión activa, redirige a /auth si no
│   ├── auth/
│   ├── dashboard/
│   ├── users/
│   ├── roles/
│   ├── categories/
│   ├── suppliers/
│   └── clients/
├── routes/
│   └── web.php               ← Todas las rutas MVC registradas
├── public/
│   ├── index.php             ← Entry point único (front controller MVC)
│   ├── .htaccess             ← Redirige al Router, excluye módulos legacy
│   ├── css/                  ← CSS personalizado
│   ├── js/                   ← JS personalizado (control_sidebar.js)
│   └── templates/            ← Assets AdminLTE (no modificar)
├── almacen/                  ← Módulo legacy (pendiente migración)
├── compras/                  ← Módulo legacy (pendiente migración)
├── ventas/                   ← Módulo legacy (pendiente migración)
└── database/
    ├── schema.sql
    └── seeder.sql
```

---

## Enrutamiento Híbrido

El proyecto usa **dos sistemas de ruteo en paralelo**:

| Tipo       | Cómo funciona                              | Módulos                                                       |
| ---------- | ------------------------------------------ | ------------------------------------------------------------- |
| **MVC**    | `public/index.php` → `Router` → Controller | roles, categories, suppliers, clients, users, auth, dashboard |
| **Legacy** | Acceso directo al archivo PHP              | almacen, compras, ventas                                      |

Las rutas MVC se registran en `routes/web.php`. Los módulos legacy no pasan por el Router.

---

## Base de Datos

```sql
-- Tablas principales
tb_usuarios    (id_usuario, nombre, apellido, email, password, id_rol, fyh_creacion, fyh_actualizacion)
tb_roles       (id_rol, nombre_rol, fyh_creacion, fyh_actualizacion)
tb_categorias  (id_categoria, nombre_categoria, fyh_creacion, fyh_actualizacion)
tb_proveedores (id_proveedor, nombre_proveedor, nit_ci_proveedor, celular_proveedor,
                email_proveedor, nombre_empresa, fyh_creacion, fyh_actualizacion)
tb_clientes    (id_cliente, nombre_cliente, nit_ci_cliente, celular_cliente,
                email_cliente, fyh_creacion, fyh_actualizacion)
tb_almacen     (id_almacen, nombre_almacen, descripcion, precio_compra, precio_venta,
                stock, imagen, id_categoria, fyh_creacion, fyh_actualizacion)
tb_ventas      (id_venta, id_cliente, total, fyh_creacion)
tb_carrito     (id_carrito, id_venta, id_almacen, cantidad, precio)
tb_compras     (id_compra, id_proveedor, id_almacen, cantidad, precio_total, fyh_creacion)

-- Roles de usuario (almacenados en tb_roles)
Administrador · Vendedor · Comprador

-- Convenciones de auditoría
fyh_creacion      DEFAULT CURRENT_TIMESTAMP  ← no insertar manualmente
fyh_actualizacion ON UPDATE CURRENT_TIMESTAMP ← queda NULL al crear
```

---

## Convenciones de Código

### PHP — Controladores MVC

- Un controlador por módulo: `SupplierController`, `ClientController`, etc.
- Métodos estándar del proyecto: `index()`, `create()`, `store()`, `edit(?int $id)`, `update()`, `destroy()`
- `index()` genera el CSRF token para el formulario oculto de eliminación
- `destroy()` llama a `$model->isReferenced($id)` antes de eliminar — si hay FK activa, flash error y redirect
- **PROHIBIDO** inventar métodos que no existan en el proyecto

### PHP — Modelos MVC

- Heredan de `App\Core\Model` — métodos disponibles: `all()`, `find()`, `create()`, `update()`, `delete()`, `count()`, `query()`
- Sobreescribir `isReferenced(int|string $id): bool` en modelos con FKs en otras tablas
- Usar PDO con prepared statements siempre — nunca concatenar variables en SQL
- **Borrado físico** (no lógico) — protegido por `isReferenced()` antes de ejecutar DELETE

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

// En módulos legacy:
require_once('../app/controllers/middleware/AuthMiddleware.php');
$auth    = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Vendedor']);

// Datos del usuario en sesión (contexto MVC):
Auth::user()   // array con datos del usuario
Auth::role()   // nombre del rol
Auth::check()  // bool
```

### JavaScript / Frontend

- jQuery para DOM y eventos
- **DataTables** sin AJAX: datos cargados desde PHP en la vista, sin filtros server-side
- **SweetAlert2** para confirmaciones de eliminación — patrón: formulario oculto `#formEliminar` con CSRF + campo hidden del ID, disparado tras confirmación
- Anti-FOUC del sidebar/tema: script inline en `parte1.php`, preferencias en `localStorage`

### Vistas MVC

- Todas usan `renderWithLayout()` del Controller base (compone parte1 + contenido + parte2)
- Constante `BASE_URL` disponible globalmente — usar para construir URLs en PHP y JS
- Layout de páginas de listado: full width, DataTables con export (PDF/Excel/CSV/Imprimir)
- Layout de formularios: col-md-8 (form) + col-md-4 (tarjeta informativa)
- Breadcrumb obligatorio en cada vista (`<section class="content-header">`)

### Módulos Legacy

- La lógica de negocio vive en `app/controllers/[modulo]/`
- Las vistas son los archivos PHP en el directorio raíz del módulo (`almacen/`, `compras/`, `ventas/`)
- Usar `$URL` (alias de `BASE_URL`) para construir links internos
- Incluir `app/config.php` y el middleware legacy al inicio de cada página

---

## Flujo de Migración MVC

Al migrar un módulo legacy a MVC, el orden es:

1. Crear `app/Models/[Nombre].php` — extender `Model`, definir `$table` y `$primaryKey`, sobreescribir `isReferenced()` si aplica
2. Crear `app/Controllers/[Nombre]Controller.php` — 6 métodos: `index`, `create`, `store`, `edit`, `update`, `destroy`
3. Crear `views/[modulo]/index.php`, `create.php`, `edit.php`
4. Registrar 6 rutas en `routes/web.php`
5. Actualizar sidebar en `views/layout/parte1.php` — bloque con control de rol
6. Actualizar `DashboardController` para usar el nuevo Model en lugar del require legacy
7. Eliminar archivos legacy del módulo
8. Actualizar CHANGELOG.md, README.md, CLAUDE.md

Orden de migración pendiente: **`almacen`** → `compras` → `ventas`

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
- **NO** modificar assets de `public/templates/` (AdminLTE)
- **NO** acceder a `Auth::` directamente en vistas — calcular datos en el controlador y pasarlos via `renderWithLayout()`

---

_Última actualización: 2026-03-27 — migración MVC en curso (v1.1.0-wip)_
_Mantener este archivo actualizado al completar cada módulo migrado._
