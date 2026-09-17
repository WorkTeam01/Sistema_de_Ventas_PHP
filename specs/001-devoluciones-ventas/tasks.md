# Tasks — Spec 001 (devoluciones de ventas)

Tareas < 30 min, ordenadas por dependencia. Cada una: sus FR, checkbox y una
línea "Done when:" verificable.

- [ ] T1. Añadir `tb_devoluciones` y `tb_devolucion_items` a
      `database/schema.sql` (mismo estilo que `tb_ajustes_stock`, con las
      constraints del final: FK `id_venta → tb_ventas` ON DELETE NO ACTION,
      `id_usuario → tb_usuarios` ON DELETE SET NULL, `id_producto →
    tb_almacen` ON DELETE NO ACTION; índice sobre `nro_devolucion` UNIQUE,
      `idx_dev_item_devolucion`, `idx_dev_item_producto`). (FR-10, FR-13, C1)
      Done when: `grep -n "CREATE TABLE IF NOT EXISTS \`tb_devoluciones\`"
      database/schema.sql` lo muestra y las FK quedan declaradas.

- [ ] T2. Añadir las mismas dos tablas a `tests/fixtures/schema.sqlite.sql`
      (sintaxis SQLite: `INTEGER PRIMARY KEY AUTOINCREMENT`, `NUMERIC`,
      `TEXT DEFAULT CURRENT_TIMESTAMP`). (FR-7-equivalente)
      Done when: `grep -n "CREATE TABLE IF NOT EXISTS tb_devoluc"` lo muestra
      y `composer test` sigue verde (el fixture carga sin error).

- [ ] T3. Crear `database/migrations/007_devoluciones.sql` con:
      (a) CREATE de ambas tablas, (b) `INSERT INTO tb_permisos ...` del slug
      `manage_returns` (modulo `ventas`), (c) asignación a Administrador vía
      `CROSS JOIN`, (d) `INSERT INTO tb_rol_permiso` del slug a Vendedor. (FR-16)
      Done when: el archivo existe; sus bloques de asignación son
      `INSERT ... SELECT ... WHERE r.rol = 'Administrador'` / `'Vendedor'` y
      `p.clave = 'manage_returns'`.

- [ ] T4. Actualizar `database/seeder.sql`: permiso `manage_returns` en la
      lista de `tb_permisos` (25ª), y añadir `'manage_returns'` al `IN` de
      asignación del rol Vendedor. Administrador ya lo toma por `CROSS JOIN`
      existente. (FR-16)
      Done when: `grep -n manage_returns database/seeder.sql` muestra las dos
      apariciones (lista de permisos + set del Vendedor).

- [ ] T5. Modelo `App\Models\SaleReturn`: `pendingByVenta()`, `findWithDetails()`,
      `byVenta()`, `allWithDetails(?int $userId)` y `register(int $idVenta,
    string $motivo, array $quantitiesByProduct)` transaccional según A1/A2 del
      plan (locks FOR UPDATE, rechazo total FR-7, `empty` FR-8, `not_found`
      FR-10, reingreso de stock FR-2, monto en SQL FR-3, `nro = id` FR-11,
      activity log FR-12). (FR-1..FR-12, FR-18, FR-19, FR-20)
      Done when: test de integración mínimo (V1/V2) corre contra el modelo y
      `register()` devuelve el array `{ok, id, nro, monto}` o el `error`
      esperado; no se compila aún nada de vistas.

- [ ] T6. `Sale::isReferenced(int|string $id): bool` (consulta a
      `tb_devoluciones`) y llamarla en `SaleController::destroy()` tras leer el
      snapshot: si true → flash warning + redirect a `/sales`, sin tocar stock.
      (FR-13)
      Done when: test V5 pasa (`isReferenced` true tras una devolución).

- [ ] T7. Agregaciones netas del Dashboard (FR-21): `Sale::totalCurrentMonth`,
      `totalPreviousMonth`, `todaySummary` (`cantidad` intacto), `totalsByMonth`
      y `Product::getTopSelling` — todas restan el monto/cantidad devueltos
      imputados al período con el mismo scoping de usuario (según A5 del plan).
      (FR-21, FR-18)
      Done when: test V6 pasa (venta 100 − devolución 30 del mes = 70;
      devolución de otro mes no afecta al mes de la venta).

- [ ] T8. Agregaciones netas de Report (FR-21): `salesByPeriod`, `salesTotals`,
      `salesSummary`, `topProducts`, `clientsByPeriod` (A5 del plan; los
      `num_ventas`/`num_compras` no se descuentan).
      (FR-21, FR-18)
      Done when: test V7 pasa sobre SQLite con fechas literales.

- [ ] T9. Indicador de devoluciones (FR-22): `Sale::allWithDetails()` expone
      `tiene_devoluciones` (EXISTS) y `views/sales/index.php` muestra badge
      "Devuelto" en la fila cuando aplica, sin cambiar `total_pagado`.
      (FR-22, FR-4)
      Done when: test V8 pasa y la vista muestra el badge con contraste
      verificado (claro y oscuro).

- [ ] T10. `views/sales/show.php`: sección "Devoluciones" con `byVenta()` y
      un botón "Registrar devolución" visible solo con permiso `manage_returns`
      (recibe `$can` desde el controlador) a `/returns/create?sale_id=`. (FR-14)
      Done when: vista abierta con axe-core: sin violaciones en claro y oscuro,
      y la sección lista las devoluciones de la venta.

- [ ] T11. `SaleReturnController` (index/create/store/show) + scoping FR-15
      (`Controller::forbidden()` si la venta no es del usuario y no hay
      `view_sales_all`; venta `id_usuario NULL` → solo `view_sales_all`) +
      registro de rutas en `routes/web.php` (`can:view_sales` para
      index/show, `can:manage_returns` para create/store). (FR-15, FR-16,
      FR-17)
      Done when: rutas registradas y la navegación
      `/returns` → `create?sale_id=` → `POST /returns` recorre el flujo sin
      error; `forbidden()` redirige a `/errors/403` en los casos de scope.

- [ ] T12. Vista `views/returns/create.php`: info de la venta (nro, cliente,
      fecha), tabla de ítems con `pendiente` y campo de cantidad (0..pendiente)
      por ítem, `motivo` obligatorio único, botón de confirmación; formulario
      con CSRF. Más `public/js/modules/returns/returns-create.js`:
      validación cliente-side de cantidades contra el `pendiente` (data
      attributes servidos por PHP, no interpolación directa) y preview del
      monto con los `precio_unitario` de la venta. (FR-1, FR-8, FR-9)
      Done when: axe-core 0 violaciones claro/oscuro; el JS bloquea cantidades
      fuera de rango y el preview suma correcto.

- [ ] T13. Vista `views/returns/index.php` (DataTables + export
      PDF/Excel/CSV/Imprimir, columnas: nro devolución, nro venta, cliente,
      motivo, monto, fecha, usuario, acción ver) + `returns-index.js`
      (mismo patrón que `sales-index.js`) y vista `views/returns/show.php`
      (cabecera: venta origen, nro_devolucion, ítems, cantidades, monto,
      motivo, usuario, fecha). (FR-17)
      Done when: listado con el patrón DataTables completo y export funciona;
      detalle muestra todo el contrato FR-17; axe-core 0 violaciones ambos
      temas.

- [ ] T14. `ActivityLogRenderer`: labels `motivo`, `monto_devuelto`,
      `detalle` (y `id_venta` si entra en `datos_nuevos`); verificar que la
      acción `create` cae al badge por defecto. (FR-12)
      Done when: un registro `sale_return` renderiza sus campos con label en
      español en `activity-log/show/{id}`.

- [ ] T15. Tests de integración de devoluciones (`tests/Integration/Models/
    SaleReturnTest.php`) con V1..V5 del plan. (FR-1..FR-13)
      Done when: V1..V5 pasan (registro atómico + monto + nro + log,
      pendiente/acumulación, rechazos FR-7/8/9/10, inmutabilidad FR-4,
      isReferenced FR-13).

- [ ] T16. Tests de agregaciones netas (`SaleReturnNetTest` o ampliar los
      tests existentes de `Sale`/`Product`/`Report`) con V6..V8 del plan.
      (FR-21, FR-22)
      Done when: V6..V8 pasan (dashboard, reportes, indicador).

- [ ] T17. `composer test` completo verde (Unit + Integration; las variantes
      de fecha del motor ya tienen cobertura MariaDB). (regresión global)
      Done when: `composer test` termina sin fallos ni errores.

## Feature closeout

_Checkboxes fuera de la numeración `Tn` — no se implementan vía `/sdd:implement`._

- [ ] Verificar todos los FR (`/sdd:validate` → `specs/001-devoluciones-ventas/validation.md`).
- [ ] Mover la feature a `Hecho ✅` en `docs/roadmap.md` enlazando esta carpeta.
- [ ] `CHANGELOG.md`: entrada en `[Unreleased]` — tablas `tb_devoluciones` +
      `tb_devolucion_items`, slug `manage_returns`, venta neta en dashboard y
      reportes, y el paso de migración `007_*.sql`.
- [ ] Guía de actualización: documentar que las BD existentes deben correr
      `database/migrations/007_devoluciones.sql` (mismo flujo que `001..006`).
- [ ] `AGENTS.md`: actualizar la firma de BD (`+ tb_devoluciones`,
      `+ tb_devolucion_items`), la lista de slugs de permisos
      (`+ manage_returns`), los enums del activity log (`entidad sale_return`)
      y la nota de `tb_ventas.total_pagado` (venta neta = ventas − devoluciones
      del período); en la sección de métodos auxiliares de controlador no hay
      verbo nuevo (index/create/store/show).
