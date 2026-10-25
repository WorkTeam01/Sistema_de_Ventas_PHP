# Arquitectura del proyecto

> Árbol completo de archivos con su rol. Resumen y reglas en [AGENTS.md](../../AGENTS.md).

```
Sistema_de_Ventas_PHP/
├── app/
│   ├── Core/
│   │   ├── Router.php        ← Enrutamiento centralizado (rutas MVC)
│   │   ├── Database.php      ← Singleton PDO
│   │   ├── Model.php         ← CRUD genérico base
│   │   ├── Controller.php    ← renderWithLayout(), redirect(), flash(), validate(), postString()/getString()
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
│   │   ├── core/             ← Utilitarios globales (sweetalert-utils.js, ui-components.js, datatables-csv-safe.js, control_sidebar.js)
│   │   ├── modules/          ← JS por módulo (auth/login.js, users/users-index.js, …)
│   │   ├── lib/              ← Vendors JS (jquery/, bootstrap/, adminlte/)
│   │   └── plugins/          ← Plugins JS (sweetalert2/)
│   └── templates/            ← AdminLTE fuente completa (no modificar)
└── database/
    ├── schema.sql
    ├── seeder.sql            ← desarrollo: catálogo + datos demo + usuarios de prueba
    └── seeder_produccion.sql ← solo catálogo (roles, permisos, métodos de pago); al cambiar el catálogo en seeder.sql, replicarlo aquí
```

## Enrutamiento

El proyecto usa **dos sistemas de ruteo en paralelo**:

| Tipo    | Cómo funciona                              | Módulos                                                                                                                             |
| ------- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------- |
| **MVC** | `public/index.php` → `Router` → Controller | auth, dashboard, users, roles, categories, suppliers, clients, products, purchases, sales, activity-log, inventory, reports (todos) |

Todos los módulos están migrados. No quedan módulos legacy.

El parámetro `{id}` de una ruta solo admite dígitos (`/products/abc` responde 404); `{token}` admite alfanuméricos, `-` y `_`.

> El detalle exacto de método + ruta + controller + middleware por endpoint vive en `routes/web.php` — es la fuente
> de verdad; no se replica aquí para evitar desincronización. Los middlewares usan la sintaxis `can:permiso` (ver
> slugs en §Autenticación).
