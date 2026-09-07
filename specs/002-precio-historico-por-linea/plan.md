# Plan 002 — Precio histórico por línea de venta

Basado en `spec.md` (Status: proposed) y `docs/constitution.md`.

## Conflicto con el spec — resolver antes de tareas

**FR-1 dice `DECIMAL(10,2) NOT NULL`, pero `tb_carrito` guarda también el carrito
vivo del POS**, que existe como filas antes de que la venta se finalice (`create`
→ `addToCart` → … → `store`). Si la columna es `NOT NULL`, cada `addToCart`
tendría que escribir un precio → es exactamente "congelar al agregar al carrito",
que el spec descartó (Out of scope) y U1 contradice.

**Resolución propuesta:** la columna es `DECIMAL(10,2) NULL`. Semántica:

- `NULL` = línea de carrito todavía no finalizada (o venta pre-feature sin
  backfillear).
- valor = precio unitario congelado al finalizar la venta.

Las lecturas usan `COALESCE(car.precio_unitario, al.precio_venta)`: para el
carrito en curso cae al precio de catálogo actual (FR-4), para una venta
registrada usa el congelado (FR-3). El backfill (FR-5) elimina los `NULL` de
ventas ya finalizadas.

→ **Editar FR-1 del spec**: `NULL` en vez de `NOT NULL`, con la semántica de
arriba. El resto del spec no cambia. Requiere aprobación antes de `/sdd:tasks`.

## Estructura de módulos

Sin módulo nuevo. Cambios acotados a la capa de venta:

| Archivo                                               | Responsabilidad                        | Cambio                                                                              |
| ----------------------------------------------------- | -------------------------------------- | ----------------------------------------------------------------------------------- |
| `database/schema.sql`                                 | Schema canónico (instalación limpia)   | + columna `precio_unitario` en `tb_carrito`                                         |
| `tests/fixtures/schema.sqlite.sql`                    | Schema SQLite de tests                 | + misma columna (tipo `NUMERIC`)                                                    |
| `database/migrations/006_carrito_precio_unitario.sql` | Migración para BD existentes           | `ALTER TABLE` + backfill idempotente                                                |
| `app/Models/Sale.php`                                 | Lógica de venta                        | `storeWithStock` congela precios y deriva total; `findWithDetails` lee el congelado |
| `app/Models/CartItem.php`                             | Ítems de carrito                       | `getByNroVenta` lee `COALESCE(congelado, actual)`                                   |
| `CHANGELOG.md`, guías de instalación/actualización    | Documentar el paso de migración manual | nota                                                                                |

No cambian: `SaleController`, `InvoicePdf`, `computeInvoiceTotals`, `withSubtotals`
— siguen leyendo la clave `precio_venta` de cada ítem, que ahora viene aliaseada
desde el valor correcto (ver decisión D2).

## Modelo de datos

`tb_carrito` gana una columna:

```
precio_unitario  DECIMAL(10,2)  NULL   -- MySQL
precio_unitario  NUMERIC        NULL   -- SQLite (fixture de tests)
```

Sin índice nuevo. Sin FK nueva. `cantidad` sigue `int(11)`.

Nombre en español (`precio_unitario`), coherente con `cantidad` / `id_producto`
de la misma tabla (constitución §9: identificadores de código en inglés; los
nombres de columna de tablas de dominio siguen la convención histórica española,
igual que `tb_compras.precio_compra`).

## Algoritmos y edge cases

### A1 — Congelar precio al finalizar (`Sale::storeWithStock`)

Orden dentro de la transacción existente, **antes** del INSERT de cabecera:

1. Verificar carrito no vacío (ya existe).
2. `UPDATE tb_carrito SET precio_unitario = (SELECT precio_venta FROM tb_almacen
WHERE id_producto = tb_carrito.id_producto) WHERE nro_venta = ?`
   — subconsulta correlacionada: funciona igual en MySQL y SQLite (no
   `UPDATE … JOIN`, que SQLite no soporta — ver D3).
3. `SELECT SUM(cantidad * precio_unitario) FROM tb_carrito WHERE nro_venta = ?`
   → `total_pagado` (FR-2). Sustituye al `SUM(car.cantidad * al.precio_venta)`
   actual.
4. INSERT cabecera con ese total, bucle de stock (sin cambios).

Edge: producto con `precio_venta = 0` → línea con `0.00`, válido.
Edge: la FK `tb_carrito_ibfk_1` (`ON DELETE NO ACTION`) impide borrar un producto
con filas en `tb_carrito`, así que "producto inexistente" no puede ocurrir para
una venta en curso; el paso 2 siempre resuelve un precio.

### A2 — Lecturas

- `Sale::findWithDetails` (venta ya registrada): el SELECT de ítems reemplaza
  `al.precio_venta` por `car.precio_unitario AS precio_venta`. Todos los ítems
  tienen valor (post-backfill).
- `CartItem::getByNroVenta` (carrito en curso **y** algún consumo de venta
  registrada): reemplaza `al.precio_venta` por
  `COALESCE(car.precio_unitario, al.precio_venta) AS precio_venta`.
  En `create()` (carrito en curso) → `precio_unitario` es NULL → cae al catálogo
  actual (FR-4). En una venta registrada → usa el congelado.
- `computeInvoiceTotals`, `withSubtotals`, `InvoicePdf` no se tocan: siguen
  leyendo `$item['precio_venta']`, que ahora es el valor correcto.

### A3 — Backfill (`006_carrito_precio_unitario.sql`)

```sql
ALTER TABLE tb_carrito ADD COLUMN precio_unitario DECIMAL(10,2) NULL AFTER cantidad;

-- Solo filas de ventas finalizadas y aún sin precio (idempotente)
UPDATE tb_carrito
SET precio_unitario = (SELECT precio_venta FROM tb_almacen
                       WHERE id_producto = tb_carrito.id_producto)
WHERE precio_unitario IS NULL
  AND nro_venta IN (SELECT nro_venta FROM tb_ventas);

-- Defensa: producto sin fila (no debería ocurrir por la FK) → 0.00
UPDATE tb_carrito
SET precio_unitario = 0.00
WHERE precio_unitario IS NULL
  AND nro_venta IN (SELECT nro_venta FROM tb_ventas);
```

Idempotente: relanzarlo solo toca filas `NULL` de ventas finalizadas; las ya
pobladas no se pisan (FR-5, NFR). Carritos huérfanos / en curso quedan `NULL` a
propósito.

`total_pagado` de ventas históricas **no se recalcula** (FR-8): la suma de los
precios backfilleados es aproximada y puede no cuadrar; el escalar histórico
manda.

## Contrato de interfaz

Firmas sin cambio. Cambia el comportamiento interno:

- `Sale::storeWithStock(array $data): int|false` — ahora, en éxito, deja
  `tb_carrito.precio_unitario` poblado para ese `nro_venta` y `total_pagado` =
  Σ(cantidad × precio_unitario). Sin cambio de firma ni de códigos de retorno.
- `Sale::findWithDetails(int $id): ?array` — cada ítem: `precio_venta` = precio
  congelado.
- `CartItem::getByNroVenta(int $nroVenta): array` — cada ítem: `precio_venta` =
  `precio_unitario` si existe, si no el de catálogo.

Sin endpoints nuevos, sin rutas nuevas, sin permisos nuevos.

## Decisiones técnicas

- **D1 — Congelar en `store()`, no en `addToCart()`.** Descartado congelar al
  agregar: obligaría a decidir el reprecio de carritos retomados días después
  (el POS persiste `pos_nro_venta` sticky), y el carrito hoy no muestra un precio
  "prometido" que proteger. En `store()` el total y las líneas salen del mismo
  snapshot en el mismo instante → cuadran por construcción. (Confirmado con el
  usuario.)
- **D2 — Aliasear `precio_unitario AS precio_venta` en las lecturas** en vez de
  renombrar la clave en todos los consumidores. Descartado tocar
  `computeInvoiceTotals` / `withSubtotals` / `InvoicePdf` / vistas: más superficie
  de cambio y más riesgo de regresión, sin beneficio (la clave `precio_venta` ya
  significa "precio de esta línea" para esos consumidores).
- **D3 — `UPDATE` con subconsulta correlacionada, no `UPDATE … JOIN`.**
  Descartado el JOIN: SQLite (suite de tests) no lo soporta; la subconsulta
  corre igual en ambos motores.
- **D4 — Columna `NULL`, no `NOT NULL`.** Ver "Conflicto con el spec". Descartado
  `NOT NULL` + default 0: rompería la distinción "carrito en curso" vs "línea a
  0" y forzaría escribir precio en `addToCart`.
- **D5 — Migración como archivo SQL numerado** (`006_…`), siguiendo
  `database/migrations/001..005`. Descartado un script PHP: el proyecto no tiene
  runner de migraciones; el patrón establecido es SQL manual documentado.

## Riesgos

| Riesgo                                                                                                    | Mitigación / aceptación                                                                                                                                                                        |
| --------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Olvidar sincronizar `schema.sqlite.sql` → tests de ventas/reportes rompen                                 | Tarea explícita + `composer test` en la verificación; es un patrón ya conocido del proyecto (AGENTS.md §Testing).                                                                              |
| BD de producción sin correr la migración → `precio_unitario` inexistente, errores SQL                     | Documentar el paso en CHANGELOG y en la guía de actualización; la migración va en el mismo release. Es el mismo flujo que `001..005`.                                                          |
| Una venta registrada entre el `ALTER` y el backfill queda con `precio_unitario` NULL en sus líneas nuevas | `storeWithStock` ya pobla el precio en el INSERT de la venta (paso A1.2); solo las filas _previas_ al deploy dependen del backfill. Correr `ALTER` + backfill en la misma transacción/ventana. |
| `withSubtotals` divide/multiplica en float sin `round()`                                                  | Preexistente y fuera de alcance; los totales de dinero reales se calculan en SQL (A1.3). Sin cambio de comportamiento.                                                                         |
| Consumidor no detectado de `al.precio_venta` vía `findWithDetails`/`getByNroVenta`                        | El grep de callers está en el plan (5 llamadas en `SaleController`, todas pasan por `withSubtotals`/`computeInvoiceTotals`). Cubierto por el test de regresión V3.                             |

## Estrategia de verificación

Suite automatizada (`composer test`, suites Unit + Integration SQLite):

- **V1 — Derivación del total (FR-2).** Integration: crear cliente + 2 productos
  con precios distintos, armar carrito de 2 líneas, `storeWithStock`; afirmar
  `total_pagado` == Σ(cantidad × `precio_unitario`) leído de `tb_carrito`.
- **V2 — Congelado en `store` (FR-1).** Tras V1, afirmar que cada fila de
  `tb_carrito` de esa venta tiene `precio_unitario` = el `precio_venta` que el
  producto tenía al momento del `store`.
- **V3 — Lectura histórica (FR-3).** Tras V1: `UPDATE tb_almacen SET precio_venta`
  a otro valor para uno de los productos; `findWithDetails` de la venta →
  afirmar que `items[*].precio_venta` y los subtotales NO cambiaron.
- **V4 — Carrito en curso (FR-4).** `addToCart` sin `store`; `getByNroVenta` →
  afirmar `precio_venta` == precio de catálogo actual (columna `precio_unitario`
  NULL, COALESCE al catálogo).
- **V5 — Backfill idempotente (FR-5/6/8).** Insertar filas de carrito con
  `precio_unitario` NULL para una venta finalizada; correr el bloque de backfill
  dos veces; afirmar que quedan pobladas con el catálogo y que la segunda corrida
  no cambia nada; afirmar que `total_pagado` de esa venta no se tocó.
- **V6 — Schema SQLite (FR-7).** `composer test` completo verde (la ausencia de
  la columna en el fixture haría fallar V1–V5).

Demo manual (evidencia: capturas): venta de 2 ítems → cambiar `precio_venta` de
uno en Productos → abrir factura PDF y `sales/show` de esa venta → los importes
son los originales y la suma cuadra con el total.

## Cobertura de FR

| FR   | Cubierto por                                              |
| ---- | --------------------------------------------------------- |
| FR-1 | A1 (paso 2), modelo de datos, V2 — con la enmienda `NULL` |
| FR-2 | A1 (paso 3), V1                                           |
| FR-3 | A2, D2, V3                                                |
| FR-4 | A2 (`COALESCE` en `getByNroVenta`), V4                    |
| FR-5 | A3, V5                                                    |
| FR-6 | A3 (segundo UPDATE), nota de FK                           |
| FR-7 | tabla de módulos (`schema.sqlite.sql`), V6                |
| FR-8 | A3 (no recalcula `total_pagado`), V5                      |
