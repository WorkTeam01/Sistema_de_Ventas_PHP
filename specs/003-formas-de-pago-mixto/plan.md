# Technical plan — Spec 003

Formas de pago + pago mixto. Normaliza el cobro por método de pago como base del
futuro arqueo de caja (roadmap). Ningún cambio de firma o esquema es retrocompatible
por sí solo; las decisiones que rompen el contrato existente están señaladas.

## Module structure

- `PaymentMethod` (model, `tb_metodos_pago`) → catálogo CRUD + lecturas actívas para el POS (FR-1, FR-3, FR-4, FR-11)
- `PaymentMethodController` → CATÁLOGO con patrón modal+AJAX (verbo estándar `index/store/show/update/destroy` + auxiliar documentado `checkNombre`) (FR-1, FR-2, FR-16; catálogo completo gateado por `manage_payment_methods`)
- `SalePayment` (model, `tb_pagos`) → persistencia por línea, `byVenta()`, `vueltoFor()` (FR-10, FR-12, FR-14)
- `Sale::storeWithStock()` (modificado) → valida y persiste pagos ATÓMICAMENTE con la venta; retorna código de error + vuelto (FR-6..FR-11)
- `Sale::findWithDetails()` (modificado) → incluye `payments` y `vuelto` (FR-12, FR-14)
- `views/sales/create.php` + `js/modules/sales/sales-create.js` → paso "Cobro" con una fila por método activo + validación/vuelto en tiempo real (FR-5, FR-9)
- `views/sales/show.php` → tarjeta "Pagos" con desglose y vuelto, vacío "—" (FR-12, FR-14)
- `app/Helpers/InvoicePdf.php` → sección de pagos en la factura (FR-13, FR-14)
- `database/migrations/008_formas_de_pago.sql` → crea tablas, siembra catálogo + permiso, backfill idempotente (FR-2, FR-4, FR-15)
- `database/schema.sql`, `database/seeder.sql`, `tests/fixtures/schema.sqlite.sql` → espejo del esquema nuevo
- `app/Core/Controller.php` (`$claves`) + `views/layouts/partials/_sidebar.php` → exponer `manage_payment_methods` y enlazar el catálogo (FR-16)

## Data model

```sql
-- tb_metodos_pago — catálogo configurable
CREATE TABLE tb_metodos_pago (
  id_metodo_pago    int(11)      NOT NULL AUTO_INCREMENT,
  nombre            varchar(60)  NOT NULL,
  tipo              enum('efectivo','no_efectivo') NOT NULL DEFAULT 'no_efectivo',
  activo            tinyint(1)   NOT NULL DEFAULT 1,
  fyh_creacion      datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fyh_actualizacion datetime     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_metodo_pago),
  UNIQUE KEY uq_metodo_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tb_pagos — una línea por método por venta (edge case del spec)
CREATE TABLE tb_pagos (
  id_pago         int(11)       NOT NULL AUTO_INCREMENT,
  id_venta        int(11)       NOT NULL,
  id_metodo_pago  int(11)       NOT NULL,
  monto           DECIMAL(10,2) NOT NULL,
  referencia      varchar(100)  DEFAULT NULL,
  detalle         varchar(255)  DEFAULT NULL,
  fyh_creacion    datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pago),
  UNIQUE KEY uq_pago_venta_metodo (id_venta, id_metodo_pago),
  KEY idx_pago_venta   (id_venta),
  KEY idx_pago_metodo  (id_metodo_pago),
  CONSTRAINT fk_pago_venta  FOREIGN KEY (id_venta)       REFERENCES tb_ventas (id_venta)      ON DELETE CASCADE,
  CONSTRAINT fk_pago_metodo FOREIGN KEY (id_metodo_pago) REFERENCES tb_metodos_pago (id_metodo_pago) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Invariantes:

- `monto > 0`, `DECIMAL(10,2)` evaluado por el motor (redondeo implícito).
- `UNIQUE (id_venta, id_metodo_pago)`: máximo una línea por método por venta; dos
  cobros del mismo método se SUMAN en el cliente antes de llegar al modelo (edge
  case "doble tarjeta" del spec).
- `FK tb_pagos.id_venta → tb_ventas ON DELETE CASCADE`: borrar una venta borra su
  desglose; no interfiere con el orden `destroyWithStock` (tb_ventas → tb_carrito)
  ni bloquea nada (los pagos son detalle puro de la venta, a diferencia de
  `tb_devoluciones`).
- `FK tb_pagos.id_metodo_pago → tb_metodos_pago ON DELETE NO ACTION`: permite
  bloquear la eliminación de un método referenciado vía `isReferenced()` (FR-2).
- El **vuelto nunca se persiste** como línea (FR-9); se deriva `max(0, suma_pagos − total)`.

## Algorithms / non-trivial logic

### 1. Validación + persistencia atómica del cobro (modifica `Sale::storeWithStock`)

Recibe `$payments = [['id_metodo_pago'=>int,'monto'=>float,'referencia'=>?string,'detalle'=>?string], …]`
y ejecuta **todo dentro de la única transacción existente** (sin ventana donde la
venta exista sin sus pagos):

1. Carrito no vacío (ya existe): vacío → `empty_cart`.
2. Congelar `precio_unitario` y calcular `$totalReal = SUM(cantidad*precio_unitario)`
   server-side (ya existe; SIEMPRE ignora el POST).
3. Si `count($metodosActivos) === 0` (catálogo vacío o todo desactivado) → `sin_metodos`
   (FR-11). Cargar mapa `id_metodo_pago → (tipo, activo)` con una query.
4. Por cada `$payment`: validar monto numérico > 0 y método existente y `activo=1`; si
   falla → `invalid_payment`.
5. `$suma = round(array_sum(montos), 2)`.
6. `$suma < $totalReal` → `faltante` (FR-7), con `faltante = $totalReal − $suma`.
7. `$suma > $totalReal` y **ninguna** línea es `tipo='efectivo'` → `exceso_sin_efectivo`
   (FR-8).
8. INSERT cabecera `tb_ventas` (ya existe).
9. INSERT una fila en `tb_pagos` por método con monto > 0 (ya sumado por método en el
   paso 4) + `referencia`/`detalle` opcionales (FR-10).
10. Decrementar stock con `AND stock >= ?` y `rowCount() === 0` → rollback (ya existe).
11. COMMIT → retorna `['ok'=>true, 'id_venta', 'vuelto' => max(0, $suma − $totalReal)]`.

Edge: suma == total → pago exacto, `vuelto = 0` (FR-6).

### 2. Backfill idempotente (migración `008`, FR-15)

```sql
INSERT INTO tb_pagos (id_venta, id_metodo_pago, monto)
SELECT v.id_venta, m.id_metodo_pago, v.total_pagado
FROM tb_ventas v
JOIN tb_metodos_pago m ON m.nombre = 'Efectivo'
WHERE NOT EXISTS (SELECT 1 FROM tb_pagos p WHERE p.id_venta = v.id_venta);
```

Re-ejecutar no duplica: el `WHERE NOT EXISTS` salta ventas que ya tienen pagos.
Opcional: aplica SOLO si el negocio decide que lo histórico se asume como efectivo
(documentado en el header de la migración). Ventas sin backfill muestran "—"
(FR-14).

### 3. Cálculo de vuelto (reutilizado en show y PDF)

`SalePayment::vueltoFor(float $total, array $payments): float` →
`max(0, round(array_sum(montos), 2) − $total)`. Función pura, testeable en Unit.

## Technical decisions

- **Un solo slug `manage_payment_methods` gatea todo el catálogo** (index + CRUD), NO el
  par `view_*`/`manage_*` → el spec nombra un solo permiso (FR-16) y es coherente con
  el patrón `manage_inventory` (un permiso = un módulo). Descarte: par view/manage —
  agregaría un slug que el spec no autoriza.
- **El paso "Cobro" REEMPLAZA el Tab 3 "Pago" existente** (`pane-pago`) en vez de añadir
  un 4º tab → el wizard ya tiene un paso de cobro; convertirlo evita duplicar los
  inputs de monto y alarga el flujo sin ganancia. Se quitan `total_pagado` (input
  único) y el hidden `total_a_cancelar`: el total y la suma salen del server (constitución §6).
  ⚠️ Si al aprobar el plan se prefiere un 4º tab independiente, decirlo aquí. La
  decisión NO cambia el modelo ni la validación, solo la vista.
- **`Sale::storeWithStock()` cambia su retorno de `int|false` a `array`** →
  `['ok'=>bool,'id_venta'=>?int,'vuelto'=>float,'error'=>?string,'faltante'=>?float]`:
  los errores de FR-7/FR-8 necesitan códigos concretos que `false` no distingue.
  Impacto acotado y controlado: único caller es `SaleController::store` y los ~10
  asserts de `SaleRepositoryTest` se actualizan en la misma tarea. Descarte: propiedad
  `$lastError` en el modelo — estado mutante menos explícito que un retorno tipado.
- **Una línea por método (UNIQUE = venta + método), sumando duplicados en cliente** →
  edge case "doble tarjeta" del spec; validado además en el modelo.
- **Borrado de venta CASCADE sobre pagos** → los pagos son detalle puro; `destroyWithStock`
  no cambia. La eliminación del método se bloquea con `isReferenced()` (NO ACTION).
- **Backfill en la migración `008` (SQL idempotente), no en código** → mismo precedente
  que 006 (backfill inline). El seeder solo toca instalaciones frescas.
- **Catálogo con patrón modal+AJAX igual a categories** (partial `_modals.php`, jQuery
  Validate + `remote` con `checkNombre`) → cero sorpresas para el mantenedor. El
  activar/desactivar se hace con el verbo `update` estándar (campo `activo`), sin
  verbos `toggle`/`activate` (NFR del spec).
- **Desglose en `show` y `InvoicePdf` deriva `vuelto` vía `SalePayment::vueltoFor`** →
  una sola fuente de verdad para el cálculo; nunca se guarda.

## Interface contract

```php
// Sale (modificado)
public function storeWithStock(array $data, array $payments): array;
//   data:   ['nro_venta'=>int, 'id_cliente'=>int]   ← total_pagado ya NO se usa
//   payments: [['id_metodo_pago'=>int,'monto'=>float,'referencia'=>?string,'detalle'=>?string], …]
//   retorna: ['ok'=>bool, 'id_venta'=>?int, 'vuelto'=>float,
//             'error'=>?('empty_cart'|'sin_metodos'|'invalid_payment'|'faltante'|'exceso_sin_efectivo'|'generic'),
//             'faltante'=>?float]
//   sin_metodos solo es alcanzable si el catálogo está vacío/desactivado (FR-11).

public function findWithDetails(int $id): ?array;   // + claves 'payments'[..] y 'vuelto'
```

```php
// PaymentMethod (nuevo, tb_metodos_pago)
public function active(): array;                          // métodos activos para el POS
public function nameExists(string $nombre, ?int $excludeId = null): bool;
public function isReferenced(int|string $id): bool;       // COUNT(tb_pagos) > 0 (FR-2)

// SalePayment (nuevo, tb_pagos)
public function byVenta(int $idVenta): array;             // JOIN tb_metodos_pago → nombre, tipo
public static function vueltoFor(float $total, array $payments): float;
```

```php
// SaleController (modificado)
store(): void      // valida CSRF, lee nro_venta/id_cliente, arma $payments desde POST
                   // (pagos[id_metodo_pago][monto|referencia|detalle]), mapea códigos
                   // de error a flashes en español, incluye vuelto en el toast de éxito.
create(): void     // + 'metodos_activos' (o flag 'sin_metodos_activos') para el paso Cobro.
show(): void       // + 'pagos' y 'vuelto' (FR-12)
invoice(): void    // + 'pagos'/'vuelto' inyectados en $sale antes de InvoicePdf::generate (FR-13)
```

```php
// PaymentMethodController (nuevo)
index(): void              // render índice + partial _modals.php
store(): void              // POST JSON (CSRF) — valida nombre único, tipo ∈ {efectivo,no_efectivo}
show(?int $id = null): void   // GET JSON para pre-llenar modal
update(?int $id = null): void // POST JSON — edita campos y/o `activo` (activar/desactivar)
destroy(?int $id = null): void // POST JSON — isReferenced() → error "desactívalo" (FR-2)
checkNombre(): void        // remote jQuery Validate (auxiliar documentado)
```

```php
// Routes (web.php) — módulo catalogO, gateado por manage_payment_methods
$router->get('/payment-methods',               [PaymentMethodController::class, 'index'],       ['auth','can:manage_payment_methods']);
$router->post('/payment-methods/store',        [PaymentMethodController::class, 'store'],       ['auth','can:manage_payment_methods']);
$router->get('/payment-methods/show/{id}',     [PaymentMethodController::class, 'show'],        ['auth','can:manage_payment_methods']);
$router->post('/payment-methods/update/{id}',  [PaymentMethodController::class, 'update'],      ['auth','can:manage_payment_methods']);
$router->post('/payment-methods/check-nombre', [PaymentMethodController::class, 'checkNombre'], ['auth','can:manage_payment_methods']);
$router->post('/payment-methods/delete',       [PaymentMethodController::class, 'destroy'],     ['auth','can:manage_payment_methods']);
```

```php
// InvoicePdf — firma SIN cambios; $sale ahora trae 'payments' y 'vuelto'
public static function generate(array $sale, array $totals, string $vendedor, int $id): void;
```

## Verification strategy

Automated (suite existente `composer test`):

- **`tests/Integration/Models/PaymentMethodRepositoryTest.php`** → catálogo: create con
  nombre único, `nameExists` con/exclusión, activar/desactivar vía `update`, `active()`
  filtra `activo=0`, `isReferenced()` con y sin líneas de pago (FR-1..FR-3).
- **`tests/Integration/Models/SalePaymentRepositoryTest.php`** (SQLite) → la matriz de
  cobro sobre `Sale::storeWithStock`:
  - pago exacto igual al total (suma == total, sin vuelto) (FR-6)
  - pago mixto efectivo+tarjeta persiste una línea por método con monto>0 y referencia (FR-10)
  - suma < total → `error='faltante'`, `faltante` correcto, sin venta ni pagos (FR-7)
  - suma > total sin línea efectivo → `exceso_sin_efectivo` (FR-8)
  - suma > total con efectivo → `vuelto = suma − total` (FR-9)
  - dos cobros del mismo método → suma en una línea (edge case)
  - `sin_metodos` con catálogo vacío/desactivado (FR-11)
  - `findWithDetails` devuelve `payments` + `vuelto` (FR-12)
  - `SalePayment::vueltoFor` puro (Unit) con 0 y positivos
- **FR-15 idempotencia** → correr el SQL de backfill de la migración 008 dos veces contra
  la fixture SQLite; assert 1 sola línea Efectivo por venta histórica y líneas existentes intactas.
- **`SaleRepositoryTest`** existente: actualizar asserts de `storeWithStock` al nuevo
  retorno array (`$r['ok']`, `$r['id_venta']`) — misma tarea que el cambio de firma.
- **Fixture**: agregar `tb_metodos_pago` y `tb_pagos` a `tests/fixtures/schema.sqlite.sql`
  (tabla de traducción MySQL→SQLite de `AGENTS.md`); sembrar en `setUp` los 4 métodos mínimos.
- No se requieren tests MariaDb: la feature no usa funciones de fecha del motor.

Manual (demo del spec, adjuntar evidencia):

- Pago mixto Efectivo 50 + Tarjeta 50 sobre total 100 → desglose en `show` + PDF.
- Vuelto: Efectivo 60 sobre total 100 → vuelto 0 en pagos, toast muestra vuelto.
- Faltante (< total) y exceso sin efectivo → rechazados con mensaje.
- Catálogo: crear método, desactivarlo (desaparece del POS), eliminar uno referenciado
  → bloqueado con hint "desactívalo".
- Backfill corrido 2 veces → sin duplicados; venta sin backfill muestra "—".
- **axe-core sin violaciones en claro y oscuro** en: paso Cobro, `sales/show.php`, catálogo.

## Risks

- **Migración sobre BDs de producción sin tracker** → `008` idempotente (`CREATE TABLE IF
NOT EXISTS`, `INSERT…WHERE NOT EXISTS`), mismo patrón que `007`.
- **Cambio de retorno de `storeWithStock`** → acotado a `SaleController::store` +
  `SaleRepositoryTest`; pasa por `composer test` antes de cerrar tarea.
- **Romper UX con el paso Cobro nuevo** → el JS valida suma en vivo, deshabilita
  "Guardar venta" hasta suma ≥ total y muestra faltante/vuelto; la validación real
  sigue en el modelo (el JS es solo UX).
- **Axe-core en el wizard** (paso nuevo + efectos en claro/oscuro) → auditar ambos
  temas al cerrar, según skill `impeccable`.
- **Cambio de `findWithDetails` rompe vistas que usan `$sale[...]`** → añadir claves
  (`payments`, `vuelto`) es aditivo, no destructivo; verificar `show` e `invoice`.
- **Pagos históricos sin método** → aceptado por diseño (FR-14: "—" hasta backfill).
  El out-of-scope mantiene la devolución informativa (spec 001 FR-20) sin reembolso.

## Which FR each part covers

| Part                                                                           | FR                      |
| ------------------------------------------------------------------------------ | ----------------------- |
| Catálogo `PaymentMethod` + `PaymentMethodController` + vistas modal            | FR-1, FR-2, FR-4, FR-16 |
| `tb_metodos_pago.activo` + `active()` filtrando POS                            | FR-3                    |
| `Sale::storeWithStock` validación suma-vs-total + vuelto                       | FR-6, FR-7, FR-8, FR-9  |
| `SalePayment` persistencia atómica (UNIQUE venta+metodo)                       | FR-10                   |
| Guard `sin_metodos` en `store` + flag en `create`                              | FR-11                   |
| `findWithDetails` + `views/sales/show.php` desglose y estado "—"               | FR-12, FR-14            |
| `InvoicePdf` desglose y estado "—"                                             | FR-13, FR-14            |
| Migración `008` + seeder + fixture (catálogo, permiso, backfill)               | FR-2, FR-4, FR-15       |
| `Controller.php $claves` + `_sidebar.php` (gate real `manage_payment_methods`) | FR-16                   |
