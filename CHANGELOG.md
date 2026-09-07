# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

### Cambiado

- Adopción de Spec-Driven Development (plugin `sdd-toolkit`). Solo documentación,
  sin cambios de código:
  - `AGENT.md` → `AGENTS.md` (fuente única de convenciones/arquitectura/setup);
    nueva sección "Planificación de features (SDD)" con la regla de cuándo una
    feature lleva spec y cuándo no.
  - `CLAUDE.md` reducido a `@AGENTS.md` + arranque local de Claude Code (se
    eliminó la duplicación de testing y la tabla de referencia).
  - Nuevos `docs/constitution.md` (10 principios derivados de las reglas ya
    vigentes) y `docs/roadmap.md` (histórico enlazado a este changelog +
    siguiente feature: devoluciones de ventas).
  - `.gitattributes` con `export-ignore` para `docs/`, `specs/`, `tests/`, etc.
  - Barrido de referencias `AGENT.md` → `AGENTS.md` en README, CONTRIBUTING y PROMPTS.

## [1.16.6] - 2026-09-07

### Cambiado

- CI (`tests.yml`): `actions/checkout@v4 → @v7` y `actions/cache@v4 → @v6`. Ambas versiones nuevas corren sobre
  el runtime Node 24 (GitHub ya marcaba Node 20 como deprecado); sin cambios de sintaxis para el uso actual
  (checkout básico, caché de Composer). `shivammathur/setup-php@v2` se mantiene (v2 sigue siendo el major
  vigente).

### Corregido

- Contraste WCAG AA en badges, alerts y botones contextuales (`ui-components.css`), verificado con axe-core en
  ambos temas sobre 6 vistas reales:
  - `.badge-success` (3.13:1) y `.badge-info` (3.04:1) fallaban en **ambos** temas — se oscurecen sin condicionar
    el tema (mismo patrón que `.badge-primary`) a los tonos que AdminLTE ya usa para `a.badge-*:hover` (≥5:1).
  - `.alert-success`, `.alert-info` y `.btn-info` también fallaban en ambos temas (AdminLTE los repinta con fondo
    sólido y texto blanco: light 3.0-3.1:1, dark 2.4-3.2:1) — se fija un verde/azul con blanco ≥5:1, con selector
    de doble clase para ganarle en especificidad al tema `select2-bootstrap4` que carga después.
  - Select2 en modo oscuro: el valor seleccionado (`#495057` sobre `#343a40`, 1.4:1) y el placeholder quedaban
    ilegibles porque el tema bootstrap4 carga después de `ui-components.css` — se sube la especificidad del
    override dark (`.select2-selection--single/--multiple`) para forzar texto blanco / gris AA.
  - `.alert-warning` y `.btn-warning` (texto oscuro sobre naranja, 6.38:1) ya cumplían — no se tocan.
  - Cabeceras de modal `.modal-header.bg-primary` (3.97:1), `.bg-success` (3.13:1) y `.bg-info` (3.04:1) con
    título/`×` blancos fallaban en ambos temas en categorías, clientes, inventario, proveedores, permisos y
    roles — override global (`!important`, porque las utilidades `.bg-*` de AdminLTE lo llevan) a los mismos
    tonos del resto del archivo, blanco ≥5:1. El módulo Ventas ya lo tenía resuelto con selectores `#modal-*`.

## [1.16.5] - 2026-08-28

### Agregado

- Cache-busting de assets propios: `layouts/header.php` y `layouts/footer.php` anexan `?v=<APP_VERSION>` a
  `ui-components.css`, `sweetalert-utils.js`, `password-toggle.js`, `control_sidebar.js`, `ui-components.js` y a
  cada entrada de `$pageStyles` / `$pageScripts`. Al subir `APP_VERSION` en `.env` el navegador vuelve a pedir el
  CSS/JS en vez de servir la copia vieja de caché (resuelve el gotcha de auditorías a11y previas donde un fix de
  CSS no se reflejaba).

### Cambiado

- CI (`tests.yml`) simplificado: los jobs `scope`/`unit`/`integration` se fusionan en un único job `test`
  (matrix PHP 8.2/8.3) que corre `phpunit` completo en cada push y PR. La suite entera tarda ~2 s, así que el
  job previo `scope` (checkout de historia completa + `git diff` para filtrar) y `paratest --processes 4`
  (medido más lento que la ejecución en serie) costaban más de lo que ahorraban. De 5 slots de runner por PR a 2.

### Corregido

- Auditoría de accesibilidad/UX del módulo Ventas (crear, detalle, anulación): contraste WCAG AA en las tabs del
  wizard, la barra de progreso `bg-primary`, el badge "Paso N de 3", las cabeceras y botones de los modales de
  búsqueda de cliente/producto, y el texto muted del estado vacío del carrito y de las filas zebra en detalle;
  orden de encabezados (`<h5>` decorativos → `<h2 class="h5">`); `<th>` de imagen con `<span class="sr-only">`;
  `aria-live="polite"` en el panel de cliente; `inputmode="decimal"` en el campo de pago; área táctil de 44 px
  (WCAG 2.5.5) en los botones sueltos del wizard y del header de la card de detalle.
- Contraste WCAG AA en modo oscuro (global, `ui-components.css`): `.btn-success`, `.btn-danger`, `.alert-danger`,
  `.text-primary` y `.text-info` que AdminLTE dark repinta con paleta contextual de bajo contraste — se fija un
  tono aclarado validado ≥4.5:1 sobre los fondos oscuros reales, cada override con su contraparte `body.dark-mode`.

### Eliminado

- Dependencia `brianium/paratest` y sus 8 paquetes transitivos.
- Generación de cobertura y subida de artifact en CI (nadie los consumía; `composer test:coverage` sigue local).

## [1.16.4] - 2026-08-23

### Agregado

- Suite de tests de integración contra MariaDB real (`tests/Concerns/RefreshMariaDatabase`) para casos que dependen
  de funciones de fecha del motor (`CURDATE()`, `NOW()`, `YEAR()`, `DATE_FORMAT()`) no soportadas por SQLite
  in-memory: `ClientRepositoryMariaDbTest`, `PurchaseRepositoryMariaDbTest`, `SaleRepositoryMariaDbTest`,
  `UserRepositoryMariaDbTest`. Se salta automáticamente en local si `.env.testing` no está configurado.
- CI (`tests.yml`) rediseñado: job `unit` (siempre) separado de `integration` (`paratest --processes 4`, scopeado
  por diff, con servicio `mariadb:10.11` para la suite anterior); `permissions`/`concurrency` agregados al workflow.

### Corregido

- Auditoría de accesibilidad/UX del módulo Clientes (listado, crear, editar): 5 elementos de contraste insuficiente
  (WCAG AA) corregidos de forma global — nombre de usuario del navbar, enlaces del breadcrumb, `.btn-primary` y
  paginación de DataTables — reemplazando el azul `#007bff` de Bootstrap por `#0056b3` en toda la app,
  consistente con el criterio ya usado en el ítem activo del sidebar.
- Orden de encabezados corregido en las 29 vistas que usan tarjetas de AdminLTE: `<h3 class="card-title">` pasa a
  `<h2>` para no saltar de `h1` a `h3` (59 ocurrencias).
- `aria-label` único en los dos landmarks `<nav>` compartidos (barra superior y menú lateral), antes
  indistinguibles para lectores de pantalla.

## [1.16.3] - 2026-08-17

### Corregido

- Auditoría de accesibilidad/UX de los módulos Registro de actividad, Categorías, Clientes, Inventario, Permisos,
  Roles y Proveedores: `aria-label`/`<span class="sr-only">` en botones icon-only (acciones de tabla y colapso de
  tarjetas), botón de cierre del modal de ajuste de inventario, y atributo `required` en los campos obligatorios de
  los formularios de creación/edición que solo validaban por JS/backend.

## [1.16.2] - 2026-08-15

### Corregido

- Auditoría de accesibilidad/UX del módulo Compras (listado, crear, editar, detalle): moneda hardcodeada
  reemplazada por `APP_CURRENCY_SYMBOL`, resumen en tiempo real poblado al cargar la página, `aria-label` en
  botones icon-only y limpieza de una clase CSS sin efecto.
- Foco de teclado visible y contraste AA en los botones "Ver perfil"/"Cerrar sesión" del menú de usuario del
  navbar, reseteados por AdminLTE.

## [1.16.1] - 2026-08-14

### Corregido

- Auditoría de accesibilidad/UX del módulo Productos (listado, crear, editar, eliminar, detalle): `<label for>`
  enlazados a sus inputs, `aria-label` en botones icon-only, imagen de fallback SVG cuando la imagen del producto
  no carga.
- Selector de fecha accesible y reutilizable: el `onclick` inline de todos los filtros de fecha (activity-log,
  inventario, reportes, compras) se reemplazó por un botón real, operable con teclado/lector de pantalla, con su
  listener delegado en `ui-components.js`.
- Lógica de validación y cálculo de margen de precios de `products/create` y `products/edit` extraída a un módulo
  compartido (`ProductFormShared`), eliminando ~100 líneas duplicadas.
- `products-index.js` retira el placeholder de carga una vez que DataTables termina de inicializar la tabla.

### Eliminado

- CSS duplicado del módulo Productos: su única regla (touch target en móvil) ya existía, con mejor criterio, en la
  regla global de `ui-components.css`.

## [1.16.0] - 2026-08-09

### Cambiado

- `DashboardController` arma los KPIs y widgets según los permisos reales del usuario en vez de comparar el nombre
  del rol hardcodeado. Un rol nuevo con cualquier combinación de permisos ve automáticamente los widgets
  correspondientes, sin tocar código.
- Nuevo permiso `view_purchases_all` (análogo a `view_sales_all`), sembrado solo para Administrador. Sin él, cada
  usuario ve solo sus propias ventas/compras en el dashboard y en los listados.
- `SaleController::index()` y `PurchaseController::index()` filtran por usuario salvo que tenga
  `view_sales_all`/`view_purchases_all` — antes listaban todos los registros del sistema sin importar el permiso.
- Permiso de reportes retirado del rol Vendedor: queda exclusivo de Administrador.

### Seguridad

- **IDOR en compras:** `PurchaseController` no chequeaba dueño — cualquier usuario con `manage_purchases` podía
  ver, editar o eliminar compras de otro usuario por URL directa. Ahora bloquea si la compra no es del usuario y
  no tiene `view_purchases_all`, replicando el chequeo que ya existía en `SaleController`.
- Nuevo helper `Controller::forbidden()` para unificar la respuesta 403 de los chequeos de dueño con la de rutas
  sin permiso.

### Corregido

- El título del gráfico de ventas/compras del dashboard estaba hardcodeado a "Ventas": un usuario que solo ve
  compras veía el gráfico mal rotulado. Ahora se arma dinámicamente según los datasets reales.
- La sección "Reportes" del sidebar se mostraba a cualquier Vendedor sin importar sus permisos reales, aunque la
  ruta sí estaba protegida (caía en 403 al hacer clic). Ahora cada enlace se gatea con su propio permiso.

---

## [1.15.0] - 2026-08-02

### Añadido

- Moneda configurable vía `APP_CURRENCY_SYMBOL` en `.env`, disponible globalmente. Reemplaza ~25 ocurrencias
  hardcodeadas en controllers, PDFs y vistas.
- `Sale::withSubtotals()` — agrega el subtotal a cada ítem del carrito/venta para consumo directo en vistas.

### Accesibilidad

- Fix global de aria-hidden en modales Bootstrap 4: se quita el foco antes de que Bootstrap oculte el modal,
  eliminando el warning de accesibilidad en todos los módulos.
- Header/sidebar globales: `aria-label` en los botones icon-only de la navbar, `role="menu"` inválido eliminado
  del sidebar, contraste del texto de marca en móvil subido a WCAG AA.
- Auditoría completa del módulo de ventas/POS (labels, ARIA en modales y tabs, `aria-label` en botones icon-only,
  breadcrumb, `aria-live` en progress bar, `loading="lazy"` en imágenes).
- Área táctil mínima de 44×44px para botones icon-only en tablas, aplicada globalmente — beneficia a todos los
  módulos con tablas de acciones, no solo ventas.

### Corregido

- Lógica de negocio movida fuera de las vistas de ventas y reportes de clientes: calculaban totales/subtotales
  directamente en la vista; ahora se resuelven en el modelo/controller y la vista solo formatea.

### Modificado

- CSS del módulo de ventas eliminado — su única regla (touch target) se consolidó en el CSS global para aplicar a
  toda la app.

---

## [1.14.3] - 2026-07-29

### Accesibilidad

- `aria-hidden="true"` en todos los iconos decorativos (Font Awesome) de las vistas de login, forgot-password y
  reset-password, para que los lectores de pantalla dejen de anunciarlos como contenido.
- Atributos nativos `required`/`aria-required="true"` en los inputs de los formularios de auth, como red de
  seguridad si la validación de jQuery Validate no llega a cargar.
- Colores del indicador de fortaleza de contraseña (`reset-password.js`) movidos de estilos inline a tokens CSS
  (`--strength-weak/fair/good/strong`) con variante dark; el tono "fair" en modo claro se ajustó para cumplir
  contraste 4.5:1 como texto.

### Corregido

- El aviso "la solicitud está tardando más de lo esperado" en los formularios de auth ya no aparece cuando el
  envío en realidad tuvo éxito y la página está navegando: `AuthFormUtils.handleSubmit` cancela el aviso al
  detectar `pagehide`/`beforeunload`.
- Copy del mismo aviso actualizado con una acción concreta ("presiona el botón para reintentar") en vez de un
  texto solo informativo.
- El botón de cambio de tema (`theme-toggle-btn`) ya no se superpone al contenido en viewports ≤360px.

### Modificado

- Configuración de jQuery Validate (placement de errores, highlight/unhighlight, foco en error) centralizada en
  `public/js/core/auth-form-utils.js` (`AuthFormUtils`), eliminando la duplicación entre `login.js`,
  `forgot-password.js` y `reset-password.js`.

---

## [1.14.2] - 2026-07-18

### Seguridad

- `Auth::isHttps()` detecta HTTPS también detrás de un proxy reverso (`X-Forwarded-Proto`), evitando que la cookie
  `remember_token` pierda el flag `secure` en despliegues con TLS-terminating proxy.
- Cabeceras de seguridad globales en `public/index.php`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy: strict-origin-when-cross-origin`.
- `escapeHtml()` en `sweetalert-utils.js` para sanear cualquier texto dinámico interpolado en opciones `html` de
  SweetAlert2.

### Modificado

- Eliminados todos los `Swal.fire(...)` inline y handlers `onclick` en vistas (productos, ventas, usuarios,
  clientes, proveedores, login, reset-password, 403): reemplazados por `AlertUtils`/`ToastUtils` centralizados en
  `sweetalert-utils.js`, con nuevos helpers `confirmDeleteItem` y `blockedDelete`.
- Vistas de eliminación de productos, ventas y usuarios ahora cargan su lógica de confirmación desde scripts de
  página dedicados (`products-delete.js`, `sales-delete.js`, `users-delete.js`) vía `pageScripts`, en vez de script
  inline.

---

## [1.14.1] - 2026-07-09

### Agregado

- Auditoría completa del módulo de audit log: creación/actualización de roles y permisos, login exitoso/fallido y
  logout, creación de usuarios, exportación de reportes, creación de ventas/compras/clientes/proveedores/productos,
  y creación/actualización de categorías — cierra el backlog de cobertura de `ActivityLog`.
- KPIs (info-box) en el listado de auditoría: total de eventos, usuarios distintos activos, eliminaciones y cambios
  sensibles, calculados por agregación SQL sobre el rango filtrado.

### Corregido

- `Cache-Control: no-store` en el listado y detalle de auditoría para evitar que datos sensibles queden cacheados.

---

## [1.14.0] - 2026-07-08

### Agregado

- Módulo de gestión de permisos con UI: CRUD de catálogo de permisos y pantalla de asignación de permisos por rol,
  completando el RBAC granular de v1.13.0 (antes solo se poblaba desde el seeder SQL).
- Recarga automática de permisos por sesión sin re-login cuando un admin modifica los permisos de un rol.

---

## [1.13.0] - 2026-06-27

### Agregado

- Sistema RBAC granular: permisos a nivel de acción (`can:permiso`) desacoplados del rol hardcodeado, con caché en
  sesión y middleware dedicado.

### Modificado

- Todas las rutas y controladores migrados de chequeos por rol (`admin`/`seller`) a permisos granulares.

### Eliminado

- Middlewares `AdminMiddleware` y `SellerMiddleware`, reemplazados por `PermissionMiddleware`.

---

## [1.12.3] - 2026-06-16

### Corregido

- Bug: dos vendedores abriendo el POS al mismo tiempo podían recibir el mismo número de venta y perder el carrito
  del otro. Corregido considerando carritos en construcción al calcular el siguiente número.

---

## [1.12.2] - 2026-06-11

### Corregido

- Falta de protección CSRF en creación/edición de roles.
- Mensajes de error de sesión expirada poco claros para el usuario final.

---

## [1.12.1] - 2026-06-10

### Corregido

- 8 vulnerabilidades de integridad en compras/ventas/usuarios: manipulación de stock vía POST, números de
  comprobante duplicados, stock negativo, totales de venta alterables por el cliente, eliminación de usuarios sin
  validación server-side, contraseñas sin longitud mínima, colisión de nombres de imágenes subidas, N+1 queries en
  autenticación.
- Vendedores ya no ven ventas de otros usuarios en reportes.

### Agregado

- Columna `id_usuario` en ventas para habilitar el scope por vendedor en reportes.

---

## [1.12.0] - 2026-06-09

### Agregado

- Módulo de Reportes (ventas, compras, top productos, clientes) con filtros de fecha, resumen del mes y
  exportación a PDF/CSV/Excel. Datos siempre acotados al scope del usuario en sesión.

---

## [1.11.0] - 2026-06-05

### Agregado

- Dashboard: fila de KPIs financieros colapsable para Administrador (utilidad bruta del mes, clientes nuevos) y
  gráfico doughnut de top 5 productos históricos.

### Seguridad

- Escapado reforzado de datos embebidos en `<script>` del dashboard para prevenir XSS.

---

## [1.10.0] - 2026-06-03

### Agregado

- Módulo de inventario completo: control de stock con alertas de stock bajo y registro de ajustes manuales
  (entrada/salida) con auditoría, solo para Administrador.

---

## [1.9.0] - 2026-05-23

### Agregado

- Módulo de auditoría: registro de operaciones sensibles (cambios de precio, rol, eliminaciones) con filtros de
  fecha, listado paginado y vista de detalle por evento, solo para Administrador.

---

## [1.8.0] - 2026-05-22

### Agregado

- Rate limiting en login: bloqueo de cuenta 15 minutos tras 5 intentos fallidos consecutivos.

### Corregido

- Carritos huérfanos del POS (sesiones abandonadas) se purgan correctamente al iniciar una nueva venta sin afectar
  al vendedor activo.
- Cancelar una venta ya no disparaba la validación del formulario de cliente.

---

## [1.7.0] - 2026-05-04

### Agregado

- Suite de tests PHPUnit (Unit + Integration con SQLite in-memory), CI en GitHub Actions y documentación de
  convenciones de testing en CLAUDE.md.

---

## [1.6.3] - 2026-04-30

### Agregado

- Opción "Recordarme" en login: auto-login persistente vía cookie segura con rotación de token, y expiración de
  sesión por inactividad configurable.

---

## [1.6.2] - 2026-04-29

### Refactorizado

- Reorganización de assets estáticos: vendors (AdminLTE, jQuery, plugins) movidos a subdirectorios `lib/`/`plugins/`
  explícitos en `public/css/` y `public/js/`.

---

## [1.6.1] - 2026-04-28

### Corregido

- Bug: el gráfico de compras del dashboard usaba la fecha de inserción del registro en vez de la fecha real de la
  compra, acumulando todo en el mes actual.

### Modificado

- Sidebar reescrito con detección automática de la ruta activa.

---

## [1.6.0] - 2026-04-11

### Agregado

- POS: creación de cliente inline sin salir del wizard de venta, con persistencia del cliente seleccionado ante
  recargas de página.

### Corregido

- Bug: el cálculo de cambio en el POS dejaba de funcionar por un error de JS que detenía la ejecución del script.

---

## [1.5.0] - 2026-04-10

### Agregado

- Flujo completo de restablecimiento de contraseña por email (SMTP vía PHPMailer), con modo desarrollo que muestra
  el link en pantalla.

---

## [1.4.1] - 2026-04-10

### Modificado

- Vistas de detalle de productos, compras y ventas rediseñadas con layout de dos columnas (imagen + métricas).
- Modales de proveedores extraídos a un partial reutilizable, con botón de ver detalle.

---

## [1.4.0] - 2026-04-09

### Modificado

- Refactor "fat model": lógica de validación, normalización de datos y generación de PDF (facturas, comprobantes de
  compra) movida de los controladores a los modelos y helpers dedicados.

### Eliminado

- Vista y ruta de detalle de usuario, redundante con el listado.

---

## [1.3.5] - 2026-04-07

### Agregado

- Página de perfil propio para todos los roles: edición de datos y cambio de contraseña.

---

## [1.3.4] - 2026-04-06

### Refactorizado

- POS rediseñado como wizard de 3 pasos (Cliente → Carrito → Pago) con barra de progreso y validación entre pasos.

---

## [1.3.3] - 2026-04-06

### Refactorizado

- Formularios de compras (crear/editar) rediseñados con panel lateral de resumen en tiempo real.

---

## [1.3.2] - 2026-04-06

### Refactorizado

- Formularios de productos (crear/editar) rediseñados con panel lateral de resumen y cálculo de margen en tiempo
  real.

---

## [1.3.1] - 2026-04-05

### Corregido

- Bug: el sidebar no se cerraba al tocar fuera en vista móvil por un `div` de overlay duplicado que impedía a
  AdminLTE inicializar su propio handler.

### Refactorizado

- Lógica de presentación del dashboard movida de la vista al controlador.

---

## [1.3.0] - 2026-04-04

### Agregado

- Dashboard rediseñado: KPIs por rol con variación vs. mes anterior y gráfico de barras de ventas/compras de los
  últimos 6 meses.

---

## [1.2.6] - 2026-04-04

### Refactorizado

- Módulo de clientes migrado al patrón modal + AJAX (sin páginas separadas de crear/editar), con validación de
  duplicados en tiempo real.

---

## [1.2.5] - 2026-04-04

### Refactorizado

- JS del módulo de ventas (incluido el POS) extraído de bloques inline a archivos dedicados y estandarizado con el
  resto del proyecto.

---

## [1.2.4] - 2026-04-04

### Refactorizado

- JS del módulo de compras extraído a archivos dedicados con validación jQuery Validate y DataTable estandarizado.

---

## [1.2.3] - 2026-04-04

### Cambiado

- DataTables de usuarios y productos estandarizados (exportación PDF/Excel con formato consistente en todo el
  sistema).

---

## [1.2.2] - 2026-04-04

### Cambiado

- Módulo de proveedores migrado al patrón modal + AJAX con validación de nombre único en tiempo real.

---

## [1.2.1] - 2026-04-03

### Agregado

- Sistema de assets por vista (`pageStyles`/`pageScripts`) para cargar CSS/JS específico de cada módulo sin tocar el
  layout global.
- Flujo de eliminación de productos con pre-verificación de referencias antes de confirmar.

---

## [1.2.0] - 2026-04-03

### Agregado

- Primera release lista para contribuciones externas: `CONTRIBUTING.md`, licencia MIT, skills de Claude Code para
  code review y commits.

---

## [1.1.8] - 2026-04-03

### Cambiado

- Módulo de categorías migrado al patrón modal + AJAX.

---

## [1.1.7] - 2026-04-02

### Cambiado

- Módulo de roles migrado al patrón modal + AJAX.

---

## [1.1.6] - 2026-04-02

### Cambiado

- Carga de plugins de frontend (DataTables, Select2, jQuery Validate) ahora es opcional por vista en vez de global,
  reduciendo peso de página.

---

## [1.1.5] - 2026-04-01

### Cambiado

- Directorio `views/layout/` renombrado a `views/layouts/` (convención plural); sidebar extraído a partial propio.

---

## [1.1.4] - 2026-04-01

### Agregado

- Validación de email único en tiempo real y vista previa en vivo en los formularios de usuarios.

---

## [1.1.3] - 2026-03-31

### Agregado

- Validación jQuery Validate y verificación de referencias antes de eliminar en el módulo de usuarios.

### Corregido

- Variable indefinida y escape incorrecto de comillas en las vistas de usuarios.

---

## [1.1.2] - 2026-03-31

### Agregado

- Utilitarios compartidos de SweetAlert2 (`ToastUtils`, `AlertUtils`) y rediseño del login.

### Eliminado

- `app/config.php` legacy, absorbido por `public/index.php`.

---

## [1.1.1] - 2026-03-30

### Agregado

- Vistas de error dedicadas (403/404/500).

### Corregido

- Redirecciones de error rotas por URLs incorrectas.

### Cambiado

- TCPDF gestionado vía Composer en lugar de copia manual en el repo.

---

## [1.1.0] - 2026-03-30

### Agregado

- Migración completa del sistema legacy a arquitectura MVC: Composer, Core (Router, Auth, Model, Middleware) y
  todos los módulos (usuarios, roles, categorías, proveedores, clientes, productos, compras, ventas).

### Eliminado

- Todo el código legacy pre-MVC (`app/controllers/`, vistas sueltas fuera de `views/`).

---

## [1.0.0] - 2026-03-24

### Agregado

- Schema y seeder inicial de base de datos con usuarios de prueba.
- Protección CSRF global y validación server-side en controladores críticos.

### Corregido

- SQL injection en múltiples controladores legacy (reemplazo de interpolación directa por placeholders).
- Subida de imágenes sin validación de tipo/tamaño.
- XSS almacenado por falta de `htmlspecialchars()` en varias vistas.
- Contraseñas mostradas en texto plano en formularios de usuarios.

[1.16.6]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.16.5...1.16.6
[1.16.5]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.16.4...1.16.5
[1.16.4]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.16.3...1.16.4
[1.16.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.16.2...1.16.3
[1.16.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.16.1...1.16.2
[1.16.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.16.0...1.16.1
[1.16.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.15.0...1.16.0
[1.15.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.14.3...1.15.0
[1.14.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.14.2...1.14.3
[1.14.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.14.1...1.14.2
[1.14.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.14.0...1.14.1
[1.14.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.13.0...1.14.0
[1.13.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.12.3...1.13.0
[1.12.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.12.2...1.12.3
[1.12.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.12.1...1.12.2
[1.12.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.12.0...1.12.1
[1.12.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.11.0...1.12.0
[1.11.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.10.0...1.11.0
[1.10.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.9.0...1.10.0
[1.9.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.8.0...1.9.0
[1.8.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.7.0...1.8.0
[1.7.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.6.3...1.7.0
[1.6.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.6.2...1.6.3
[1.6.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.3.5...1.4.0
[1.3.5]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.3.4...1.3.5
[1.3.4]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.3.3...1.3.4
[1.3.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.6...1.3.0
[1.2.6]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.5...1.2.6
[1.2.5]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.8...1.2.0
[1.1.8]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.7...1.1.8
[1.1.7]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.6...1.1.7
[1.1.6]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.5...1.1.6
[1.1.5]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/releases/tag/1.0.0
