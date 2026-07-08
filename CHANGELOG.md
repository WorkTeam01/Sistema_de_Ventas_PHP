# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

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

[Unreleased]: https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/compare/1.14.0...HEAD
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
