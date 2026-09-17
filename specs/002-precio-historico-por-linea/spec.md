# Spec 002 — Precio histórico por línea de venta

**Status:** proposed
**Habilita:** spec 001 (devoluciones de ventas) — FR-3 de 001 consume el precio
persistido que introduce esta spec.

## Context and goal

Hoy `tb_carrito` solo guarda `id_producto` y `cantidad`. Cada vez que el sistema
muestra una venta ya registrada (`sales/show`, `sales/delete`, factura PDF) o
calcula sus subtotales, hace un JOIN en vivo contra `tb_almacen.precio_venta` y
usa el **precio actual del catálogo**, no el precio al que realmente se vendió.
`tb_ventas.total_pagado` sí conserva el total histórico correcto (se calcula y
persiste en `Sale::storeWithStock`), pero es un único escalar por venta: con dos
o más ítems no permite reconstruir el precio de cada línea.

Consecuencia hoy: si `precio_venta` de un producto cambia después de una venta,
la factura PDF y la vista de detalle de esa venta muestran importes que **no
cuadran con su propio `total_pagado`**. Es una inconsistencia latente ya
presente, independiente de devoluciones.

Esta spec persiste el precio unitario por línea en el momento de finalizar la
venta y hace que todas las lecturas usen ese valor. Es prerrequisito de las
devoluciones (spec 001), que necesitan saber a qué precio se vendió cada ítem
para calcular el monto devuelto.

## Users / actors

- **Vendedor / administrador** — ve el detalle de una venta y su factura PDF con
  los importes reales de esa venta.
- **Sistema** — al registrar una venta, guarda el precio unitario de cada línea.

## User stories

- U1: Como vendedor quiero que la factura PDF y el detalle de una venta muestren
  siempre los importes a los que se vendió, aunque el precio del producto haya
  cambiado después.
- U2: Como desarrollador de la feature de devoluciones quiero poder leer el
  precio unitario histórico de cada línea de una venta.

## Functional requirements (EARS acceptance criteria)

- FR-1: THE SYSTEM shall persistir, por cada línea de una venta, el precio
  unitario de venta vigente en el momento en que la venta se finaliza
  (`Sale::storeWithStock`), en una columna `DECIMAL(10,2) NULL` de la línea.
  `NULL` en una línea significa "carrito aún no finalizado" (o venta pre-feature
  sin backfillear); un valor es el precio congelado. Las lecturas de una línea
  ya finalizada nunca ven `NULL` (backfill, FR-5).
- FR-2: WHEN se finaliza una venta, THE SYSTEM shall calcular `total_pagado` como
  Σ (cantidad × precio unitario persistido de cada línea), de modo que el total
  de la venta y la suma de sus líneas coincidan por construcción.
- FR-3: WHEN el sistema muestra una venta ya registrada (detalle, página de
  eliminación, factura PDF), calcula sus subtotales
  (`Sale::findWithDetails`, `Sale::withSubtotals`, `Sale::computeInvoiceTotals`,
  `InvoicePdf`) o agrega cantidades/ingresos de ventas
  (`Product::getTopSelling`, `Report::topProducts`), THE SYSTEM shall usar el
  precio unitario persistido de cada línea, no `tb_almacen.precio_venta`
  actual.
- FR-4: THE SYSTEM shall mantener sin cambios el flujo de armado del carrito del
  POS (`addToCart` / `removeFromCart` / vista `sales/create`), que sigue
  mostrando el precio actual del catálogo mientras la venta no está finalizada.
- FR-5: THE SYSTEM shall poblar la columna nueva para todas las líneas de ventas
  ya registradas con el `tb_almacen.precio_venta` actual del producto como mejor
  aproximación disponible (backfill único), documentando que es una aproximación.
- FR-6: IF una línea de venta ya registrada referencia un producto que ya no
  existe en `tb_almacen` al momento del backfill, THEN THE SYSTEM shall dejar su
  precio unitario en `0.00` y el proceso de backfill shall continuar con el
  resto.
- FR-7: THE SYSTEM shall reflejar la columna nueva y su semántica en
  `database/schema.sql` y en `tests/fixtures/schema.sqlite.sql` con la
  equivalencia de tipos correspondiente.
- FR-8: THE SYSTEM shall dejar `tb_ventas.total_pagado` de las ventas ya
  registradas **sin recalcular**; sigue siendo el total histórico autoritativo
  aunque la suma de los precios backfilleados de sus líneas no coincida
  exactamente.

## Non-functional requirements

- Dinero: `DECIMAL(10,2)`, cálculo de totales en SQL, `number_format(…, 2)` solo
  en salida; sin `round()` intermedio (convención del proyecto).
- El backfill debe poder correrse una sola vez de forma idempotente (volver a
  correrlo no debe pisar precios ya poblados con un valor distinto).
- Sin cambio observable para el usuario en ventas cuyo precio de catálogo no
  cambió desde que se registraron (los importes mostrados son los mismos).
- Identificadores de código en inglés; nombre de columna en el estilo de
  `tb_carrito` (español, coherente con `cantidad`, `id_producto`).

## Edge cases

- Venta registrada **después** de esta feature cuyo producto luego cambia de
  precio → detalle y factura siguen mostrando el precio persistido (correcto).
- Venta registrada **antes** de esta feature: sus líneas quedan con el precio de
  catálogo al momento del backfill; puede diferir del real, y Σ(líneas) puede no
  dar exactamente `total_pagado` (FR-8). Aceptado como limitación del histórico.
- Producto con `precio_venta = 0` al momento de la venta o del backfill → línea
  con precio `0.00`, válido.
- Línea de carrito huérfana (sin venta finalizada) al momento del backfill → no
  se toca; solo se backfillean líneas de ventas ya finalizadas.
- Tests que usan SQLite in-memory → el schema SQLite debe tener la columna o los
  tests de ventas/reportes fallan (FR-7).

## Out of scope

- Persistir el precio en `addToCart` / congelar el precio al agregar al carrito
  (se decidió congelar al finalizar la venta).
- Reconciliar o recalcular `total_pagado` de ventas históricas (FR-8).
- Persistir también el costo (`precio_compra`) por línea de venta.
- Historial de cambios de precio de catálogo (ya cubierto por `price_change` en
  `tb_activity_log`; no es objeto de esta spec).
- Cambiar el precio mostrado en la vista `sales/create` del POS (FR-4).
- Migración equivalente para `tb_compras` (ya guarda `precio_compra` por fila).

## Completion criteria

- Columna nueva en `database/schema.sql` y `tests/fixtures/schema.sqlite.sql`.
- `Sale::storeWithStock` persiste el precio por línea y deriva `total_pagado` de
  esas líneas; test que verifica que Σ(líneas) == `total_pagado` en una venta
  multi-ítem.
- `Sale::findWithDetails`, `withSubtotals`, `computeInvoiceTotals`, `InvoicePdf`,
  `Product::getTopSelling` y `Report::topProducts` leen el precio persistido;
  test que registra una venta, cambia el `precio_venta` del producto, y verifica
  que el detalle, los subtotales y las agregaciones de ese top (cantidad e
  ingresos) de esa venta no cambian.
- Script/paso de backfill idempotente ejecutado sobre la BD; documentado en el
  CHANGELOG y en las guías de instalación/actualización.
- `composer test` verde (incluye la sincronización del schema SQLite).
- Demo manual: venta con 2 ítems → cambiar precio de catálogo de uno → abrir
  factura PDF y detalle → los importes son los originales y cuadran con el total.

## Open questions

Ninguna. La única decisión abierta (momento de congelar el precio) se resolvió:
al finalizar la venta (`store`).

## Clarifications

Fase de planificación (2026-09-07): FR-1 cambia de `DECIMAL(10,2) NOT NULL` a
`NULL`. `NOT NULL` obligaría a escribir precio en cada `addToCart` (= congelar al
agregar al carrito, descartado). `NULL` = línea no finalizada; las lecturas usan
`COALESCE(precio_unitario, tb_almacen.precio_venta)` → carrito en curso ve el
precio actual (FR-4), venta registrada ve el congelado (FR-3). Toca FR-1;
Edge cases y Out of scope ya lo contemplaban.

Revisión cruzada con spec 001 (2026-09-17): FR-3 se amplía a
`Product::getTopSelling` y `Report::topProducts`. Sin el cambio, esas
agregaciones seguían sumando con `tb_almacen.precio_venta` (precio actual) y el
neto de devoluciones de la spec 001 (venta neta) mezclaría precio histórico en
un lado y precio actual en el otro. Como no era un out-of-scope explícito, se
corrige antes de implementar (la 002 aún no está implementada). El neto del top
tendrá así la misma base de precio por línea en ambos lados.
