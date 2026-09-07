# Tasks — Spec 002 (precio histórico por línea de venta)

Tareas < 30 min, ordenadas por dependencia. Cada una: sus FR, checkbox y una
línea "Done when:" verificable.

- [ ] T1. Añadir `precio_unitario DECIMAL(10,2) NULL` a `tb_carrito` en
      `database/schema.sql` (después de `cantidad`). (FR-1, FR-7)
      Done when: `grep -n precio_unitario database/schema.sql` muestra la columna
      dentro del `CREATE TABLE ... tb_carrito`.

- [ ] T2. Añadir `precio_unitario NUMERIC` a `tb_carrito` en
      `tests/fixtures/schema.sqlite.sql` (después de `cantidad`). (FR-7)
      Done when: `grep -n precio_unitario tests/fixtures/schema.sqlite.sql`
      muestra la columna y `composer test` sigue verde (schema carga sin error).

- [ ] T3. Crear `database/migrations/006_carrito_precio_unitario.sql` con el
      `ALTER TABLE` + los dos `UPDATE` de backfill idempotente del plan (§A3).
      (FR-5, FR-6, FR-8)
      Done when: el archivo existe; correr su contenido dos veces seguidas sobre
      una BD MySQL de prueba no cambia filas en la segunda corrida
      (`ROW_COUNT() = 0`) y no toca `tb_ventas.total_pagado`.

- [ ] T4. En `Sale::storeWithStock`, antes del INSERT de cabecera: `UPDATE
    tb_carrito SET precio_unitario = (SELECT precio_venta FROM tb_almacen WHERE
    id_producto = tb_carrito.id_producto) WHERE nro_venta = ?`. (FR-1)
      Done when: test de integración crea carrito de 1 ítem, llama
      `storeWithStock`, y `SELECT precio_unitario FROM tb_carrito WHERE nro_venta`
      devuelve el `precio_venta` del producto (no NULL).

- [ ] T5. En `Sale::storeWithStock`, reemplazar el cálculo de `$totalReal` por
      `SELECT SUM(cantidad * precio_unitario) FROM tb_carrito WHERE nro_venta = ?`
      (tras el UPDATE de T4). (FR-2)
      Done when: test de integración con carrito de 2 ítems de precios distintos
      afirma `tb_ventas.total_pagado == SUM(cantidad * precio_unitario)` de esa
      venta.

- [ ] T6. En `Sale::findWithDetails`, cambiar `al.precio_venta` por
      `car.precio_unitario AS precio_venta` en el SELECT de ítems. (FR-3)
      Done when: test registra una venta, hace `UPDATE tb_almacen SET
    precio_venta` a otro valor para un producto, y `findWithDetails` devuelve
      `items[*].precio_venta` = el valor original congelado.

- [ ] T7. En `CartItem::getByNroVenta`, cambiar `al.precio_venta` por
      `COALESCE(car.precio_unitario, al.precio_venta) AS precio_venta`. (FR-4)
      Done when: test añade ítems al carrito sin `store` y `getByNroVenta`
      devuelve `precio_venta` = precio de catálogo actual; y para una venta
      registrada devuelve el congelado.

- [ ] T8. Test de regresión de la factura/subtotales (FR-3): venta de 2 ítems →
      cambiar `precio_venta` de uno → afirmar que `withSubtotals` y
      `computeInvoiceTotals` sobre `findWithDetails($id)['items']` dan los mismos
      subtotales y total que antes del cambio. (FR-3)
      Done when: el test existe en `tests/Integration/Models/` y pasa.

- [ ] T9. Test del backfill (FR-5/6/8): insertar filas de `tb_carrito` con
      `precio_unitario` NULL para una venta finalizada, ejecutar el bloque de
      backfill (adaptado a SQLite), afirmar que quedan pobladas con el catálogo,
      que una segunda ejecución no cambia nada, y que `total_pagado` no se tocó.
      (FR-5, FR-6, FR-8)
      Done when: el test existe y pasa.

- [ ] T10. `composer test` completo verde (Unit + Integration). (FR-7 y regresión
      global)
      Done when: `composer test` termina sin fallos ni errores.

## Feature closeout

_Checkboxes fuera de la numeración `Tn` — no se implementan vía `/sdd:implement`._

- [ ] Verificar todos los FR (`/sdd:validate` → `specs/002-precio-historico-por-linea/validation.md`).
- [ ] Mover la feature a `Hecho ✅` en `docs/roadmap.md` enlazando esta carpeta.
- [ ] `CHANGELOG.md`: entrada en `[Unreleased]` — columna `tb_carrito.precio_unitario`,
      cambio de comportamiento en factura/detalle de venta (ahora precio histórico),
      y el paso de migración `006_*.sql`.
- [ ] Guía de actualización: documentar que las BD existentes deben correr
      `database/migrations/006_carrito_precio_unitario.sql` (mismo flujo que
      `001..005`).
- [ ] `AGENTS.md` §Base de Datos: actualizar la firma de `tb_carrito`
      (`+ precio_unitario`) y la nota de `Sale::storeWithStock` / `total_pagado`
      (ahora Σ de precios por línea persistidos, no del catálogo actual).
