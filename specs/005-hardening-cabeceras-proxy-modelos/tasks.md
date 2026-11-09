# Tasks — Spec 005

Tareas bajo 30 min, ordenadas por dependencia. Ninguna se marca `[x]` sin `composer test` en verde (la suite corre ~1,5 min; no lanzar dos `phpunit` a la vez). No hay cambios de UI, así que no se exige axe-core. FR-9 y FR-10 están retirados y no tienen tarea.

**Transición de la lista blanca:** T8 introduce las listas con valor `null` (= sin restricción) para no romper la suite; T13 las pasa a `[]` (deny-by-default) cuando los 18 modelos ya declaran las suyas.

## A — Cabeceras y HTTPS

- [x] T1. Crear `app/Helpers/SecurityHeaders.php` con `isHttpsUrl()` y `hstsValue()` (puras, sin `includeSubDomains` ni `preload`, valor por defecto 86400) y `tests/Unit/Helpers/SecurityHeadersTest.php`. (FR-4, FR-5, FR-6, FR-7, FR-8)
      Done when: `vendor/bin/phpunit tests/Unit/Helpers/SecurityHeadersTest.php` pasa con casos para `https`/`HTTPS`/`http`/vacío/sin esquema, `HSTS_MAX_AGE` válido, `0`, vacío, texto, negativo y decimal, y un caso que comprueba que el valor nunca contiene `includeSubDomains` ni `preload`.
- [x] T2. Conectar: `Auth::isHttps()` delega en `SecurityHeaders::isHttpsUrl(Config::get('APP_URL'))` (deja de leer `$_SERVER`), `SecurityHeaders::send()` emite HSTS y se llama en `public/index.php` junto a las cabeceras existentes, y `HSTS_MAX_AGE=86400` en `.env.example`. (FR-4, FR-5, FR-6, FR-11)
      Done when: `grep -n "HTTP_X_FORWARDED_PROTO\|\$_SERVER\['HTTPS'\]" app/Core/Auth.php` no devuelve nada; `curl -sI` con `APP_URL=http://` y `X-Forwarded-Proto: https` forjado no trae `Strict-Transport-Security` y la cookie de sesión no lleva `Secure`; `nosniff`, `X-Frame-Options` y `Referrer-Policy` siguen presentes; `composer test` verde.

## B — PDF sin caché

- [x] T3. Crear `app/Helpers/PdfResponse.php` con `headersFor()` (pura: `Content-Type`, `Content-Disposition` con `filename` saneado y `filename*` UTF-8, `Content-Length`, una sola `Cache-Control: private, no-store`) y `send()` (`Output('S')` antes de cualquier cabecera, `header_remove('Pragma')`), más `tests/Unit/Helpers/PdfResponseTest.php`. (FR-1, FR-2, FR-3)
      Done when: `PdfResponseTest` pasa y verifica una sola línea `Cache-Control`, ninguna `Pragma`, nombre con acentos y comillas saneado, y `Content-Length` igual al largo recibido.
- [x] T4. Cambiar `InvoicePdf`, `ReportPdf` y `PurchaseReportPdf` de `Output(…, 'I')` a `PdfResponse::send()`, conservando los nombres `factura_venta_{id}.pdf`, `comprobante_compra_{id}.pdf` y `{filename}.pdf`. (FR-1, FR-2, FR-3)
      Done when: `grep -rn "Output(" app/Helpers` solo muestra el modo `'S'` dentro de `PdfResponse`; `curl -sI` autenticado de los tres PDF muestra una sola `Cache-Control: private, no-store`, sin `Pragma: public`, `Content-Type: application/pdf` e `inline` con el nombre esperado; `composer test` verde.

## C — Revalidación única de sesión

- [x] T5. Memoizar `Auth::check()` con `private static ?bool $checked` y anularlo en `login()`, `logout()`, `destroySession()`, `loginWithCookie()` (si tiene éxito) y `refreshPermissions()`. (FR-12, FR-14)
      Done when: `composer test` verde (incluidos `AuthCheckTest`, `AuthPermissionsTest` y `AuthFormTokenTest` con el reset por reflexión ampliado a `$checked`); `grep -n "checked" app/Core/Auth.php` muestra la asignación en las cinco transiciones.
- [x] T6. Ampliar `tests/Integration/Core/AuthCheckTest.php`: segunda llamada reutiliza el resultado (se borra el usuario entre ambas y sigue `true`), tras reset/`logout()`/`login()` se reevalúa, sesión inválida (cuenta borrada, timeout, contraseña cambiada) devuelve `false` como antes, y un cambio de `permisos_version` se aplica en la petición siguiente. (FR-12, FR-13, FR-14, FR-15)
      Done when: los casos nuevos pasan y fallan si se quita la anulación del memo en `logout()` (comprobado quitándola temporalmente).
- [x] T7. Crear `tests/Unit/RoutesAuthOrderTest.php` que lee `routes/web.php` y falla si algún `'can:…'` aparece sin `'auth'` antes en el mismo arreglo de middleware. (FR-16)
      Done when: el test pasa con las 86 rutas actuales y falla sobre un fragmento sintético con `can:` sin `auth`.

## D — Lista blanca de columnas

- [x] T8. En `app/Core/Model.php`: propiedades `protected ?array $createColumns = null` y `$updateColumns = null`, y `assertWritable()` que valida cada clave con `^[a-z_][a-z0-9_]*$` y, si la lista no es `null`, exige pertenencia (`InvalidArgumentException` antes de `prepare()`); `create()` y `update()` la usan (`insert()` obsoleto la hereda). Crear `tests/Unit/Core/ModelWhitelistTest.php` sobre SQLite con subclase anónima. (FR-18, FR-19)
      Done when: `ModelWhitelistTest` pasa con: columna permitida escribe, no permitida lanza y el recuento de filas no cambia, nombre inválido (`a;b`, `A`, `1x`) lanza aunque no haya lista, y `insert()` valida; `composer test` verde.
- [x] T9. `Product`: `$createColumns` con `stock`, `$updateColumns` sin `codigo`, `stock` ni `id_usuario`; `createProduct()` relanza `InvalidArgumentException` antes de su `catch (\Throwable)`. Test en el repositorio de productos (o nuevo). (FR-18, FR-20, FR-21)
      Done when: `update(1, ['stock' => 5])` y `update(1, ['id_usuario' => 2])` lanzan y no modifican la fila; `createProduct()` sigue insertando con `stock = 0`; `composer test` verde.
- [x] T10. `User`, `Role` y `Permission`: listas de `create` y `update` (confirmar con `grep` las columnas que cada `update()` usa hoy, incluidas las de contraseña); `Role` sin `permisos_version`, `Permission` sin `clave` en `update`. Un test negativo y uno positivo por modelo. (FR-17, FR-18, FR-22)
      Done when: los seis tests pasan, `update` de `Permission` con `clave` lanza, y `composer test` verde con los repositorios existentes sin cambios.
- [x] T11. `Category`, `Client`, `Supplier` y `PaymentMethod`: listas de `create` y `update`; en `PaymentMethod::update()` llamar a `assertWritable()` al inicio del override, antes de sus SELECT. Un test negativo y uno positivo por modelo. (FR-17, FR-18, FR-22)
      Done when: los ocho tests pasan, un `PaymentMethod::update` con columna ajena lanza sin ejecutar ninguna consulta previa (comprobado con un PDO espía o contador de sentencias), y `composer test` verde.
- [x] T12. `CartItem`, `StockAdjustment` y `ActivityLog`: listas de `create` (`update` vacío) y relanzar `InvalidArgumentException` antes de sus `catch (\Throwable)`. Un test negativo y uno positivo por modelo. (FR-17, FR-18, FR-21, FR-22)
      Done when: los seis tests pasan, un `create` con columna ajena en cada uno lanza `InvalidArgumentException` en vez de devolver `false` o escribir en el log, y `composer test` verde.
- [x] T13. Declarar listas vacías en `AuthAttempt`, `RememberToken`, `PosReservation`, `Purchase`, `Sale`, `SaleReturn` y `SalePayment`; cambiar el valor por defecto de `Model` a `[]` (deny-by-default); crear un test con _data provider_ de los 18 modelos concretos que verifica el rechazo de una columna ajena y que cada columna declarada existe en `tests/fixtures/schema.sqlite.sql`. (FR-17, FR-18, FR-22, FR-26)
      Done when: `grep -n "?array \$createColumns\|?array \$updateColumns" app/Core/Model.php` no devuelve nada; el test del _data provider_ pasa para los 18 modelos; `composer test` verde completo.

## E — Auditoría de stock

- [x] T14. Ampliar `tests/Unit/StockWritesAuditTest.php` para detectar `UPDATE tb_almacen [alias] SET … stock`, `INSERT INTO tb_almacen (… stock …)` y `REPLACE INTO tb_almacen`, con o sin backticks y en varias líneas, cada patrón con un caso positivo y uno negativo (p. ej. `stock_minimo`). (FR-23, FR-25)
      Done when: los casos nuevos pasan, la auditoría sobre `app/` real no detecta nada fuera de `ALLOWED`, y agregar temporalmente un `INSERT INTO tb_almacen (stock)` en un controlador la hace fallar.
- [x] T15. Añadir a `StockWritesAuditTest` la comprobación de que ninguna clase de `app/Models` salvo `Product` declara `$table = 'tb_almacen'`. (FR-24, FR-25)
      Done when: el test pasa y falla sobre un archivo temporal con otra clase que declare esa tabla.

## F — Documentación

- [x] T16. Actualizar `docs/guides/autenticacion.md` (`Secure` por `APP_URL`, `X-Forwarded-*` ignorado, revalidación única), `docs/guides/despliegue.md` (`HSTS_MAX_AGE`, nota de que el proyecto no soporta proxies y `LOGIN_MAX_PER_IP` compartido), `docs/guides/testing.md` (nuevos tests), y `AGENTS.md`: lista de escritores de stock sin `Product::createProduct`, lista blanca de `Model` y `HSTS_MAX_AGE`. Sin referencias a `FR-n` ni a la spec en los textos.
      Done when: `grep -n "FR-\|spec 005" AGENTS.md docs/guides/*.md` no añade coincidencias nuevas; `AGENTS.md` y `docs/guides/despliegue.md` mencionan `HSTS_MAX_AGE` y la lista blanca.

## Cobertura de FR

| FR      | Tareas                         |
| ------- | ------------------------------ |
| 1, 2, 3 | T3, T4                         |
| 4, 5, 6 | T1, T2                         |
| 7, 8    | T1                             |
| 9, 10   | retirados                      |
| 11      | T2                             |
| 12, 14  | T5, T6                         |
| 13, 15  | T6                             |
| 16      | T7                             |
| 17, 22  | T10, T11, T12, T13             |
| 18      | T8, T9, T10, T11, T12, T13     |
| 19      | T8                             |
| 20      | T9                             |
| 21      | T9, T12                        |
| 23, 25  | T14                            |
| 24      | T15                            |
| 26      | T13 y cierre (`composer test`) |
| 27      | demo manual del cierre         |

## Feature closeout

_Casillas fuera de la numeración `Tn` — no se implementan vía `/sdd:implement`. Dejar `<blank>` lo que dependa del proyecto._

- [x] Verificar todos los FR (`/sdd:validate` → `specs/005-hardening-cabeceras-proxy-modelos/validation.md`).
- [x] Mover la feature a `Hecho ✅` en `docs/roadmap.md`, enlazando esta carpeta.
- [x] Actualizar `CHANGELOG.md` (versión nueva: 1.20.0) y `APP_VERSION` en `.env` si la versión cambia.
- [x] Pasos de deploy/migración: sin migraciones ni cambios de esquema. Añadir `HSTS_MAX_AGE` al `.env` de cada instalación (opcional; por defecto 86400). Avisar en el CHANGELOG: `Secure` y HSTS dependen de `APP_URL=https://`; detrás de un proxy todos los clientes comparten IP en el límite de intentos.
- [x] Demo manual del spec (evidencia en `specs/005-hardening-cabeceras-proxy-modelos/evidence/`): (1) `curl -I` de los tres PDF; (2) `X-Forwarded-Proto` forjado con `APP_URL=http://` y cookie `Secure` + HSTS con `APP_URL=https://`; (3) `nosniff`, `X-Frame-Options` y `Referrer-Policy` intactos; (4) sesión con cuenta eliminada, contraseña cambiada y timeout con la misma respuesta de antes, permisos de rol aplicados sin re-login y «recordarme» funcionando; (5) recorrido de cada CRUD (productos, usuarios, roles, permisos, categorías, clientes, proveedores, métodos de pago, POS, ajuste de stock, bitácora) sin errores de columna.
- [x] `composer test` verde en local (hecho: 1023 tests) y en CI (PHP 8.2–8.5, MariaDB 10.11) antes de subir el tag 1.20.0.
- axe-core: no aplica (sin cambios de UI).
