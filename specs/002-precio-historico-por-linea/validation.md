# Validación — Spec 002: Precio histórico por línea de venta

**Fecha:** 2026-09-17
**Suite:** `composer test` → 277 tests, 508 assertions, 0 fallos

## FR por FR

| FR   | Descripción                                                  | Evidencia                                                                                                                                                                                                                                                                                                                                                                                                                         | Veredicto |
| ---- | ------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| FR-1 | Columna `precio_unitario DECIMAL(10,2) NULL` en `tb_carrito` | T1: `database/schema.sql` línea 42; T2: `tests/fixtures/schema.sqlite.sql`; T3: migración `006_carrito_precio_unitario.sql` aplicada                                                                                                                                                                                                                                                                                              | ✅ Pass   |
| FR-2 | `total_pagado` = Σ(cantidad × precio_unitario)               | T5: `Sale::storeWithStock` usa `SELECT SUM(cantidad * precio_unitario)`; test `test_storeWithStock_total_pagado_matches_sum_of_precio_unitario`                                                                                                                                                                                                                                                                                   | ✅ Pass   |
| FR-3 | Lecturas de venta registrada usan precio persistido | T6: `Sale::findWithDetails` usa `COALESCE(car.precio_unitario, al.precio_venta) AS precio_venta`; T7: `CartItem::getByNroVenta` usa `COALESCE(car.precio_unitario, al.precio_venta)`; T11: `Product::getTopSelling` y `Report::topProducts` usan `COALESCE`; tests `test_findWithDetails_returns_frozen_price_after_catalog_change`, `test_getByNroVenta_returns_frozen_price_for_finalized_sale`, `test_top_selling_uses_frozen_price_after_catalog_change` | ✅ Pass |
| FR-4 | Carrito en curso muestra precio de catálogo actual           | T7: `COALESCE(car.precio_unitario, al.precio_venta)` → NULL en carrito activo → catálogo; test `test_getByNroVenta_returns_catalog_price_when_cart_is_in_progress`                                                                                                                                                                                                                                                                | ✅ Pass   |
| FR-5 | Backfill de ventas ya registradas con precio de catálogo     | T9: test `test_backfill_populates_null_precio_unitario_and_is_idempotent`; migración 006 ejecutada en BD de desarrollo (33 líneas backfilleadas)                                                                                                                                                                                                                                                                                  | ✅ Pass   |
| FR-6 | Producto eliminado → backfill pone 0.00                      | Migración 006 línea 22-25: `UPDATE ... SET precio_unitario = 0.00 WHERE precio_unitario IS NULL`                                                                                                                                                                                                                                                                                                                                  | ✅ Pass   |
| FR-7 | Schema en `schema.sql` y `schema.sqlite.sql`                 | T1: `database/schema.sql` línea 42; T2: `tests/fixtures/schema.sqlite.sql`                                                                                                                                                                                                                                                                                                                                                        | ✅ Pass   |
| FR-8 | `total_pagado` no se recalcula                               | T9: test verifica `total_pagado` intacto tras backfill                                                                                                                                                                                                                                                                                                                                                                            | ✅ Pass   |

## Tests de regresión

| Test                                                                | Cubre                                     |
| ------------------------------------------------------------------- | ----------------------------------------- |
| `test_storeWithStock_persists_precio_unitario_in_cart`              | T4 — precio_unitario poblado al finalizar |
| `test_storeWithStock_total_pagado_matches_sum_of_precio_unitario`   | T5 — total = Σlíneas                      |
| `test_findWithDetails_returns_frozen_price_after_catalog_change`    | T6 — findWithDetails congelado            |
| `test_getByNroVenta_returns_catalog_price_when_cart_is_in_progress` | T7 — carrito activo → catálogo            |
| `test_getByNroVenta_returns_frozen_price_for_finalized_sale`        | T7 — venta finalizada → congelado         |
| `test_invoice_subtotals_use_frozen_prices_after_catalog_change`     | T8 — subtotales/totales estables          |
| `test_backfill_populates_null_precio_unitario_and_is_idempotent`    | T9 — backfill idempotente                 |
| `test_top_selling_uses_frozen_price_after_catalog_change`           | T11 — agregaciones top products           |

## Veredicto general

✅ **Todos los FR pasan.** La spec 002 queda validada.
