# Validación — Spec 005: Hardening de cabeceras, caché y escritura de modelos

**Date:** 2026-11-09

**Verificación ejecutada:** `composer test` → OK (1023 tests, 3214 assertions), local (PHP 8.2, SQLite y MariaDB). Evidencia manual en `evidence/`.

## FR por FR

| FR    | Resumen                                                                                         | Verificación                                                                                                                                                                                                                  | Resultado |
| ----- | ----------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| FR-1  | Una sola `Cache-Control: private, no-store` y sin `Pragma: public` en los PDF                   | `PdfResponseTest` (una línea, sin `Pragma`/`Expires`); `evidence/demo1-pdf.txt` (los 3 PDF reales)                                                                                                                            | PASS      |
| FR-2  | Bytes generados antes de enviar cabeceras                                                       | `PdfResponse::send()` llama a `Output('S')` antes de cualquier `header()`; camino feliz en `demo1-pdf.txt`. El fallo de TCPDF no se provocó (ver Notas)                                                                       | PASS      |
| FR-3  | Se conservan tipo, `inline` y nombres (`filename*` UTF-8)                                       | `PdfResponseTest` (tipo, disposición, longitud, nombre con acentos/saltos de línea); `demo1-pdf.txt`                                                                                                                          | PASS      |
| FR-4  | HTTPS decidido por el esquema de `APP_URL`; se ignoran `X-Forwarded-*` y `$_SERVER['HTTPS']`    | `SecurityHeadersTest` (`https`/`HTTPS`/`http`/vacío/sin esquema); `demo2-3-headers.txt` §A (cabecera forjada sin efecto) y §C (`X-Forwarded-Proto: http` con `APP_URL` https sigue siendo seguro)                             | PASS      |
| FR-5  | Con `https://`: `Secure` en sesión y «recordarme» y HSTS con `HSTS_MAX_AGE`                     | `SecurityHeadersTest` (`hstsValue`); `demo2-3-headers.txt` §C y la sección de «recordarme» (`secure` en ambas cookies, `max-age` 86400/604800)                                                                                | PASS      |
| FR-6  | Con `http://`: sin `Secure` ni HSTS                                                             | `SecurityHeadersTest`; `demo2-3-headers.txt` §A y §B                                                                                                                                                                          | PASS      |
| FR-7  | Sin `includeSubDomains` ni `preload`                                                            | `SecurityHeadersTest` (el valor nunca los contiene); salidas de `demo2-3-headers.txt`                                                                                                                                         | PASS      |
| FR-8  | `HSTS_MAX_AGE` no entero ≥ 0 usa el valor por defecto                                           | `SecurityHeadersTest` (válido, `0`, vacío, texto, negativo, decimal); `demo2-3-headers.txt` §C-invalid (86400) y §C-zero (`max-age=0`)                                                                                        | PASS      |
| FR-9  | _Retirado_                                                                                      | —                                                                                                                                                                                                                             | N/A       |
| FR-10 | _Retirado_                                                                                      | —                                                                                                                                                                                                                             | N/A       |
| FR-11 | `nosniff`, `X-Frame-Options` y `Referrer-Policy` intactos                                       | `demo2-3-headers.txt` (presentes en todas las respuestas, con y sin HTTPS)                                                                                                                                                    | PASS      |
| FR-12 | Revalidación completa una sola vez por petición                                                 | `AuthCheckTest::test_second_check_in_the_same_request_reuses_the_result`                                                                                                                                                      | PASS      |
| FR-13 | Sesión inválida responde igual que antes                                                        | `AuthCheckTest` (cuenta eliminada, timeout, contraseña cambiada); `demo4-sesion.txt`: mismo escenario contra `HEAD` y contra el código nuevo, `diff` vacío                                                                    | PASS      |
| FR-14 | Login por «recordarme» válido en la misma petición; `logout`/destrucción descartan el resultado | `AuthCheckTest::test_login_discards_a_memoized_false_result`, `test_logout_discards_the_memoized_result`; `demo4-sesion.txt` §d (solo con la cookie «recordarme» → 200)                                                       | PASS      |
| FR-15 | Cambio de permisos de un rol aplicado en la petición siguiente                                  | `AuthCheckTest::test_permission_change_is_applied_on_the_next_request`; `demo4-sesion.txt` §c (200 → 403 → 200 sin re-login)                                                                                                  | PASS      |
| FR-16 | `auth` precede a `can:` en toda ruta                                                            | `RoutesAuthOrderTest` (86 de 86; casos sintéticos negativos)                                                                                                                                                                  | PASS      |
| FR-17 | Listas de `create` y `update` por modelo, vacías por defecto                                    | `ModelWhitelistCoverageTest` (18 modelos: columnas declaradas existen en el esquema; el proveedor de datos cubre todos los modelos de `app/Models`); `ModelWhitelistTest::test_a_model_without_declared_lists_writes_nothing` | PASS      |
| FR-18 | Columna fuera de la lista → `InvalidArgumentException` sin escribir                             | `ModelWhitelistTest`; `ModelWhitelistCoverageTest` (`create` y `update` en los 18); un test negativo y uno positivo por modelo en sus repositorios                                                                            | PASS      |
| FR-19 | Nombres de columna `^[a-z_][a-z0-9_]*$` en `create`, `update` e `insert`                        | `ModelWhitelistTest` (7 nombres inválidos, `insert()` obsoleto)                                                                                                                                                               | PASS      |
| FR-20 | `Product`: `update` sin `stock`, `id_usuario` ni `codigo`; `stock` solo en `create`             | `ProductRepositoryTest` (5 tests nuevos)                                                                                                                                                                                      | PASS      |
| FR-21 | Los `catch` relanzan `InvalidArgumentException`                                                 | `ProductRepositoryTest`, `StockAdjustmentTest`, `ActivityLogRepositoryTest` (relanzado); `CartItem` ya relanzaba todo en su `catch`                                                                                           | PASS      |
| FR-22 | Operaciones existentes intactas                                                                 | Suites existentes sin cambios (1023 verdes); `evidence/demo5-crud.txt` (11 CRUD)                                                                                                                                              | PASS      |
| FR-23 | La suite falla ante SQL de `UPDATE`/`INSERT`/`REPLACE` sobre `tb_almacen.stock`                 | `StockWritesAuditTest` (patrones con alias, backticks y varias líneas; mutación temporal en un controlador hizo fallar el test)                                                                                               | PASS      |
| FR-24 | Solo `Product` declara `tb_almacen`                                                             | `StockWritesAuditTest::test_only_product_declares_the_stock_table` (+ mutación sobre `Category`)                                                                                                                              | PASS      |
| FR-25 | Caso positivo y negativo por patrón                                                             | `StockWritesAuditTest` (11 positivos y 7 negativos; casos de archivo temporal)                                                                                                                                                | PASS      |
| FR-26 | Tests unitarios de FR-4 a FR-8 y de FR-17 a FR-22 por modelo                                    | `SecurityHeadersTest` (23), `ModelWhitelist*Test`, tests por modelo                                                                                                                                                           | PASS      |
| FR-27 | Envío real de cabeceras y middleware con demo registrada                                        | `evidence/demo1` a `demo5`                                                                                                                                                                                                    | PASS      |

## FR sin verificación asociada

Ninguno.

## Criterios de cierre

- [x] Todos los FR con test o demo manual registrada.
- [x] `composer test` verde en local (1023 tests).
- [x] `composer test` verde en CI (matriz PHP 8.2/8.5, MariaDB 10.11): 1023 tests en ambos jobs sobre el commit `f1c53fa`.
- [x] Demo manual: tres PDF con una sola `Cache-Control: private, no-store`; `X-Forwarded-Proto` forjado sin efecto; `Secure` y HSTS solo con `APP_URL` https; cabeceras de navegador intactas; sesión revocada con la misma respuesta que antes.
- [x] `.env.example`, `docs/guides/autenticacion.md`, `despliegue.md`, `testing.md`, `AGENTS.md` (con la corrección de escritores de stock) y `docs/roadmap.md` actualizados.
- [x] `CHANGELOG.md`: entrada 1.20.0.

## Verdict

Spec fulfilled. Los 25 requisitos vigentes (FR-9 y FR-10 retirados) tienen test o demo registrada y pasan. Los pasos del cierre están completos; falta solo crear el tag 1.20.0.

## Notes

- FR-2: no se provocó un fallo real de TCPDF; la garantía sale del orden del código (`Output('S')` antes de las cabeceras) y del camino feliz en la demo.
- La demo 4 compara contra `HEAD` en un _worktree_ temporal (ya retirado) con la misma BD de desarrollo.
- Los datos de la demo llevaban el marcador `Demo005`/`demo005` y se borraron tras comprobar con `SELECT` que los conteos coincidían. Residuos inocuos en la BD de desarrollo: `permisos_version` del rol Vendedor en 4 y tokens de «recordarme» del administrador.
- La spec exige `Secure`/HSTS por `APP_URL`; la demo del HTTPS se hizo con `php -S` y un enrutador que fija `APP_URL=https://…`, sin TLS real.

## Updated files

- `docs/roadmap.md` — la feature pasa de «Siguiente» a «Hecho ✅».
- `docs/schema.md` — sin cambios: la spec no toca `database/` (sin esquema ni migraciones); no se regeneró.
