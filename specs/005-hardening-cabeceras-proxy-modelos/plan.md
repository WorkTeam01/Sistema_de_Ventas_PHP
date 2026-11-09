# Plan 005 — Hardening de cabeceras, caché y escritura de modelos

**Spec:** [spec.md](spec.md) · **Status:** draft · **Fecha:** 2026-11-09

## Cumplimiento de la constitución

| Principio          | Cómo se respeta                                                                                                                                                                 |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2 Stack fijo       | Sin librerías nuevas; solo PHP, TCPDF existente y PHPUnit.                                                                                                                      |
| 4 Fat model        | La lista blanca vive en `Model` y en cada modelo; los helpers nuevos son funciones puras, sin lógica en vistas ni controladores.                                                |
| 5 Métodos cerrados | No se añade ningún método de controlador.                                                                                                                                       |
| 6 Datos            | Sin SQL nuevo con interpolación de valores; los nombres de columna interpolados ya existentes pasan a validarse (FR-19).                                                        |
| 7 Autorización     | No cambia ninguna regla; FR-16 solo la protege con un test.                                                                                                                     |
| 8 Verificación     | Todo FR con test de unidad/integración; solo el envío real de cabeceras queda como demo. Los middleware no se testean: el cambio de sesión vive en `Auth` (que sí tiene tests). |

Sin contradicciones entre la spec y la constitución.

## Módulos

| Módulo                                                                | Responsabilidad                                                                         | Cambio      |
| --------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | ----------- |
| `app/Helpers/SecurityHeaders.php`                                     | Funciones puras: ¿`APP_URL` es HTTPS?, valor de HSTS; `send()` lo emite con `header()`. | Nuevo       |
| `app/Helpers/PdfResponse.php`                                         | Envía los bytes de un PDF con cabeceras propias; `headersFor()` es pura.                | Nuevo       |
| `app/Core/Auth.php`                                                   | `isHttps()` delega en `SecurityHeaders`; `check()` memoiza su resultado por petición.   | Modificado  |
| `app/Core/Model.php`                                                  | Validación de nombres de columna y lista blanca en `create()`/`update()`.               | Modificado  |
| 18 modelos (`app/Models/*`)                                           | Declaran `$createColumns` y `$updateColumns`.                                           | Modificados |
| `InvoicePdf`, `ReportPdf`, `PurchaseReportPdf`                        | Pasan de `Output('…','I')` a `PdfResponse::send()`.                                     | Modificados |
| `public/index.php`                                                    | Llama a `SecurityHeaders::send()` junto a las cabeceras existentes.                     | Modificado  |
| `ActivityLog`, `Product`, `StockAdjustment`, `CartItem`               | Sus `catch (\Throwable)` relanzan `InvalidArgumentException`.                           | Modificados |
| `tests/Unit/StockWritesAuditTest.php`                                 | Detecta más patrones SQL y que solo `Product` declara `tb_almacen`.                     | Modificado  |
| `.env.example`, guías, `AGENTS.md`, `CHANGELOG.md`, `docs/roadmap.md` | `HSTS_MAX_AGE`, nota de proxies, corrección de escritores de stock.                     | Docs        |

## Modelo de datos

Sin cambios de esquema, migraciones ni permisos. Variable nueva en `.env`: `HSTS_MAX_AGE` (por defecto `86400`).

## Algoritmos y casos borde

### 1. HTTPS por `APP_URL` (FR-4 a FR-8)

- `SecurityHeaders::isHttpsUrl(string $appUrl): bool` → `true` si `parse_url($appUrl, PHP_URL_SCHEME)` es `https` (sin distinguir mayúsculas). Cadena vacía, sin esquema o `http` → `false`.
- `SecurityHeaders::hstsValue(string $appUrl, mixed $rawMaxAge): ?string` → `null` si no es HTTPS; si lo es, `max-age=N`. `N` es el entero no negativo de `$rawMaxAge`; cualquier otro valor (vacío, texto, negativo, decimal) usa 86400. Nunca añade `includeSubDomains` ni `preload`.
- `Auth::isHttps()` pasa a `SecurityHeaders::isHttpsUrl((string) Config::get('APP_URL', ''))`. Ya no lee `$_SERVER`.
- Casos borde: `APP_URL=https://x/sub/public` (con ruta) funciona; `HSTS_MAX_AGE=0` es válido (el navegador olvida HSTS); `APP_URL` sin esquema se trata como HTTP.

### 2. Cabeceras de PDF (FR-1 a FR-3)

- `PdfResponse::send(TCPDF $pdf, string $filename)`: `$bytes = $pdf->Output($filename, 'S')` **antes** de emitir nada; luego `header()` de cada línea de `headersFor($filename, strlen($bytes))` con reemplazo y `echo $bytes`.
- `headersFor()` devuelve exactamente: `Content-Type: application/pdf`, `Content-Disposition: inline; filename="X"; filename*=UTF-8''X`, `Content-Length: N`, `Cache-Control: private, no-store`. El nombre se sanea para la parte `filename=` (sin comillas ni saltos de línea).
- La sesión de PHP ya envía `Cache-Control: no-store, no-cache, must-revalidate`, `Pragma: no-cache` y `Expires`; `header(…, true)` reemplaza `Cache-Control` y `header_remove('Pragma')` evita cualquier `Pragma` residual.
- Caso borde: si TCPDF lanza durante `Output('S')` no se ha enviado ninguna cabecera, y el manejador de excepciones de `index.php` responde la página 500.

### 3. Una revalidación por petición (FR-12 a FR-16)

- Nuevo `private static ?bool $checked = null` en `Auth`. `check()` devuelve `$checked` si no es `null`; en caso contrario ejecuta la lógica actual y guarda el booleano resultante en cada salida (los `return` actuales se convierten en una asignación única al final, o en un `return self::$checked = …`).
- Se anula (`= null`) en: `login()`, `logout()`, `destroySession()`, `loginWithCookie()` (si tiene éxito) y `refreshPermissions()`.
- Efecto esperado: `AuthMiddleware` → `Auth::check()` (1 SELECT) y `PermissionMiddleware` → `Auth::check()` (memo, 0 SELECT). `Auth::user()` conserva su SELECT.
- Casos borde: `check()` falso + `loginWithCookie()` exitoso → `login()` anula el memo y el `check()` siguiente vuelve a evaluar. `logout()` dentro de la petición → memo anulado, no reutiliza el `true` previo. Tests y CLI: el reset se hace por reflexión sobre `$checked` junto a `$cachedUser`, como ya hace `AuthCheckTest`.
- `PermissionMiddleware` y `AuthMiddleware` **no cambian**: la mejora está dentro de `Auth`.

### 4. Lista blanca en `Model` (FR-17 a FR-22)

- Dos propiedades `protected array $createColumns = []` y `$updateColumns = []`. Vacías por defecto (deny-by-default).
- `assertWritable(array $data, array $allowed)`: para cada clave, primero `preg_match('/^[a-z_][a-z0-9_]*$/', $key)`; si falla → `InvalidArgumentException("Columna inválida")`. Después `in_array($key, $allowed, true)`; si falla → `InvalidArgumentException("Columna no permitida: {$key} en {$this->table}")`. Se ejecuta **antes** de `prepare()`, de modo que no hay escritura.
- `create()` y `update()` llaman al helper con su lista; `insert()` (obsoleto) delega en `create()` y hereda la validación.
- `PaymentMethod::update()` sobrescribe y hace SELECTs antes de `parent::update()`: se añade la llamada a `assertWritable` al inicio del override para que la excepción preceda a esos SELECTs.
- Listas (de la lectura del código; se confirman al implementar con `grep`):

| Modelo                                                                              | `$createColumns`                                                                                                                                 | `$updateColumns`                                                                                              |
| ----------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| Product                                                                             | codigo, nombre, descripcion, **stock**, stock_minimo, stock_maximo, precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria | las mismas **sin codigo, stock ni id_usuario**                                                                |
| User                                                                                | nombres, email, id_rol, password_user                                                                                                            | nombres, email, id_rol, password_user (+ columnas de reset/token que hoy actualiza vía `update`, a confirmar) |
| Role                                                                                | rol                                                                                                                                              | rol (nunca `permisos_version`)                                                                                |
| Permission                                                                          | clave, descripcion, modulo                                                                                                                       | descripcion, modulo (`clave` inmutable)                                                                       |
| Category                                                                            | nombre_categoria                                                                                                                                 | nombre_categoria                                                                                              |
| Client                                                                              | nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente                                                                                   | las mismas                                                                                                    |
| Supplier                                                                            | nombre_proveedor, empresa, celular, telefono, email, direccion                                                                                   | las mismas                                                                                                    |
| PaymentMethod                                                                       | nombre, tipo, activo                                                                                                                             | nombre, tipo, activo                                                                                          |
| CartItem                                                                            | nro_venta, id_producto, cantidad                                                                                                                 | (ninguna)                                                                                                     |
| StockAdjustment                                                                     | id_producto, tipo, cantidad, stock_anterior, stock_posterior, motivo, id_usuario, usuario_nombre                                                 | (ninguna)                                                                                                     |
| ActivityLog                                                                         | id_usuario, usuario_nombre, accion, entidad, entidad_id, descripcion, datos_anteriores, datos_nuevos, ip_address                                 | (ninguna)                                                                                                     |
| AuthAttempt, RememberToken, PosReservation, Purchase, Sale, SaleReturn, SalePayment | (vacías: solo SQL directo)                                                                                                                       | (vacías)                                                                                                      |

- Casos borde: `fyh_creacion`/`fyh_actualizacion` no están en ninguna lista (las gestiona el motor); un `create([])` no pasa por la lista y falla en SQL como hoy.

### 5. Relanzar la excepción (FR-21)

En `ActivityLog`, `Product::createProduct`, `StockAdjustment` y `CartItem` se antepone `catch (\InvalidArgumentException $e) { throw $e; }` al `catch (\Throwable)` existente. Los errores de BD siguen tratándose como hoy.

### 6. Auditoría de stock (FR-23 a FR-25)

- Se amplían las expresiones de `StockWritesAuditTest` para: `UPDATE tb_almacen [alias] SET … stock`, `INSERT INTO tb_almacen (… stock …)` y `REPLACE INTO tb_almacen`, con o sin backticks, también entre líneas.
- Nuevo test: recorre `app/Models` y comprueba que ninguna clase salvo `Product` declara `$table = 'tb_almacen'` (regex sobre el código fuente, sin instanciar).
- Cada patrón lleva un caso positivo (archivo temporal que debe detectarse) y uno negativo (`stock_minimo`, comentarios).

### 7. `auth` precede a `can:` (FR-16)

Test estático que lee `routes/web.php` y falla si algún middleware `'can:…'` aparece sin `'auth'` antes en el mismo arreglo. No instancia `Router`.

## Decisiones técnicas

| Decisión                                                          | Alternativa descartada                               | Motivo                                                                                                                                |
| ----------------------------------------------------------------- | ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `Secure`/HSTS por esquema de `APP_URL`                            | Confiar en `X-Forwarded-Proto` con lista de proxies  | El proyecto no soporta proxies (decisión del usuario); `APP_URL` ya es obligatorio y `https://` figura en el checklist de despliegue. |
| Memoizar dentro de `Auth::check()`                                | Quitar `Auth::check()` de `PermissionMiddleware`     | Quitarlo rompería la garantía si una ruta `can:` perdiera `auth`; la memoización conserva ambas llamadas y no toca los middleware.    |
| Lista blanca por modelo con listas separadas de `create`/`update` | Una sola lista por modelo                            | `stock`, `codigo`, `clave` se permiten al crear y no al actualizar.                                                                   |
| Deny-by-default en los 18 modelos                                 | Lista solo en modelos sensibles                      | Con lista vacía por defecto, un modelo nuevo no escribe nada hasta declarar columnas; los 7 modelos sin uso no cuestan nada.          |
| Validar el nombre de columna con regex                            | Confiar solo en la lista blanca                      | Cubre el modelo con lista nueva mal escrita y cualquier interpolación futura.                                                         |
| `Output('S')` + cabeceras propias                                 | `Output('I')` y luego `header()` / `header_remove()` | TCPDF emite sus cabeceras dentro de `Output('I')` y lanza si ya hubo salida; en modo `S` no se envía nada antes.                      |
| `HSTS_MAX_AGE` por defecto 86400                                  | 31536000 con `preload`                               | Un valor largo es difícil de revertir; se sube desde el `.env` tras comprobar el sitio.                                               |
| Test estático de rutas para FR-16                                 | Test del `Router`/middleware                         | La constitución excluye Router y middleware; leer el archivo no los ejecuta.                                                          |

## Contrato de interfaces

```php
// app/Helpers/SecurityHeaders.php
final class SecurityHeaders {
    public static function isHttpsUrl(string $appUrl): bool;
    public static function hstsValue(string $appUrl, mixed $rawMaxAge): ?string; // "max-age=N" | null
    public static function send(): void;                                         // emite HSTS (si aplica)
}

// app/Helpers/PdfResponse.php
final class PdfResponse {
    /** @return list<string> líneas de cabecera, p. ej. "Cache-Control: private, no-store" */
    public static function headersFor(string $filename, int $length): array;
    public static function send(\TCPDF $pdf, string $filename): void;           // Output('S') + headers + echo
}

// app/Core/Model.php (protegido)
protected array $createColumns = [];
protected array $updateColumns = [];
// create()/update(): lanzan \InvalidArgumentException ante columna inválida o no permitida; sin SQL ejecutado.
```

Errores: `InvalidArgumentException` (programación, no se muestra al usuario; el manejador global responde 500 y registra en el log). Sin códigos HTTP nuevos.

## Riesgos

| Riesgo                                                                                             | Mitigación / aceptación                                                                                                                                     |
| -------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Una lista incompleta rompe una pantalla que escribe una columna no prevista                        | Inventario por `grep` de todas las llamadas y suite completa; test negativo y positivo por modelo; demo recorriendo cada CRUD.                              |
| `ActivityLog` relanzando la excepción tumbaría una operación                                       | Solo ocurre si el código escribe una columna fuera de su lista (error de programación cubierto por tests). Aceptado: es el comportamiento "ruidoso" pedido. |
| La memoización devuelve un estado viejo tras cambiar la sesión                                     | Anular en `login`, `logout`, `destroySession`, `loginWithCookie` y `refreshPermissions`; tests de cada transición.                                          |
| Tests que mutan `$_SESSION` entre dos `check()` en el mismo proceso                                | Reset por reflexión en `setUp` de las suites que usan `Auth`.                                                                                               |
| HSTS en un entorno HTTPS mal configurado deja el sitio inaccesible hasta que expira                | Valor por defecto 86400 y sin `includeSubDomains`/`preload`; documentado.                                                                                   |
| Detrás de un proxy, todos los clientes comparten IP y `LOGIN_MAX_PER_IP` (20)                      | Aceptado: el proyecto no soporta proxies; se documenta en despliegue y CHANGELOG.                                                                           |
| `Output('S')` consume memoria con PDFs grandes                                                     | Los reportes son acotados por rango; aceptado.                                                                                                              |
| El 403 de `PermissionMiddleware` (`http_response_code(403)` + `Location`) podría terminar como 302 | No cambia con esta spec; solo se comprueba con curl en la demo que la respuesta es la misma antes y después.                                                |

## Estrategia de verificación

**Automática (`composer test`, PHPUnit 11):**

- `tests/Unit/Helpers/SecurityHeadersTest.php` — `isHttpsUrl`, `hstsValue` (valores válidos e inválidos, sin `includeSubDomains`/`preload`). FR-4 a FR-8.
- `tests/Unit/Helpers/PdfResponseTest.php` — `headersFor()`: una sola `Cache-Control: private, no-store`, sin `Pragma`, `filename*`, `Content-Length`. FR-1, FR-3.
- `tests/Integration/Core/AuthCheckTest.php` (ampliado) — segunda llamada a `check()` reutiliza el resultado (se borra el usuario entre ambas y sigue siendo `true`); tras reset/`logout()`/`login()` se reevalúa; sesión inválida responde `false` como antes; cambio de `permisos_version` se aplica en la petición siguiente. FR-12 a FR-15.
- `tests/Unit/Core/ModelWhitelistTest.php` (sobre SQLite, con subclase anónima) — columna permitida ok, no permitida lanza sin escribir, nombre inválido lanza, `insert()` heredado valida. FR-17 a FR-19.
- Test por modelo con _data provider_ de los 18 modelos concretos: negativo (`create`/`update` con columna ajena lanza) y consistencia (cada columna declarada existe en el fixture SQLite). Positivo: las suites de repositorio existentes y las de los modelos que no las tengan. FR-20, FR-22.
- `StockWritesAuditTest` ampliado (+ solo `Product` declara `tb_almacen`). FR-23 a FR-25.
- `tests/Unit/RoutesAuthOrderTest.php` — `auth` antes de `can:` en `routes/web.php`. FR-16.
- Los tests existentes (890) siguen verdes: FR-22 y regresión.

**Demo manual (registrada con evidencia en `validation.md`):**

1. `curl -I` de los tres PDF (factura, reporte, comprobante de compra): una sola `Cache-Control: private, no-store`, sin `Pragma: public`, mismos nombres y `inline`.
2. `APP_URL=http://`: `X-Forwarded-Proto: https` forjado no marca `Secure` ni envía HSTS. Con `APP_URL=https://` (host local con TLS o cabecera simulada del lado de prueba): cookie `Secure` y `Strict-Transport-Security: max-age=86400`, sin `includeSubDomains`/`preload`.
3. `nosniff`, `X-Frame-Options` y `Referrer-Policy` intactos en las respuestas.
4. Sesión con la cuenta eliminada, con la contraseña cambiada y con timeout responde igual que antes; el cambio de permisos de un rol se aplica sin re-login; el login con «recordarme» sigue funcionando.
5. Recorrido de cada CRUD (productos, usuarios, roles, permisos, categorías, clientes, proveedores, métodos de pago, POS/carrito, ajuste de stock, bitácora) sin errores de columna.

Evidencia: salidas de `curl -I`, capturas de la consola y resultado de `composer test` en `specs/005-hardening-cabeceras-proxy-modelos/evidence/`. No hay cambios de UI, por lo que no se exige axe-core.

## Cobertura FR → módulo → verificación

| FR  | Módulo                                              | Verificación                                 |
| --- | --------------------------------------------------- | -------------------------------------------- |
| 1   | `PdfResponse`, 3 generadores                        | `PdfResponseTest` + demo 1                   |
| 2   | `PdfResponse::send` (`Output('S')`)                 | demo 1 (revisión de código y error simulado) |
| 3   | `PdfResponse::headersFor`                           | `PdfResponseTest` + demo 1                   |
| 4   | `SecurityHeaders::isHttpsUrl`, `Auth::isHttps`      | `SecurityHeadersTest` + demo 2               |
| 5   | `SecurityHeaders::hstsValue/send`, `Auth` (cookies) | `SecurityHeadersTest` + demo 2               |
| 6   | idem                                                | `SecurityHeadersTest` + demo 2               |
| 7   | `SecurityHeaders::hstsValue`                        | `SecurityHeadersTest`                        |
| 8   | `SecurityHeaders::hstsValue`                        | `SecurityHeadersTest`                        |
| 9   | _retirado_                                          | —                                            |
| 10  | _retirado_                                          | —                                            |
| 11  | `public/index.php` (sin tocar las existentes)       | demo 3                                       |
| 12  | `Auth::check` memoizado                             | `AuthCheckTest`                              |
| 13  | `Auth::check`                                       | `AuthCheckTest` + demo 4                     |
| 14  | `Auth::login/logout/loginWithCookie/destroySession` | `AuthCheckTest` + demo 4                     |
| 15  | `Auth::check`                                       | `AuthCheckTest` + demo 4                     |
| 16  | `routes/web.php`                                    | `RoutesAuthOrderTest`                        |
| 17  | `Model` + 18 modelos                                | test por modelo                              |
| 18  | `Model::assertWritable`                             | `ModelWhitelistTest` + test por modelo       |
| 19  | `Model::assertWritable`                             | `ModelWhitelistTest`                         |
| 20  | `Product` (listas)                                  | test de `Product`                            |
| 21  | 4 modelos con `catch`                               | tests de relanzado por modelo                |
| 22  | todos los modelos                                   | suites existentes + `composer test` + demo 5 |
| 23  | `StockWritesAuditTest`                              | el propio test                               |
| 24  | `StockWritesAuditTest`                              | el propio test                               |
| 25  | `StockWritesAuditTest`                              | casos positivo/negativo del test             |
| 26  | todas las pruebas automáticas                       | `composer test`                              |
| 27  | demos 1–5                                           | `validation.md` + `evidence/`                |

## Orden de construcción sugerido (para `/sdd:tasks`)

1. `SecurityHeaders` + `.env.example` + integración en `index.php` y `Auth::isHttps`.
2. `PdfResponse` y los tres generadores.
3. Memoización en `Auth::check()` + tests.
4. `Model::assertWritable` + listas de los 18 modelos + `catch` relanzados + tests.
5. `StockWritesAuditTest` ampliado y `RoutesAuthOrderTest`.
6. Documentación (guías, `AGENTS.md`, `CHANGELOG`, roadmap) y demo manual.
