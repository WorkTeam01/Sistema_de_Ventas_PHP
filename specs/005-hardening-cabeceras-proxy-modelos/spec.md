# Spec 005 — Hardening de cabeceras, caché y escritura de modelos

**Status:** approved
**Origen:** hardening pendiente de la auditoría de seguridad del 2026-10-17 (`docs/roadmap.md` § Siguiente), que la spec 004 dejó fuera de alcance (CSP/HSTS) o que se descubrió después; revisada contra el código, las specs 001–004 y la constitución.

## Context and goal

La 1.19.4 cerró los hallazgos explotables de la auditoría. Quedan endurecimientos de defensa en profundidad con una causa común: la app **confía en datos que controla el cliente o no restringe lo que escribe**.

1. Las respuestas no declaran HSTS y los PDF con datos financieros pueden quedar en caché (`InvoicePdf`, `ReportPdf`, `PurchaseReportPdf` usan `Output('I')`, que fija `Cache-Control: private, must-revalidate` y `Pragma: public`).
2. `Auth::isHttps()` acepta `X-Forwarded-Proto` de cualquier cliente, que puede falsearlo.
3. `Model::create/update` aceptan cualquier columna; `StockWritesAuditTest` solo detecta `UPDATE tb_almacen SET … stock` en SQL literal.
4. `AuthMiddleware` y `PermissionMiddleware` ejecutan cada uno `Auth::check()` (un SELECT de identidad cada vez).

Objetivo: la información financiera no se cachea; el flag `Secure` no depende de una cabecera falsificable; ningún modelo escribe columnas que no declaró; la revalidación de sesión ocurre una vez por petición sin perder garantías.

## Requisitos de otras specs que esta modifica

- **004 FR-30** («`Secure` cuando la conexión es HTTPS»): el criterio pasa a ser el esquema de `APP_URL` (FR-4 y FR-5). La demo de 004 que forjaba `X-Forwarded-Proto` desde curl deja de aplicar.
- **AGENTS.md**, lista de escritores de stock: `Product::createProduct` inserta `stock = 0` y delega el stock inicial en `StockAdjustment`; no escribe stock real.

## Users / actors

- **Operador / desarrollador** — despliega la app y configura `.env`.
- **Usuario autenticado (cualquier rol)** — descarga facturas, reportes y comprobantes en PDF.
- **Atacante** — cliente que falsifica cabeceras o intenta escribir columnas no previstas.

## User stories

- U1: Como administrador quiero que ningún PDF financiero quede en cachés del navegador o de intermediarios.
- U2: Como operador quiero que el flag `Secure` y HSTS dependan de mi configuración y no de una cabecera que el cliente puede falsear.
- U3: Como desarrollador quiero que cada modelo declare qué columnas puede escribir y que un intento fuera de la lista falle de forma detectable.
- U4: Como desarrollador quiero que la suite detecte escrituras de `tb_almacen.stock` fuera de los modelos autorizados.

## Functional requirements (EARS acceptance criteria)

Convención de verificación (constitución, principio 8): las reglas se prueban a nivel de modelo, de `Auth` o de funciones puras, que sí tienen tests. Lo que reside en el envío real de cabeceras (`public/index.php`, PDF), `Router` o middleware se comprueba con demo manual registrada en `validation.md`. La resolución de HTTPS, IP de cliente y cabeceras vive en funciones puras que reciben `$_SERVER` y la configuración, con test unitario.

### A. Caché de PDF

- **FR-1:** WHEN la app envía un PDF (factura de venta, reporte o comprobante de compra), THE system SHALL incluir una única cabecera `Cache-Control: private, no-store` y SHALL NOT incluir `Pragma: public`.
- **FR-2:** THE system SHALL generar los bytes del PDF antes de enviar cualquier cabecera, de modo que un error de generación no deje una respuesta PDF vacía y responda con el manejo de errores existente.
- **FR-3:** THE system SHALL conservar tipo `application/pdf`, disposición `inline` y los nombres de archivo actuales (`factura_venta_{id}.pdf`, `comprobante_compra_{id}.pdf`, `{filename}.pdf` en reportes, con `filename*` UTF-8).

### B. HTTPS y HSTS

- **FR-4:** THE system SHALL determinar si la aplicación se sirve por HTTPS según el esquema de `APP_URL` (`https://`) e ignorar `X-Forwarded-Proto`, `X-Forwarded-For` y `$_SERVER['HTTPS']` para esa decisión.
- **FR-5:** WHILE `APP_URL` use `https://`, THE system SHALL marcar `Secure` las cookies de sesión y «recordarme» y SHALL enviar `Strict-Transport-Security` con `max-age` tomado de `HSTS_MAX_AGE` del `.env`.
- **FR-6:** WHILE `APP_URL` use `http://`, THE system SHALL NOT marcar `Secure` ni enviar HSTS.
- **FR-7:** THE system SHALL NOT enviar `includeSubDomains` ni `preload`.
- **FR-8:** IF `HSTS_MAX_AGE` no es un entero no negativo, THEN THE system SHALL usar el valor por defecto documentado en `.env.example`.

### C. Cabeceras de navegador (CSP retirada)

- **FR-9:** _Retirado (2026-11-09): la CSP en modo informe no protege sin enforcing y queda para una spec propia. El número no se reutiliza._
- **FR-10:** _Retirado junto con FR-9._
- **FR-11:** THE system SHALL conservar sin cambios `nosniff`, `X-Frame-Options` y `Referrer-Policy` que ya envía `public/index.php`.

### D. Revalidación de sesión

- **FR-12:** THE system SHALL ejecutar la revalidación completa de `Auth::check()` (cuenta existente, timeout, huella de contraseña `pw_fp` y `permisos_version`) una sola vez por petición, y las llamadas posteriores en la misma petición SHALL reutilizar el resultado.
- **FR-13:** IF la sesión es inválida (cuenta eliminada, timeout, contraseña cambiada), THEN THE system SHALL responder igual que antes del cambio.
- **FR-14:** WHEN `check()` falla y el login por cookie «recordarme» tiene éxito en la misma petición, THE system SHALL tratar la sesión como válida; WHEN la petición cierra o destruye la sesión, THE system SHALL descartar el resultado reutilizado.
- **FR-15:** WHEN cambian los permisos de un rol entre dos peticiones, THE system SHALL aplicarlos en la petición siguiente sin re-login.
- **FR-16:** WHERE una ruta declare `can:permiso`, THE system SHALL garantizar que `auth` la precede (hoy 86 de 86).

### E. Lista blanca de escritura en modelos

- **FR-17:** THE system SHALL exigir a cada modelo que hereda de `Model` declarar por separado las columnas escribibles de `create()` y de `update()`; por defecto, vacías.
- **FR-18:** IF `create()` o `update()` recibe una columna fuera de la lista correspondiente, THEN THE system SHALL lanzar `InvalidArgumentException` sin ejecutar ninguna escritura.
- **FR-19:** THE system SHALL rechazar nombres de columna que no cumplan `^[a-z_][a-z0-9_]*$` en `create()` y `update()` de cualquier modelo, incluido `insert()` obsoleto.
- **FR-20:** THE system SHALL excluir `stock` e `id_usuario` de la lista de `update` de `Product`, y SHALL permitir `stock` solo en la lista de `create` de `Product` (valor inicial 0).
- **FR-21:** WHEN un `catch` existente rodea una llamada a `create`/`update`, THE system SHALL relanzar `InvalidArgumentException` en lugar de ocultarla (`ActivityLog`, `Product`, `StockAdjustment`, `CartItem` y los demás que apliquen).
- **FR-22:** THE system SHALL mantener sin cambios el comportamiento de las operaciones existentes con sus columnas legítimas.

### F. Auditoría de escrituras de stock

- **FR-23:** THE test suite SHALL fallar si algún archivo fuera de los modelos autorizados contiene SQL `UPDATE`/`INSERT`/`REPLACE` sobre `tb_almacen` que toque `stock`, incluidos alias y backticks.
- **FR-24:** THE test suite SHALL comprobar que ninguna clase salvo `Product` declara `$table = 'tb_almacen'`.
- **FR-25:** THE test suite SHALL cubrir con un caso positivo y uno negativo cada patrón que el auditor detecta.

### G. Calidad y regresión

- **FR-26:** THE test suite SHALL cubrir FR-4 a FR-8 con tests unitarios de las funciones puras, y FR-17 a FR-22 con un test positivo y uno negativo por modelo.
- **FR-27:** Lo que reside en el envío real de cabeceras o en middleware (FR-1 a FR-3, FR-5, FR-11, FR-13) SHALL comprobarse con demo manual registrada en `validation.md`.

## Non-functional requirements

- **Compatibilidad:** una instalación con `APP_URL=https://` conserva `Secure` y HSTS, también detrás de un proxy TLS. La IP de cliente sigue siendo `REMOTE_ADDR`; detrás de un proxy todos los clientes compartirían IP y `LOGIN_MAX_PER_IP` (20) valdría para todos a la vez. Es una limitación conocida que se documenta en `docs/guides/despliegue.md` y `CHANGELOG.md`; el proyecto no soporta proxies.
- **Rendimiento:** la revalidación de identidad pasa de dos SELECT a uno por petición (`Auth::user()` conserva el suyo).
- Sin cambios de esquema, sin permisos nuevos y sin librerías nuevas.
- Variable nueva en `.env` y `.env.example`: `HSTS_MAX_AGE`.

## Edge cases

- Cabecera `X-Forwarded-Proto: https` forjada con `APP_URL=http://`: se ignora.
- `X-Forwarded-For` con varias IPs, con puerto o con basura:
- Error de generación de TCPDF: no se envían cabeceras de PDF con cuerpo vacío (FR-2).
- Columna escribible nueva en un modelo sin actualizar su lista: falla con excepción (deseado).
- Columnas con `fyh_creacion`/`fyh_actualizacion`: no están en las listas, el motor las gestiona.
- Sesión cerrada por la propia petición (`logout`): no reutiliza el resultado previo (FR-14).

## Out of scope

- Content-Security-Policy en cualquier modo (informe o enforcing) y la eliminación de scripts/estilos inline y fuentes externas que exigiría.
- Subir jQuery, DataTables, JSZip y Chart.js (flujo directo con smoke test por librería).
- `aria-expanded` de `.select2-selection` (viene de la librería).
- Prueba de despliegue real y zona horaria del MySQL de producción (operativo).
- Soporte de proxies reversos: lista de proxies de confianza, `X-Forwarded-For` y CIDR (el proyecto no los soporta; ver NFR de compatibilidad).
- Throttle global por IP para todos los endpoints.
- Endpoint de informes de CSP, `Permissions-Policy`, COOP/COEP, `includeSubDomains` y `preload`.
- Mover a modelos las llamadas directas a `Model::create/update` que hoy hacen algunos controladores.

## Completion criteria

- Todos los FR con test o demo manual registrada en `validation.md`.
- `composer test` verde en local y en CI (PHP 8.2/8.3, MariaDB 10.11), con tests nuevos por modelo.
- Demo manual: los tres PDF con una sola `Cache-Control: private, no-store`; `X-Forwarded-Proto` forjado sin efecto; cookie `Secure` y HSTS solo con `APP_URL` https; `nosniff`, `X-Frame-Options` y `Referrer-Policy` intactos; sesión revocada con la misma respuesta que antes.
- `.env.example`, `docs/guides/autenticacion.md`, `docs/guides/despliegue.md`, `docs/guides/testing.md`, `AGENTS.md` (incluida la corrección de escritores de stock), `CHANGELOG.md` y `docs/roadmap.md` actualizados.

## Clarifications

- Alcance: solo hardening de código (sin librerías JS, select2 ni despliegue real).
- Proxies: fuera de alcance por decisión del usuario (2026-11-09): no usa proxy en Hostinger, no sabe si otros usuarios del proyecto lo usan y no lo llevará a producción. La IP de cliente sigue siendo `REMOTE_ADDR`.
- `Secure` y HSTS: por esquema de `APP_URL`; `X-Forwarded-*` se ignora.
- HSTS: `max-age` por `.env`, sin `includeSubDomains` ni `preload`.
- CSP: retirada de la spec (2026-11-09); sin enforcing no protege y el informe solo genera ruido.
- Lista blanca: obligatoria en todos los modelos, vacía por defecto, excepción ante columna no permitida.
- `PermissionMiddleware`: una sola revalidación por petición; «adelante» explícito del usuario (2026-11-09).
- PDF: `no-store` en los tres generadores.
- Pendiente de verificar en el plan: si el 403 de `PermissionMiddleware` termina como 302.
