# Plan 001 — Devoluciones de ventas

Basado en `spec.md` (Status: proposed) y `docs/constitution.md`.
Depende de spec 002 (precio histórico por línea) ya implementada: este plan asume
que `tb_carrito.precio_unitario` existe y que las lecturas de una venta
registrada devuelven el precio congelado por línea.

## Conflictos con el spec — resolver antes de tareas

**C1 — Deleción de la venta con la FK `tb_devoluciones.id_venta → tb_ventas`.**
El spec FR-13 pide bloquear eliminar una venta con devoluciones vía patrón
`isReferenced()`. Si además se declara la FK con `ON DELETE NO ACTION` (default),
el `DELETE` físico fallaría con excepción aunque `isReferenced()` no se llamara —
doble protección. Decisión: **declarar la FK** (integridad de la convención del
proyecto) **y** implementar `Sale::isReferenced()` (mensaje de flash amigable,
patrón clásico). La FK hace imposible el estado "venta eliminada con
devoluciones", prueba para FR-10 (devolver una venta eliminada).

**C2 — `nro_devolucion` correlativo.** FR-11 exige unicidad resistente a
concurrencia. El spec (edge) deja elegir al plan: UNIQUE + retry, o derivar del
AUTO_INCREMENT. **Decisión: `nro_devolucion INT UNIQUE NOT NULL` poblado con el
`id_devolucion` generado** (`UPDATE` dentro de la misma transacción, última
instrucción antes del activity log). En InnoDB el AUTO_INCREMENT es la única
fuente de unicidad realmente atómica sin retry; el `UPDATE` corre sobre un id
que solo esta transacción puede ver (no commiteado). El correlativo resultante
es exactamente el id (`1, 2, 3, …`), puede saltar tras un rollback — aceptado
(un correlativo visible no exige contigüidad; los `AUTO_INCREMENT` del proyecto
ya saltan). Descartado `MAX(nro_devolucion)+1` con retry: complicado de hacer
atómico bajo concurrencia y replica la no-atomicidad de `Sale::nextNumber`, que
el spec dejó explícitamente fuera de esta feature.

**C3 — Producto eliminado del catálogo (edge del spec).** El spec contempla
"si la fila del producto ya no existe, el reingreso de stock de esa línea se
omite". Con la FK `tb_carrito_ibfk_1` (`ON DELETE NO ACTION`) un producto con
líneas en `tb_carrito` no se puede eliminar, y toda venta registrada tiene sus
líneas en `tb_carrito` → el caso es **inherentemente imposible** en este esquema.
Decisión: se maneja de forma defensiva (si el `UPDATE tb_almacen` toca 0 filas,
la línea se procesa igual y queda constancia), pero el plan no construye
infraestructura especial para él.

**C4 — Precio de los ingresos del top de productos.** `Report::topProducts` y
`Product::getTopSelling` suman `cantidad * tb_almacen.precio_venta` (precio de
catálogo **actual**), no el histórico. La spec 002 **ampliada** esta sesión
(FR-3, revisión cruzada 2026-09-17) ahora también incluye esas dos
agregaciones en el alcance de la lectura del precio persistido, así que tras
implementar 002 **ambos lados del neto** (ventas brutas y devoluciones) usan el
mismo `precio_unitario` de línea. **Resuelto sin desfase.**

## Estructura de módulos

Módulo nuevo `returns`, más cambios acotados en ventas/reportes/dashboard:

| Archivo                                                                                    | Responsabilidad                                                                                | Cambio    |
| ------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- | --------- |
| `app/Models/SaleReturn.php`                                                                | Cabecera + líneas de devolución (fat model)                                                    | nuevo     |
| `app/Controllers/SaleReturnController.php`                                                 | `index`, `create`, `store`, `show` + scoping FR-15                                             | nuevo     |
| `app/Models/Sale.php`                                                                      | `isReferenced()`, `allWithDetails()` + indicador FR-22, agregaciones netas FR-21               | modificar |
| `app/Models/Product.php`                                         | `getTopSelling()` neta de devoluciones (FR-21). El precio histórico del top lo resuelve ya spec 002 (FR-3 ampliada) | modificar |
| `app/Models/Report.php`                                          | `salesByPeriod`, `salesTotals`, `salesSummary`, `topProducts`, `clientsByPeriod` netas (FR-21). Igual: el precio histórico de `topProducts` lo resuelve spec 002 | modificar |
| `app/Controllers/SaleController.php`                                                       | `destroy()` llama `isReferenced()` (FR-13)                                                     | modificar |
| `app/Helpers/ActivityLogRenderer.php`                                                      | labels de `datos_nuevos` de la devolución (FR-12)                                              | modificar |
| `routes/web.php`                                                                           | rutas del módulo `returns`                                                                     | modificar |
| `database/schema.sql`                                                                      | + `tb_devoluciones`, `tb_devolucion_items` (instalación limpia)                                | modificar |
| `tests/fixtures/schema.sqlite.sql`                                                         | + mismas tablas (sintaxis SQLite, FR-7-equivalente)                                            | modificar |
| `database/migrations/007_devoluciones.sql`                                                 | tablas + permiso `manage_returns` + asignación (BD existentes)                                 | nuevo     |
| `database/seeder.sql`                                                                      | + permiso `manage_returns` + asignación Vendedor y Admin                                       | modificar |
| `views/returns/index.php`, `views/returns/create.php`, `views/returns/show.php`            | vistas del módulo (FR-1, FR-14, FR-17)                                                         | nuevo     |
| `views/sales/show.php`                                                                     | sección "Devoluciones" + botón registrar (FR-14)                                               | modificar |
| `views/sales/index.php`                                                                    | columna/badge "devuelto" (FR-22)                                                               | modificar |
| `public/js/modules/returns/returns-index.js`, `returns-create.js`; CSS de módulo si aplica | DataTables+export; validación de cantidades y preview (FR-1)                                   | nuevo     |
| `CHANGELOG.md`, guías de actualización, `AGENTS.md` (enums, slugs, firma BD)               | documentar                                                                                     | closeout  |

## Modelo de datos

Dos tablas nuevas (cabecera + líneas). Nombres en español con prefijo `tb_` y
campos en el estilo de las tablas de dominio (constitución §9).

```
tb_devoluciones
  id_devolucion    INT AUTO_INCREMENT PK
  nro_devolucion   INT NOT NULL UNIQUE          -- = id_devolucion (C2)
  id_venta         INT NOT NULL FK → tb_ventas(id_venta) ON DELETE NO ACTION
  id_usuario       INT DEFAULT NULL FK → tb_usuarios(id_usuario) ON DELETE SET NULL
                                                   -- quien REGISTRA, scoping vía venta
  motivo           VARCHAR(255) NOT NULL
  monto            DECIMAL(10,2) NOT NULL DEFAULT 0.00   -- FR-3
  fyh_creacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP   -- fecha de imputación FR-21
  -- sin fyh_actualizacion: la devolución es inmutable en v1 (spéc "irreversible")

tb_devolucion_items
  id_detalle       INT AUTO_INCREMENT PK
  id_devolucion    INT NOT NULL FK → tb_devoluciones(id_devolucion) ON DELETE CASCADE
  id_producto      INT NOT NULL FK → tb_almacen(id_producto) ON DELETE NO ACTION
  cantidad         INT NOT NULL
  precio_unitario  DECIMAL(10,2) NOT NULL        -- snapshot copiado de tb_carrito.precio_unitario (spec 002)
  -- sin subtotal materializado: monto derivado en SQL (Σ cantidad × precio_unitario)
```

Índices: `UNIQUE (nro_devolucion)`; LLAVE en `tb_devolucion_items` por
`id_devolucion` (acumulación de pendiente y detalle), y por `id_producto`
(reportes netos de top). Sin más.

`tb_devoluciones.id_usuario` registra _quién_ devolvió (FR-15: "la devolución
además guarda quién la registró"); la autorización y el scoping de reportes se
resuelven por JOIN a la **venta** (`tb_devoluciones.id_venta →
tb_ventas.id_usuario`), nunca por el usuario de la devolución.

## Algoritmos y edge cases

### A1 — Cantidad pendiente por ítem (FR-1, FR-6, FR-14)

```
SELECT car.id_producto,
       al.nombre AS nombre_producto,
       car.cantidad AS vendida,
       car.precio_unitario AS precio_unitario,
       car.cantidad - COALESCE(dev.devuelto, 0) AS pendiente
FROM tb_carrito car
LEFT JOIN tb_almacen al ON al.id_producto = car.id_producto
LEFT JOIN (
    SELECT det.id_producto, SUM(det.cantidad) AS devuelto
    FROM tb_devolucion_items det
    JOIN tb_devoluciones dv ON dv.id_devolucion = det.id_devolucion
    WHERE dv.id_venta = ?
    GROUP BY det.id_producto
) dev ON dev.id_producto = car.id_producto
WHERE car.nro_venta = (SELECT nro_venta FROM tb_ventas WHERE id_venta = ?)
ORDER BY car.id_carrito ASC
```

`LEFT JOIN tb_almacen` por robustez (C3): si el producto no existiera, la línea
se muestra con nombre `null` y `precio_unitario null` (se muestra el id
formateado); con la FK no ocurre. La pendiente se calcula **siempre desde la
BD** dentro de la transacción en el momento de registrar (A2) — nunca del POST.

### A2 — Registrar devolución (`SaleReturn::register`), transacción única

Orden dentro de `$db->beginTransaction()`, con rollback ante cualquier
`Throwable`:

1. **Leer la venta con lock**: `SELECT nro_venta, id_usuario FROM tb_ventas
WHERE id_venta = ? FOR UPDATE`. Si no existe → rollback, `not_found` (FR-10).
   En MySQL InnoDB, el `FOR UPDATE` serializa dos devoluciones concurrentes de la
   misma venta; en SQLite (tests) se ignora sin efecto. El lock de la fila de la
   venta es el único punto de exclusión mutua que necesita la transacción (FR-6).
2. **Leer las líneas vivas del carrito con lock**: `SELECT id_producto, cantidad,
precio_unitario FROM tb_carrito WHERE nro_venta = ? FOR UPDATE`. Esas filas
   solo cambian al finalizar la venta (inmutable tras `store`), así que el lock
   es de baja contención.
3. **Calcular pendientes desde BD** (mismo JOIN de A1, sin el filtro de venta
   redundante): para cada `id_producto` del POST, `solicitado[i]` ∈ [0,
   pendiente[i]]. Si algún `solicitado[i] > pendiente[i]` → rollback y devolver
   `conflict` (FR-7, rechazo total: nada se aplica). Si todas las cantidades son
   0 → rollback y devolver `empty` (FR-8).
4. Motivo: validado ya en el controlador (obligatorio, no vacío) → FR-9; el
   modelo lo revalida por si el controlador pierde ese código.
5. `INSERT tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo)` con
   `nro_devolucion` provisional = 0 ó NULL, e `id_devolucion = lastInsertId`.
6. Por cada línea con `cantidad ≥ 1`: `INSERT tb_devolucion_items
(id_devolucion, id_producto, cantidad, precio_unitario)` copiando
   `precio_unitario` de la fila del carrito leída en (2) — el precio congelado
   de la venta (F3/FR-3 + spec 002). Un `INSERT` de línea que falle (p. ej. FK)
   revienta la transacción → rollback completo (FR-7 atómico).
7. **Reingreso de stock** (FR-2): por cada línea `UPDATE tb_almacen SET stock =
stock + ? WHERE id_producto = ?`. Si `rowCount() == 0` (producto inexistente,
   imposible por FK — defensivo, C3), la línea se **procesa igual** (queda
   constancia en la devolución y su detalle) y el resto continúa.
8. **Monto en SQL** (FR-3): `UPDATE tb_devoluciones SET monto = (SELECT
COALESCE(SUM(cantidad * precio_unitario), 0) FROM tb_devolucion_items WHERE
id_devolucion = ?) WHERE id_devolucion = ?`. El cómputo ocurre en la BD con
   `DECIMAL`, sin `round()` intermedio (NFR).
9. **Número correlativo** (FR-11, C2): `UPDATE tb_devoluciones SET nro_devolucion
= id_devolucion WHERE id_devolucion = ?`.
10. **Activity log** (FR-12): `ActivityLog::record('create', 'sale_return',
id_devolucion, desc, null, datos_nuevos)` — mismo PDO singleton, así que
    entra en la misma transacción. `datos_nuevos` con `nro_venta`, `motivo`,
    `monto_devuelto` y `detalle` = ítems con `id_producto`, `cantidad`,
    `precio_unitario`. (Devoluciones rechazadas en 3/4 no llegan a registrar.)
11. `commit`; devuelve `[ok => true, id, nro, monto]`.

Edge: `monto` puede salir `0.00` si todas las líneas devueltas tenían
`precio_unitario = 0` (promociones) → devolución válida, monto 0 (spec "Edge
cases"). El `AUTO_INCREMENT` del paso 5 puede saltar números tras un rollback →
aceptado (C2).

### A3 — Lecturas

- **`SaleReturn::pendingByVenta(int $idVenta)`**: A1 para la vista create.
- **`SaleReturn::findWithDetails(int $id)`**: cabecera + venta (nro, cliente,
  usuario) + items de `tb_devolucion_items` JOIN `tb_almacen` (nombre, código).
- **`SaleReturn::byVenta(int $idVenta)`**: devoluciones de una venta (rango
  compacto) para la sección de `sales/show` (FR-14).
- **`SaleReturn::allWithDetails(?int $userId = null)`**: listado (FR-17).
  Sin `$userId`: todas. Con `$userId`: `JOIN tb_ventas v ON
v.id_venta = d.id_venta WHERE v.id_usuario = ?` (FR-15). Incluye cliente y
  nro de venta.

### A4 — Bloqueo de eliminación de venta (FR-13)

`Sale::isReferenced(int|string $id): bool` → `SELECT COUNT(*) FROM
tb_devoluciones WHERE id_venta = ?`. `SaleController::destroy()` lo verifica
después de leer el `$snapshot` (que ya valida scoping) y antes de
`destroyWithStock()`: si true → flash `warning` "la venta tiene devoluciones
asociadas" + redirect a `/sales`. La FK `ON DELETE NO ACTION` queda como red de
seguridad silenciosa.

### A5 — Ventas netas en dashboard y reportes (FR-21)

Principio: **venta neta del período = Σ ventas del período − Σ montos devueltos
imputados al período** (cada lado con el mismo rango y el mismo scoping por
usuario de la venta). No se empareja por venta: una venta del mes devuelta el
mes siguiente reduce **el mes siguiente** (imputación de la devolución, según
spec). El neto nunca da negativo por el lado de las devoluciones solas: el KPI
muestra `ventas − devoluciones` (puede ser 0 o negativo solo si hay devoluciones
sin venta equivalente en el mismo rango — imposible en la práctica porque la
devolución existe **después** de su venta, pero el administrador puede filtrar
rangos donde una venta anterior a otro período se devolvió en este; se muestra
tal cual, sin `ABS`).

Implementación por agregación (todas conservan su firma y su scoping actual):

- **`Sale::totalCurrentMonth` / `totalPreviousMonth` / `todaySummary`**:
  subconsulta escalar por rango del mismo mes/día:
  `COALESCE(SUM(v.total_pagado), 0) - COALESCE((SELECT SUM(dv.monto) FROM
tb_devoluciones dv JOIN tb_ventas v2 ON v2.id_venta = dv.id_venta WHERE
<rango fecha dv.fyh_creacion> [AND v2.id_usuario = ?]), 0)`.
  `todaySummary.cantidad` **no** se descuenta (número de ventas, no unidades).
- **`Sale::totalsByMonth`**: `UNION ALL` de dos SELECTs (ventas y devoluciones
  por mes) agrupado por `mes`, restando columnas. Scoping por usuario en ambos
  lados vía `v.id_usuario`.
- **`Product::getTopSelling`** y **`Report::topProducts`**: agregar un `LEFT
  JOIN` a un subquery de `tb_devolucion_items` / `tb_devoluciones` con el rango
  de `dv.fyh_creacion`, para **restar** `cantidad` devuelta y
  `cantidad * precio_unitario` sobre las columnas `unidades_vendidas` /
  `ingresos`. Gracias a la ampliación de spec 002 FR-3, el lado bruto ya usa
  también el precio persistido (con `COALESCE`) → ambos lados del neto son
  coherentes en la base de precio. El dashboard usa el rango "todo el
  histórico" implícito en `getTopSelling`; la devolución se imputa por su
  `fyh_creacion` siempre.
- **`Report::salesByPeriod` / `salesTotals` / `salesSummary`**: rango `BETWEEN
? AND ?` de la venta; se resta la subconsulta de devoluciones con
  `dv.fyh_creacion BETWEEN ? AND ?` y el mismo scoping (`v2.id_usuario`).
  `salesTotals.num_ventas` / `salesSummary.num_ventas` no se descuentan.
- **`Report::clientsByPeriod`**: restar por cliente. `num_compras` intacto;
  `monto_acumulado = COALESCE(SUM(v.total_pagado),0) − COALESCE((SELECT
SUM(dv.monto) FROM tb_devoluciones dv JOIN tb_ventas vc ON vc.id_venta =
dv.id_venta WHERE vc.id_cliente = c.id_cliente AND dv.fyh_creacion BETWEEN ?
AND ?),0)` — subconsulta correlacionada dentro del SELECT, sin tocar el
  `GROUP BY`.

En SQLite (tests) estas consultas corren igual: `FOR UPDATE` se ignora
(tests single-thread), `BETWEEN`/`UNION ALL` son estándar. Las variantes
`YEAR()/MONTH()/CURDATE()/DATE_FORMAT()` ya están cubiertas por los tests
MariaDB existentes del mismo archivo.

### A6 — Indicador en el listado de ventas (FR-22)

`Sale::allWithDetails()` añade `EXISTS(SELECT 1 FROM tb_devoluciones WHERE
id_venta = v.id_venta)` como `tiene_devoluciones`; `views/sales/index.php`
muestra un badge "Devuelto" (`badge-warning` o similar, contraste verificado)
cuando es true. El `total_pagado` de la venta **no** cambia (FR-4, FR-22).

## Contrato de interfaz

**Modelo `App\Models\SaleReturn`** (tabla `tb_devoluciones`; las líneas de
`tb_devolucion_items` se operan vía `$this->db` dentro del modelo, como hace
`Sale::findWithDetails` con `tb_carrito`):

- `register(int $idVenta, string $motivo, array $quantitiesByProduct): array`
  → `['ok' => true, 'id' => int, 'nro' => int, 'monto' => float]` | `['ok' =>
false, 'error' => 'not_found'|'empty'|'conflict'|'validation'|'generic']`.
  `$quantitiesByProduct` = `[id_producto => cantidad]` con `cantidad ≥ 0`.
- `pendingByVenta(int $idVenta): array` → ítems A1 (`id_producto`, `nombre`,
  `vendida`, `precio_unitario`, `pendiente`).
- `findWithDetails(int $id): ?array` → cabecera + `venta` + `items`.
- `byVenta(int $idVenta): array` → devoluciones de la venta, compactas.
- `allWithDetails(?int $userId = null): array` → listado (FR-17) scopeado
  (FR-15).

**`Sale`**: `isReferenced(int|string $id): bool` (A4) y los cambios de A5/A6.

**Controller `SaleReturnController`** — solo métodos del estándar, sin verbos
nuevos fuera de él (NFR §Methods):

- `index(): void` — listado (permiso `view_sales`), scope FR-15.
- `create(): void` — formulario de devolución. `?sale_id=N`: carga
  `pendingByVenta` y renderiza. Sin `sale_id`: renderiza un buscador de venta
  (tabla DataTables de ventas + botón/cursor que enruta a
  `create?sale_id=`). Permiso `manage_returns`.
- `store(): void` — `validateCsrfOrFail()`, valida motivo, llama `register()`,
  convierte `ok/error` a flash + redirect. Permiso `manage_returns`.
- `show(?int $id): void` — detalle (permiso `view_sales`).

Scoping aplicado en los 4 métodos: sin `view_sales_all`, la venta destino (o la
de la devolución consultada) debe tener `id_usuario == Auth::user()[id_usuario]`;
si `NULL` → solo `view_sales_all` (FR-15). Con `Controller::forbidden()`.

**Rutas** (literales antes de paramétricas):

```php
$router->get('/returns',           [SaleReturnController::class, 'index'],  ['auth', 'can:view_sales']);
$router->get('/returns/create',    [SaleReturnController::class, 'create'], ['auth', 'can:manage_returns']);
$router->post('/returns',          [SaleReturnController::class, 'store'],  ['auth', 'can:manage_returns']);
$router->get('/returns/show/{id}', [SaleReturnController::class, 'show'],   ['auth', 'can:view_sales']);
```

**Permiso nuevo**: `manage_returns` — "Registrar devoluciones de ventas",
`modulo => 'ventas'`. Sembrado en `seeder.sql` (lista de permisos + set del rol
Administrador vía `CROSS JOIN` existente + añadido al `IN` del Vendedor) y, para
BD existentes, en `007_devoluciones.sql` (mismo patrón: `INSERT ... SELECT` con
`ON p.clave = 'manage_returns'` para Admin y Vendedor). El `view_sales` ya
cubre la consulta (FR-16). Sin slug `view_*` nuevo.

**Activity log**: `datos_nuevos` con claves `nro_venta` (label existe),
`motivo`, `monto_devuelto`, `detalle`. Nuevos labels en `ActivityLogRenderer`:
`motivo`, `monto_devuelto`, `detalle` (y `id_venta` si se incluye). La acción
`create` ya usa `badge-primary` por defecto; sin cambio.

## Decisiones técnicas

- **D1 — Dos tablas (cabecera + líneas), no JSON.** El acumulado de pendiente
  (FR-5/FR-6), el detalle estructurado del log (FR-12) y sobre todo el neto de
  `topProducts`/`getTopSelling` por producto y período (FR-21) necesitan líneas
  consultables. Descartado guardar las líneas como JSON en la cabecera: obligaría
  a re-procesar JSON en cada lectura y en las agregaciones SQL.
- **D2 — `nro_devolucion = id_devolucion`** (ver C2). Descartado `MAX+1` con
  retry (no atómico sin artificios) y descartado una secuencia separada.
- **D3 — `id_usuario` de la devolución = quien la registra; scoping SIEMPRE vía
  la venta.** El spec (clarif. 11) lo exige para autorización; se aplica igual
  en reportes netos para que un vendedor sin `*_all` vea la neta de **sus**
  ventas.
- **D4 — Reingreso de stock con `UPDATE tb_almacen` directo en la transacción**,
  sin `StockAdjustment::register()` (spec edge 3.4: evita transacción anidada).
- **D5 — Locks `FOR UPDATE` sobre la venta y sus líneas** (A2.1/A2.2) como
  exclusión mutua de devoluciones concurrentes. Funciona en MySQL, se ignora en
  SQLite (donde los tests son de un solo hilo). Descartado bloquear toda la
  tabla de devoluciones: contención innecesaria.
- **D6 — `LEFT JOIN tb_almacen` y nombres nullable en A1** — por robustez ante
  el edge C3 (imposible por FK), evitando que una línea extraña rompa el
  formulario.
- **D7 — Migración numerada `007_…`.** La serie es `001..005` existente + `006`
  de la spec 002; devoluciones toma la siguiente. Sin runner: SQL manual
  documentado (patrón establecido).
- **D8 — Permiso `manage_returns` para Vendedor y Administrador** (no Comprador).
  U1 del spec pone el registro en manos del vendedor; el Admin lo recibe por el
  `CROSS JOIN`. Comprador queda fuera: no vende.

## Riesgos

| Riesgo                                                                                                         | Mitigación / aceptación                                                                                                                                        |
| -------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Olvidar sincronizar `schema.sqlite.sql` → tests de integración de ventas/reportes rompen                       | Tarea explícita (T2) + `composer test` en verificación; patrón ya conocido del proyecto.                                                                       |
| BD de producción sin correr `007_…` → tablas/permiso inexistentes, errores SQL                                 | Documentar en CHANGELOG y guías; misma mecánica que `001..005`/`006`.                                                                                          |
| Dos devoluciones concurrentes de la misma venta                                                                | `FOR UPDATE` sobre la venta (A2.1) serializa; la segunda relee pendientes ya actualizados (FR-6). No probado en SQLite (single-thread); documentado.           |
| El neto leído con `FOR UPDATE` no aplica en SQLite                                                             | Aceptado: SQLite no tiene concurrencia real en tests; la lógica de validación (comparar contra pendiente recalculado en la misma transacción) se testea igual. |
| Cambiar 10 agregaciones introduce regresiones en dashboard/reportes | Un test por agregación neta (o al menos uno por cada grupo de función); todos sobre el mismo fixture. |
| `nro_devolucion` salta números tras rollback | Aceptado (C2); el correlativo no exige contigüidad. |
| Desfase de precio en el top (bruto vs. devuelto) | Resuelto (C4): la spec 002 ampliada (FR-3) alinea `getTopSelling`/`topProducts` al precio persistido, así que ambos lados del neto usan la misma base. |
| Producto eliminado (C3)                                                                                        | Imposible por FK `tb_carrito_ibfk_1`; manejo defensivo `rowCount()==0` en stock, nombre nullable.                                                              |
| Olvidar el badge en `views/sales/index.php` o en `allWithDetails`                                              | Tarea que cubre modelo + vista juntos (T10).                                                                                                                   |
| Método/verbo fuera del estándar                                                                                | El controlador usa solo `index/create/store/show` — sin verbo nuevo que justificar en `AGENTS.md`.                                                             |

## Estrategia de verificación

Suite automatizada (`composer test`, Unit + Integration SQLite; las variantes
con `CURDATE()/YEAR()/DATE_FORMAT()` de dashboard en los tests MariaDB ya
existentes del mismo archivo):

- **V1 — Registro atómico (FR-1/2/3/11/12).** Con 002 implementada: crear venta
  de 2 ítems con `storeWithStock` (precios congelados), registrar devolución
  parcial; afirmar: stock incrementado por ítem, `monto` == Σ(cantidad ×
  `precio_unitario` original), `nro_devolucion` == `id_devolucion`, fila de
  `tb_activity_log` con entidad `sale_return` y `datos_nuevos.monto_devuelto`
  correcto.
- **V2 — Pendiente y acumulación (FR-5/6).** Venta 5 uds → devolución de 2 →
  `pendingByVenta` da 3 → segunda devolución de 3 ok → `pendingByVenta` da 0 →
  tercera devolución se rechaza (`conflict`, FR-7) y `stock`/registros sin
  cambios.
- **V3 — Rechazos (FR-7/8/9/10).** Cantidad > pendiente (rechazo total, nada se
  aplica); todas las cantidades 0 (`empty`); sin motivo (`validation`); venta
  inexistente (`not_found`).
- **V4 — Inmutabilidad de la venta (FR-4).** Tras la devolución, `findWithDetails`
  de la venta devuelve total/líneas intactas; `monto` informativo, sin tocar
  `tb_ventas`.
- **V5 — Bloqueo de eliminación (FR-13).** `Sale::isReferenced` true tras una
  devolución; `destroyWithStock` no se invoca (se cubre el modelo; controller no
  se testea).
- **V6 — Neto dashboard (FR-21).** Venta 100 del mes + devolución de 30 en el
  mismo mes → `totalCurrentMonth` = 70; devolución en mes distinto no afecta el
  mes de la venta (y sí al del devolución) en `totalsByMonth`.
- **V7 — Neto reportes (FR-21).** `salesByPeriod`/`salesTotals`/`salesSummary`
  con fechas literales en SQLite: venta 100 − devolución 30 = 70; `num_ventas`
  intacto; `topProducts`/`getTopSelling` restan cantidades y montos;
  `clientsByPeriod` descuenta por cliente.
- **V8 — Indicador (FR-22).** `allWithDetails` con `tiene_devoluciones = 1`
  solo para la venta devuelta.
- **V9 — `composer test` completo verde** (regresión global) + axe-core sin
  violaciones en claro y oscuro en `returns/*`, `sales/show`, `sales/index`.

Demo manual (evidencia: capturas): vender 3 uds → devolver 2 con motivo → stock
reingresa 2 → listado de devoluciones con export → sección en detalle de la
venta → badge en listado → reporte de ventas del período muestra la neta →
dashboard del mes la descuenta.

## Cobertura de FR

| FR    | Cubierto por                                                                                                |
| ----- | ----------------------------------------------------------------------------------------------------------- |
| FR-1  | A1 (formulario), A2, D1; vistas create; V1                                                                  |
| FR-2  | A2.7; V1                                                                                                    |
| FR-3  | A2.8 (copia `precio_unitario` de la venta, monto SQL); V1                                                   |
| FR-4  | A3 lectura inmutable; V4                                                                                    |
| FR-5  | A1 (acumulativo); V2                                                                                        |
| FR-6  | A2.1/A2.3 (lock + pendiente desde BD); V2                                                                   |
| FR-7  | A2.3 (rechazo total); V3                                                                                    |
| FR-8  | A2.3 (`empty`); V3                                                                                          |
| FR-9  | A2.4 + controller; V3                                                                                       |
| FR-10 | A2.1 (`not_found`) + FK; V3                                                                                 |
| FR-11 | C2, A2.9; V1                                                                                                |
| FR-12 | A2.10 + labels renderer; V1                                                                                 |
| FR-13 | A4 (`isReferenced` + FK + controller); V5                                                                   |
| FR-14 | A1/A3 (`byVenta` + sección en `sales/show`); V2 (pendiente); demo                                           |
| FR-15 | controller scoping + `allWithDetails` + join de venta en reportes; (no testeado: no se testean controllers) |
| FR-16 | rutas con `can:manage_returns` / `can:view_sales`; closeout AGENTS.md                                       |
| FR-17 | `index`/`show` + DataTables/export + detalle; demo                                                          |
| FR-18 | sin ventana temporal (rango libre en `dv.fyh_creacion`); V6/V7                                              |
| FR-19 | A2.7 siempre reingresa; V1                                                                                  |
| FR-20 | `monto` informativo, sin tocar `tb_ventas`; V4                                                              |
| FR-21 | A5 (10 agregaciones); V6/V7                                                                                 |
| FR-22 | A6; V8                                                                                                      |
