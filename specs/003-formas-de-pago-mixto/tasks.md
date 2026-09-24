# Tasks — Spec 003

Tareas bajo 30 min, ordenadas por dependencia. Cada una: FRs, checkbox y criterio
`Done when:` verificable. Verificación obligatoria: `composer test` en verde antes
de marcar cualquier `[x]`.

## A — Esquema y migración

- [x] T1. Crear `tb_metodos_pago` y `tb_pagos` en `database/schema.sql` (MySQL/InnoDB, UNIQUE venta+metodo, FKs: id_venta→tb_ventas CASCADE, id_metodo_pago→tb_metodos_pago NO ACTION). (FR-4, FR-10)
      Done when: `schema.sql` contiene ambas tablas; correrlo sobre una BD limpia las crea sin errores.
- [x] T2. Crear las mismas tablas en `tests/fixtures/schema.sqlite.sql` con la traducción MySQL→SQLite (AUTO_INCREMENT→AUTOINCREMENT, DECIMAL(10,2)→NUMERIC, datetime→TEXT, enum→TEXT, ENGINE=→omitir). (FR-10, base de tests)
      Done when: `composer test:integration` sigue en verde y una prueba trivial puede `SELECT`/`INSERT` en ambas tablas.
- [x] T3. Escribir `database/migrations/008_formas_de_pago.sql` idempotente: CREATE TABLE IF NOT EXISTS de ambas tablas, INSERT…WHERE NOT EXISTS de los 4 métodos sembrados (`Efectivo` efectivo, `Tarjeta`/`Transferencia bancaria`/`QR` no efectivo), INSERT del permiso `manage_payment_methods` + grant al rol Administrador (patrón `007`), y el backfill `INSERT INTO tb_pagos … WHERE NOT EXISTS (ventas sin pagos)`. (FR-2, FR-4, FR-15, FR-16)
      Done when: la migración corre dos veces seguidas sin duplicar tablas, métodos, permisos ni líneas de pago (backfill salta ventas ya pagadas).
- [x] T4. Actualizar `database/seeder.sql`: fila `manage_payment_methods` en `tb_permisos` (módulo `ventas`) e INSERT de los 4 métodos en `tb_metodos_pago`; el grant al Administrador ya ocurre por el `CROSS JOIN` existente. (FR-4, FR-16)
      Done when: seeder completo corre sobre BD limpia y queda el permiso + 4 métodos activos + solo Administrador con el permiso.

## B — Modelos y tests

- [x] T5. Crear modelo `PaymentMethod` (`tb_metodos_pago`) con `active()`, `nameExists($nombre, $excludeId)`, `isReferenced($id)` (COUNT `tb_pagos` > 0); crear `tests/Integration/Models/PaymentMethodRepositoryTest.php` (crear con nombre único, duplicado rechazado, desactivar oculta de `active()`, bloqueo por referencia). (FR-1, FR-2, FR-3)
      Done when: `composer test:unit` + `composer test:integration` verdes con los nuevos tests.
- [x] T6. Crear modelo `SalePayment` (`tb_pagos`) con `byVenta($idVenta)` (JOIN `tb_metodos_pago` → nombre, tipo) y `vueltoFor($total, $payments)` puro (`max(0, round(suma,2) − total)`); test Unit de `vueltoFor` (suma == total → 0, suma > total → positivo). (FR-9, FR-12, FR-14)
      Done when: test de `vueltoFor` verde y `byVenta` retorna líneas con nombre/tipo.
- [x] T7. Modificar `Sale::storeWithStock()` a firma `array $data, array $payments): array` con validación server-side dentro de la transacción (carrito vacío → `empty_cart`; sin métodos activos → `sin_metodos`; monto/método inválido → `invalid_payment`; suma < total → `faltante` + monto; suma > total sin efectivo → `exceso_sin_efectivo`; OK → inserta líneas y retorna `ok/id_venta/vuelto`). (FR-6, FR-7, FR-8, FR-9, FR-10, FR-11)
      Done when: `SalePaymentRepositoryTest` cubre la matriz (pago exacto, mixto, faltante, exceso sin efectivo, vuelto, doble método sumado, `sin_metodos`) y todo verde.
- [x] T8. Actualizar `SaleRepositoryTest` a la nueva firma/retorno de `storeWithStock` (`$r['ok']`, `$r['id_venta']` en vez de `int|false`). (FR-6, FR-10)
      Done when: `composer test:integration` verde sin `storeWithStock` antiguo en el test.
- [x] T9. Modificar `Sale::findWithDetails()` para adjuntar `payments` (desde `SalePayment::byVenta`) y `vuelto` (desde `vueltoFor` con `total_pagado`). (FR-12, FR-14)
      Done when: test que llama `findWithDetails` a una venta con y sin pagos retorna `payments`/`vuelto` correctamente (0 y positivo).
- [x] T10. Test de idempotencia FR-15: correr el SQL de backfill de la migración 008 dos veces contra la fixture SQLite; asertar 1 sola línea `Efectivo` por venta histórica y líneas existentes intactas. (FR-15)
      Done when: el test ejecuta el backfill 2 veces y pasa sin duplicados.

## C — Catálogo (rutas, controller, vistas)

- [x] T11. Crear `PaymentMethodController` (verbo estándar `index/store/show/update/destroy` + auxiliar documentado `checkNombre`), registrar las 6 rutas `/payment-methods…` en `routes/web.php` con `can:manage_payment_methods`, y añadir `manage_payment_methods` a `$claves` en `app/Core/Controller.php`. (FR-1, FR-2, FR-16)
      Done when: las 6 rutas responden 403 sin permiso y JSON correcto con permiso.
- [x] T12. Vistas del catálogo: `views/payment-methods/index.php` + `partial/_modals.php` (crear/editar) siguiendo el patrón modal+AJAX de categories, con `pageStyles`/`pageScripts` propios (`/js/modules/payment-methods/*.js`), jQuery Validate + `remote` a `check-nombre`, y switch `activo` dentro de `update` (sin verbos `toggle`/`activate`). (FR-1, FR-2, FR-3)
      Done when: crear, editar, activar/desactivar y eliminar (bloqueado si referenciado) funcionan desde la UI con toasts.
- [x] T13. Añadir enlace "Métodos de pago" en `views/layouts/partials/_sidebar.php` bajo la sección Ventas, gateado por `$can['manage_payment_methods'] ?? false`. (FR-16)
      Done when: el enlace solo aparece para roles con `manage_payment_methods`; sin permiso no está en el DOM.

## D — POS: paso Cobro

- [x] T14. Vista: renombrar el Tab 3 `pane-pago` a "Cobro", quitar los inputs `total_pagado`/`total_a_cancelar`, e imprimir una fila por método activo (nombre + monto; `referencia`/`detalle` solo si tipo `no efectivo`) y una alerta si `sin_metodos_activos`. `SaleController::create()` pasa `metodos_activos` o flag. (FR-5, FR-11)
      Done when: con catálogo activo el paso muestra las filas; con catálogo vacío muestra el mensaje "configure al menos un método".
- [x] T15. JS `sales-create.js`: suma en vivo de montos, muestra de faltante/vuelto, deshabilitar "Guardar venta" hasta suma ≥ total y aviso si exceso sin efectivo (UX; la validación real es server-side). (FR-6, FR-8, FR-9)
      Done when: en navegador, bajar la suma bloquea el submit y subirla habilita; el vuelto aparece en vivo.
- [x] T16. `SaleController::store()`: armar `$payments` desde el POST (una entrada por método con monto > 0), llamar `storeWithStock` con la nueva firma, mapear códigos de error a flashes en español, incluir el vuelto en el toast de éxito. (FR-7, FR-8, FR-9, FR-11)
      Done when: cada error de validación muestra su mensaje y el éxito muestra el vuelto cuando aplica.

## E — Presentación: detalle y PDF

- [x] T17. `views/sales/show.php`: tarjeta "Pagos" con tabla de método, monto, referencia/detalle y vuelto; estado vacío "—" cuando no hay líneas. `SaleController::show()` pasa `pagos`/`vuelto`. (FR-12, FR-14)
      Done when: venta con pagos muestra el desglose; venta histórica sin pagos muestra "—" sin errores.
- [x] T18. `app/Helpers/InvoicePdf.php`: sección de desglose de pagos + vuelto (misma lógica que el detalle); estado vacío "—" cuando no hay líneas. (FR-13, FR-14)
      Done when: la factura PDF de una venta mixta lista método/monto/vuelto y la histórica muestra "—".

## F — Documentación

- [x] T19. Actualizar `AGENTS.md`: añadir `manage_payment_methods` a la lista de slugs de permisos, y documentar `tb_metodos_pago`/`tb_pagos` en el esquema. (FR-16, criterio de completación)
      Done when: el slug y ambas tablas figuran en `AGENTS.md`; el esquema del doc coincide con `schema.sql`.

## Feature closeout

_Casillas fuera de la numeración `Tn` — no se implementan vía `/sdd:implement`. Dejar `<blank>` lo que dependa del proyecto._

- [x] Verificar todos los FR (`/sdd:validate` → `specs/003-formas-de-pago-mixto/validation.md`).
- [x] Mover la feature a `Done ✅` en `docs/roadmap.md`, enlazando esta carpeta.
- [x] Actualizar `CHANGELOG.md` (versión nueva) y `APP_VERSION` en `.env` (cambios en CSS/JS core del POS y sidebar).
- [x] Pasos de deploy/migración: ejecutar `database/migrations/008_formas_de_pago.sql` sobre BDs existentes (idempotente; backfill opcional documentado).
- [x] Demo manual del spec: pago mixto, vuelto, desglose en detalle y PDF, gestión de catálogo (crear, desactivar, bloqueo de eliminación) y backfill corrido dos veces.
- [x] axe-core sin violaciones en claro y oscuro en paso Cobro, `sales/show.php` y catálogo (usar skill `impeccable`).
      Done when: 6 escaneos (3 vistas × light/dark) con 0 violaciones — evidencia en `validation.md` y `evidence/summary.json`.
