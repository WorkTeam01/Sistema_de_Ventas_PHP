# Spec 001 — Devoluciones de ventas

**Status:** proposed
**Depende de:** spec 002 (precio histórico por línea) — FR-3 consume el precio
unitario persistido que introduce 002. Se implementa 002 primero.

## Context and goal

Hoy el módulo de ventas solo puede **eliminar una venta completa**
(`SaleController::destroy`), lo que revierte todo el stock y borra el registro.
No existe forma de registrar que un cliente devolvió **parte** de lo que compró
(2 de 5 unidades, un ítem de varios) manteniendo la venta original en el
historial. Esta feature agrega devoluciones totales o parciales sobre ventas ya
registradas, con reingreso de stock, trazabilidad en el registro de actividad y
efecto neto en los reportes — el comportamiento estándar de un POS de retail.

**Devolución vs. eliminación de venta (frontera):** _eliminar_ una venta
(`destroy`, ya existente) es para una venta que nunca debió registrarse — error
en el punto de venta corregido de inmediato: borra el registro. Una _devolución_
es para una venta legítima cuya mercadería volvió después: la venta permanece en
el historial y en su comprobante; la devolución es un registro aparte. Una
"devolución total" devuelve todas las unidades de todas las líneas, pero la venta
y su factura siguen existiendo. Una venta con al menos una devolución ya no se
puede eliminar (FR-13).

## Definiciones

- **Cantidad pendiente de devolver de un ítem** = cantidad vendida de ese ítem en
  la venta − suma de cantidades ya devueltas de ese ítem en devoluciones previas
  de la misma venta.
- **Precio unitario original** = precio de venta unitario vigente en el momento
  en que se registró la venta, persistido por línea (lo introduce spec 002).
- **Monto devuelto** = Σ (cantidad devuelta × precio unitario original) por ítem
  de la devolución. Es informativo (reporte y auditoría); no dispara ningún
  reembolso de dinero.

## Users / actors

- **Vendedor** — registra la devolución de una venta propia.
- **Administrador / rol con `view_sales_all`** — registra la devolución de
  cualquier venta y consulta todas las devoluciones.

## User stories

- U1: Como vendedor quiero registrar que un cliente devolvió algunos ítems de una
  venta para que el stock vuelva a estar disponible y el registro refleje lo
  realmente vendido.
- U2: Como administrador quiero ver el historial de devoluciones con su motivo
  para auditar mermas y detectar patrones.
- U3: Como administrador quiero que los reportes y KPIs de ventas descuenten las
  devoluciones para que reflejen la venta neta.

## Functional requirements (EARS acceptance criteria)

### Registro

- FR-1: WHEN un usuario con el permiso `manage_returns` abre una venta existente,
  el sistema le presenta todos los ítems de la venta con su cantidad pendiente de
  devolver, un campo de cantidad por ítem (0 a la cantidad pendiente) y un campo
  de motivo único para la devolución; WHEN confirma, THE SYSTEM shall registrar
  una devolución asociada a esa venta con la fecha, el usuario que la registra,
  el motivo y las líneas cuya cantidad es ≥ 1 (las de cantidad 0 se ignoran).
- FR-2: WHEN se registra una devolución, THE SYSTEM shall incrementar
  `tb_almacen.stock` de cada producto devuelto en la cantidad devuelta, de forma
  atómica dentro de la misma transacción que persiste la devolución.
- FR-3: WHEN se registra una devolución, THE SYSTEM shall calcular el monto
  devuelto como Σ (cantidad devuelta × precio unitario original de ese ítem en la
  venta — spec 002) evaluado en SQL, y almacenarlo en la devolución como
  `DECIMAL(10,2)`.
- FR-4: THE SYSTEM shall no alterar el registro almacenado de la venta original
  (número, total, líneas, cantidades); la devolución es un registro
  independiente. La vista de detalle de la venta sí puede mostrar sus
  devoluciones (FR-14) y una venta con devoluciones no se puede eliminar (FR-13).
- FR-5: WHILE una venta tiene ítems con cantidad pendiente de devolver > 0, THE
  SYSTEM shall permitir registrar devoluciones adicionales sobre esa venta,
  acumulando las cantidades devueltas por ítem.
- FR-6: WHEN valida las cantidades de una devolución, THE SYSTEM shall calcular la
  cantidad pendiente de cada ítem desde la base de datos dentro de la misma
  transacción que registra la devolución, de modo que dos devoluciones
  concurrentes sobre la misma venta no puedan superar en conjunto lo vendido.
- FR-7: IF la cantidad a devolver de algún ítem supera su cantidad pendiente,
  THEN THE SYSTEM shall rechazar la devolución completa (ninguna línea se aplica)
  y reportar el conflicto sin alterar stock ni registros. El rechazo total es
  deliberado: la devolución es atómica.
- FR-8: IF se intenta registrar una devolución sin ninguna línea con cantidad
  ≥ 1, THEN THE SYSTEM shall rechazar la operación e indicar que no hay nada que
  devolver.
- FR-9: IF se intenta registrar una devolución sin motivo, THEN THE SYSTEM shall
  rechazar la operación e indicar que el motivo es obligatorio.
- FR-10: IF se intenta registrar una devolución sobre una venta inexistente o ya
  eliminada, THEN THE SYSTEM shall responder 404 / venta no encontrada.
- FR-11: THE SYSTEM shall generar para cada devolución un número correlativo
  `nro_devolucion` único, resistente a registros concurrentes.
- FR-12: THE SYSTEM shall registrar cada devolución en `tb_activity_log` con
  acción `create`, entidad `sale_return`, el `id` de la devolución, el usuario, y
  en `datos_nuevos` el número de venta, el motivo, el monto devuelto y el detalle
  de ítems y cantidades. Las devoluciones rechazadas (FR-7/8/9) no se registran.

### Consulta y autorización

- FR-13: IF se intenta eliminar una venta que tiene al menos una devolución
  registrada, THEN THE SYSTEM shall bloquear la eliminación (patrón
  `isReferenced()`, hoy inexistente en `Sale` — se crea) e indicar que existen
  devoluciones asociadas.
- FR-14: THE SYSTEM shall mostrar en la vista de detalle de una venta sus
  devoluciones asociadas y la cantidad pendiente de devolver por ítem.
- FR-15: WHERE el usuario no tiene `view_sales_all`, THE SYSTEM shall permitirle
  registrar y ver devoluciones únicamente de ventas cuyo `id_usuario` sea el
  suyo, tanto en el listado de devoluciones como en el detalle y al registrar. IF
  `id_usuario` de la venta es NULL (vendedor eliminado), THEN solo un usuario con
  `view_sales_all` puede devolverla.
- FR-16: THE SYSTEM shall requerir `manage_returns` para registrar una devolución
  y `view_sales` para consultarlas.
- FR-17: THE SYSTEM shall exponer un listado de devoluciones en su propia ruta
  (scopeado por FR-15) con el patrón DataTables + export (PDF/Excel/CSV/Imprimir/
  columnas) del resto de listados, y una vista de detalle por devolución (venta
  de origen, `nro_devolucion`, ítems, cantidades, monto, motivo, usuario, fecha).

### Reglas fijas

- FR-18: THE SYSTEM shall aceptar devoluciones sobre una venta sin importar
  cuánto tiempo pasó desde que se registró (sin ventana temporal).
- FR-19: THE SYSTEM shall reingresar al stock la totalidad de la mercadería
  devuelta; no existe en esta iteración la opción de marcar un ítem como no
  revendible.
- FR-20: THE SYSTEM shall tratar el monto devuelto como dato informativo
  (reporte/auditoría); no genera reembolso de dinero, reversión de pago ni
  movimiento de caja.

### Efecto en reportes y KPIs (venta neta)

- FR-21: THE SYSTEM shall descontar las cantidades y montos devueltos, imputados
  al período en que se registró la **devolución** (su `fecha`), de las siguientes
  agregaciones, de modo que reflejen la venta neta del rango consultado:
  - Dashboard: `Sale::totalCurrentMonth`, `totalPreviousMonth`, `todaySummary`,
    `totalsByMonth`, `Product::getTopSelling`.
  - Reportes: `Report::salesByPeriod`, `salesTotals`, `salesSummary`,
    `topProducts`, `clientsByPeriod`.
- FR-22: THE SYSTEM shall añadir al listado de ventas un indicador de que la
  venta tiene devoluciones (p. ej. columna / badge "devuelto"), sin cambiar el
  total mostrado de la venta.

## Non-functional requirements

- Seguridad: CSRF en el formulario de registro; cantidades pendientes, monto y
  validación se calculan server-side desde la BD, nunca desde el POST
  (constitución §6). El POST solo aporta las cantidades solicitadas por ítem.
- Atomicidad: validación + stock + persistencia de la devolución + log ocurren en
  una única transacción con rollback ante cualquier error.
- Dinero: cálculos monetarios en SQL con `DECIMAL(10,2)`, formateo con
  `number_format(…, 2)` y `APP_CURRENCY_SYMBOL` solo en la capa de salida; nunca
  `round()` intermedio (convención de facto del proyecto).
- Idioma: strings de usuario en español; identificadores PHP y slug de permiso en
  inglés (`SaleReturn`, `manage_returns`); nombres de tabla en español con
  prefijo `tb_` (`tb_devoluciones`, …) siguiendo la convención real del código.
- Accesibilidad: vistas nuevas sin violaciones de axe-core en modo claro y oscuro.
- Métodos de controlador: si se usa un verbo fuera del estándar de 6 métodos, el
  `plan.md` debe justificarlo y actualizar la lista de auxiliares de `AGENTS.md`
  (precedentes: `InventoryController::storeAdjustment`,
  `RoleController::permisos/syncPermisos`, `SaleController::addToCart`).

## Edge cases

- Venta de un solo ítem con devolución total → la venta queda "totalmente
  devuelta" pero permanece en el historial, en el listado de ventas (con el
  indicador de FR-22) y su factura PDF sigue mostrando los montos originales
  (comportamiento correcto de un comprobante emitido).
- Devoluciones parciales sucesivas que agotan lo vendido → la siguiente
  devolución sobre esa venta se rechaza por FR-7.
- Dos usuarios devolviendo la misma venta a la vez → el segundo ve la cantidad
  pendiente ya actualizada (FR-6, validación transaccional).
- Precio de venta cambiado tras la venta → el monto devuelto usa el precio
  original persistido por línea (FR-3 / spec 002), no el precio actual del
  catálogo.
- Ítem cuyo precio original era 0 (promoción) → se puede devolver; su aporte al
  monto devuelto es 0.
- Producto eliminado del catálogo tras la venta → la devolución se registra
  igual; si la fila del producto ya no existe, el reingreso de stock de esa línea
  se omite y la devolución lo deja constar (el resto de líneas se procesan).
- Venta con `id_usuario` NULL (vendedor eliminado) → solo `view_sales_all` puede
  devolverla (FR-15).
- Devolución mal cargada → no hay edición ni anulación en v1 (irreversible); la
  única compensación es un ajuste manual de stock en el módulo de inventario y
  dejar nota del error.
- `nro_devolucion` bajo concurrencia → FR-11 exige unicidad garantizada (el plan
  elige: UNIQUE + retry, o derivar del AUTO_INCREMENT).

## Out of scope

- Snapshot de precio unitario por línea de venta y refactor de `InvoicePdf` /
  `Sale::findWithDetails` → es **spec 002**, dependencia de esta.
- Fix del hueco de scoping en `SaleController::destroy` (un vendedor puede
  eliminar por POST una venta ajena): no cambia esquema ni agrega permiso ni
  regla de negocio → va como `fix(sales):` directo, **fuera de specs**, antes de
  implementar esta feature.
- Nota de crédito / comprobante PDF de la devolución (la vista de detalle basta).
- Reembolso de dinero real, medios de pago del reembolso, integración con caja
  (se resuelve con los módulos de arqueo de caja / formas de pago).
- Fila en `tb_ajustes_stock` por la devolución: esa tabla es para ajustes
  manuales de inventario; ventas y compras ya mueven stock sin ella. La
  trazabilidad de la devolución es `tb_devoluciones` + activity log.
- Editar o anular una devolución ya registrada (irreversible en v1).
- Devoluciones con cambio de producto (devuelve A, se lleva B).
- Marca de mercadería dañada / no revendible (v1 siempre reingresa, FR-19).
- Corregir la asimetría preexistente de scoping por usuario en
  `Report::topProducts` / `clientsByPeriod` (no aceptan `$userId` mientras
  `salesByPeriod`/`salesTotals` sí): el neto de devoluciones se aplica igual, pero
  emparejar el scoping es otra tarea.
- Ventana temporal configurable (v1 sin límite, FR-18).
- Aprobación de la devolución por un segundo usuario (workflow).
- Atomicidad real de `Sale::nextNumber` / `Purchase::nextNumber` (fuera de esta
  feature; `nro_devolucion` sí nace atómico por FR-11).

## Completion criteria

- Todos los FR con tests que pasan: modelo de devoluciones (cálculo de monto,
  cantidad pendiente, atomicidad stock+registro, rechazo total FR-7), `Sale`
  (nuevo `isReferenced()`), y las 10 agregaciones de reportes/KPIs netas de
  devoluciones (FR-21).
- Slug `manage_returns` sembrado en `schema.sql` + `seeder.sql` (catálogo +
  asignación al rol Administrador), y añadido a la lista de slugs de `AGENTS.md`.
- Acción/entidad `sale_return` añadidas a los enums documentados de
  `tb_activity_log` en `AGENTS.md` y labels nuevos en `ActivityLogRenderer`.
- Demo manual: registrar una devolución parcial, verificar reingreso de stock,
  verla en su listado (con export) y en el detalle de la venta, y confirmar que
  el reporte de ventas y el KPI del mes reflejan la venta neta en el período de
  la devolución.
- Sin violaciones de axe-core (claro y oscuro) en las vistas nuevas.

## Clarifications

Ronda de especificación (2026-09-07):

- Ventana temporal: sin límite → FR-18.
- Reingreso de stock: siempre reingresa en v1, sin marca de dañado → FR-19.
- Monto devuelto: contra precio unitario original de la venta → FR-3 (dependencia
  de spec 002).
- Permiso de lectura: reutilizar `view_sales`; slug nuevo solo para registrar
  (`manage_returns`) → FR-16.

Ronda de clarificación / QA (2026-09-07) — resueltos con análisis del código:

1. Contrato del formulario (amb. 1.1): todos los ítems se muestran con su
   pendiente; cantidad 0..pendiente por ítem; las de 0 se ignoran; ≥1 línea
   obligatoria → FR-1, FR-8.
2. Precio histórico por línea (amb. 1.2): hoy no existe. Se separa a **spec 002**
   (con backfill + refactor de factura); 001 lo consume → FR-3, "Depende de".
3. Término "cantidad pendiente" (amb. 1.3): definido en § Definiciones.
4. Valor de entidad/acción del log (amb. 1.4): `create` / `sale_return`; enums de
   `AGENTS.md` y labels de `ActivityLogRenderer` a actualizar → FR-12 + Completion.
5. Imputación temporal del neto (amb. 1.5): la devolución se imputa al período en
   que se **registró la devolución** (su `fecha`), no al de la venta original →
   FR-21.
6. Ruta del listado (amb. 1.6): listado propio scopeado + devoluciones también
   visibles en el detalle de la venta → FR-17, FR-14.
7. Monto informativo (amb. 1.7): explícito, no dispara reembolso → FR-20.
8. Frontera devolución vs. eliminar venta (amb. 1.8): definida en § Context.
9. Motivo por devolución, no por ítem (amb. 1.9): explícito → FR-1.
10. "Sin modificar" acotado (contrad. 2.1): FR-4 se limita al registro
    almacenado; presentación y operaciones permitidas sí cambian.
11. Usuario de la venta vs. de la devolución (contrad. 2.2): la autorización mira
    el `id_usuario` de la **venta**; la devolución además guarda quién la
    registró → FR-15.
12. Rechazo total en FR-7 (contrad. 2.3): confirmado deliberado (atomicidad).
13. Concurrencia en la cantidad pendiente (edge 3.1): validación transaccional
    desde BD → FR-6.
14. Precio 0 (edge 3.2): devolución válida, aporte 0 → Edge cases.
15. Redondeo (edge 3.3): SQL `DECIMAL(10,2)` + `number_format` en salida → NFR.
16. `tb_ajustes_stock` (edge 3.4): la devolución NO crea fila ahí; escribe
    `stock = stock + ?` directo en su transacción (evita la transacción anidada
    de `StockAdjustment::register`) → Out of scope + FR-2.
17. Dashboard/KPIs (edge 3.5): FR-21 enumera las 10 agregaciones (dashboard +
    reportes), no solo los 3 reportes originales.
18. Export en el listado (edge 3.6): patrón DataTables + export universal → FR-17.
19. Log de devoluciones fallidas (edge 3.7): no se registran (consistente con el
    proyecto) → FR-12.
20. `nro_devolucion` (edge 3.8): correlativo único resistente a concurrencia →
    FR-11.
21. Venta totalmente devuelta (edge 3.9): sigue en el listado con indicador
    (FR-22); la factura PDF no cambia.
22. Sin camino de corrección (edge 3.10): aceptado, irreversible en v1 → Edge
    cases + Out of scope.
23. §5 métodos de controlador (const. 4.1): el plan justifica el verbo si lo hay;
    hay precedentes → NFR.
24. §1 no contabilidad (const. 4.2): monto informativo (item 7); sin ledger ni
    saldo de cliente → Out of scope.
25. §9 identificadores (const. 4.3): `tb_devoluciones` (español, convención de
    tablas) + `manage_returns` / `SaleReturn` (inglés) → NFR.
26. Bug preexistente en `SaleController::destroy` sin chequeo de scoping: NO entra
    en esta feature — es un `fix(sales):` directo (no cambia esquema/permiso/
    regla) → Out of scope, se hace antes de implementar 001.
