# Tasks — Spec 004

Tareas bajo 30 min, ordenadas por dependencia. **T61 va antes que todo lo demás**; T5, T35–T37 y T56 (tests `*MariaDbTest`) no se pueden marcar `[x]` si el test se salta. Cada una: FRs, checkbox y criterio
`Done when:` verificable. Verificación obligatoria: `composer test` en verde antes de
marcar cualquier `[x]`; si la tarea toca UI, además axe-core sin violaciones en claro y
oscuro. Los tests de ventanas de tiempo (login, reset, "recordarme") van en
`*MariaDbTest` (reloj de la BD).

## A — Esquema, fixtures y CI

- [x] T61. **Ejecutar primero.** Entorno de tests MariaDB: copiar `.env.testing.example` a `.env.testing` (ignorado por git) con `DB_TEST_NAME=sistemadeventas_test` y credenciales del MariaDB local; el usuario debe poder crear/borrar BDs `sistemadeventas_test_*` (el trait sufija el PID). Registrar la línea base. (FR-63)
      Done when: `composer test` informa 0 skipped por falta de servidor (los 15 actuales ahora se ejecutan y pasan, o los que fallen quedan listados y resueltos); `git status` no muestra `.env.testing`; la BD de desarrollo no se toca (`DB_TEST_NAME` ≠ `DB_NAME` del `.env`).

- [x] T1. Migración `011_pos_reservas.sql` (tabla `tb_pos_reservas` + fila de arranque de la secuencia por encima de `MAX(nro_venta)` de `tb_ventas` ∪ `tb_carrito`, solo si la tabla está vacía) y la misma tabla en `database/schema.sql` y `tests/fixtures/schema.sqlite.sql`. (FR-2, FR-47)
      Done when: la migración corre dos veces seguidas sin error ni filas duplicadas; `composer test:integration` sigue verde y un `INSERT`+`lastInsertId()` en el fixture devuelve un id mayor al máximo sembrado.
- [x] T2. Migración `012_integridad_unicos.sql`: comprobaciones previas (`nro_venta` repetido, ventas sin líneas, `nro_compra` repetido) que abortan con un error cuyo texto lleva los primeros valores (sin `SIGNAL`: no es preparable y exigiría `CREATE ROUTINE`; la lista completa la da `preflight_004.sql`, T55); luego quitar la FK `tb_ventas_ibfk_2` si existe y crear `UNIQUE uq_venta_nro` y `uq_compra_nro` si no existen. Reflejarlo en `schema.sql` y el fixture SQLite. (FR-5, FR-9, FR-25, FR-56)
      Done when: sobre una BD con un `nro_venta` repetido la migración aborta e informa las filas sin cambiar nada; sobre una BD limpia corre dos veces sin error; `schema.sql` ya no declara la FK y sí los dos `UNIQUE`; la FK se quita aunque su nombre no sea `tb_ventas_ibfk_2` (se localiza por `REFERENCED_TABLE_NAME='tb_carrito'`). (FR-62a)
- [x] T3. Migración `013_tokens_y_auth.sql`: columna `tb_devoluciones.token_formulario CHAR(32) NULL` + `UNIQUE`, tablas `tb_intentos_auth` y `tb_sesiones_recordadas`, y anulación de `reset_token*` y `remember_token*` existentes en `tb_usuarios`. Reflejarlo en `schema.sql` y el fixture SQLite. (FR-17, FR-33, FR-36, FR-47, FR-58)
      Done when: corre dos veces sin error; tras correrla `reset_token` y `remember_token` están en NULL para todos los usuarios; el fixture permite insertar en las tres estructuras nuevas.
- [x] T4. Migración `014_portabilidad_mysql84.sql` (`tb_almacen.imagen` a `VARCHAR(255)` con `MODIFY` condicionado) y reescribir `002`, `003` y `006` sin `ADD COLUMN IF NOT EXISTS` (comprobación en `information_schema` + `PREPARE/EXECUTE`), `schema.sql` sin `TEXT` con default literal. (FR-46, FR-57)
      Done when: `grep -rn "ADD COLUMN IF NOT EXISTS" database/` no devuelve resultados; las migraciones `002`, `003` y `006` corren dos veces seguidas sin error sobre MariaDB; `schema.sql` no contiene `text NOT NULL DEFAULT`; una BD creada con las `002`/`003`/`006` originales (`git show 1.18.3:database/migrations/…`) y otra con las reescritas tienen idéntico `information_schema` (columnas, tipos, defaults y posición); la `014` aborta sin tocar nada si hay una `imagen` de más de 255 caracteres e informa los `id_producto`. (FR-62a, FR-62b, FR-62d)
- [x] T5. Actualizar `database/seeder.sql` si hace falta y crear `tests/Integration/Models/MigrationsMariaDbTest.php`: aplica todas las migraciones en orden sobre una BD vacía y compara tablas/columnas/índices con `schema.sql` vía `information_schema`. (FR-46, FR-47)
      Done when: el test pasa en local con `.env.testing` y se salta sin servidor; falla si se quita una columna de una migración.
- [x] T6. Declarar todas las claves foráneas en `tests/fixtures/schema.sqlite.sql` (según `schema.sql` tras T2) y activar `PRAGMA foreign_keys = ON` en `RefreshDatabase`; completar los seeders de tests que insertan filas hijas sin sus padres. Procedimiento: activar el pragma, correr la suite, listar cada test que falla por clave foránea y corregir su seeder con los padres mínimos (no desactivar el pragma ni borrar FKs del fixture). (FR-50)
      Done when: `composer test` verde con el pragma activo (cero tests rotos por FK, ninguno marcado skipped/incompleto para esquivarlo) y una prueba que inserta una venta con `id_cliente` inexistente falla con error de clave foránea.
- [x] T7. Hacer que `RefreshMariaDatabase` falle (no salte) cuando la variable de entorno `CI` está definida y no hay conexión, y verificar `.github/workflows/tests.yml`. (FR-49)
      Done when: ejecutar un test `*MariaDbTest` con `CI=1` y credenciales inválidas falla; sin `CI` se salta; el job de CI sigue levantando `mariadb:10.11`.

## B — Helpers puros

- [x] T8. Crear `app/Helpers/Money.php` (`toCents`, `fromCents`) y `tests/Unit/Helpers/MoneyTest.php` (mitad hacia arriba, coma o punto, entradas inválidas → null, `33.30 − 11.10`, 10.004 + 10.004). (FR-10)
      Done when: `composer test:unit` verde con los casos listados.
- [x] T9. Crear `app/Helpers/MonthSeries.php` (`lastMonths(int $n)`) y su test Unit con fecha base inyectable (día 31, enero→diciembre). (FR-29)
      Done when: el test devuelve 6 meses consecutivos distintos para el 31 de marzo y para el 29/30/31 de cualquier mes.
- [x] T10. Crear `app/Helpers/ImageUpload.php` (`validate(array $file): ?string`: código de error de subida, extensión, MIME real, 2 MB) con test Unit. (FR-40)
      Done when: el test cubre `UPLOAD_ERR_INI_SIZE`, extensión inválida, MIME falso y archivo válido.

## C — POS: reserva, carrito y cobro

- [x] T11. Crear modelo `PosReservation` (`openFor`, `isOpenFor`, `release`) con el paso de limpieza de reservas propias duplicadas y su test de integración. (FR-2, FR-3)
      Done when: tests verdes: dos usuarios reciben ids distintos aunque abran sin agregar ítems, el mismo usuario retoma su reserva, `release` la marca sin borrarla y las reservas duplicadas propias se reducen a una sin tocar las de otro usuario.
- [x] T12. Refactorizar `CartItem` (`addItem`, `removeItem`, `clearCart` con `$userId` y retorno tipado `['ok','error']`), eliminar `purgeOrphans()` y acumular cantidad en una línea validando el stock total. Actualizar en la misma tarea los llamadores y tests existentes de `CartItem` (`grep -rn "CartItem" app/ tests/`). Tests de integración. (FR-1, FR-4, FR-7, FR-8)
      Done when: `grep -rn "purgeOrphans" app/ tests/` sin resultados y suite completa verde con la firma nueva; tests verdes: carrito ajeno → `forbidden`, venta finalizada → `finalized` (también al cancelar), mismo producto acumula en una sola línea y el stock se valida contra el total ya presente.
- [x] T13. Adaptar `SaleController` (`create`, `addToCart`, `removeItem`, `cancel`) a `PosReservation`/`CartItem` con `Controller::forbidden()`, dejar de depender de `$_SESSION['pos_nro_venta']` y retirar `Sale::nextNumber()`. (FR-1, FR-2, FR-3, FR-4)
      Done when: `grep -rn "pos_nro_venta\|nextNumber\|purgeOrphans" app/ views/ public/js` solo devuelve la caché de lectura permitida (o nada); `composer test` verde.
- [x] T14. `Sale::storeWithStock(array $data, array $payments, int $userId)`: reenvío idempotente (misma venta del mismo usuario → `replayed`, sin tocar stock ni precios; otro usuario → `forbidden`), comprobar reserva abierta, captura del `UNIQUE` para el reenvío concurrente, liberar la reserva al terminar. Tests. (FR-5, FR-6, FR-9)
      Done when: tests verdes: segundo cobro del mismo `nro_venta` devuelve el id original y deja stock y `tb_ventas` iguales; cobro de la reserva de otro usuario → `forbidden`; sin líneas de carrito → `empty_cart`.
- [x] T15. En `Sale::storeWithStock`: montos en céntimos con `Money`, rechazar `total_cero`, `total_excede` (> 99.999.999,99), faltante y `vuelto_excede_efectivo`, sin línea de pago → `invalid_payment`. Tests. (FR-10, FR-11, FR-12, FR-52)
      Done when: tests verdes: total 100 con tarjeta 150 y efectivo 1 → `vuelto_excede_efectivo`; 10.004 + 10.004 se valida y guarda igual; total 0 y total mayor al máximo se rechazan sin crear nada.
- [x] T16. Adaptar `SaleController::store` (y `public/js/modules/sales/sales-create.js` si cambia el contenido enviado) para pasar los montos como texto, mapear los códigos nuevos a mensajes en español y redirigir a `/sales` en el reenvío con el vuelto recalculado. (FR-6, FR-10, FR-11, FR-12, FR-52)
      Done when: `composer test` verde; demo con `curl` de dos envíos del mismo cobro deja una sola venta; cada código de error muestra un flash distinto.
- [x] T17. `PaymentMethod::update`: bloquear el cambio de `tipo` cuando el método tiene pagos (nombre y `activo` siguen editables) y mostrarlo en el modal del catálogo. Test de integración. (FR-13)
      Done when: test verde: método con pagos no cambia de tipo y devuelve error; sin pagos sí cambia; el campo tipo aparece deshabilitado en el modal si tiene pagos.
- [x] T18. Factura y detalle: `Sale::computeInvoiceTotals($items, $totalPagado)` expone `ajuste` (positivo o negativo); `InvoicePdf`, el importe en letras, el vuelto y `views/sales/show.php` usan `total_pagado` y muestran la fila "Ajuste por precios históricos" solo si difiere. Test de integración. (FR-14)
      Done when: test verde con venta histórica de ajuste positivo y otra negativo (la tabla suma `total_pagado`); el PDF de una venta sin diferencia no muestra la fila.
- [x] T19. `Sale::destroyWithStock` vuelve a comprobar devoluciones dentro de la transacción y devuelve `has_returns`; `SaleController::destroy` muestra el mensaje claro. Test. (FR-53)
      Done when: test verde: una venta con devolución registrada no se elimina y devuelve `has_returns`; sin devoluciones se elimina y repone stock como antes.

## D — Devoluciones

- [x] T20. `SaleReturn`: pendiente por producto sumando todas las líneas repetidas, tope del monto acumulado a `total_pagado` (remanente, 0 si agotado) y reposición completa del stock. Tests. (FR-15, FR-16)
      Done when: tests verdes: venta con dos líneas del mismo producto permite devolver el total; la última devolución guarda el remanente y repone todas las unidades; venta sin `tb_pagos` aplica el mismo tope.
- [x] T21. `SaleReturn::register(..., ?string $token)` con `issueToken()`, columna `token_formulario`, reenvío con el mismo token → devuelve la original (`replayed`) y choque de clave → buscar la original; choque de `nro_devolucion` temporal → `conflict`. Tests. (FR-17, FR-18)
      Done when: tests verdes: mismo token dos veces crea una devolución y repone una vez; dos tokens distintos son dos devoluciones válidas si no exceden lo pendiente; la segunda que excede devuelve `conflict`.
- [x] T22. `SaleReturnController::create/store` y la vista de devolución: emitir el token como campo oculto y mostrar el resultado original en un reenvío. (FR-17)
      Done when: `composer test` verde; reenviar el formulario de devolución con `curl` deja una sola fila en `tb_devoluciones`.

## E — Stock, inventario, compras y productos

- [x] T23. `StockAdjustment`: `UPDATE` relativo con piso en 0, `stock_posterior` leído de la BD y `stock_anterior` derivado; extraer `registerInTransaction()` y hacer que `register()` la envuelva. Tests. (FR-21)
      Done when: tests verdes: un ajuste tras una venta simulada entre lectura y escritura no pierde la venta; salida mayor al stock se rechaza; `registerInTransaction` no abre transacción propia (se puede llamar dentro de una externa).
- [x] T24. `Product::createProduct` registra "Stock inicial" vía `registerInTransaction`; `updateProduct` deja de escribir `stock` e `id_usuario`; `ProductController` y `views/products/edit.php` muestran el stock como solo lectura. Tests. (FR-19, FR-54)
      Done when: tests verdes: alta con stock 5 crea la entrada en `tb_ajustes_stock`; editar con `stock=999` en el POST no cambia el stock ni el `id_usuario`; la vista no tiene `input` de stock editable en la edición.
- [x] T25. `Product::isReferenced` y `getReferenceCount` cuentan `tb_ajustes_stock` y `tb_devolucion_items`; `ProductController::destroy` muestra el bloqueo en lugar de un error interno. Test. (FR-24)
      Done when: test verde: producto con ajuste o con línea de devolución no se elimina y devuelve el mensaje de referencias.
- [x] T26. `Purchase::updateWithStock` y `destroyWithStock` con `UPDATE ... AND stock >= ?`, manejo del cambio de producto, `id_usuario` original conservado y retorno `['ok','error','available']`. Actualizar en la misma tarea `PurchaseController` provisionalmente (mensaje mínimo, T27 lo completa) y los tests existentes de `Purchase`. Tests. (FR-22, FR-23)
      Done when: suite completa verde con el retorno nuevo (ningún llamador usa el booleano viejo: `grep -rn "updateWithStock\|destroyWithStock" app/`); tests verdes: eliminar o editar una compra cuyo producto ya se vendió se rechaza con `stock_insufficient` y el stock disponible; cambiar el producto valida ambos; `id_usuario` no cambia al editar.
- [x] T27. `PurchaseController` adapta mensajes al nuevo retorno y registra en `ActivityLog` la edición con valores anteriores y nuevos. (FR-22, FR-23)
      Done when: `composer test` verde; editar una compra deja una fila `update`/`purchase` con `datos_anteriores` y `datos_nuevos` en `tb_activity_log`.
- [x] T28. `Purchase::validateData` y `Product` con los límites de FR-25 (precios > 0 y ≤ 9.999.999,99; stock 0–1.000.000; cantidad de compra 1–1.000.000; `nro_compra` único en el modelo; fecha válida y no futura). Tests con proveedor de datos. (FR-25)
      Done when: tests verdes para cada límite (válido, borde y fuera de rango) y para `nro_compra` duplicado y fecha futura.
- [x] T29. Registrar en `ActivityLog` la edición de clientes y proveedores, la edición de productos distinta del precio y la eliminación de productos (comprobar que alta y baja de proveedor, cliente y categoría ya registran). (FR-26)
      Done when: `grep -n "ActivityLog::record" app/Controllers/{Client,Supplier,Category,Product}Controller.php` muestra una llamada en cada alta, edición y baja existente; una edición de cliente genera su fila.
- [x] T30. Test de auditoría de escrituras de stock: recorrer `app/` y fallar si aparece un `UPDATE tb_almacen SET stock` fuera de `Sale`, `SaleReturn`, `Purchase`, `StockAdjustment` y `Product::createProduct`. (FR-20)
      Done when: el test pasa y falla si se agrega un `UPDATE tb_almacen SET stock` en un controlador.

## F — Reportes y dashboard

- [x] T31. `Report`: total de ventas con las devoluciones imputadas por `fyh_creacion`, `returnsOutsideListed()` para la fila "Devoluciones de ventas de períodos anteriores" y ticket promedio neto (0 sin ventas). Tests (MariaDB para fechas). (FR-27, FR-28)
      Done when: tests verdes: filas + fila adicional suman el total; ticket = neto / número de ventas; sin ventas el ticket es 0.
- [x] T32. `ReportController` y `views/reports/sales.php` muestran la fila adicional y el ticket promedio neto sin cálculos en la vista. (FR-27, FR-28)
      Done when: `composer test` verde; el reporte de un período con devoluciones de ventas anteriores muestra la fila y la suma coincide con el total.
- [x] T33. `DashboardController`, `Sale::totalsByMonth` y `Purchase::totalsByMonth` usan `MonthSeries` y una sola constante `MONTHS = 6`. (FR-29)
      Done when: `grep -n "strtotime(\"-\$i months\")" app/` no devuelve resultados; test de integración verde con fecha base en día 31.

## G — Autenticación y sesión

- [x] T34. `User`: `normalizeEmail`, `MIN_PASSWORD_LENGTH = 8`, `validatePassword`, normalizar al buscar y al guardar (`createUser`, `updateUser`, `updateProfileInfo`), `verifyCurrentPassword`. Tests. (FR-31, FR-34)
      Done when: tests verdes: `"  ADMIN@Sistema.com "` se guarda y busca como `admin@sistema.com`; contraseña de 7 caracteres se rechaza; `verifyCurrentPassword` acepta la correcta y rechaza la incorrecta.
- [x] T35. `User`: `storeResetToken` guarda el hash SHA-256 con expiración `DATE_ADD(NOW(), INTERVAL 1 HOUR)` (pasa a `RESET_TOKEN_MINUTES` en T62), `findByResetToken` busca por hash. Test `*MariaDbTest`. (FR-36)
      Done when: test verde en MariaDB: el valor en BD no es el token en claro, el token en claro lo encuentra y un token con expiración pasada (calculada en SQL) no.
- [x] T36. Crear `AuthAttempt` (umbrales fijos de 5/20/15 min; pasan a `.env` en T62) (`register`, `isBlocked`, `countSince`, `recentForUser`, `clear`) con tests `*MariaDbTest`, incluida una ráfaga: 10 registros y solo los 5 primeros por `id` quedan permitidos. (FR-33, FR-36)
      Done when: tests verdes en MariaDB: 6.º intento por correo → bloqueado; 21.º por IP → bloqueado; correo inexistente también cuenta; `clear` reinicia el contador del correo; intentos fuera de la ventana no cuentan.
- [x] T37. Crear `RememberToken` (`issue` con vigencia `AuthSettings::rememberDays()`, `consume` con rotación solo de esa fila, `revoke`, `revokeAllFor`) con tests `*MariaDbTest`. (FR-37, FR-58)
      Done when: tests verdes en MariaDB: dos dispositivos conviven; consumir rota el validador y el anterior deja de servir; validador incorrecto devuelve `null` sin modificar la fila; `revokeAllFor` borra todas; token vencido no entra.
- [x] T62. Umbrales de autenticación por `.env` (cambio de spec, FR-64): `Config::int()`, `app/Helpers/AuthSettings.php` con los 7 getters (defaults y rangos del plan), y adaptar lo ya hecho: `AuthAttempt` (máximos y ventana; la purga usa `max(1 día, ventana)`) y `User::storeResetToken` (`INTERVAL <minutos> MINUTE` con el entero validado). Declarar las variables en `.env.example` con sus defaults. Tests Unit de `Config::int`/`AuthSettings` y ajustar los de `AuthAttempt`/`User` para fijar los valores con `$_ENV`. **Ejecutar antes de T37.** (FR-64, FR-33, FR-36)

- [x] T63. `AuthController::sendResetLink`: enviar el correo después de cerrar la respuesta al cliente (`fastcgi_finish_request()` si existe, o cierre de conexión con `Content-Length`/`flush`), para que el tiempo de respuesta no distinga un correo registrado de uno inexistente (el envío SMTP tarda ~6 s). Verificar con `curl -w %{time_total}` que ambos casos responden en tiempos comparables. (FR-35, FR-36)
      Done when: `composer test` verde; tests Unit cubren default, no entero, fuera de rango y valor válido de cada variable; un test de `AuthAttempt` con `LOGIN_MAX_PER_EMAIL=2` bloquea el 3.º intento y otro con `LOGIN_WINDOW_MINUTES=1` ignora intentos de hace 2 minutos; `grep -rn "const LOGIN_" app/` no devuelve resultados; `.env.example` lista las 7 variables.
- [x] T38. `Auth`: `startSession() con `HttpOnly`, `SameSite=Lax`y`Secure`según HTTPS (proxy incluido), regenerar el token CSRF en`login()`. (FR-30)
Done when: `curl -i`a`/auth`muestra`Set-Cookie`con`HttpOnly; SameSite=Lax`; con `X-Forwarded-Proto: https`aparece también`Secure`; el CSRF cambia tras iniciar sesión.
- [x] T39. `Auth::loginWithCookie`/`login($remember)`/`logout` pasan a `RememberToken`, registran el cambio de IP en `ActivityLog` sin rechazar, y una cookie inválida solo borra la cookie del cliente; la vigencia de la cookie (`expires`) usa `AuthSettings::rememberDays()` (o el `expira` que devuelve `issue()`), no `$_ENV` a mano; retirar de `User` los métodos viejos de "recordarme" y de bloqueo por cuenta. (FR-37, FR-58)
      Done when: `grep -rn "storeRememberToken\|findByRememberToken\|clearRememberToken\|recordFailedLogin\|clearExpiredLock" app/` no devuelve resultados; `composer test` verde; demo con dos navegadores: ambos siguen recordados.
- [x] T40. `AuthController::store` con el flujo de `AuthAttempt` (umbrales de `AuthSettings`): registrar antes de verificar, mensaje único con salida a "¿Olvidaste tu contraseña?", hash ficticio para correos inexistentes y limpiar al éxito; el mensaje de bloqueo no cita un número fijo de minutos (o usa `AuthSettings::loginWindowMinutes()`). (FR-33, FR-34, FR-64)
      Done when: ráfaga de 20 logins paralelos (`xargs -P 20`) contra una cuenta hace como máximo 5 verificaciones (conteo en `tb_intentos_auth`); los tres fallos (correo inexistente, clave incorrecta, bloqueada) muestran el mismo texto.
- [x] T41. `AuthController::sendResetLink`: límites con `AuthAttempt` y `AuthSettings` (`RESET_COOLDOWN_MINUTES` por usuario, `RESET_MAX_PER_IP_HOUR` por hora por IP), mismo mensaje exista o no el correo, nunca mostrar el enlace, `error_log` solo con `APP_DEBUG=true`; eliminar `views/auth/show-reset-link.php`, escapar datos en el HTML de los correos de `EmailService`, y que el plazo mostrado en el correo (texto plano y HTML) salga de `AuthSettings::resetTokenMinutes()` en vez de "60 minutos" fijo. (FR-35, FR-36, FR-41, FR-64)
      Done when: `views/auth/show-reset-link.php` no existe y nada lo referencia; con `APP_DEBUG=true` y con `APP_ENV=local` el enlace no aparece en pantalla; un nombre con `<script>` sale escapado en el HTML del correo (test Unit sobre el cuerpo generado).
- [x] T42. `UserController::updatePassword` exige la contraseña actual, `User::updatePassword`/`updateUser` con contraseña y el restablecimiento llaman a `RememberToken::revokeAllFor`; mínimo de 8 en alta, perfil, administrador y reset. (FR-31, FR-32)
      Done when: tests verdes (cambio sin contraseña actual → error; cambio válido borra todas las filas de `tb_sesiones_recordadas` del usuario); un administrador cambia la de otro sin pedirla.

## H — Robustez

- [x] T43. `Router`: el parámetro `{id}` solo coincide con dígitos (404 si no); mantener `{token}` alfanumérico. Test Unit del patrón. (FR-38)
      Done when: `GET /products/abc` y `GET /sales/show/1x` responden 404; `/auth/reset-password/{token}` sigue funcionando; el test Unit del patrón pasa; un script recorre las 29 rutas con `{id}` de `routes/web.php` y confirma que ninguna recibe hoy valores no numéricos desde vistas ni JS (`grep` de los `href`/`fetch` que las construyen). (FR-62)
- [x] T44. `Database::connect()` lanza `RuntimeException` genérica (detalle a `error_log`) en lugar de `die()`, y `public/index.php` la captura y renderiza `errors/500` sin datos de conexión. (FR-39)
      Done when: con MariaDB detenida la respuesta es la página 500 sin usuario ni host y el detalle aparece en el log de errores.
- [x] T45. `ProductController` usa `ImageUpload` (verifica el código de error antes de procesar), ignora `image_text` del cliente y conserva la ruta de la BD; añadir `public/uploads/products/.htaccess` que impide ejecutar PHP y confirmar que no está en `.gitignore`. (FR-40)
      Done when: subir 3 MB muestra "No se pudo subir la imagen" sin error 500; `git check-ignore public/uploads/products/.htaccess` no lo ignora; un POST con `image_text` manipulado no cambia la imagen guardada.

## I — Interfaz y assets

- [x] T46. Ocultar Nuevo/Editar/Eliminar sin permiso en categorías, proveedores y clientes (listados y detalles) usando el `$can` del controlador; sin `Auth::` en vistas. (FR-42)
      Done when: con un rol solo de lectura (`view_*` sin `manage_*`) los botones no se renderizan en esas tres vistas; `grep -n "Auth::" views/` no devuelve resultados nuevos.
- [x] T47. Ocultar botones sin permiso en productos, compras y ventas (listados y detalles) más el botón "Nuevo cliente" del POS sin `manage_clients`. (FR-42)
      Done when: con rol sin `manage_*` correspondiente los botones no se renderizan en esas vistas y el botón "Nuevo cliente" desaparece del POS.
      Nota: axe sin violaciones nuevas; `products/show`, `purchases/index` y `purchases/show` ya fallaban antes de la tarea (`aria-required-children`, `aria-required-parent`, `heading-order`, `listitem`, `button-name`, `link-name`) y se corrigen en la pasada de axe del cierre.
- [x] T48. Ocultar botones sin permiso en usuarios (`manage_users`), roles y permisos (`manage_roles`), métodos de pago (`manage_payment_methods`) y devoluciones (`manage_returns`); no crear botones de eliminar que hoy no existen. (FR-42)
      Done when: con rol sin esos permisos los botones existentes no se renderizan; la ruta sigue respondiendo 403 por URL directa.
      Nota: axe sin violaciones nuevas; `users/index` falla `button-name` por el botón de colapsar sin `aria-label` (ya fallaba antes de la tarea) y se corrige en la pasada de axe del cierre.
- [x] T49. Corregir enlaces rotos: `/categories/create` → `/categories`, `/suppliers/create` → `/suppliers` y el `/dashboard` de `views/returns/show.php` → `/`; verificar con un barrido de `href` contra `routes/web.php`. (FR-43)
      Done when: ningún `href` ni `action` de `views/` apunta a una ruta inexistente según un script que lo compare con `routes/web.php`.
      Nota: axe sin violaciones nuevas; `purchases/create` falla `button-name` y `label` en elementos que la tarea no tocó (el enlace corregido ya tiene `aria-label`) y se corrige en la pasada de axe del cierre.
- [x] T50. Versionar con `?v=<?= APP_VERSION ?>` los assets propios de `views/layouts/auth.php` y de `views/errors/*`. (FR-44)
      Done when: `grep -n "css/\|js/" views/layouts/auth.php views/errors/*.php` muestra `?v=` en todo asset propio (no vendors).
- [x] T51. Quitar `Auth::` del layout: el controlador entrega el token CSRF a la vista; emitir `BASE_URL` con `json_encode` en JS y escapar los atributos `data-*` de moneda. (FR-45, FR-55)
      Done when: `grep -rn "Auth::" views/layouts/` no devuelve resultados; `grep -n "BASE_URL" views/layouts/*.php` en contexto JS usa `json_encode`; `composer test` verde.

## J — Calidad y regresión

- [x] T52. Completar los tests pedidos por la spec que aún no existan: `Role::grantsSuperadmin` y reversión de compra con stock ya vendido (los demás los cubren T11–T37). (FR-48)
      Done when: `composer test` verde y `grep -rn "grantsSuperadmin" tests/` encuentra al menos un caso positivo y uno negativo.
- [x] T53. Reemplazar los `expectNotToPerformAssertions` por aserciones verificables. (FR-51)
      Done when: `grep -rn "expectNotToPerformAssertions" tests/` no devuelve resultados y `composer test` sigue verde.
- [x] T54. Revisión de cobertura FR → test: tabla en `specs/004-auditoria-seguridad-integridad/validation.md` (borrador) con, para cada FR, el test o la demo que lo cubre. (FR-1 a FR-63)
      Done when: los 63 FR aparecen en la tabla con un test existente o una demo asignada; ninguno queda vacío.

## K — Compatibilidad, limpieza y documentación

_Ejecutar después de A–J (T58 puede hacerse en cuanto estén T2 y T4)._

- [x] T55. `database/preflight_004.sql` (solo `SELECT`, sin ningún `INSERT`/`UPDATE`/`DELETE`/`ALTER`): lista `nro_venta` repetidos, ventas sin líneas en `tb_carrito`, `nro_compra` repetidos, `tb_almacen.imagen` de más de 255 caracteres y carritos abiertos sin venta. Documentar su uso en el closeout. (FR-62c)
      Done when: sobre una BD con cada problema sembrado devuelve las filas de cada comprobación; sobre una BD limpia devuelve cero filas en todas; `grep -inE "insert|update|delete|alter|drop" database/preflight_004.sql` no devuelve sentencias.
- [x] T56. `tests/Integration/Models/MigrationsUpgradeMariaDbTest.php`: crea una BD con el esquema de `1.18.3` (`git show 1.18.3:database/schema.sql`), la llena con datos representativos (ventas con y sin cliente, pagos, devoluciones, compras, un carrito abierto, correos con mayúsculas, tokens de "recordarme"), aplica `011`–`014` y compara conteos y sumas de ventas, pagos, devoluciones, compras y stock antes y después; un segundo caso con datos que deben abortar (duplicados, ventas sin líneas, imagen de 300 caracteres) comprueba que ninguna fila cambia. (FR-62a, FR-62b, FR-62f)
      Done when: el test pasa en local con `.env.testing` y se salta sin servidor; falla si una migración borra o reescribe una fila de negocio; un login con contraseña de 6 caracteres sembrada y un correo con mayúsculas siguen funcionando contra los modelos nuevos.
- [x] T57. Prueba de compatibilidad hacia atrás: con la BD de la demo ya migrada (`011`–`014`), levantar la `1.18.3` en un `git worktree` temporal y recorrer login, abrir el POS, vender, devolver y listar reportes. (FR-62e)
      Done when: el recorrido termina sin errores en la `1.18.3` contra la BD migrada; evidencia (salida y capturas) en `specs/004-…/evidence/`; el worktree se elimina al terminar.
- [x] T58. Limpiar los comentarios con FR/specs en `app/` (hoy `Sale.php`, `SaleReturnController.php`, `PaymentMethod.php`, `PaymentMethodController.php`), conservando los que expliquen el porqué en términos del dominio. **Commit aparte**, solo comentarios. (FR-59)
      Done when: `grep -rnE "FR-[0-9]+|[Ss]pec [0-9]{3}" app/` no devuelve resultados; `git diff` del commit solo toca comentarios; `composer test` verde.
- [x] T59. Adaptar `.claude/skills/code-review/` a este repo: quitar `SistemaReservasHospital`, ramas `feature/rfXX` y `develop`, usar `master`, y derivar el checklist de `docs/constitution.md` y de las prohibiciones de `AGENTS.md`. (FR-60)
      Done when: `grep -rniE "SistemaReservasHospital|feature/rf|develop" .claude/skills/code-review/` no devuelve resultados; el checklist cita al menos SQL con placeholders, borrado físico con `isReferenced()`, `$can` en vistas y CSRF; quitar el aviso de "tarea pendiente" de `CLAUDE.md`.
- [x] T60. Sincronizar `AGENTS.md`, `CLAUDE.md` y `.env.example` con el código final: firma real de `SalePayment::vueltoFor`, columnas reales de `tb_usuarios` (las obsoletas marcadas sin uso), `tb_intentos_auth` y `tb_sesiones_recordadas`, las 7 variables de umbrales de autenticación (FR-64), reglas de login y reset (sin enlace en pantalla, ni con `APP_DEBUG`), mínimo de contraseña 8, `SESSION_LIFETIME` con default 60 min (igual al código), `REMEMBER_LIFETIME` 14 días, `show-reset-link.php` ya eliminada, la limitación de que un atacante puede bloquear el login de un correo ajeno durante la ventana (la salida es «¿Olvidaste tu contraseña?»), reserva del POS y requisito del `.htaccess` de subidas. (FR-61)
      Done when: cada afirmación tocada se contrasta con el código (`grep`/lectura) y `AGENTS.md` ya no dice que `APP_DEBUG` muestra el enlace de reset ni mínimo de 6; `.env.example` y `CLAUDE.md` coinciden.

## L — Correcciones surgidas de la demo manual

_Hallazgo H-1 de `validation.md`, encontrado en la demo manual del cierre._

- [x] T64. Reenvío simultáneo de un cobro: en `Sale::storeWithStock`, cuando `PosReservation::isOpenFor` falla, volver a buscar la venta con una lectura nueva (fuera de la instantánea de la transacción) antes de responder `forbidden`; si existe y es del mismo usuario devolver `replayed` (sin tocar stock ni precios), si es de otro usuario `forbidden`, y si no existe `forbidden` como hoy. Test primero en `SaleRepositoryTest`: una subclase de `Sale` cuyo `findFinalized` devuelve `null` en la primera llamada (la instantánea vieja de la pestaña perdedora) y el dato real después, con la venta ya confirmada y la reserva liberada. (FR-5, FR-6)
      Done when: `composer test` verde con tests nuevos que fallan antes del cambio y pasan después: (a) misma persona, instantánea vieja → `replayed` con el `id_venta` original y stock y `tb_ventas` iguales; (b) otro usuario → `forbidden`; (c) reserva nunca abierta y sin venta → `forbidden`; la demo `evidence/demo-manual.py` ya no reporta el FAIL del paso 1 (dos envíos simultáneos desde dos pestañas dejan una sola venta y ninguna pestaña recibe «sin permisos»); `validation.md` actualizado (FR-6 PASS y H-1 cerrado).

## Feature closeout

_Casillas fuera de la numeración `Tn` — no se implementan vía `/sdd:implement`. Dejar `<blank>` lo que dependa del proyecto._

- [x] Verificar todos los FR (`/sdd:validate` → `specs/004-auditoria-seguridad-integridad/validation.md`).
- [x] Mover la feature a `Done ✅` en `docs/roadmap.md`, enlazando esta carpeta.
- [x] Actualizar `CHANGELOG.md` (versión nueva) y `APP_VERSION` en `.env` (cambios en CSS/JS core y layouts).
- [x] Pasos de deploy/migración, en este orden (FR-62): (1) correr `database/preflight_004.sql` en la BD de la instalación (`mysql -u USUARIO -p NOMBRE_BD < database/preflight_004.sql`; solo lee) y resolver a mano lo que informe — cero filas significa que se puede migrar; (2) respaldo de la BD; (3) fuera del horario de ventas, `006`–`010` pendientes (las `002`/`003`/`006` reescritas ya aplicadas se omiten) y luego `011`–`014`; (4) código nuevo y `APP_VERSION`. Avisar en el CHANGELOG los cambios visibles: stock solo lectura en la ficha de producto, contraseña actual para cambiar la propia, mínimo 8 en contraseñas nuevas, reset sin enlace en pantalla, un reinicio de sesión para quien usaba "recordarme" y botones ocultos sin permiso. Plan de vuelta atrás: restaurar solo el código (el esquema migrado es compatible con 1.18.3, T57).
- [x] Demo manual del spec: doble envío de venta y de devolución; dos vendedores abriendo el POS a la vez; carrito ajeno rechazado; vuelto mayor al efectivo; compra revertida con stock vendido; ficha de producto con stock de solo lectura; ráfaga de logins; reset sin enlace; cookie de sesión con flags; dos dispositivos recordados; perfil del vendedor (cambio de email sin error y de contraseña exigiendo la actual) — antes de esta demo, dejar en la BD local un vendedor con contraseña conocida y `login_intentos`/`login_bloqueado_hasta` en limpio.
- [x] axe-core sin violaciones en claro y oscuro en las vistas modificadas (usar skill `impeccable`).
      Done when: escaneos en claro y oscuro de cada vista tocada con 0 violaciones — evidencia en `validation.md` y `evidence/summary.json`.
