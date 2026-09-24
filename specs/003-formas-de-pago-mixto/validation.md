# Validación — Spec 003: Formas de pago + pago mixto

**Fecha:** 2026-09-23
**Suite:** `composer test` → 345 tests, 759 assertions, 15 skips esperados (MariaDB), 0 fallos

## FR por FR

| FR   | Descripción | Evidencia | Veredicto |
| ---- | ----------- | --------- | --------- |
| FR-1 | CRUD del catálogo con permiso `manage_payment_methods` | `PaymentMethodRepositoryTest` (create único, `nameExists`); UI modal+AJAX `/payment-methods` (store/create OK en smoke); rutas en `routes/web.php` con `can:manage_payment_methods`; `Controller::check()` en `$claves` | ✅ Pass |
| FR-2 | Bloqueo de eliminación si está referenciado | `PaymentMethodRepositoryTest::test_isReferenced_is_true_when_method_has_at_least_one_payment`; smoke: `POST /payment-methods/delete` id=1 → `"No se puede eliminar… Desactívalo en su lugar."` | ✅ Pass |
| FR-3 | Método desactivado oculto del POS; pagos históricos intactos | `test_deactivating_method_hides_it_from_active` + `test_active_returns_only_enabled_methods`; smoke: `update` con `activo=0` persiste en BD | ✅ Pass |
| FR-4 | Catálogo sembrado: Efectivo, Tarjeta, Transferencia bancaria, QR | `schema.sql`, `seeder.sql`, migración `008` (INSERT…WHERE NOT EXISTS ×4); BD real: 4 métodos tras migración ×2 | ✅ Pass |
| FR-5 | Paso Cobro: total + fila por método activo; `referencia`/`detalle` solo en no efectivo | `views/sales/create.php` (`.pago-row[data-tipo]`, campos condicionales); `SaleController::create()` pasa `metodos_activos`; smoke POS T14–T16 | ✅ Pass |
| FR-6 | Validación suma ≥ total server-side en la transacción | `SalePaymentRepositoryTest::test_storeWithStock_exact_payment_ok_without_vuelto`; `Sale::storeWithStock` calcula total y suma en BD | ✅ Pass |
| FR-7 | Suma < total → rechazo con faltante, sin venta ni pagos | `test_storeWithStock_faltante_rejects_without_sale_or_payments`; smoke: toast `Faltan Bs 3,199.00` sin crear venta | ✅ Pass |
| FR-8 | Exceso sin línea efectivo → rechazo | `test_storeWithStock_exceso_sin_efectivo_rejects`; smoke: toast `El exceso sobre el total solo se admite en efectivo.` | ✅ Pass |
| FR-9 | Exceso con efectivo → vuelto = suma − total; no se persiste | `test_storeWithStock_efectivo_exceso_returns_vuelto_not_persisted`; `SalePaymentVueltoForTest`; smoke venta 31: `monto=3250`, `total_pagado=3200`, badge/PDF `Vuelto: Bs 50.00` | ✅ Pass |
| FR-10 | Una línea por método con monto > 0 en la misma transacción | `test_storeWithStock_mixed_payment_persists_one_line_per_method`; `test_storeWithStock_same_method_twice_sums_into_one_line`; UNIQUE `(id_venta,id_metodo_pago)` | ✅ Pass |
| FR-11 | Sin métodos activos → bloquear venta | `test_storeWithStock_sin_metodos_when_catalog_empty_or_all_deactivated`; smoke: `#alert-sin-metodos`, botón `disabled`, 0 filas | ✅ Pass |
| FR-12 | Detalle de venta con desglose + vuelto | `findWithDetails` + `views/sales/show.php` tarjeta "Pagos"; smoke: venta 30 (2 líneas), 31 (vuelto) | ✅ Pass |
| FR-13 | Factura PDF con desglose + vuelto | `InvoicePdf` sección "Pagos"; smoke: PDF 30 Efectivo+Tarjeta, PDF 31 vuelto 50 | ✅ Pass |
| FR-14 | Estado vacío "—" en detalle y PDF | `test_findWithDetails_returns_empty_payments_and_zero_vuelto_for_historical_sale`; smoke con `tb_pagos` vacío en venta 29: card `—` y PDF `—` | ✅ Pass |
| FR-15 | Backfill idempotente opcional | `test_backfill_is_idempotent_and_leaves_existing_lines_intact` (SQL extraído de `008`); smoke real: migración 008 ×2 → `pagos=32`, `dup_venta_metodo=0`; backfill ×2 sobre venta 1 → 1 línea, monto = total | ✅ Pass |
| FR-16 | `manage_sales` en cobro; `manage_payment_methods` en catálogo | Rutas `/sales/*` y `/payment-methods/*` con los `can:`; seeder + migración 007/008; smoke: vendedor sin permiso → 403 al catálogo | ✅ Pass |
| FR-17 | Pagos informativos; sin reversión en devoluciones ni movimiento de caja | Devoluciones (spec 001 FR-20) no tocan `tb_pagos`; no hay endpoint de reembolso ni arqueo en esta spec | ✅ Pass |

## Criterios de completitud

- [x] Todos los FR con tests que pasan (matriz de cobro + `vueltoFor` + backfill ×2) — `composer test` 345 OK.
- [x] Permiso `manage_payment_methods` en `schema.sql` + `seeder.sql` y en la lista de slugs de `AGENTS.md`.
- [x] Tablas documentadas en `AGENTS.md` y en `tests/fixtures/schema.sqlite.sql` (traducción MySQL→SQLite).
- [x] Demo manual: pago mixto, vuelto, desglose detalle + PDF, catálogo (crear / desactivar / bloqueo de eliminación), backfill ×2 sin duplicar.
- [x] Sin violaciones axe-core (claro y oscuro) en paso Cobro, `sales/show.php` y catálogo — **2026-09-23**: axe-core 4.13.0 (Playwright chromium, admin autenticado), tags `wcag2a/aa`, `wcag21a/aa`, `best-practice`; 6/6 PASS (0 violaciones):
  | Vista | light | dark |
  | ----- | ----- | ---- |
  | POS paso Cobro (`/sales/create` + `#pane-pago`) | 0 viol / 46 passes / 2 incomplete | 0 / 46 / 2 |
  | Detalle venta (`/sales/show/30`) | 0 / 43 / 0 | 0 / 43 / 0 |
  | Catálogo (`/payment-methods`) | 0 / 47 / 2 | 0 / 47 / 2 |
  Evidencia JSON: `specs/003-formas-de-pago-mixto/evidence/` (`summary.json` con `total_violations: 0` + un JSON por vista/tema). Dark mode = `body.dark-mode` en AdminLTE.

## Veredicto

**Spec cumplida y cerrada** — 17/17 FR con evidencia; criterio axe-core completado
(6/6 escaneos con 0 violaciones en claro y oscuro sobre paso Cobro, `sales/show.php`
y catálogo de métodos de pago).

## Notas

- 15 skips = tests `*MariaDbTest.php` cuando el servicio no está disponible; política del proyecto.
- El backfill es **opcional** por diseño (FR-15): sin él, las ventas históricas muestran "—" (FR-14), que es el estado por defecto hasta decidir asumir efectivo.
- Cambio de firma `storeWithStock(...): array` acotado a `SaleController::store` + tests actualizados (T8).
