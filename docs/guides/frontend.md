# Frontend y vistas

> Convenciones de JavaScript, vistas MVC, layouts, accesibilidad y lecciones de Bootstrap/DataTables. Reglas clave resumidas en [AGENTS.md](../../AGENTS.md).

## JavaScript / Frontend

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

## Vistas MVC

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

## Lecciones de frontend (Bootstrap / DataTables)

- Un `<button>` dentro de un `.input-group` (flex `align-items:stretch`) no se encoge igual que un `<div>` hermano. Envuélvelo en un `<div>` tipo `.input-group-text` y resetea el botón (`all:unset; display:flex; align-items:center; justify-content:center; width:100%; height:100%`). Para el área táctil de 44px no pongas `min-height`/`min-width` en la caja visible: usa un `::before` absoluto con `width/height: max(100%, 44px)`.
- No parches una regla de un selector base compartido (`.form-control`, `.btn`) para un síntoma local. Compara `getComputedStyle` con un hermano que sí se vea bien y acota el fix al selector del elemento roto.
- Los tooltips de Bootstrap se pierden al paginar/ordenar/buscar en DataTables (las filas se reemplazan). Inicializa en un handler delegado `$(document).on('draw.dt', …)` **sin** `tooltip('dispose')` previo, comprobando `!$(this).data('bs.tooltip')`; sube la versión de cache-busting tras cambiar JS core.
- Una plantilla HTML oculta con la misma clase que los items clonados rompe cualquier selector global por esa clase (conteos, guards): escopa los selectores al contenedor real (`'#contenedor .item'`).
