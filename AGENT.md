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
│   │   └── NumberToWords.php
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
│   ├── auth/
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
│   │   └── modules/          ← CSS por módulo (auth/login.css, …)
│   ├── js/
│   │   ├── core/             ← Utilitarios globales (sweetalert-utils.js, control_sidebar.js)
│   │   └── modules/          ← JS por módulo (auth/login.js, users/users-index.js, …)
│   └── templates/            ← Assets AdminLTE (no modificar)
└── database/
    ├── schema.sql
    └── seeder.sql
```

---

## Enrutamiento Híbrido

El proyecto usa **dos sistemas de ruteo en paralelo**:

| Tipo    | Cómo funciona                              | Módulos                                                                                           |
| ------- | ------------------------------------------ | ------------------------------------------------------------------------------------------------- |
| **MVC** | `public/index.php` → `Router` → Controller | auth, dashboard, users, roles, categories, suppliers, clients, products, purchases, sales (todos) |

Todos los módulos están migrados. No quedan módulos legacy.

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

// Datos del usuario en sesión:
Auth::user()   // array con datos del usuario
Auth::role()   // nombre del rol
Auth::check()  // bool
```

### JavaScript / Frontend

- jQuery para DOM y eventos
- **DataTables** sin AJAX: datos cargados desde PHP en la vista, sin filtros server-side
- **SweetAlert2** para confirmaciones de eliminación — patrón: formulario oculto `#formEliminar` con CSRF + campo hidden del ID, disparado tras confirmación
- Anti-FOUC del sidebar/tema: script inline en `layouts/header.php`, preferencias en `localStorage`

### Vistas MVC

- Todas usan `renderWithLayout()` del Controller base (compone header + contenido + footer)
- Constante `BASE_URL` disponible globalmente — usar para construir URLs en PHP y JS
- Layout de páginas de listado: full width, DataTables con export (PDF/Excel/CSV/Imprimir)
- Layout de formularios: col-md-8 (form) + col-md-4 (tarjeta informativa)
- Breadcrumb obligatorio en cada vista (`<section class="content-header">`)

---

Migración MVC completada. No quedan módulos legacy pendientes.

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

_Última actualización: 2026-03-31 — v1.1.2 (login rediseñado, assets core, JS por módulos)_
