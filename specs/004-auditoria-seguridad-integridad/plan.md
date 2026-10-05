# Technical plan — Spec 004

Auditoría de seguridad e integridad. Tres bloques de trabajo con riesgo distinto:
(1) integridad de dinero/stock/numeración (esquema + modelos, el grueso), (2) controles
de autenticación y autorización (`Auth`, `AuthController`, `User`), (3) robustez,
interfaz y calidad (Router, vistas, tests, CI). Los puntos que **rompen contratos
existentes** están marcados con ⚠️.

## Module structure

**Modelos nuevos**

- `PosReservation` (`tb_pos_reservas`) → reserva del `nro_venta` y propiedad del carrito: `openFor()`, `isOpenFor()`, `release()` (FR-1, FR-2, FR-3)
- `AuthAttempt` (`tb_intentos_auth`) → registro y conteo de intentos de login y de solicitudes de restablecimiento por correo e IP (FR-33, FR-36)
- `RememberToken` (`tb_sesiones_recordadas`) → un token de "recordarme" por dispositivo: `issue()`, `consume()`, `revoke()`, `revokeAllFor()` (FR-32, FR-37, FR-58)

**Helpers nuevos** (`app/Helpers/`)

- `Money` → `toCents()` / `fromCents()`: parseo decimal a céntimos enteros, mitad hacia arriba, sin `float` ni `bcmath` (FR-10, FR-11)
- `MonthSeries` → `lastMonths(int $n)`: etiquetas `Y-m` consecutivas desde el primer día del mes (FR-29)
- `AuthSettings` → lee de `.env` (vía `Config`) los umbrales de login, reset y "recordarme" con default y rango por variable; cualquier valor inválido cae al default (FR-64)
- `ImageUpload` → `validate(array $file): ?string` (código de error de subida, extensión, MIME real, 2 MB) y `store()` (FR-40)

**Modelos modificados**

- `CartItem` → todas las operaciones reciben `$userId` y validan contra la reserva; desaparece `purgeOrphans()` global (FR-1, FR-3, FR-4, FR-7, FR-8)
- `Sale` → `storeWithStock()` idempotente, con reserva, centavos, tope de vuelto, límite de total; `destroyWithStock()` re-comprueba devoluciones; `nextNumber()` se retira; `computeInvoiceTotals()` expone el ajuste (FR-5, FR-6, FR-9..FR-12, FR-14, FR-52, FR-53)
- `SalePayment` → `vueltoFor()` calcula en céntimos (FR-10, FR-11, FR-14)
- `PaymentMethod` → `update()` bloquea el cambio de tipo si hay pagos (FR-13)
- `SaleReturn` → token de formulario, remanente, pendiente sumado por producto, conflicto ante choque de clave (FR-15..FR-18)
- `StockAdjustment` → `register()` relativo con piso en 0; nueva variante `registerInTransaction()` sin transacción propia (FR-21, FR-54)
- `Product` → `updateProduct()` ya no escribe `stock` ni `id_usuario`; `createProduct()` registra stock inicial; `isReferenced()`/`getReferenceCount()` cuentan ajustes y líneas de devolución (FR-19, FR-24, FR-54)
- `Purchase` → `storeWithStock/updateWithStock/destroyWithStock` con `UPDATE` condicionado y retorno tipado; `validateData()` con los límites de FR-25; conserva `id_usuario` (FR-22, FR-23, FR-25)
- `Report` → `salesByPeriod/salesTotals/salesSummary` + `returnsOutsideListed()`; ticket promedio neto (FR-27, FR-28)
- `User` → correo normalizado, hash del token de reset, expiración con reloj de la BD, `verifyCurrentPassword()`, `validatePassword()`, invalidación del "recordarme" al cambiar contraseña (FR-31..FR-36)

**Núcleo**

- `Config` → `int(string $key, int $default, int $min, int $max): int` (cast + rango) usado por `AuthSettings`
- `Auth` → `startSession()` fija flags de cookie; regenera CSRF al iniciar sesión; `loginWithCookie()`/`logout()` usan `RememberToken` y no tocan tokens ajenos ante cookie inválida (FR-30, FR-37, FR-58)
- `Router` → el parámetro `{id}` solo coincide con dígitos (404 si no) (FR-38)
- `Database` → deja de usar `die()` con el mensaje de PDO: lanza `RuntimeException` genérica y registra el detalle; `public/index.php` la captura y muestra `errors/500` (FR-39)

**Controladores** (solo orquestan, sin métodos nuevos)

- `SaleController` → `create()` usa `PosReservation::openFor()`; `store()`/`cancel()`/`addToCart()`/`removeItem()` pasan el usuario y mapean códigos a `forbidden()`/flash; reenvío redirige a `/sales` (FR-1..FR-9)
- `SaleReturnController` → `create()` emite el token; `store()` lo recibe (FR-17)
- `AuthController` → login con reserva atómica de intento y mensaje único; `sendResetLink()` con límites y sin enlace en pantalla (FR-33..FR-36)
- `UserController` → `updatePassword()` exige contraseña actual; mínimo 8 (FR-31, FR-32)
- `ProductController` → ficha sin stock editable, `ImageUpload`, ignora `image_text`, registra actividad (FR-19, FR-26, FR-40)
- `ClientController`, `SupplierController` → registran edición en actividad (FR-26)
- `DashboardController` → usa `MonthSeries` y una constante `MONTHS = 6` (FR-29)
- Todos los de listados/detalles → solo vistas (FR-42); `$can` ya llega desde `Controller::renderWithLayout()`

**Vistas y assets**

- Quitar botones sin permiso; enlaces rotos; `?v=APP_VERSION` en `layouts/auth.php` y `errors/*`; CSRF entregado por el controlador; `json_encode` de `BASE_URL` y `data-*` de moneda escapados (FR-42..FR-45, FR-55)
- Eliminar `views/auth/show-reset-link.php` (FR-35)
- `public/uploads/products/.htaccess` (FR-40)

**Esquema y calidad**

- Migraciones `011`..`014` (ver Data model), `schema.sql`, `seeder.sql`, `tests/fixtures/schema.sqlite.sql` (FR-46, FR-47, FR-56, FR-57)
- Tests nuevos y ajuste de los existentes, CI (FR-48..FR-51)
- Documentación: `AGENTS.md`, `CLAUDE.md`, `.env.example`, `CHANGELOG.md`, `docs/roadmap.md`

**Higiene, herramientas y compatibilidad** (sin cambio de comportamiento del producto)

- Limpieza de comentarios con FR/specs en `app/` (hoy: `Sale.php`, `SaleReturnController.php`, `PaymentMethod.php`, `PaymentMethodController.php`) en un commit aparte (FR-59)
- `.claude/skills/code-review/` reescrita para este repo: rama base `master`, checklist derivado de `docs/constitution.md` y de las prohibiciones de `AGENTS.md` (FR-60)
- `AGENTS.md`/`CLAUDE.md` sincronizados al final, cuando el código ya es el definitivo (FR-61)
- `database/preflight_004.sql` (solo `SELECT`) y pruebas de actualización sobre una BD en estado 1.18.3 (FR-62)

## Data model

Migraciones nuevas (reejecutables, sintaxis portable MariaDB/MySQL 8.4 — ver decisión D-9):

```sql
-- 011_pos_reservas.sql
CREATE TABLE IF NOT EXISTS tb_pos_reservas (
  id_reserva     int(11)  NOT NULL AUTO_INCREMENT,     -- ES el nro_venta
  id_usuario     int(11)  NOT NULL,
  fyh_creacion   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fyh_liberacion datetime DEFAULT NULL,
  PRIMARY KEY (id_reserva),
  KEY idx_reserva_usuario (id_usuario, fyh_liberacion),
  CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario) REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- Arranque de la secuencia: una fila liberada con id = MAX(nro_venta) de tb_ventas ∪ tb_carrito,
-- solo si la tabla está vacía (idempotente). El usuario de esa fila: el menor id_usuario existente.
```

```sql
-- 012_integridad_unicos.sql   (orden estricto; aborta antes de modificar nada)
-- 1. Comprobaciones (FR-56), todas antes de cualquier cambio:
--      · nro_venta repetido en tb_ventas
--      · ventas sin líneas en tb_carrito
--      · nro_compra repetido en tb_compras
--    Si alguna devuelve filas → error forzado (PREPARE sobre un nombre de tabla con el informe, ~125 car.;
--    no SIGNAL: no es preparable y pide CREATE ROUTINE); no se modifica nada.
-- 2. Quitar la FK de tb_ventas que referencia a tb_carrito, si existe. Se localiza en
--    information_schema.KEY_COLUMN_USAGE por REFERENCED_TABLE_NAME='tb_carrito' (no por el nombre
--    `tb_ventas_ibfk_2`, que solo es seguro en instalaciones creadas desde schema.sql) (FR-62).
-- 3. Crear UNIQUE uq_venta_nro (nro_venta) en tb_ventas si no existe.
-- 4. Crear UNIQUE uq_compra_nro (nro_compra) en tb_compras si no existe.
```

```sql
-- 013_tokens_y_auth.sql   (aditiva; no aborta)
-- tb_devoluciones: token_formulario CHAR(32) NULL + UNIQUE uq_devolucion_token
--   (varios NULL permitidos; se agrega comprobando information_schema, sin ADD COLUMN IF NOT EXISTS)
CREATE TABLE IF NOT EXISTS tb_intentos_auth (
  id_intento   int(11)      NOT NULL AUTO_INCREMENT,
  tipo         varchar(10)  NOT NULL,                  -- 'login' | 'reset'
  ip           varchar(45)  NOT NULL,
  correo       varchar(150) NOT NULL,                  -- normalizado (trim + minúsculas)
  id_usuario   int(11)      DEFAULT NULL,              -- NULL si el correo no existe
  fyh_creacion datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_intento),
  KEY idx_intento_correo (tipo, correo, fyh_creacion),
  KEY idx_intento_ip (tipo, ip, fyh_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tb_sesiones_recordadas (
  id_sesion      int(11)     NOT NULL AUTO_INCREMENT,
  id_usuario     int(11)     NOT NULL,
  selector       char(24)    NOT NULL,                 -- aleatorio, va en la cookie
  token_hash     char(64)    NOT NULL,                 -- SHA-256 del validador
  fyh_expira     datetime    NOT NULL,
  ip             varchar(45) NOT NULL,
  user_agent     varchar(255) DEFAULT NULL,
  fyh_creacion   datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fyh_ultimo_uso datetime    DEFAULT NULL,
  PRIMARY KEY (id_sesion),
  UNIQUE KEY uq_sesion_selector (selector),
  KEY idx_sesion_usuario (id_usuario),
  CONSTRAINT fk_sesion_usuario FOREIGN KEY (id_usuario) REFERENCES tb_usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- UPDATE tb_usuarios SET reset_token = NULL, reset_token_expiracion = NULL
--   (los tokens en claro anteriores quedan inválidos; duran 1 h)
-- UPDATE tb_usuarios SET remember_token = NULL, remember_token_expiry = NULL
--   (los "recordarme" anteriores se cierran). Las columnas login_intentos, login_bloqueado_hasta,
--   remember_token y remember_token_expiry quedan sin uso; se eliminan en una migración posterior.
```

```sql
-- 014_portabilidad_mysql84.sql   (aditiva; la más fácil de revertir, va al final)
-- tb_almacen.imagen: de TEXT con DEFAULT literal a VARCHAR(255) NOT NULL DEFAULT '…' (MODIFY idempotente).
--   Antes del MODIFY: si algún CHAR_LENGTH(imagen) > 255 → SIGNAL SQLSTATE '45000' con los id_producto
--   afectados, sin tocar nada (en modo no estricto el MODIFY truncaría rutas en silencio) (FR-62b).
-- Las ediciones de 002, 003 y 006 (sin ADD COLUMN IF NOT EXISTS, D-9) se hacen en esos archivos, no aquí.
```

Orden: `011` y `013` son independientes y pueden aplicarse en cualquier momento; `012` debe correr cuando
no queden carritos abiertos que dependan de la FK; `014` va al final.

### Compatibilidad con instalaciones existentes (FR-62)

Principio: la actualización es **aditiva y reversible a nivel de código**. Ninguna migración elimina tablas,
columnas ni filas de negocio.

| Migración | Qué toca en una BD con datos                                                                                                          | Efecto sobre 1.18.3 si se revierte solo el código                      |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| 011       | Tabla nueva + una fila de arranque de secuencia                                                                                       | Ninguno: 1.18.3 ignora la tabla                                        |
| 012       | Quita la FK invertida; añade `UNIQUE` en `nro_venta` y `nro_compra`; aborta antes de tocar nada si hay duplicados o ventas sin líneas | 1.18.3 sigue funcionando; un duplicado ahora falla en vez de guardarse |
| 013       | Tablas nuevas, columna `token_formulario` NULL, anula `reset_token*` y `remember_token*`                                              | 1.18.3 funciona; los usuarios inician sesión una vez más               |
| 014       | `imagen` `TEXT`→`VARCHAR(255)` tras comprobar que ninguna ruta excede 255                                                             | Ninguno: 1.18.3 lee la columna igual                                   |

- **Orden de despliegue**: (1) comprobación previa `database/preflight_004.sql` en producción y resolver a
  mano lo que informe; (2) respaldo de la BD; (3) migraciones pendientes `006`–`010` y luego `011`–`014`
  fuera del horario de ventas; (4) código nuevo y `APP_VERSION`. Con las migraciones aplicadas y el código aún
  viejo, el sistema funciona (tabla de arriba); con el código nuevo y sin migraciones **no** (faltan tablas):
  por eso el esquema va primero.
- **Carritos abiertos al desplegar**: sus líneas quedan sin reserva y nadie puede operarlas (fuera de alcance);
  `011` siembra la secuencia por encima de su `nro_venta`, así que no colisionan con ventas nuevas. Desplegar
  sin ventas en curso; si hubiera una, el vendedor vuelve a agregar los ítems.
- **Contraseñas y correos existentes** (FR-62f): el login no valida largo mínimo; `findByEmail` normaliza a
  minúsculas y la collation de MariaDB/MySQL (`*_ci`) encuentra los correos guardados con mayúsculas. Los
  bloqueos de login activos (`login_bloqueado_hasta`) dejan de aplicarse (quedan sin uso): se levantan solos.
- **Columnas obsoletas** (`login_intentos`, `login_bloqueado_hasta`, `remember_token`, `remember_token_expiry`):
  se conservan, sin uso, para no romper la vuelta atrás; su eliminación va en una migración posterior.
- **Reescritura de `002`, `003`, `006` (D-9)**: quien ya las aplicó no las vuelve a correr; quien aún no, debe
  obtener la misma estructura. Se demuestra comparando `information_schema` de dos BD, una con los archivos
  originales (de `git`) y otra con los reescritos.
- **Cambios de comportamiento visibles** que van al `CHANGELOG`: stock solo lectura en la ficha de producto,
  contraseña actual para cambiar la propia, mínimo de 8 en contraseñas nuevas, reset sin enlace en pantalla,
  un reinicio de sesión para quien usaba "recordarme", botones ocultos sin permiso.

Invariantes:

- `tb_pos_reservas` nunca borra filas (los huecos de numeración son aceptables y la secuencia no se reinicia). `fyh_liberacion IS NULL` ⇔ carrito en curso.
- Una venta finalizada ⇔ existe fila en `tb_ventas` con ese `nro_venta` (`UNIQUE`). Sin FK a `tb_carrito`; la integridad la impone el modelo (FR-9).
- `tb_devoluciones.token_formulario` es único; los registros históricos quedan en NULL.
- `tb_carrito` **no** recibe `id_usuario` (ver D-2): el dueño vive en la reserva.
- `schema.sql`, `seeder.sql` y `schema.sqlite.sql` reflejan lo mismo. Para SQLite: `INTEGER PRIMARY KEY AUTOINCREMENT` en `tb_pos_reservas`, `tb_intentos_auth` y `tb_sesiones_recordadas`; `UNIQUE` como índice; claves foráneas declaradas y `PRAGMA foreign_keys = ON` (FR-50) — `tb_ventas` pierde la FK a `tb_carrito` (SQLite también la rechazaría).

## Algorithms / non-trivial logic

### 1. Reserva del `nro_venta` (`PosReservation::openFor(int $userId): int`)

1. `SELECT id_reserva FROM tb_pos_reservas WHERE id_usuario=? AND fyh_liberacion IS NULL ORDER BY id_reserva DESC`.
2. Si hay ≥1: conservar la primera; para las demás, **en una transacción**: `UPDATE … SET fyh_liberacion=CURRENT_TIMESTAMP` y `DELETE FROM tb_carrito WHERE nro_venta=? AND nro_venta NOT IN (SELECT nro_venta FROM tb_ventas)` (FR-3).
3. Si no hay: `INSERT INTO tb_pos_reservas (id_usuario) VALUES (?)` → `lastInsertId()` (FR-2).
4. Devuelve el `nro_venta`; el controlador ya no usa `$_SESSION['pos_nro_venta']` como fuente (puede conservarlo solo como caché de lectura, nunca para decidir).

- Carrera: dos pestañas del mismo usuario que abren simultáneamente pueden crear dos reservas; la siguiente apertura las reduce a una (paso 2). Ningún otro usuario puede colisionar, porque el id sale del `AUTO_INCREMENT`.

### 2. Pertenencia del carrito (`CartItem`, FR-1, FR-4)

`PosReservation::isOpenFor(int $nro, int $userId): bool` = existe fila con ese id, ese usuario y `fyh_liberacion IS NULL`. `addItem/removeItem/clear` la llaman primero. Para `removeItem(int $idCarrito, int $userId)`: `SELECT nro_venta FROM tb_carrito WHERE id_carrito=?` y luego `isOpenFor`. Un `nro_venta` cuya venta ya existe en `tb_ventas` falla en `isOpenFor` (la reserva está liberada) → `finalized`. Retorno: `['ok'=>bool,'error'=>?('forbidden'|'finalized'|'invalid_qty'|'stock')]`. El controlador mapea `forbidden`/`finalized` a `Controller::forbidden()`.

### 3. Finalizar la venta idempotente (`Sale::storeWithStock(array $data, array $payments, int $userId)`)

Dentro de **la** transacción existente:

1. `SELECT id_venta, id_usuario FROM tb_ventas WHERE nro_venta=?`. Si existe: mismo usuario → `['ok'=>true,'replayed'=>true,'id_venta'=>…,'vuelto'=>vueltoFor(...)]` (no toca stock ni precios, FR-6); otro usuario → `forbidden`.
2. Si no existe: `isOpenFor($nro,$userId)` con bloqueo de fila (`forUpdate()` en MySQL; en SQLite la transacción ya serializa) o → `forbidden`.
3. Líneas del carrito: vacío → `empty_cart`. Congelar `precio_unitario` y total desde la BD (como hoy).
4. Total en céntimos: `0` → `total_cero`; `> 99_999_999_99` → `total_excede` (FR-52, FR-12).
5. Pagos: `Money::toCents()` por línea, sumar por método en céntimos (FR-10); método activo; suma `< total` → `faltante`; exceso `= suma − total`; exceso `> Σ efectivo` → `vuelto_excede_efectivo` (FR-11). Sin línea de pago → `invalid_payment`.
6. `INSERT tb_ventas` → atrapar `PDOException` con SQLSTATE `23000` en el `UNIQUE(nro_venta)`: rollback y volver al paso 1 (replay concurrente de dos pestañas).
7. `INSERT tb_pagos`, descontar stock con `AND stock >= ?` (como hoy), `UPDATE tb_pos_reservas SET fyh_liberacion=CURRENT_TIMESTAMP`.
8. `COMMIT`; el vuelto se devuelve calculado, no se persiste.
   Casos borde: reenvío a otro usuario; reenvío tras cancelar (reserva liberada sin venta → `forbidden`); doble clic simultáneo (paso 6).

### 4. Dinero en céntimos (`Money`)

`toCents(string|int|float $v): ?int`: acepta `^\d+([.,]\d+)?$`, separa parte entera y decimal como **texto**, toma 3.º decimal para redondear mitad hacia arriba; `float` se convierte con `number_format($v, 4, '.', '')` primero. Devuelve `null` si no es un monto válido. `fromCents(int): string` → `"1234.50"`. Todas las comparaciones de FR-10/11/12/52 ocurren en enteros. `SalePayment::vueltoFor(float $total, array $payments): float` conserva su firma y usa `Money` internamente.

### 5. Devolución con remanente e idempotencia (`SaleReturn::register(int $idVenta, string $motivo, array $qty, ?string $token = null)`)

1. Si `$token` ya existe en `tb_devoluciones` → devolver esa devolución con `replayed=true` (sin tocar stock).
2. Transacción + `FOR UPDATE` sobre `tb_ventas` (ya existe). Pendiente por producto = Σ cantidad de **todas** las líneas de la venta de ese producto − Σ ya devuelto (FR-15). `qty > pendiente` → `conflict` (mensaje de conflicto, FR-18).
3. `monto_calc = Σ cantidad × precio_unitario`; `remanente = total_pagado − Σ monto previo`; `monto = max(0, min(monto_calc, remanente))` (FR-16). El stock se repone siempre completo.
4. `INSERT` con `token_formulario`; si falla por `UNIQUE` del token → buscar y devolver la original; si falla por la clave temporal de `nro_devolucion` → `conflict`.
5. `issueToken(): string` = `bin2hex(random_bytes(16))` (32 caracteres), generado en `SaleReturnController::create()` y enviado como campo oculto.
   Edge: remanente 0 con unidades pendientes → monto 0 y stock repuesto.

### 6. Stock relativo (FR-21, FR-22)

`UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ? AND stock + ? >= 0` (delta con signo). `rowCount()=0` → insuficiente. Luego `SELECT stock` para `stock_posterior`; `stock_anterior = stock_posterior − delta`. En `Purchase::updateWithStock`: si cambia `id_producto`, restar al anterior (condicionado) y sumar al nuevo; cualquier fallo → rollback y `['ok'=>false,'error'=>'stock_insufficient','available'=>n]`. `destroyWithStock` igual. `StockAdjustment::registerInTransaction()` es la misma lógica sin `beginTransaction`; `register()` la envuelve. `Product::createProduct()` la usa para el "Stock inicial" (FR-54).

### 7. Intentos de login (`AuthAttempt`, FR-33, FR-34)

Flujo de `AuthController::store()`:

1. `$correo = User::normalizeEmail()`; `$user = findByEmail($correo)` (puede ser `false`).
2. `$id = AuthAttempt::register('login', $ip, $correo, $user['id_usuario'] ?? null)` → `lastInsertId()`.
3. `AuthAttempt::isBlocked('login', $id, $ip, $correo)`: bloquea si `COUNT(*)` de filas `tipo='login'`, mismo `correo`, `id_intento <= $id` y `fyh_creacion >= NOW() - INTERVAL <LOGIN_WINDOW_MINUTES> MINUTE` es **> `LOGIN_MAX_PER_EMAIL`** (5), o si el mismo conteo por `ip` es **> `LOGIN_MAX_PER_IP`** (20). Los números entre paréntesis son los defaults de `AuthSettings`. Bloqueado → mensaje único (FR-34) **sin** llamar a `password_verify`.
4. No bloqueado: `password_verify` (contra hash ficticio si el usuario no existe). Éxito → `AuthAttempt::clear('login', $correo)` y `Auth::login`.
   Contar solo hasta el propio `id` hace determinista la ráfaga: de N peticiones paralelas solo las 5 primeras por `id` llegan a verificar contraseña. Las ventanas usan el reloj de la BD (`NOW() - INTERVAL …`, sintaxis MySQL/MariaDB), así que sus tests van en `*MariaDbTest`. Las columnas `login_intentos`/`login_bloqueado_hasta` y los métodos `isLocked/clearExpiredLock/recordFailedLogin/clearLoginAttempts` de `User` desaparecen.
   Edge: correo inexistente se limita igual (por correo e IP) y responde igual; un atacante que conoce el correo de la víctima puede bloquearla 15 minutos (riesgo aceptado, la salida es "¿Olvidaste tu contraseña?"); el límite por IP solo frena a quien prueba muchos correos desde una IP.

### 8. Restablecimiento (`AuthController::sendResetLink`)

1. Normalizar correo; `$id = AuthAttempt::register('reset', $ip, $correo, $userId|null)` siempre (existan o no).
2. `AuthAttempt::countSince('reset', ip, 1 hora)` > `RESET_MAX_PER_IP_HOUR` (5) → mismo mensaje genérico sin enviar.
3. Usuario existente y hay otra solicitud `reset` de ese `id_usuario` en los últimos `RESET_COOLDOWN_MINUTES` (5) min (anterior a `$id`) → mismo mensaje.
4. Si procede: token `bin2hex(random_bytes(32))`; `User::storeResetToken($id, hash('sha256',$token))` con `DATE_ADD(NOW(), INTERVAL <RESET_TOKEN_MINUTES> MINUTE)` (reloj de la BD, 60 por defecto); envío por `EmailService`; con `APP_DEBUG=true` solo `error_log` del enlace.
5. `findByResetToken($plain)` busca por `hash('sha256',$plain)`.
   Las ventanas usan el reloj de la BD → tests en `*MariaDbTest`.

### 9. Cookie de sesión y "recordarme" (`Auth`, `RememberToken`)

- `startSession()` llama `session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>$https])` antes de `session_start()`, reutilizando la detección de HTTPS tras proxy de `Auth`. `Auth::login()` regenera el token CSRF.
- Emitir (`RememberToken::issue($userId, $ip, $ua)`): `selector = bin2hex(random_bytes(12))` (24 car.), `validador = bin2hex(random_bytes(32))`; se guarda `sha256(validador)` con `fyh_expira = DATE_ADD(NOW(), INTERVAL ? DAY)` (`REMEMBER_LIFETIME` vía `AuthSettings`, 14 por defecto); cookie `remember_token` = `selector:validador` (httponly, SameSite=Strict, `secure` según HTTPS). Antes de insertar se borran las filas vencidas de ese usuario.
- Consumir (`RememberToken::consume($selector, $validador, $ip, $ua)`): busca por `selector`; `hash_equals` contra el hash; si es válido y vigente, **rota solo ese validador** (nuevo hash, nueva expiración, ip, user agent, `fyh_ultimo_uso`) y devuelve el usuario y si la IP cambió (el controlador lo registra en `ActivityLog`, sin rechazar). Si no es válido → `null`: `Auth::loginWithCookie()` solo borra la cookie del cliente (FR-37); **no** borra la fila (así una pestaña con cookie vieja no cierra el dispositivo).
- Cerrar sesión: `RememberToken::revoke($selector)`. Cambio de contraseña: `RememberToken::revokeAllFor($userId)`.

## Technical decisions

- **D-1 Reserva en tabla propia, no `MAX()+1`, `GET_LOCK` ni fila centinela en `tb_carrito`.** `tb_carrito.id_producto` es NOT NULL con FK, así que no hay carrito vacío que reservar; `GET_LOCK` y `FOR UPDATE` sobre `MAX` no existen o no bloquean huecos en SQLite. Un `AUTO_INCREMENT` es atómico en los tres motores.
- **D-2 El dueño del carrito vive en `tb_pos_reservas`, no en una columna `tb_carrito.id_usuario`.** Una columna nueva dejaría los carritos heredados en NULL y no cubriría el carrito vacío. Se descarta además mantener `$_SESSION['pos_nro_venta']` como fuente de verdad: no sobrevive a un cambio de navegador ni a una sesión expirada. ⚠️ Cambia el significado de FR-3 respecto de la redacción original ("registrar a qué usuario pertenece cada carrito"); la spec ya lo recoge.
- **D-3 `UNIQUE(nro_venta)` + eliminar la FK a `tb_carrito` + reglas en el modelo.** Alternativa descartada: hacer único `tb_carrito.nro_venta` (imposible, hay varias líneas por venta) o una tabla `tb_carritos` padre (reescribiría el flujo del POS y sus datos históricos). La FK invertida solo rompe en MySQL 8.4.
- **D-4 Idempotencia de la venta por `nro_venta` (ya único y por reserva), sin token propio.** Alternativa: token por formulario como en devoluciones. Descartada porque el `nro_venta` ya identifica de forma unívoca el carrito y evita una columna más.
- **D-5 Idempotencia de la devolución con token de formulario persistido.** Alternativa descartada: reutilizar el CSRF (es único por sesión, no distingue dos devoluciones parciales); un nonce solo en sesión (no sobrevive a la expiración y no se prueba en el modelo). ⚠️ `SaleReturn::register()` añade un parámetro opcional (compatible).
- **D-6 Céntimos enteros desde texto, no `float` ni `bcmath`.** `bcmath` es extensión opcional; `round()` sobre `float` falla en `33.30−11.10`. Coste: los controladores dejan de castear a `float` los montos de pago y pasan el texto del POST.
- **D-7 `UPDATE` condicionado (`AND stock >= ?`) en vez de `SELECT … FOR UPDATE` + `UPDATE`.** Funciona igual en SQLite (que no tiene `FOR UPDATE`) y no necesita lock explícito. Alternativa: bloquear la fila del producto; descartada por la diferencia entre motores.
- **D-8 Intentos de login en `tb_intentos_auth`, registrados antes de verificar y contados por `id`.** Alternativas descartadas: seguir con columnas en `tb_usuarios` (solo por cuenta, no cuentan correos inexistentes ni IP) y un `UPDATE` condicionado sobre esas columnas (depende del orden de asignaciones entre MySQL y SQLite). Limitación aceptada: un atacante que conoce un correo puede bloquearlo 15 minutos.
- **D-9 Portabilidad MySQL 8.4 con `information_schema` + `PREPARE/EXECUTE`.** Alternativa descartada: mantener `ADD COLUMN IF NOT EXISTS` y declarar el soporte "solo MariaDB" (contradice el NFR). ⚠️ Edita migraciones antiguas (002, 003, 006) conservando su resultado final; no cambia el esquema de quien ya las aplicó.
- **D-10 Una sola tabla de intentos para login y reset, con reloj de la BD.** Alternativas descartadas: dos tablas separadas (mismas columnas, doble código), archivos o sesión (no atan a la IP) y `APCu` (extensión opcional). La IP es `REMOTE_ADDR`; no se confía en `X-Forwarded-For` (spoofeable sin proxy de confianza).
- **D-10b "Recordarme" en `tb_sesiones_recordadas`, una fila por dispositivo con selector + hash del validador.** Alternativa descartada: seguir con un token en `tb_usuarios` (un login nuevo invalida el otro dispositivo y el `id` en la cookie es adivinable). La IP se guarda y su cambio se registra en actividad, pero **no** se exige que coincida: las IP móviles cambian y cerrarían la sesión del vendedor varias veces al día. ⚠️ Retira de `User` los métodos `storeRememberToken/findByRememberToken/clearRememberToken`.
- **D-11 `{id}` numérico en el Router en vez de validar en cada controlador.** Cambia el patrón de `{id}` a `(\d+)`; el resto de parámetros (`{token}`) conserva `[a-zA-Z0-9_-]+`. Un solo punto, 404 coherente.
- **D-12 Retorno tipado `array` también en `Purchase` y `CartItem`**, igual que ya hizo `Sale::storeWithStock` en la spec 003. ⚠️ Afecta a `PurchaseController`, `SaleController` y sus tests; se actualizan en la misma tarea.
- **D-13 Mínimo de contraseña 8** (`User::MIN_PASSWORD_LENGTH`) en alta, perfil, administrador y reset; unifica el 6 de `AGENTS.md` con el 8 que ya usaba el reset. Las contraseñas existentes no se tocan.
- **D-14 Sin tablas de "sesiones activas" ni `credenciales_version`**: invalidar sesiones al cambiar la contraseña queda fuera de alcance (spec).
- **D-15 `PRAGMA foreign_keys = ON` en el fixture SQLite**: se activa en `tests/TestCase`/`RefreshDatabase`, no en producción. Obliga a ajustar los seeders mínimos; es parte de la tarea de FR-50.
- **D-16 Umbrales de autenticación en `.env` con rango y default (FR-64).** Alternativas descartadas: constantes de clase (cambiar la política exige tocar código) y tabla de configuración editable por UI (fuera de alcance, más superficie). Cada valor se valida en `AuthSettings` (cast a entero, rango, default si falla) para que un `.env` mal escrito no desactive la protección; el entero validado es el único que se interpola en `INTERVAL`. El orden por `id`, el `UNIQUE` y el reloj de la BD no son configurables. La purga de `tb_intentos_auth` usa `max(1 día, ventana de login)`. Los tests fijan los valores con `$_ENV` en `setUp` y los restauran.

## Interface contract

```php
// PosReservation (nuevo)
public function openFor(int $userId): int;                       // nro_venta de la reserva abierta (la crea o retoma)
public function isOpenFor(int $nroVenta, int $userId): bool;
public function release(int $nroVenta): void;                    // solo dentro de la transacción del cobro / cancelar

// CartItem (modificado) — ⚠️ añade $userId y retorno tipado
public function addItem(int $nroVenta, int $idProducto, int $cantidad, int $userId): array;
public function removeItem(int $idCarrito, int $userId): array;
public function clearCart(int $nroVenta, int $userId): array;
//   ['ok'=>bool,'error'=>?('forbidden'|'finalized'|'invalid_qty'|'stock')]
//   desaparece purgeOrphans(int): lo reemplaza la limpieza propia de PosReservation::openFor()

// Sale (modificado)
public function storeWithStock(array $data, array $payments, int $userId): array;
//   ['ok','id_venta','vuelto','replayed'=>bool,'faltante'=>?float,
//    'error'=>?('empty_cart'|'sin_metodos'|'invalid_payment'|'faltante'|'exceso_sin_efectivo'
//               |'vuelto_excede_efectivo'|'total_cero'|'total_excede'|'forbidden'|'generic')]
public function destroyWithStock(int $id): array;                 // + error 'has_returns' dentro de la transacción
public function computeInvoiceTotals(array $items, float $totalPagado): array; // + 'ajuste'
// nextNumber() se retira

// Money
public static function toCents(string|int|float $amount): ?int;
public static function fromCents(int $cents): string;

// SalePayment
public static function vueltoFor(float $total, array $payments): float;   // firma intacta

// SaleReturn
public function register(int $idVenta, string $motivo, array $quantitiesByProduct, ?string $token = null): array;
//   + 'replayed'=>bool ; error: 'conflict' | 'nothing_pending' | 'generic'
public static function issueToken(): string;

// StockAdjustment
public function register(int $idProducto, string $tipo, int $cantidad, string $motivo): array;       // envuelve en transacción
public function registerInTransaction(int $idProducto, string $tipo, int $cantidad, string $motivo): array;

// Purchase — ⚠️ retornos tipados
public function updateWithStock(array $data, int $oldProductId, int $oldCantidad): array;
public function destroyWithStock(int $id, int $idProducto, int $cantidad): array;
//   ['ok'=>bool,'error'=>?('stock_insufficient'|'invalid'|'generic'),'available'=>?int,'errors'=>?array]
public function validateData(array $data): bool|array;            // + límites FR-25, nro_compra único, fecha no futura

// Report
public function returnsOutsideListed(string $from, string $to, ?int $userId = null): float;

// User
public const MIN_PASSWORD_LENGTH = 8;
public static function normalizeEmail(string $email): string;      // trim + minúsculas
public function verifyCurrentPassword(int $id, string $plain): bool;
public function storeResetToken(int $id, string $tokenHash): bool; // ⚠️ ya no recibe $expiracion
public function findByResetToken(string $plainToken): array|false;  // busca por hash

// Config
public static function int(string $key, int $default, int $min, int $max): int;  // default si falta, no es entero o está fuera de rango

// AuthSettings (nuevo) — todos los umbrales de FR-64
public static function loginMaxPerEmail(): int;   // LOGIN_MAX_PER_EMAIL   5   (1–50)
public static function loginMaxPerIp(): int;      // LOGIN_MAX_PER_IP      20  (1–500)
public static function loginWindowMinutes(): int; // LOGIN_WINDOW_MINUTES  15  (1–1440)
public static function resetTokenMinutes(): int;  // RESET_TOKEN_MINUTES   60  (5–1440)
public static function resetCooldownMinutes(): int; // RESET_COOLDOWN_MINUTES 5 (1–60)
public static function resetMaxPerIpHour(): int;  // RESET_MAX_PER_IP_HOUR 5   (1–100)
public static function rememberDays(): int;       // REMEMBER_LIFETIME     14  (1–90)

// AuthAttempt (nuevo)
public function register(string $tipo, string $ip, string $correo, ?int $userId): int;   // id del intento
public function isBlocked(string $tipo, int $idIntento, string $ip, string $correo): bool; // >5 por correo o >20 por IP en 15 min, hasta este id
public function countSince(string $tipo, string $ip, int $seconds): int;                  // para el límite de reset
public function recentForUser(string $tipo, int $userId, int $seconds, int $beforeId): int;
public function clear(string $tipo, string $correo): void;                                // tras login correcto

// RememberToken (nuevo)
public function issue(int $userId, string $ip, ?string $userAgent): array;               // ['cookie'=>'selector:validador','expira'=>…]
public function consume(string $selector, string $validator, string $ip, ?string $userAgent): ?array; // ['id_usuario'=>int,'ip_changed'=>bool,'cookie'=>nueva]
public function revoke(string $selector): void;
public function revokeAllFor(int $userId): void;
```

Rutas: ninguna nueva. `Router`: `{id}` → `(\d+)`. Se retira cualquier referencia a `show-reset-link`.

Códigos de error y mensajes (flash en español; `403` vía `Controller::forbidden()` para `forbidden`/`finalized`): `faltante`, `exceso_sin_efectivo`, `vuelto_excede_efectivo`, `total_cero`, `total_excede`, `conflict`, `stock_insufficient` (con "stock disponible: N"), `has_returns`.

## Risks

| Riesgo                                                                                       | Mitigación                                                                                                                                                                                                                    |
| -------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Migración 012 aborta en producción por `nro_venta`/`nro_compra` repetido o ventas sin líneas | Es el comportamiento pedido (FR-56): informa las filas sin tocar datos. Antes de desplegar, correr las consultas de comprobación en producción y resolver a mano.                                                             |
| Editar migraciones 002/003/006 (D-9) altera instalaciones ya migradas                        | Solo se reescribe la sintaxis; el resultado final es el mismo. Probado comparando `information_schema` de una BD con los archivos originales y otra con los reescritos, y re-ejecutando sobre una BD ya migrada (FR-62d).     |
| `MODIFY imagen` a `VARCHAR(255)` trunca rutas largas existentes                              | La 014 comprueba `CHAR_LENGTH > 255` y aborta sin tocar nada (FR-62b); `preflight_004.sql` lo informa antes de desplegar.                                                                                                     |
| La FK de `tb_ventas` a `tb_carrito` tiene otro nombre en una instalación                     | La 012 la busca por tabla referenciada en `information_schema`, no por nombre (FR-62a).                                                                                                                                       |
| Código nuevo desplegado antes que el esquema                                                 | Orden de despliegue documentado: respaldo, preflight, migraciones, luego código; el closeout lo repite.                                                                                                                       |
| Hay que volver a la 1.18.3 tras migrar                                                       | El esquema migrado es compatible hacia atrás (tabla de compatibilidad); se prueba con la 1.18.3 contra la BD migrada (FR-62e).                                                                                                |
| `PRAGMA foreign_keys=ON` rompe tests que insertan hijos sin padres                           | Se activa en una tarea propia con la suite entera; los seeders se completan con los padres mínimos.                                                                                                                           |
| Cambiar la firma de `CartItem`/`Purchase`/`Sale` rompe llamadores y tests                    | Se actualizan en la misma tarea; la suite es el filtro.                                                                                                                                                                       |
| Reserva de POS: usuario con dos pestañas crea dos reservas a la vez                          | Aceptado: la siguiente apertura las reduce a una (algoritmo 1). Ningún otro usuario puede colisionar.                                                                                                                         |
| Un atacante bloquea la cuenta de otro durante 15 min enviando intentos con su correo         | Aceptado en la spec; la salida es "¿Olvidaste tu contraseña?" y el límite por IP frena la fuerza bruta distribuida solo en parte.                                                                                             |
| Los "recordarme" anteriores se cierran al migrar                                             | Una sola vez por usuario; se documenta en el CHANGELOG.                                                                                                                                                                       |
| `tb_intentos_auth` crece sin límite                                                          | Se borran los intentos del correo al iniciar sesión bien; el resto se purga con el mismo mecanismo que `ActivityLog::purgeOlderThan` (tarea de mantenimiento) o al registrar (borrado oportunista de filas con más de 1 día). |
| Pruebas de tiempo de reset dependen del reloj de la BD                                       | Los tests de ventana usan `NOW() ± INTERVAL` en SQL (regla de tests con fechas) y corren solo en `*MariaDbTest`.                                                                                                              |
| `Database` lanzando en lugar de `die()` cambia el manejo en `public/index.php`               | Se captura en el front controller con la vista `errors/500`; verificado con una BD inalcanzable en demo manual.                                                                                                               |
| `.htaccess` de uploads ignorado por Nginx o `AllowOverride None`                             | Requisito documentado en `AGENTS.md`/README (FR-40); el MIME real se valida igualmente. Comprobar que el `.htaccess` no esté en `.gitignore`.                                                                                 |
| Reenvío de venta devuelve éxito a un usuario con sesión distinta                             | Solo si coincide `id_usuario`; si no, `forbidden` (FR-1).                                                                                                                                                                     |
| Pago con `float` en `SaleReturn`/`Sale` heredados puede dejar un resto de 1 céntimo          | Todas las comparaciones pasan a céntimos; cubierto por test con `33.30−11.10`.                                                                                                                                                |

## Verification strategy

Convención de la spec: reglas en modelo → tests PHPUnit; lo que vive en `Auth`/`AuthController`/`Router`/vistas → demo manual registrada en `validation.md`. Puerta: `composer test` verde + CI con MariaDB sin saltos; en UI, axe-core claro/oscuro.

- **Unit** (`tests/Unit/`): `Money` (redondeo, entradas inválidas, `33.30−11.10`), `MonthSeries` (día 31), `ImageUpload::validate`, `SalePayment::vueltoFor`, `User::normalizeEmail`.
- **Integration SQLite** (`tests/Integration/Models/`): `PosReservation` (reserva única por usuario, libera, retoma, purga solo las propias), `CartItem` (dueño ajeno, venta finalizada, acumulación, stock total), `Sale` (replay, tope de vuelto, total 0 y exceso, límite, destroy con devoluciones), `SaleReturn` (token, remanente, líneas repetidas, conflicto), `Purchase`/`StockAdjustment` (piso en 0, cambio de producto, stock inicial), `Product::isReferenced`, `Role::grantsSuperadmin`, `Report` (fila de períodos anteriores, ticket neto).
- **Integration MariaDB** (`*MariaDbTest`, `RefreshMariaDatabase`): ventanas y ráfaga de `AuthAttempt`, rotación de `RememberToken` por dispositivo, expiración del token con `NOW()`, migraciones 011–014 aplicadas en orden vs `schema.sql`, `UNIQUE(nro_venta)` y su aborto ante duplicados, concurrencia de reserva con dos conexiones.
- **Umbrales por `.env`** (FR-64): test Unit de `Config::int`/`AuthSettings` (default, no entero, fuera de rango, valor válido); los tests de `AuthAttempt`, `User` y `RememberToken` fijan los valores con `$_ENV` y los restauran.
- **Entorno local** (FR-63, T61): `.env.testing` con BD `sistemadeventas_test` (el trait sufija el PID); es prerrequisito de toda tarea que cite `*MariaDbTest`.
- **CI**: FR-49 hace fallar el job si `CI` está definida y no hay MariaDB.
- **Actualización desde 1.18.3** (FR-62): `MigrationsUpgradeMariaDbTest` crea una BD con el esquema de 1.18.3
  (`git show 1.18.3:database/schema.sql`), la llena con datos representativos (ventas con y sin cliente,
  pagos, devoluciones, compras, un carrito abierto, correos con mayúsculas, tokens de "recordarme"), aplica
  `011`–`014` y compara conteos y sumas antes/después. Segunda prueba: con `preflight_004.sql` y con `012`/`014`
  sobre datos que deben abortar (duplicados, ventas sin líneas, imagen de 300 caracteres) no cambia ninguna fila.
- **Demo manual** (evidencia en `specs/004-…/evidence/`, capturas + salida de comandos): doble envío de venta y de devolución; dos vendedores abriendo el POS a la vez (BD, no solo UI); carrito ajeno por `curl` con sesión de otro rol → 403; vuelto > efectivo; compra revertida con stock vendido; ficha de producto con stock de solo lectura; ráfaga paralela de 20 logins con `xargs -P` contra una cuenta (≤5 verificaciones); reset sin enlace con `APP_DEBUG=true` y `APP_ENV=local`; cabecera `Set-Cookie` con `HttpOnly; SameSite=Lax` (y `Secure` tras proxy HTTPS); `/products/abc` → 404; BD detenida → 500 sin datos; subida de 3 MB → mensaje; botones ocultos por rol (vendedor, comprador, rol sin permisos); axe-core en las vistas tocadas.

## Coverage table (FR → parte del plan)

| FR                         | Parte                                                                                            |
| -------------------------- | ------------------------------------------------------------------------------------------------ |
| FR-1, FR-4                 | `PosReservation::isOpenFor`, `CartItem`, `SaleController`; algoritmo 2                           |
| FR-2, FR-3                 | `PosReservation`, migración 011; algoritmo 1                                                     |
| FR-5, FR-9                 | Migración 012, `schema.sql`/fixture; D-3; `Sale::storeWithStock`                                 |
| FR-6                       | `Sale::storeWithStock`; algoritmo 3                                                              |
| FR-7, FR-8                 | `CartItem::addItem`                                                                              |
| FR-10, FR-11, FR-12, FR-52 | `Money`, `Sale::storeWithStock`, `SalePayment`; algoritmos 3 y 4                                 |
| FR-13                      | `PaymentMethod::update`                                                                          |
| FR-14                      | `Sale::computeInvoiceTotals`, `InvoicePdf`, `views/sales/show.php`                               |
| FR-15, FR-16, FR-17, FR-18 | `SaleReturn`, `SaleReturnController`, migración 013; algoritmo 5                                 |
| FR-53                      | `Sale::destroyWithStock`                                                                         |
| FR-19, FR-54               | `Product`, `ProductController`, `StockAdjustment::registerInTransaction`                         |
| FR-20, FR-21               | `StockAdjustment`, algoritmo 6                                                                   |
| FR-22, FR-23, FR-25        | `Purchase`, migración 012                                                                        |
| FR-24                      | `Product::isReferenced` / `getReferenceCount`                                                    |
| FR-26                      | `ClientController`, `SupplierController`, `ProductController` (`ActivityLog::record`)            |
| FR-27, FR-28               | `Report`, vista de reporte de ventas                                                             |
| FR-29                      | `MonthSeries`, `DashboardController`, `Sale/Purchase::totalsByMonth`                             |
| FR-30, FR-37, FR-58        | `Auth`, `RememberToken`, migración 013; algoritmo 9                                              |
| FR-31, FR-32               | `UserController::updatePassword`, `User`                                                         |
| FR-33, FR-34               | `AuthController::store`, `AuthAttempt`, `User::normalizeEmail`; algoritmo 7                      |
| FR-35, FR-36               | `AuthController::sendResetLink`, `AuthAttempt`, migración 013; algoritmo 8                       |
| FR-38                      | `Router` (D-11)                                                                                  |
| FR-39                      | `Database`, `public/index.php`                                                                   |
| FR-40                      | `ImageUpload`, `ProductController`, `.htaccess`                                                  |
| FR-41                      | `EmailService`                                                                                   |
| FR-42, FR-43               | Vistas de listados/detalles/POS, `views/returns/show.php`                                        |
| FR-44, FR-45, FR-55        | `layouts/auth.php`, `errors/*`, `header.php`/`footer.php`                                        |
| FR-46, FR-47, FR-56, FR-57 | Migraciones 011–014, edición de 002/003/006, `schema.sql`, fixture                               |
| FR-48, FR-49, FR-50, FR-51 | Suite de tests, `tests.yml`, `RefreshDatabase`, fixture SQLite                                   |
| FR-63                      | `.env.testing` local (T61), `RefreshMariaDatabase`                                               |
| FR-64                      | `Config::int`, `AuthSettings`, `AuthAttempt`, `User::storeResetToken`, `RememberToken`, `.env.example`; D-16 |
| FR-59                      | Limpieza de comentarios en `app/` (commit aparte)                                                |
| FR-60                      | `.claude/skills/code-review/`                                                                    |
| FR-61                      | `AGENTS.md`, `CLAUDE.md`, `.env.example`                                                         |
| FR-62                      | Migraciones 011–014, `preflight_004.sql`, `MigrationsUpgradeMariaDbTest`; sección Compatibilidad |

## Constitution check

Sin contradicciones. Observaciones:

- **Principio 2** (librerías): ninguna librería nueva; tres tablas y una columna nuevas, justificadas en D-1/D-5/D-10/D-10b.
- **Principio 4**: la lógica (reserva, céntimos, remanente, límites) vive en modelos y helpers; vistas solo reciben valores (el ajuste de factura sale de `computeInvoiceTotals`).
- **Principio 5**: ningún verbo nuevo de controlador; la reserva y el token se hacen dentro de `create()`.
- **Principio 6**: todos los montos y el stock se calculan en la BD dentro de la transacción; `Money` solo normaliza la entrada.
- **Principio 8**: `Auth`, `AuthController`, `Router` y vistas no se testean; se cubren con demo manual (convención de la spec). La lógica de límite de intentos y de rotación de tokens vive en `AuthAttempt` y `RememberToken` para poder probarla (con MariaDB, por el reloj de la BD).
- **Principio 10**: la nueva fila de factura y los mensajes de error usan `APP_CURRENCY_SYMBOL`.
- **Principio 6** (borrado físico): `preflight_004.sql` es de solo lectura; ninguna migración borra datos de negocio.
