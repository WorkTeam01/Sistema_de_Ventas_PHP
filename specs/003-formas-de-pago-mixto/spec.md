# Spec 003 — Formas de pago + pago mixto

**Status:** done
**Prerequisito de:** arqueo / cuadre de caja (roadmap backlog) — normaliza los
pagos por método para que ese módulo pueda cuadrar la caja.

## Context and goal

Hoy una venta se registra con un único `total_pagado` (suma server-side de las
líneas del carrito) y no existe forma de saber **con qué método** se cobró. Para
un negocio retail, esa información es imprescindible: el efectivo va a la caja
física, la tarjeta al POS bancario, una transferencia/QR a la cuenta — y cada
método se concilia después. Esta feature agrega un **catálogo configurable de
métodos de pago** (efectivo, tarjeta, transferencia, QR y otros) y permite
registrar **pagos mixtos por venta** (una venta cubierta con varios métodos, p.
ej. mitad efectivo + mitad tarjeta), con validación del total, cálculo de vuelto
y desglose visible en el detalle de la venta y en la factura PDF. No emite
reembolsos: la **devolución** ya está definida como informativa (spec 001 FR-20,
no revierte pagos). Es la base que el futuro arqueo de caja consumirá.

**Vuelto vs. pago parcial (frontera):** el vuelto es el exceso de **efectivo**
sobre el total que se devuelve al cliente al momento del cobro — la caja recibe
el _total_, no lo bruto. Un pago **menor** al total no pertenece a esta feature:
eso es una venta a crédito / cuenta corriente (backlog). Regla acordada: la suma
de pagos debe ser **≥ total**; el exceso se admite **solo** si hay una línea de
un método tipo _efectivo_, y ese exceso es el vuelto.

## Users / actors

- **Vendedor** — registra el cobro de una venta en el POS (paso "Cobro" del
  wizard) con uno o varios métodos activos.
- **Administrador / rol con `manage_payment_methods`** — gestiona el catálogo de
  métodos (crear, editar, activar/desactivar, eliminar si no está referenciado).

## User stories

- U1: Como vendedor quiero cobrar una venta con varios métodos (efectivo + tarjeta)
  para que el cliente pague como pueda y el registro refleje qué entró por cada
  método.
- U2: Como vendedor quiero que el sistema calcule el vuelto cuando el cliente
  paga de más en efectivo, para no equivocarme en el cambio.
- U3: Como administrador quiero configurar los métodos de pago disponibles
  (efectivo, tarjeta, transferencia, QR, …) para adaptar el POS al negocio.
- U4: Como administrador quiero ver en el detalle de la venta y en la factura PDF
  con qué métodos y montos se pagó, para conciliar cada método al cierre.

## Functional requirements (EARS acceptance criteria)

### Catálogo de métodos de pago

- FR-1: WHEN un usuario con el permiso `manage_payment_methods` gestiona el
  catálogo, THE SYSTEM shall permitirle crear, editar, activar, desactivar y
  eliminar métodos de pago, cada uno con un nombre, un tipo (`efectivo` / `no
efectivo`) y un estado activo.
- FR-2: IF un método de pago está referenciado por al menos una línea de pago,
  THEN THE SYSTEM shall bloquear su eliminación (patrón `isReferenced()`) y
  ofrecer desactivarlo en su lugar.
- FR-3: WHEN un método es desactivado, THE SYSTEM shall ocultarlo del paso Cobro
  del POS e impedir nuevos pagos con él, sin alterar los pagos históricos que lo
  usan.
- FR-4: THE SYSTEM shall sembrar el catálogo con los métodos `Efectivo` (tipo
  `efectivo`), `Tarjeta`, `Transferencia bancaria` y `QR` (tipo `no efectivo`).

### Registro del pago mixto en el POS

- FR-5: WHEN el vendedor llega al paso Cobro del wizard de venta, THE SYSTEM
  shall mostrar el total de la venta y una fila por cada método de pago activo
  con un campo de monto.
- FR-6: WHEN el vendedor confirma el cobro, THE SYSTEM shall validar que la suma
  de los montos por método sea **mayor o igual** al total de la venta, calculando
  tanto el total como la suma server-side dentro de la transacción.
- FR-7: IF la suma de los montos es menor al total de la venta, THEN THE SYSTEM
  shall rechazar el cobro, indicar el faltante y no crear la venta ni pagos.
- FR-8: IF la suma de los montos es mayor al total y **ninguna** línea es de un
  método tipo `efectivo`, THEN THE SYSTEM shall rechazar el cobro indicando que
  el exceso solo se admite en efectivo.
- FR-9: WHERE la suma de los montos es mayor al total y al menos una línea es
  efectivo, THE SYSTEM shall calcular el **vuelto** como la diferencia
  (suma − total) y mostrarlo en la confirmación; el vuelto no se persiste como
  línea de pago.
- FR-10: WHEN el cobro es válido y se confirma, THE SYSTEM shall registrar una
  línea de pago por cada método con monto > 0 (método, monto), dentro de la misma
  transacción que crea la venta (`Sale::storeWithStock`).
- FR-11: IF no existe ningún método de pago activo, THEN THE SYSTEM shall
  bloquear la finalización de la venta e indicar al administrador que debe
  configurar al menos un método.

### Consulta y presentación

- FR-12: THE SYSTEM shall mostrar en el detalle de la venta el desglose de pagos:
  método, monto, y el vuelto si correspondió.
- FR-13: THE SYSTEM shall incluir en la factura PDF (`InvoicePdf`) el desglose de
  pagos y el vuelto si correspondió.
- FR-14: WHERE una venta no tiene líneas de pago (histórica o backfill no
  ejecutado), THE SYSTEM shall mostrar un estado vacío ("—") en el detalle y en
  el PDF, sin errores ni duplicación de montos.

### Datos históricos (backfill)

- FR-15: THE SYSTEM shall proveer un backfill **idempotente** que cree una línea
  de pago `Efectivo` con monto = total de la venta para toda venta **sin** líneas
  de pago, y que no duplique líneas al re-ejecutarse (salta ventas que ya tienen
  pagos). Su ejecución es opcional y documentada.

### Reglas fijas y autorización

- FR-16: THE SYSTEM shall requerir `manage_sales` para registrar el cobro (flujo
  de venta existente) y `manage_payment_methods` para gestionar el catálogo.
- FR-17: THE SYSTEM shall tratar los pagos como dato informativo por venta; no
  emite reembolso ni reversión de pagos en devoluciones (consistente con spec 001
  FR-20) y no genera movimiento de caja propio — eso pertenece al arqueo futuro.

## Non-functional requirements

- Seguridad: monto total y montos por método se calculan/validan server-side
  desde la BD (`SUM(cantidad * precio_unitario)` del carrito y suma de pagos),
  nunca desde el POST (constitución §6); CSRF en formularios y modales del
  catálogo; PDO con placeholders `?`.
- Dinero: montos en `DECIMAL(10,2)` evaluados en SQL, formateo con
  `number_format(…, 2)` y `APP_CURRENCY_SYMBOL` solo en la capa de salida; nunca
  `round()` intermedio.
- Idempotencia: el backfill de FR-15 puede correrse varias veces sin efecto
  duplicado (venta sin pagos → una línea Efectivo; con pagos → se salta).
- Idioma: strings de usuario en español; identificadores PHP, slug de permiso
  (`manage_payment_methods`) y clases en inglés; nombres de tabla en español con
  prefijo `tb_` (convención real del código).
- Métodos de controlador: la activación/desactivación de métodos se hace vía el
  `update` estándar (campo `activo`), sin inventar verbos `toggle`/`activate`; si
  el módulo nuevo del catálogo require un auxiliar fuera del estándar, el
  `plan.md` debe justificarlo y actualizar la lista de `AGENTS.md`.
- Accesibilidad: vistas nuevas/modificadas sin violaciones de axe-core en modo
  claro y oscuro.

## Edge cases

- Suma de pagos exactamente igual al total → pago exacto, sin vuelto (FR-6).
- Doble tarjeta o doble QR en una misma venta → el paso Cobro ofrece una fila por
  método activo; dos cobros del mismo método se suman en su monto (una sola
  línea por método por venta).
- Pago confirmado: cada fila del Cobro persiste una línea `método + monto`; no
  se capturan referencias de pago en caja (FR-5/FR-10).
- Catálogo vacío o todos desactivados → no se puede finalizar venta (FR-11).
- Método referenciado en ventas históricas → no se elimina, solo se desactiva
  (FR-2/FR-3).
- Ventas previas a la feature → sin líneas de pago; se muestran con "—" hasta
  correr el backfill (FR-14) y el backfill no toca ventas que ya pagaron (FR-15).
- Concurrencia en el cobro → la validación suma-vs-total y la persistencia de
  pagos ocurren en la misma transacción de la venta; no hay ventana donde la
  venta exista sin sus pagos.
- Montos con más de 2 decimales → se redondean a `DECIMAL(10,2)` por el motor.
- Carrito vacío al llegar a Cobro → el wizard ya impide avanzar sin ítems; no
  cambia con esta feature.

## Out of scope

- **Arqueo / cuadre de caja** — este módulo lo consume después; aquí solo se
  normalizan los pagos por método.
- **Reembolso o reversión de pagos** en devoluciones (spec 001 FR-20: devolución
  informativa).
- **Ventas a crédito / cuenta corriente** — el pago parcial (< total) es otra
  feature (backlog).
- Reporte por método de pago o listado propio de pagos (el desglose vive en el
  detalle de la venta y en el PDF; el reporte puede venir con el arqueo).
- Nota de crédito / comprobante adicional por la venta.
- Métodos de pago con integración real a pasarelas/POS bancario (solo registro
  informativo del monto por método).
- Fix del hueco de scoping en `SaleController::destroy` (un vendedor puede
  eliminar por POST una venta ajena): no cambia esquema ni agrega permiso ni
  regla de negocio → va como `fix(sales):` directo, fuera de specs.

## Completion criteria

- Todos los FR con tests que pasan: modelo de pagos (validación suma ≥ total,
  rechazo por faltante, rechazo de exceso sin efectivo, vuelto, pago mixto,
  una línea por método) y el backfill idempotente (FR-15: no duplica al
  re-ejecutar).
- Permiso `manage_payment_methods` sembrado en `schema.sql` + `seeder.sql`
  (catálogo + asignación al rol Administrador) y añadido a la lista de slugs de
  `AGENTS.md`.
- Tablas nuevas del esquema documentadas en `AGENTS.md` y reflejadas en
  `tests/fixtures/schema.sqlite.sql` (con las diferencias de sintaxis de la
  tabla de `AGENTS.md`).
- Demo manual: pago mixto (efectivo + tarjeta), vuelto correcto, desglose en el
  detalle de la venta y en la factura PDF, gestión del catálogo (crear,
  desactivar, bloqueo de eliminación de un método referenciado) y backfill
  corrido dos veces sin duplicar.
- Sin violaciones de axe-core (claro y oscuro) en el paso Cobro, el detalle de la
  venta y las vistas del catálogo.

## Clarifications

Ronda de especificación (2026-09-23):

- Catálogo configurable por el administrador (no métodos fijos) → FR-1 a FR-4.
- Validación del cobro: suma ≥ total recomendada y aceptada; exceso solo con
  efectivo = vuelto; pago menor al total queda fuera (crédito/cuenta corriente)
  → FR-6 a FR-9.
- Nuevo paso "Cobro" en el wizard POS (no apelotonar el paso existente) → FR-5.
- Visibilidad: detalle de venta + factura PDF; sin listado propio ni reporte por
  método en esta iteración → FR-12 a FR-14.
- Histórico: datos existentes válidos sin pagos (mostrar "—") + backfill
  idempotente opcional que asume "Efectivo = total" → FR-15 y FR-14.
- Métodos típicos mínimos: Efectivo, Tarjeta, Transferencia bancaria, QR → FR-4
  (el catálogo permite otros).
