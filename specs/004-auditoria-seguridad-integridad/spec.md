# Spec 004 — Auditoría de seguridad e integridad

**Status:** approved
**Origen:** auditoría de dos rondas (38 hallazgos, 16 corregidos en 1.18.2), una
segunda auditoría con tres revisores (dinero/stock, auth/permisos,
frontend/tests) y la lista ClickUp "Sistema de Ventas PHP — Auditoría" (10
tareas).

## Context and goal

La versión 1.18.2 cerró los hallazgos más urgentes de la primera auditoría. Los
restantes se agrupan aquí porque tocan esquema, reglas de dinero/stock o permisos
(flujo SDD obligatorio según `AGENTS.md`) y, junto con los hallazgos nuevos de la
segunda auditoría, comparten dos causas de fondo:

1. **El servidor acepta repetir operaciones y confía en estado desactualizado**:
   reenviar una venta o devolución ya registrada, abrir el POS dos vendedores a la
   vez y recibir el mismo `nro_venta`, escribir el stock de un formulario viejo,
   leer-y-luego-escribir sin bloqueo. El resultado es stock duplicado o negativo,
   ventas repetidas y totales que no cuadran.
2. **Controles de seguridad con huecos puntuales**: carrito sin verificación de
   dueño, sesión sin flags de cookie, login que verifica contraseñas en paralelo
   por encima del límite, enlace de reset visible en pantalla, entre otros.

El objetivo es que, tras esta spec, ninguna operación de dinero o stock pueda
duplicarse, dejar stock negativo ni descuadrar totales, y que los controles de
autenticación y autorización no tengan los huecos detectados.

**Ya resuelto fuera de esta spec (fix directo, commiteado):** clave de permisos
inmutable (escalada a superusuario), validación del email en "Mi perfil" para
roles sin `manage_users`, y bloqueo del doble clic en "Registrar devolución" (lado
cliente). Esta spec cubre el equivalente del lado servidor (FR-6, FR-17).

**Reclasificación ClickUp "Fix #9"** (`salesTotals()` con subconsulta no
correlacionada): tras revisar el código, el cálculo agregado es correcto con la
regla "venta neta = ventas − devoluciones imputadas al período". Lo que sí existe
es una inconsistencia de criterio entre filas y total del reporte y un ticket
promedio bruto frente a un total neto → FR-27 y FR-28.

## Users / actors

- **Vendedor** — cobra ventas en el POS y registra devoluciones.
- **Comprador** — registra compras y mantiene productos.
- **Administrador / rol con `manage_*`** — administra catálogos, usuarios, roles y
  permisos, y ajusta inventario.
- **Usuario autenticado (cualquier rol)** — gestiona su propio perfil y
  contraseña.
- **Atacante no autenticado / usuario con permisos acotados** — actor que estos
  requisitos deben contener (fuerza bruta, escalada, acceso a registros ajenos).

## User stories

- U1: Como vendedor quiero que reenviar o recargar el cobro no duplique la venta
  ni el descuento de stock.
- U2: Como vendedor quiero que mi carrito en curso no pueda ser modificado,
  finalizado ni borrado por otro vendedor.
- U3: Como administrador quiero que el stock solo cambie por operaciones
  auditadas (Inventario, compras, ventas, devoluciones) y nunca quede negativo.
- U4: Como administrador quiero que los reportes y el dashboard muestren cifras
  coherentes entre sí y que cada cambio sensible quede en el registro de
  actividad.
- U5: Como dueño del negocio quiero que las contraseñas, sesiones y enlaces de
  restablecimiento no se puedan abusar para tomar cuentas ajenas.
- U6: Como desarrollador quiero que la suite de tests y la CI detecten las
  regresiones de estas reglas.

## Functional requirements (EARS acceptance criteria)

Convención de verificación (constitución, principio 8): las reglas de negocio y de
propiedad viven en el modelo y se prueban a nivel de modelo; lo que reside en
`Auth`, `AuthController`, `Router` o vistas se comprueba con demo manual registrada
en `validation.md`.

Definiciones usadas en toda la spec:

- **Reserva (carrito en curso):** fila abierta de `tb_pos_reservas` (FR-2). Su id
  es el `nro_venta` del carrito; pertenece a un usuario y se identifica desde la
  BD, no desde la sesión.
- **Venta finalizada:** existe una fila de `tb_ventas` con ese `nro_venta`.
- **Línea de carrito:** fila de `tb_carrito`, identificada por `id_carrito`.

### A. POS: carrito y numeración de ventas

- FR-1: WHEN una operación de carrito (agregar, quitar, cancelar, finalizar)
  recibe un `nro_venta` que no es la reserva abierta del usuario autenticado, o un
  `id_carrito` que no pertenece a esa reserva, THE SYSTEM shall rechazarla sin
  modificar nada y mostrar el mismo aviso de acceso denegado que usa la app para
  recursos ajenos (`Controller::forbidden()`). Ningún rol, incluido el que tiene
  `view_sales_all`, queda exento. La comprobación vive en el modelo, que recibe
  explícitamente el id del usuario. Este requisito no aplica a la eliminación de
  ventas ya finalizadas, que sigue el scoping `view_sales_all` vigente.
- FR-2: WHEN se abre el POS y el usuario no tiene una reserva abierta, THE SYSTEM
  shall crear una fila en la tabla nueva `tb_pos_reservas` (`id_reserva`
  autoincremental que es el `nro_venta`, `id_usuario`, `fyh_creacion`,
  `fyh_liberacion` NULL) y usar ese id como `nro_venta`; si ya tiene una abierta,
  shall retomarla. Así dos vendedores nunca reciben el mismo número aunque ambos
  abran el POS antes de agregar ningún ítem. La reserva se libera (rellena
  `fyh_liberacion`, nunca se borra la fila) al finalizar o cancelar la venta; los
  huecos son aceptables. La migración inicializa la secuencia por encima del
  máximo `nro_venta` existente en ventas y carritos. `$_SESSION['pos_nro_venta']`
  deja de ser la fuente de verdad. La tabla funciona igual en MariaDB, MySQL 8.4 y
  SQLite.
- FR-3: WHEN el usuario abre el POS y tiene más de una reserva abierta (datos
  anteriores a esta spec), THE SYSTEM shall conservar la más reciente, liberar las
  demás y eliminar únicamente las líneas de carrito de esas reservas propias que no
  correspondan a una venta finalizada. THE SYSTEM shall dejar de purgar los
  carritos de todos los usuarios al abrir el POS (hoy `purgeOrphans`). Al cambiar
  de navegador o recuperar una sesión expirada, el usuario retoma el mismo carrito
  porque la reserva vive en la BD.
- FR-4: IF se agrega, quita o cancela un carrito cuyo `nro_venta` ya corresponde a
  una venta finalizada, THEN THE SYSTEM shall rechazar la operación sin modificar
  nada. (El reenvío del cobro de una venta ya finalizada se rige por FR-6, no por
  este requisito.)
- FR-5: THE SYSTEM shall garantizar a nivel de datos que no existan dos ventas
  con el mismo `nro_venta` (índice `UNIQUE` en `tb_ventas.nro_venta`).
- FR-6: IF el mismo usuario reenvía el cobro de un `nro_venta` ya finalizado
  (doble clic, recarga, segunda pestaña, reintento), THEN THE SYSTEM shall no crear
  una venta nueva, no descontar stock otra vez, no volver a fijar precios de las
  líneas ya congeladas, y responder igual que el cobro original: mismo mensaje de
  éxito y redirección al listado de ventas, con el vuelto recalculado desde
  `tb_pagos`. Si el `nro_venta` pertenece a otro usuario se aplica FR-1.
- FR-7: WHEN se agrega al carrito un producto que ya está en ese carrito, THE
  SYSTEM shall acumular la cantidad en una sola línea.
- FR-8: WHEN se agrega un producto al carrito, THE SYSTEM shall validar el stock
  disponible contra la cantidad total de ese producto ya presente en el carrito,
  no solo contra la cantidad que se agrega.
- FR-9: THE SYSTEM shall poder instalarse en MySQL 8.4 sin depender de una clave
  foránea que apunte a una columna no única (`tb_ventas.nro_venta →
tb_carrito.nro_venta`; la migración la elimina). La integridad se mantiene así:
  una venta no puede finalizarse sin líneas de carrito y las líneas de una venta
  finalizada no pueden modificarse (se considera finalizada según la definición de
  arriba); ambas reglas las impone el modelo dentro de la transacción.
- FR-52: IF el total de un carrito supera el máximo representable de la columna
  (99.999.999,99), THEN THE SYSTEM shall rechazar el cobro con un mensaje claro. La
  comprobación se hace en PHP porque SQLite no impone el límite.

### B. Pagos y cobro

- FR-10: WHEN se normalizan los montos de pago, THE SYSTEM shall convertir cada
  monto a céntimos enteros (mitad hacia arriba) al recibirlo, sin depender de
  `bcmath` ni de aritmética `float`, sumar líneas del mismo método en céntimos y
  validar la suma de los montos que realmente se guardarán.
- FR-11: IF el vuelto (suma de pagos menos total de la venta) supera la suma de los
  pagos en efectivo de esa venta, THEN THE SYSTEM shall rechazar la venta con un
  mensaje claro y no registrar nada. En un pago mixto el vuelto sale siempre del
  efectivo recibido.
- FR-12: IF el total de la venta es 0 o la venta quedaría sin ninguna línea de
  pago, THEN THE SYSTEM shall rechazar el cobro. (Un carrito vacío ya se rechaza;
  un total negativo no es alcanzable porque precios y cantidades se validan > 0 en
  el servidor.)
- FR-13: IF un método de pago ya tiene pagos registrados, THEN THE SYSTEM shall
  permitir editar solo su nombre y su estado activo, y bloquear el cambio de tipo
  (`efectivo` / `no_efectivo`).
- FR-14: THE SYSTEM shall mostrar en la factura PDF y en el detalle de la venta
  `total_pagado` como monto de la venta, y usarlo también para el importe en letras
  y para el vuelto. IF la suma de las líneas a precio congelado difiere de
  `total_pagado` (ventas históricas), THEN THE SYSTEM shall mostrar una fila
  "Ajuste por precios históricos" con la diferencia, que puede ser positiva o
  negativa, de modo que la tabla sume `total_pagado`. Esto enmienda la regla de
  factura de la spec 002 solo para ventas donde las cifras difieren.

### C. Devoluciones

- FR-15: WHEN se calcula lo pendiente de devolver, THE SYSTEM shall sumar todas
  las líneas de la venta del mismo producto y restar lo ya devuelto, sin
  subestimar el pendiente cuando hay líneas repetidas.
- FR-16: THE SYSTEM shall limitar el monto acumulado de devoluciones de una venta
  a su `total_pagado` (también en ventas sin líneas de pago). WHEN el monto
  calculado de una devolución superaría el remanente, THE SYSTEM shall registrar
  el remanente como monto (0 si ya está agotado) y reponer igualmente el stock de
  todas las unidades devueltas. Una venta con devoluciones sigue sin poder
  eliminarse (`isReferenced`).
- FR-17: THE SYSTEM shall generar al abrir el formulario de devolución un token de
  formulario aleatorio (distinto del token CSRF, que es único por sesión) y
  guardarlo en una columna nueva `tb_devoluciones.token_formulario` (`CHAR(32)`
  NULL, `UNIQUE`). IF se reenvía una devolución con un token ya registrado, THEN
  THE SYSTEM shall no crear otra devolución ni reponer stock otra vez y mostrar el
  resultado de la devolución original; si dos reenvíos chocan con la clave única,
  el perdedor busca la original y la devuelve. Dos devoluciones parciales
  distintas, obtenidas abriendo el formulario dos veces (tokens distintos), siguen
  siendo válidas mientras no superen lo pendiente.
- FR-18: WHEN dos devoluciones de la misma venta se registran simultáneamente, THE
  SYSTEM shall procesarlas en serie: la segunda ve lo pendiente ya actualizado y,
  si pide más de lo pendiente, se rechaza con un mensaje de conflicto, sin error
  interno. Esto incluye el choque de la clave temporal de `nro_devolucion`, que
  debe producir el mismo mensaje de conflicto, y funciona también con el fixture
  SQLite.
- FR-53: WHEN se elimina una venta, THE SYSTEM shall volver a comprobar dentro de
  la transacción que no tenga devoluciones y, si las tiene, rechazar con un mensaje
  que lo indique (no un error genérico), para que una devolución concurrente no deje
  una venta eliminada con devoluciones huérfanas.

### D. Stock, inventario y compras

- FR-19: THE SYSTEM shall mostrar el stock de un producto como solo lectura en su
  ficha de edición e ignorar cualquier valor de stock enviado desde ese
  formulario. La edición tampoco sobrescribe el `id_usuario` del producto. El
  formulario de alta sí permite un stock inicial.
- FR-54: WHEN se crea un producto con stock inicial mayor que 0, THE SYSTEM shall
  registrarlo como una entrada en el historial de ajustes con el motivo "Stock
  inicial", dentro de la misma transacción del alta (sin abrir una transacción
  anidada).
- FR-20: THE SYSTEM shall cambiar el stock únicamente mediante el alta de producto
  (stock inicial), Inventario (ajustes), compras (alta, edición y baja), ventas
  (alta y eliminación) y devoluciones, dejando cada operación registrada en el
  registro de actividad; los ajustes manuales además en el historial de ajustes.
- FR-21: WHEN se registra un ajuste de stock, THE SYSTEM shall aplicarlo con un
  `UPDATE` relativo y condicionado (`stock = stock ± ?` con piso en 0) y obtener
  `stock_posterior` de la BD y `stock_anterior` como posterior menos el delta, de
  modo que una venta o compra concurrente no se pierda ni falsee los valores.
- FR-22: IF eliminar o editar una compra dejaría el stock de cualquier producto
  afectado (el anterior y, si cambia el producto, el nuevo) por debajo de 0, THEN
  THE SYSTEM shall rechazar la operación sin cambios y mostrar un mensaje que
  indique el stock disponible. La comprobación es un `UPDATE` condicionado
  (`AND stock >= ?`) con verificación de filas afectadas y rollback.
- FR-23: WHEN se edita una compra, THE SYSTEM shall conservar su `id_usuario`
  original y registrar la edición en el registro de actividad con los valores
  anteriores y nuevos.
- FR-24: IF un producto está referenciado por ajustes de stock o por líneas de
  devolución (además de lo que ya cuenta hoy), THEN THE SYSTEM shall bloquear su
  eliminación con el mensaje de referencias, sin error interno.
- FR-25: THE SYSTEM shall validar en el servidor, al crear o editar productos y
  compras: precio de venta y de compra mayores que 0 y como máximo 9.999.999,99;
  stock entero entre 0 y 1.000.000; cantidad de compra entera entre 1 y
  1.000.000; `nro_compra` único (comprobado en el modelo y respaldado por un
  índice `UNIQUE`); y `fecha_compra` con formato válido y no futura.
- FR-26: WHEN se crea, edita o elimina un proveedor, un cliente, una categoría
  (donde la operación existe; hoy no hay eliminación) o un producto, THE SYSTEM
  shall registrar la acción en el registro de actividad. Hoy faltan: edición de
  cliente y de proveedor, edición de producto distinta del precio y eliminación de
  producto.

### E. Reportes y dashboard

- FR-27: THE SYSTEM shall incluir en el total del reporte de ventas las
  devoluciones imputadas al período, entendiendo imputadas como las registradas con
  `fyh_creacion` dentro del período, y mostrar en el reporte una fila
  "Devoluciones de ventas de períodos anteriores" con la parte que no corresponde
  a las ventas listadas, de modo que filas y fila adicional sumen el total.
- FR-28: THE SYSTEM shall calcular el ticket promedio como total de ingresos neto
  dividido entre el número de ventas, y 0 cuando no hay ventas.
- FR-29: WHEN el dashboard genera la serie de los últimos 6 meses (una sola
  constante usada por las etiquetas y por la consulta), THE SYSTEM shall mostrar 6
  meses consecutivos distintos independientemente del día del mes actual, partiendo
  del primer día del mes actual.

### F. Autenticación y sesión

- FR-30: WHEN se inicia una sesión, THE SYSTEM shall emitir la cookie de sesión
  con HttpOnly, SameSite=Lax y Secure (cuando la conexión es HTTPS) y regenerar el
  token CSRF.
- FR-31: WHEN un usuario cambia su propia contraseña desde el perfil, THE SYSTEM
  shall exigir su contraseña actual. Un administrador que cambia la contraseña de
  otro usuario no la necesita. En todos los flujos que fijan una contraseña nueva
  (alta, perfil, administrador, restablecimiento) el mínimo es de 8 caracteres.
- FR-32: WHEN se cambia la contraseña de un usuario (perfil, administrador o
  restablecimiento), THE SYSTEM shall eliminar todos los tokens de "recordarme" (de
  todos sus dispositivos) de ese usuario. Las sesiones ya abiertas expiran por inactividad (ver Out of scope).
- FR-33: WHEN se recibe un intento de login (de un correo existente o no), THE
  SYSTEM shall registrarlo en la tabla nueva `tb_intentos_auth` (`tipo` `login`,
  `ip`, `correo` normalizado, `id_usuario` NULL si no existe) antes de verificar la
  contraseña, y rechazarlo con el mensaje de FR-34 sin verificarla IF las
  solicitudes de ese correo en los últimos 15 minutos por defecto (ventana configurable), contando hasta este intento,
  superan el máximo por correo (5 por defecto), o las de esa IP superan el máximo por IP (20 por defecto). El orden de llegada lo fija el `id` del
  registro, de modo que una ráfaga paralela no verifique más de 5 contraseñas por
  correo ni más de 20 por IP con los valores por defecto. WHEN el login es correcto, THE SYSTEM shall borrar los
  intentos de ese correo. La IP es `REMOTE_ADDR` y las ventanas de tiempo se
  calculan con el reloj de la BD. Las columnas `login_intentos` y
  `login_bloqueado_hasta` de `tb_usuarios` dejan de usarse.
- FR-34: WHEN falla un login por correo inexistente, contraseña incorrecta o
  cuenta bloqueada, THE SYSTEM shall mostrar el mismo mensaje, que además dirige a
  "¿Olvidaste tu contraseña?", y shall ejecutar la verificación de contraseña
  contra un hash ficticio cuando el correo no existe. El correo se normaliza
  (espacios y minúsculas) al buscarlo y también al guardarlo (alta de usuario y
  edición del perfil).
- FR-35: THE SYSTEM shall nunca mostrar en pantalla el enlace de restablecimiento,
  tampoco con `APP_DEBUG=true` ni `APP_ENV=local`, ni revelar si el correo existe;
  WHILE `APP_DEBUG=true`, THE SYSTEM shall escribir el enlace en el registro de
  errores. La vista `show-reset-link.php` se elimina.
- FR-36: THE SYSTEM shall almacenar el hash SHA-256 del token de restablecimiento
  (la búsqueda es por hash) y limitar las solicitudes a 1 cada 5 minutos por
  usuario y 5 por hora por IP (`REMOTE_ADDR`) —valores por defecto, configurables—, respondiendo siempre con el mismo
  mensaje exista o no el correo. Las solicitudes se registran en `tb_intentos_auth`
  (`tipo` `reset`, también para correos inexistentes), y las comparaciones de tiempo
  (límites y expiración del token, 1 hora por defecto) se calculan con el reloj de la BD, no con el de
  PHP.
- FR-37: IF una cookie de "recordarme" es inválida (selector inexistente, validador
  incorrecto o vencida), THEN THE SYSTEM shall eliminar solo la cookie del cliente y
  no modificar ningún token almacenado.
- FR-58: THE SYSTEM shall guardar los tokens de "recordarme" en la tabla nueva
  `tb_sesiones_recordadas`, una fila por dispositivo (`selector` único aleatorio,
  hash SHA-256 del validador, `id_usuario`, `fyh_expira` según `REMEMBER_LIFETIME`, `ip`, `user_agent`,
  `fyh_ultimo_uso`); la cookie lleva `selector:validador`. WHEN se usa un token
  válido, THE SYSTEM shall rotar el validador solo de esa fila y actualizar IP, agente
  y último uso; IF la IP difiere de la guardada, THEN THE SYSTEM shall registrar el
  cambio en el registro de actividad sin rechazar el acceso. WHEN el usuario cierra
  sesión, THE SYSTEM shall borrar solo la fila de ese dispositivo; el cambio de
  contraseña las borra todas (FR-32). Iniciar sesión con "recordarme" en un
  dispositivo no invalida los demás. La migración borra los tokens anteriores de
  `tb_usuarios`.

### G. Robustez y respuesta ante errores

- FR-38: IF un parámetro de ruta `{id}` no es numérico, THEN THE SYSTEM shall
  responder 404 en lugar de un error interno. Los parámetros no numéricos por
  diseño (p. ej. `{token}` de restablecimiento) quedan fuera.
- FR-39: IF la conexión a la BD falla, THEN THE SYSTEM shall mostrar la página de
  error 500 sin exponer usuario ni host y registrar el detalle en el log.
- FR-40: WHEN se sube una imagen de producto, THE SYSTEM shall verificar
  `$_FILES[...]['error']` antes de procesarla y, ante un fallo (p. ej. tamaño
  excedido), mostrar "No se pudo subir la imagen" en lugar de un error interno;
  shall ignorar la ruta de imagen enviada por el cliente (`image_text`) y conservar
  la de la BD; y el directorio de subidas shall incluir su `.htaccess` que impide
  ejecutar PHP, con el requisito equivalente documentado para servidores sin
  `.htaccess`. Se mantienen las validaciones vigentes de extensión, MIME real y
  2 MB.
- FR-41: THE SYSTEM shall escapar los datos de usuario que inserta en el HTML de
  los correos.

### H. Autorización en la interfaz y coherencia de assets

- FR-42: THE SYSTEM shall mostrar los botones Nuevo, Editar y Eliminar que ya
  existen solo a quien tenga el permiso correspondiente, usando el `$can` que
  `Controller::renderWithLayout()` ya entrega a toda vista, en los listados y
  detalles de categorías, proveedores, clientes, productos, compras, usuarios
  (`manage_users`), roles y permisos (`manage_roles`), métodos de pago
  (`manage_payment_methods`) y devoluciones (`manage_returns`), y ocultar en el POS
  el botón "Nuevo cliente" a quien no tenga `manage_clients`. No se crean botones
  ni rutas de eliminación que hoy no existen (categorías, roles, permisos).
- FR-43: THE SYSTEM shall enlazar solo a rutas existentes (hoy
  `/categories/create`, `/suppliers/create` y el `/dashboard` de
  `views/returns/show.php` no existen; el inicio es `/`).
- FR-44: THE SYSTEM shall versionar con `APP_VERSION` todos los assets propios,
  incluidos los de los layouts de autenticación y error.
- FR-45: THE SYSTEM shall no invocar `Auth::` desde el layout: el controlador
  entrega el token CSRF a la vista.
- FR-55: THE SYSTEM shall emitir `BASE_URL` hacia JavaScript con `json_encode` y
  los atributos `data-*` de moneda escapados.

### I. Esquema y migraciones

- FR-46: THE SYSTEM shall permitir volver a ejecutar cada migración sin error
  (idempotencia), incluida `002_ventas_id_usuario.sql`. Las migraciones nuevas se
  numeran a partir de la 011 y se aplican después de las 006 a 010 pendientes en
  producción.
- FR-47: THE SYSTEM shall mantener que aplicar en orden todas las migraciones
  produzca la misma estructura que `database/schema.sql`, y que
  `tests/fixtures/schema.sqlite.sql` refleje esa estructura con las diferencias de
  sintaxis documentadas en `AGENTS.md`.
- FR-56: IF la BD ya contiene ventas con `nro_venta` repetido, compras con
  `nro_compra` repetido o ventas sin líneas de carrito, THEN la migración
  correspondiente shall abortar sin modificar datos e informar cuáles son, para
  resolverlas manualmente.
- FR-57: THE SYSTEM shall poder instalarse desde cero en MySQL 8.4: las
  migraciones no usan sintaxis exclusiva de MariaDB (`ADD COLUMN IF NOT EXISTS`) y
  `schema.sql` no declara columnas `TEXT` con valor por defecto literal; la
  idempotencia se logra comprobando `information_schema`.

### J. Calidad y regresión

- FR-48: THE SYSTEM shall tener tests de modelo que verifiquen: pertenencia del
  carrito y reservas (FR-1, FR-2), `Role::grantsSuperadmin`, reversión de compra con
  stock ya vendido, replay de venta y de devolución (con token), tope de vuelto,
  límite por devolución, pendiente con líneas repetidas y límite de intentos
  por correo y por IP (con ráfaga) y rotación de tokens de "recordarme" por
  dispositivo, a nivel de modelo.
- FR-49: WHILE la variable de entorno `CI` está definida, WHEN la CI no puede
  conectar con MariaDB, THE SYSTEM shall fallar el job; en local los tests
  `*MariaDbTest` siguen saltándose si no hay servidor.
- FR-50: THE SYSTEM shall activar las claves foráneas en el fixture SQLite de
  tests y declararlas en él, ajustando los seeders de test que hoy insertan filas
  sin sus padres. Depende de FR-9 (SQLite también rechaza una FK a columna no
  única).
- FR-51: THE SYSTEM shall tener tests con aserciones verificables (sin
  `expectNotToPerformAssertions` donde hay algo que comprobar).
- FR-63: THE SYSTEM shall poder ejecutar en local los tests `*MariaDbTest` sin
  saltarse ninguno: existe un `.env.testing` (ignorado por git, copiado de
  `.env.testing.example`) que apunta a una BD de test dedicada (`DB_TEST_NAME`,
  distinta de la de desarrollo), y la suite informa 0 tests saltados por falta de
  servidor. Los tests de autenticación (ventanas y ráfaga), `RememberToken`, migraciones
  y actualización desde 1.18.3 dependen de esto: sin entorno, no se marcan como hechos.
- FR-64: THE SYSTEM shall leer los umbrales de autenticación de variables de
  entorno del `.env` mediante `Config`, con estos valores por defecto (los de FR-33,
  FR-36 y FR-58) y rangos válidos: `LOGIN_MAX_PER_EMAIL` 5 (1–50),
  `LOGIN_MAX_PER_IP` 20 (1–500), `LOGIN_WINDOW_MINUTES` 15 (1–1440),
  `RESET_TOKEN_MINUTES` 60 (5–1440), `RESET_COOLDOWN_MINUTES` 5 (1–60),
  `RESET_MAX_PER_IP_HOUR` 5 (1–100) y `REMEMBER_LIFETIME` 14 días (1–90). IF una
  variable no está definida, no es un entero o está fuera de su rango, THEN THE
  SYSTEM shall usar el valor por defecto. La lectura vive en el modelo
  (`AuthAttempt`, `User`, `RememberToken`), el valor se convierte a entero antes de
  usarse en SQL, las variables se documentan en `.env.example` y `AGENTS.md`, y los
  tests fijan los valores que ejercen sin depender del `.env` local. La purga de
  intentos antiguos usa como mínimo la ventana de login configurada.
- FR-59: THE SYSTEM shall no contener en el código de `app/` comentarios que citen
  FR, specs, tareas o tickets (hoy: `Sale.php`, `SaleReturnController.php`,
  `PaymentMethod.php` y `PaymentMethodController.php`); los comentarios que se conserven explican el comportamiento
  en términos del dominio. La limpieza no cambia comportamiento y se entrega en un
  commit propio, separado de los cambios funcionales.
- FR-60: THE SYSTEM shall tener la skill `code-review` del proyecto adaptada a este
  repositorio: sin referencias a `SistemaReservasHospital`, ramas `feature/rfXX` ni
  `develop`, con `master` como rama base y un checklist alineado con la constitución
  y las prohibiciones de `AGENTS.md`.
- FR-61: THE SYSTEM shall mantener `AGENTS.md` y `CLAUDE.md` sincronizados con el
  código al cerrar esta spec: firma real de `SalePayment::vueltoFor`, columnas reales
  de `tb_usuarios`, reglas de login y restablecimiento (sin enlace en pantalla,
  `.env.example`) y mínimo de contraseña.
- FR-62: THE SYSTEM shall poder actualizarse desde la versión 1.18.3 sin pérdida de
  datos para quien ya la usa:
  (a) las migraciones 011 a 014 no eliminan tablas, columnas ni filas; solo retiran la
  clave foránea `tb_ventas.nro_venta → tb_carrito.nro_venta` (se localiza por la tabla
  que referencia, no por su nombre) y anulan los tokens de restablecimiento y de
  "recordarme" anteriores;
  (b) IF una conversión de columna puede truncar datos existentes (hoy
  `tb_almacen.imagen` a `VARCHAR(255)` con valores de más de 255 caracteres), THEN la
  migración shall abortar sin modificar nada e informar cuáles;
  (c) existe una consulta de comprobación previa, de solo lectura, que informa todo lo
  que haría abortar a las migraciones 012 y 014, para ejecutarla en producción antes de
  desplegar;
  (d) la reescritura de las migraciones 002, 003 y 006 produce exactamente la misma
  estructura que las originales;
  (e) la BD migrada sigue siendo utilizable por la versión 1.18.3, de modo que se puede
  revertir solo el código;
  (f) las contraseñas, correos y registros existentes siguen siendo válidos: el inicio
  de sesión no aplica el mínimo de 8 caracteres (solo las contraseñas nuevas) y un
  correo guardado con mayúsculas se sigue encontrando.

## Non-functional requirements

- Compatible con PHP 8.2/8.3, MariaDB 10.4+ y MySQL 8.4; sin librerías nuevas
  (constitución, principio 2). Las tablas nuevas (`tb_pos_reservas`,
  `tb_intentos_auth`, `tb_sesiones_recordadas`) y la columna nueva (`tb_devoluciones.token_formulario`)
  usan solo SQL estándar y se justifican en el `plan.md`.
- Sin métodos de controlador nuevos (principio 5): la reserva ocurre dentro de
  `create()` del POS y el token de devolución dentro del `create()` de devoluciones.
- Todo cambio de esquema se entrega como migración numerada reejecutable más
  actualización de `schema.sql` y del fixture SQLite.
- Las operaciones de dinero y stock se resuelven dentro de transacción, con montos
  y stock calculados desde la BD (constitución, principio 6).
- Sin regresiones de accesibilidad: axe-core sin violaciones en claro y oscuro en
  las vistas tocadas.
- Sin pérdida de datos: ninguna migración borra ni reescribe precios o montos
  históricos.
- Los comentarios del código no citan FR ni specs; los comentarios existentes que
  citan FR de specs anteriores (colisionan con esta numeración) se limpian en un
  commit aparte (FR-59).

## Edge cases

- Dos pestañas del mismo vendedor con el mismo carrito: la segunda finalización
  muestra el resultado de la primera (FR-6).
- Dos navegadores del mismo vendedor: ambos retoman la misma reserva (FR-3).
- Venta con carrito de líneas repetidas previas a FR-7: las ventas históricas se
  devuelven sumando líneas (FR-15).
- Devolución parcial seguida de otra de la misma venta: ambas válidas mientras no
  superen lo pendiente; el monto se limita al remanente (FR-16).
- Ventas históricas sin `tb_pagos`: se muestran con "—", el tope de devolución usa
  `total_pagado` y no impiden devolver (FR-16).
- Ventas históricas con ajuste de precios negativo o positivo: la factura cuadra
  con `total_pagado` (FR-14).
- Compra eliminada cuyo producto ya se vendió: se rechaza y se informa el stock
  disponible (FR-22).
- Servidor sin HTTPS (desarrollo local): la cookie no lleva `Secure` (FR-30).
- `APP_DEBUG=true` o `APP_ENV=local`: el enlace de reset no aparece nunca en
  pantalla (FR-35).
- Sesión expirada en medio de una venta: la reserva sigue abierta y se retoma al
  volver a abrir el POS (FR-3).
- Carritos abiertos anteriores a esta spec, sin reserva: nadie puede operarlos y ya
  no se purgan (Out of scope).
- Tokens de reset emitidos antes de aplicar FR-36 quedan inválidos (duran 1 hora).
- Los "recordarme" activos antes de esta spec se cierran: el usuario vuelve a iniciar
  sesión una vez (FR-58).
- Dos pestañas con la misma cookie de "recordarme": la segunda, con el validador ya
  rotado, queda como cookie inválida y no afecta a los demás dispositivos (FR-37).
- Series de meses del dashboard: PHP genera las etiquetas desde el primer día del
  mes; la diferencia horaria PHP/MariaDB en el instante límite se acepta y los
  tests de fechas calculan los límites con SQL del propio motor.
- Correo con mayúsculas ya guardado: MariaDB lo busca sin distinguir mayúsculas por
  su collation; SQLite sí, por eso se normaliza también al escribir (FR-34).

## Out of scope

- **Arqueo / cuadre de caja** y ventas a crédito (backlog del roadmap).
- Reembolso o reversión de pagos en devoluciones (la devolución sigue siendo
  informativa).
- Reescribir precios históricos con una migración de reparto (se elige limitar la
  devolución, no inventar datos; FR-14, FR-16).
- Cabeceras CSP/HSTS.
- Doble codificación HTML al guardar (`Controller::input()` escapa antes de
  persistir): requiere una migración de datos existentes; candidata a spec propia.
- Throttle global por IP para todos los endpoints (solo login y restablecimiento) y
  pantalla para ver o cerrar los dispositivos recordados.
- Corregir stock negativo ya existente en producción, imágenes de producto con
  rutas arbitrarias ya guardadas y limpieza de carritos abiertos heredados de otros
  usuarios.
- Invalidar las sesiones ya abiertas al cambiar la contraseña (exigiría una versión
  de credenciales en sesión; se acepta que expiren por inactividad).
- Bloqueo malicioso de una cuenta por intentos fallidos (riesgo aceptado; la
  salida es "¿Olvidaste tu contraseña?"); el límite por IP reduce, sin eliminar, el
  riesgo de fuerza bruta distribuida. Exigir que la IP coincida para el
  "recordarme" (se registra, no se exige: las IP móviles cambian).
- Pantalla de administración de los umbrales de autenticación y cambio en caliente
  sin reiniciar: se editan en el `.env`. Un valor permisivo dentro del rango sigue
  siendo decisión del operador.
- Fixes ya aplicados: clave de permisos inmutable, validación del email en el
  perfil y bloqueo del doble clic en devoluciones (lado cliente).

## Completion criteria

- Todos los FR con test de modelo que pasa o, si residen en `Auth`,
  `AuthController`, `Router` o vistas, con demo manual documentada en
  `validation.md`.
- `composer test` verde, en local con `.env.testing` (FR-63) y en CI con MariaDB, sin tests saltados por falta de servidor.
- Migraciones reejecutables; `schema.sql` y fixture SQLite sincronizados; `AGENTS.md`
  y `CLAUDE.md` sincronizados según FR-61.
- Comentarios con referencias a specs limpiados (FR-59) y skill `code-review`
  adaptada (FR-60), verificados con `grep` sin coincidencias.
- Actualización verificada sobre una BD en el estado de 1.18.3 con datos (FR-62): los
  conteos y sumas de ventas, pagos, devoluciones, compras y stock son idénticos antes y
  después de aplicar 011–014, y la 1.18.3 funciona contra esa BD.
- Demo manual: doble envío de venta y devolución sin duplicados; dos vendedores
  abriendo el POS a la vez; carrito ajeno rechazado; vuelto mayor al efectivo
  rechazado; compra revertida con stock vendido rechazada; ficha de producto con
  stock de solo lectura; login con ráfaga paralela; reset sin enlace en pantalla;
  perfil propio con un rol sin `manage_users` (vendedor): cambio de email sin error
  y cambio de contraseña exigiendo la actual. Prerrequisito de entorno (no es un
  requisito del producto): dejar en la BD local un vendedor con contraseña conocida y
  sin bloqueo de login.
- axe-core sin violaciones en claro y oscuro en las vistas modificadas.
- `CHANGELOG.md` y `docs/roadmap.md` actualizados.

## Clarifications

- Alcance: una sola spec con todo lo de ClickUp más los hallazgos nuevos,
  organizada por secciones → FR-1 a FR-57.
- Reenvío de venta o devolución ya registrada: idempotente, muestra el resultado
  original → FR-6, FR-17.
- Stock en la ficha de producto: solo lectura; cambia únicamente por Inventario,
  compras, ventas y devoluciones → FR-19, FR-20.
- Pagos: se rechaza el vuelto mayor al efectivo y cada monto se redondea a 2
  decimales al entrar → FR-10, FR-11.
- Histórico: la devolución no puede superar lo cobrado y la factura usa
  `total_pagado`, sin reescribir precios; el tipo de un método con pagos queda
  bloqueado → FR-13, FR-14, FR-16.
- Auth: se incluyen cookie de sesión, contraseña actual, límite atómico de login
  con mensaje uniforme, y reset sin enlace en pantalla con token hasheado →
  FR-30 a FR-37.

- Ambigüedad: significado de "carrito en curso" y exención por rol → un solo
  carrito por usuario, sin exención por `view_sales_all` → FR-1.
- Ambigüedad: cuándo se reserva el número y qué pasa con una reserva abandonada →
  se reserva al abrir el POS, se libera al finalizar o cancelar, los huecos son
  aceptables → FR-2.
- Ambigüedad: propiedad y limpieza de carritos → el carrito registra su usuario y
  solo se purgan los propios; la limpieza de carritos de otros queda fuera →
  FR-3, Out of scope.
- Contradicción FR-4 vs FR-6 y ambigüedad de qué es un reenvío → FR-4 rechaza
  agregar/quitar ítems en un carrito finalizado; el reenvío del cobro del mismo
  `nro_venta` es idempotente con éxito → FR-4, FR-6.
- Ambigüedad: "actualizar" una línea del carrito, que no existe → se reduce a
  agregar → FR-8.
- Ambigüedad: "integridad entre venta y carrito" → reglas verificables impuestas
  por el modelo; conflicto con el principio 6 resuelto al fijarlas explícitamente
  → FR-9.
- Ambigüedad: modo de redondeo → mitad hacia arriba → FR-10.
- Contradicción con la spec 002 y ambigüedad de subtotales en la factura →
  `total_pagado` como total más una fila "Ajuste por precios históricos" solo
  cuando difiere → FR-14.
- Ambigüedad de "lo realmente cobrado" y contradicción con FR-15 al devolver todo
  → cobrado es `total_pagado`; el monto de la última devolución se limita al
  remanente y el stock se repone completo → FR-16.
- Ambigüedad: qué identifica un reenvío de devolución → token de formulario; dos
  devoluciones parciales distintas siguen siendo válidas → FR-17.
- Ambigüedad: comportamiento ante devoluciones simultáneas → procesamiento en
  serie, la segunda se rechaza con conflicto si excede → FR-18.
- Caso borde: eliminar una venta mientras se registra una devolución → nueva
  comprobación dentro de la transacción → FR-53.
- Ambigüedad: ignorar o rechazar el stock en la ficha, y stock inicial al crear →
  se ignora; el alta permite stock inicial registrado como entrada → FR-19,
  FR-54.
- Contradicción: FR-20 omitía la eliminación de ventas y "auditoría" mezclaba dos
  tablas → lista completa y registro definido → FR-20.
- Ambigüedad y `[NEEDS CLARIFICATION]` de FR-25 → precios > 0 y ≤ 9.999.999,99,
  stock y cantidades enteros de 0 a 1.000.000, `nro_compra` único en el modelo,
  fecha no futura; resuelve también la contradicción con FR-12 → FR-25.
- Caso borde: total de carrito que desborda `DECIMAL(10,2)` → rechazo → FR-52.
- Ambigüedad: entidades que exigen bitácora → todo el CRUD de proveedores,
  clientes, categorías y productos → FR-26.
- Ambigüedad: dos alternativas en FR-27 y FR-28 → una sola: fila de devoluciones
  de períodos anteriores y ticket promedio neto → FR-27, FR-28.
- Ambigüedad: SameSite → Lax → FR-30.
- Ambigüedad: contraseña actual y cambio por administrador → solo en el perfil
  propio; el cambio de otro usuario no la exige → FR-31, FR-32.
- Contradicción de FR-34 con la regla vigente de bloqueo y ambigüedad de
  contador/tiempo → mensaje único que incluye la salida "¿Olvidaste tu
  contraseña?", hash ficticio para correos inexistentes y correo normalizado;
  contador solo para cuentas existentes → FR-33, FR-34.
- Contradicción con la documentación vigente de `APP_DEBUG` → la regla nueva
  manda y `AGENTS.md`/`CLAUDE.md` se actualizan al cerrar → FR-35, Completion
  criteria.
- Ambigüedad y `[NEEDS CLARIFICATION]` de FR-36 → 1 solicitud cada 5 minutos por
  usuario y 5 por hora por IP, mensaje idéntico exista o no el correo → FR-36.
- Ambigüedad: protección del directorio de subidas fuera de Apache → `.htaccess`
  más requisito documentado; mensaje de fallo definido → FR-40.
- Ambigüedad: qué listados cubre FR-42 y quién calcula `$can` (conflicto con
  principios 4 y 7) → lista explícita, controlador calcula `$can` → FR-42.
- Ambigüedad: FR-45 mezclaba dos reglas → separado en FR-45 y FR-55.
- Ambigüedad: "equivalentes entre sí" y orden con migraciones pendientes → misma
  estructura aplicando en orden; nuevas desde la 011 después de 006-010 → FR-46,
  FR-47.
- Caso borde: duplicados de `nro_venta` en producción → la migración aborta sin
  tocar datos → FR-56.
- Ambigüedad: FR-49 en local → solo falla con `CI` definida → FR-49.
- Conflicto con el principio 8: tests de lógica de controlador, `Auth`, `Router` y
  vistas → pertenencia del carrito pasa a modelo y se prueba allí; el resto con
  demo manual → convención de verificación, FR-48.
- Conflicto con el principio 3: marcadores abiertos en la spec aprobada → todos
  resueltos, ya no quedan `[NEEDS CLARIFICATION]`.
- Casos borde aceptados sin cambio de requisitos (documentados): sesión
  expirada, dos pestañas, tokens de reset en vuelo, zona horaria del dashboard;
  fuera de alcance: stock negativo existente, imágenes con rutas ya guardadas,
  bloqueo malicioso de cuentas y carrera de rotación del "recordarme".

- Ambigüedad: dónde vive la reserva de `nro_venta` y qué pasa con un carrito vacío
  → tabla `tb_pos_reservas` con id autoincremental (funciona sin filas de carrito y
  en los tres motores); se libera con `fyh_liberacion`, nunca se borra → FR-2.
- Ambigüedad: cómo se resuelve el carrito en curso y contradicción de FR-3 con dos
  pestañas/navegadores → el carrito se resuelve desde la BD (reserva abierta del
  usuario), no desde la sesión; abrir el POS retoma la reserva y se deja de purgar
  carritos de todos los usuarios (`purgeOrphans` borraba los ajenos) → FR-2, FR-3.
- Ambigüedad: identificación de ítems y endpoints → `id_carrito`; no hay endpoints
  AJAX de carrito, son POST de formulario; los métodos de modelo reciben el id del
  usuario → FR-1.
- Contradicción de FR-1 con `view_sales_all` → FR-1 aplica solo al carrito en
  curso; eliminar ventas finalizadas conserva su scoping → FR-1.
- Hueco: `cancel` sobre una venta finalizada no estaba cubierto (borraba líneas y
  podía dar 500) → FR-4 incluye cancelar.
- Ambigüedad: qué ve el usuario ante un reenvío y re-fijación de precios en el
  reenvío (hoy crea una segunda venta con el mismo número) → mismo mensaje y
  redirección, vuelto recalculado, sin re-fijar precios → FR-6.
- Ambigüedad: qué es una venta "finalizada" → existe `tb_ventas.nro_venta`;
  definiciones al inicio de la sección funcional → FR-9.
- Ambigüedad: método de redondeo (float, sin `bcmath`) y suma validada distinta de
  la guardada → céntimos enteros → FR-10.
- Ambigüedad: vuelto en pago mixto (hoy basta con cualquier línea de efectivo;
  total 100, tarjeta 150 y efectivo 1 da vuelto 51) → vuelto = suma − total y debe
  caber en el efectivo → FR-11.
- Caso borde: total negativo o carrito vacío → carrito vacío ya se rechaza y el
  negativo no es alcanzable; se documenta → FR-12.
- Ambigüedad: signo del ajuste de factura y total usado por letras/vuelto → puede
  ser positivo o negativo; letras y vuelto usan `total_pagado` → FR-14.
- Caso borde: remanente 0 con unidades por reponer → monto 0 y stock repuesto →
  FR-16.
- Ambigüedad: dónde se persiste el token de formulario y relación con CSRF (el CSRF
  es único por sesión y no sirve) → columna `tb_devoluciones.token_formulario`
  única, generado al abrir el formulario → FR-17.
- Contradicción FR-19/FR-20/FR-54: el alta con stock inicial no figuraba en la
  lista, el ajuste de stock no puede anidar transacciones y la edición del producto
  pisaba el `id_usuario` → lista ampliada, alta sin transacción anidada, `id_usuario`
  conservado → FR-19, FR-20, FR-54.
- Hueco de concurrencia: ajustes y compras escriben stock absoluto o sin piso →
  `UPDATE` relativo con condición de piso y verificación de filas → FR-21, FR-22.
- Ambigüedad: cambio de producto al editar una compra → se valida el producto
  anterior y el nuevo → FR-22.
- Hueco: eliminar producto con ajustes o líneas de devolución daba error interno →
  `isReferenced` ampliado → FR-24.
- Contradicción de FR-25 con el código y FR-12: cantidad de compra 0 → cantidad de
  compra 1–1.000.000, stock 0–1.000.000; `nro_compra` con índice único contra la
  condición de carrera → FR-25, FR-56.
- Inexactitud: FR-26 citaba eliminación de categorías, que no existe, y no
  enumeraba lo que falta → aclarado → FR-26.
- Ambigüedad: a qué fecha se imputan las devoluciones → `fyh_creacion` de la
  devolución → FR-27.
- Ambigüedad: N de la serie del dashboard y desborde de fin de mes → 6 meses, una
  constante, desde el primer día del mes → FR-29.
- Inexactitud: FR-33 asumía un contador no atómico, pero ya lo es (`login_intentos
= login_intentos + 1`); el hueco real es verificar contraseñas antes de reservar
  el intento → el intento se registra antes de verificar (ver el rediseño en la última
  entrada) → FR-33.
- Ambigüedad: correo normalizado solo al buscar y mayúsculas ya guardadas → se
  normaliza también al escribir → FR-34.
- Hueco: `APP_ENV=local` también mostraba el enlace y el modo depuración revelaba
  si el correo existe; vista `show-reset-link.php` → eliminada → FR-35.
- Ambigüedad: almacenamiento del límite de reset, definición de IP y reloj → tabla
  `tb_intentos_auth`, `REMOTE_ADDR`, reloj de la BD → FR-36.
- Inconsistencia: mínimo de contraseña 8 en restablecimiento, 6 en perfil y en
  `AGENTS.md` → 8 en todos los flujos → FR-31.
- Hueco: sesiones abiertas tras cambiar la contraseña → fuera de alcance
  explícito → FR-32, Out of scope.
- Ambigüedad: alcance de FR-38 frente a rutas con parámetro no numérico → solo
  `{id}` → FR-38.
- Hueco: `$_FILES['error']` sin revisar (`mime_content_type('')` lanza error) y uso
  de `image_text` del cliente → FR-40 los exige explícitos.
- Inexactitud: FR-42 pedía ocultar botones que no existen y el POS ya recibe `$can`
  de forma central → se limita a los botones existentes con `$can` del layout →
  FR-42.
- Inexactitud: FR-43 omitía el enlace roto de `views/returns/show.php` → incluido →
  FR-43.
- Hueco: la compatibilidad con MySQL 8.4 no se agotaba en la FK (`ADD COLUMN IF NOT
EXISTS` y `TEXT` con default literal) → requisito nuevo → FR-57.
- Dependencia: SQLite también rechaza una FK a columna no única; activar claves
  foráneas puede romper seeders mínimos → FR-50 depende de FR-9 y exige ajustar
  seeders.
- Hueco: la migración debe abortar también por compras duplicadas y ventas sin
  líneas → FR-56.
- Conflicto con el principio 2: las tablas/columnas nuevas no estaban declaradas →
  se enumeran en Non-functional, a justificar en el `plan.md`.
- Conflicto con el principio 5: se evita crear métodos nuevos (reserva y token
  dentro de `create()`) → Non-functional.
- Observación: comentarios del código que citan FR de specs anteriores chocan con
  esta numeración y con la regla de no citar specs → limpieza en commit aparte →
  Non-functional.
- Observación: `AGENTS.md` tiene firma de `vueltoFor` y columnas de `tb_usuarios`
  inexactas → se corrigen al cerrar → Completion criteria.
- Aclaración: FR-18 ya serializa con `FOR UPDATE` en MySQL; faltaba el mensaje de
  conflicto ante el choque de la clave temporal y el soporte en SQLite → FR-18.
- Aclaración: la FK de devoluciones ya evita huérfanas en InnoDB; FR-53 se reduce a
  mensaje claro y cobertura en SQLite → FR-53.
- Hueco: el límite de intentos vivía en `tb_usuarios` (solo por cuenta, sin contar
  correos inexistentes ni IP) y el "recordarme" admitía un solo dispositivo →
  tablas `tb_intentos_auth` (login y reset, por correo e IP, orden por `id` ante
  ráfagas) y `tb_sesiones_recordadas` (un token por dispositivo, IP registrada pero
  no exigida) → FR-32, FR-33, FR-36, FR-37, FR-58.
- Cambio (sdd:change): se integran pendientes sueltos → limpieza de comentarios con
  referencias a specs (FR-59), skill `code-review` del proyecto (FR-60) y sincronía
  de `AGENTS.md`/`CLAUDE.md` promovida de criterio de cierre a requisito (FR-61).
  Resetear el bloqueo y la contraseña del vendedor local para la demo del perfil no es
  un requisito del producto: queda como prerrequisito de entorno de la demo.
- Cambio (sdd:change): pedido de no afectar a quien ya usa el proyecto → FR-62.
- Cambio (sdd:change): los tests MariaDB se saltaban por falta de `.env.testing` →
  FR-63 y T61; los seeders rotos por `PRAGMA foreign_keys` y las firmas públicas
  cambiadas (`CartItem`, `Purchase`) se resuelven dentro de T6, T12 y T26 con criterio
  explícito.
  Hallazgos al revisar las migraciones: la conversión de `tb_almacen.imagen` (`TEXT` a
  `VARCHAR(255)`) podía truncar rutas largas; el nombre `tb_ventas_ibfk_2` de la FK no
  es seguro en instalaciones creadas por otra vía; los "recordarme" y bloqueos de
  login activos cambian de formato (un solo reinicio de sesión).
- Cambio (sdd:change): los umbrales de login, reset y "recordarme" dejan de ser
  constantes de código y pasan a `.env` con los mismos valores por defecto, validados
  por rango (fuera de rango → defecto) → FR-64; FR-33, FR-36 y FR-58 pasan a citar el
  valor por defecto. Las reglas de diseño (orden por `id`, `UNIQUE`, reloj de la BD)
  no son configurables.
