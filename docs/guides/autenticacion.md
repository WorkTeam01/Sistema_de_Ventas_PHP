# Autenticación, permisos y sesión

> Detalle de RBAC, sesión, "recordarme", restablecimiento y rate limiting. Las reglas operativas resumidas viven en [AGENTS.md](../../AGENTS.md).

```php
// En módulos MVC (via middleware en routes/web.php):
$router->get('/ruta', [Controller::class, 'method'], ['auth']);                    // cualquier rol autenticado
$router->get('/ruta', [Controller::class, 'method'], ['auth', 'can:permiso']);     // requiere permiso específico

// Datos del usuario en sesión:
Auth::user()            // array con datos del usuario
Auth::role()            // nombre del rol
Auth::check()           // bool — revalida sesión, timeout de inactividad y que la cuenta/rol sigan vigentes en BD
Auth::can('permiso')    // bool — verifica si el usuario tiene el permiso; usa caché en $_SESSION['permisos']
Auth::isAdmin()         // bool — alias de Auth::can('is_superadmin')
Auth::refreshPermissions() // recarga permisos desde BD (llamar tras cambiar rol o permisos)
Auth::login($user, $remember) // inicia sesión; $remember=true emite cookie de 14 días
Auth::loginWithCookie() // auto-login desde cookie remember_token; rota el token
Auth::logout()          // limpia sesión, BD y cookie
```

**Permisos granulares (RBAC):**

- Los permisos se almacenan en `tb_permisos` y se asignan a roles en `tb_rol_permiso`. Se gestionan solo a nivel de
  rol — no hay asignación individual por usuario.
- Al hacer login, `Auth::loadPermissions()` carga todos los slugs de permisos del rol en `$_SESSION['permisos']`.
- `PermissionMiddleware` resuelve el prefijo `can:` en rutas — redirige a `/errors/403` si el permiso falta.
- En controladores, usar `Auth::can('permiso')` para scoping de datos o restricciones inline.
- En vistas, el controlador pasa `$can` (array) con los permisos necesarios via `renderWithLayout()` — nunca llamar `Auth::` directamente en vistas.
- Slugs de permisos en uso: `view_dashboard`, `manage_users`, `manage_roles`, `view_categories`, `manage_categories`, `view_suppliers`, `manage_suppliers`, `view_clients`, `manage_clients`, `view_products`, `manage_products`, `view_purchases`, `manage_purchases`, `view_sales`, `manage_sales`, `manage_returns`, `view_sales_all`, `view_purchases_all`, `view_activity_log`, `manage_inventory`, `manage_payment_methods`, `view_reports`, `view_sales_report`, `view_purchases_report`, `view_top_products_report`, `view_clients_report`, `is_superadmin`.
- **Scoping de datos por usuario (`*_all`):** `view_sales_all` y `view_purchases_all` distinguen "ver todos los
  registros" de "ver solo los propios". Sin el permiso `_all`, los métodos de listado/agregado filtran por
  `id_usuario` (ver `Sale`/`Purchase`/`Product::getTopSelling()`, que aceptan `?int $userId` opcional). Patrón usado
  en `DashboardController` (KPIs y gráficos), `SaleController::index/show/destroy` y
  `PurchaseController::index/show/edit/update/report/destroy` (todos con el chequeo
  `!Auth::can('view_*_all') && (int)$registro['id_usuario'] !== (int)Auth::user()['id_usuario']` antes de
  mostrar/modificar). Solo Administrador tiene ambos `_all` por defecto — un rol nuevo sin ellos automáticamente ve
  y gestiona solo sus propios registros, sin tocar código. **Al agregar un módulo con este patrón, aplicar el
  filtro tanto al listado (`index`) como al detalle/edición/borrado (`show`/`edit`/`update`/`destroy`) — filtrar
  solo el listado deja abierto el acceso a registros ajenos por URL directa.**
- **`Controller::forbidden(string $mensaje)`:** helper para el chequeo de dueño de arriba — hace flash + redirect a
  `/errors/403` (mismo mecanismo de toast que `PermissionMiddleware` usa para rutas sin permiso). Usarlo en vez de
  `flash()` + `redirect()` al listado, así "este registro no es tuyo" se ve igual que "no tienes acceso a esta
  ruta" en toda la app.
- **Portada de reportes (`/reports`):** cada bloque del resumen se carga y se muestra con su propio permiso
  (`view_sales_report`, `view_purchases_report`, `view_clients_report`, `view_top_products_report`), no con
  `view_reports`; el margen solo se calcula si ventas y compras cubren el mismo alcance (`*_all`).
- **El sidebar (`views/layouts/partials/_sidebar.php`) debe gatear cada enlace con su permiso real vía `$can[...]`,
  nunca con los proxies de rol `$isAdmin`/`$isSeller`/`$isBuyer`.** Esos proxies solo aproximan "tiene view_sales" /
  "tiene view_purchases" — usarlos para secciones con permisos más granulares (ej. Reportes, gateado antes por
  `$isSeller` en vez de `$can['view_reports']`) hace que el menú muestre enlaces a los que el usuario en realidad no
  tiene acceso (la ruta responde 403, pero la UI confunde). Está bien usarlos para las secciones cuyo único gate es
  precisamente `view_sales`/`view_purchases` (Ventas, Compras, Inventario).
- **Gestión vía UI:** catálogo de permisos en `/permissions` (alta con clave/descripción/módulo; la `clave` es **inmutable** tras crearla — los guards de escalada y las rutas `can:` la usan como identificador, renombrarla reasignaría su efecto; `PermissionController`,
  `views/permissions/`) y asignación por rol en `/roles/permisos/{id}` (checkboxes agrupados por `modulo`,
  `RoleController::permisos()`/`syncPermisos()`, `views/roles/permisos.php`). Ambas rutas reutilizan el permiso
  `manage_roles` — no se sembró un slug nuevo.
- **Invalidación de caché por versión:** `tb_roles.permisos_version` se incrementa en cada `Role::syncPermissions()`.
  `Auth::check()` compara la versión en sesión contra la de BD (una query barata por request) y llama
  `refreshPermissions()` si difiere — así los usuarios activos del rol ven el cambio en su siguiente request, sin
  re-login. El propio admin que edita su rol activo se refresca de inmediato vía `Auth::refreshPermissions()` en
  `syncPermisos()`.
- **Guard anti escalada de privilegios:** poder crear/editar usuarios (`manage_users`) o asignar permisos a roles
  (`manage_roles`) **no** incluye poder otorgarse `is_superadmin`. `UserController::blocksSuperadminEscalation()` y
  `Role::grantsSuperadmin()` + el guard de `RoleController::syncPermisos()` exigen `Auth::isAdmin()` cuando el rol
  destino es superusuario o cuando el usuario/rol afectado ya lo es. Usar este helper en cualquier flujo que asigne
  roles o permisos — gatear el CRUD no gatea la escalada. Además, `Role::isLastSuperadminRole()` impide quitar
  `is_superadmin` al único rol que lo otorga: los guards anteriores impedirían volver a concedérselo desde la UI.

**Sesión y CSRF:**

- La cookie de sesión es `HttpOnly`, `SameSite=Lax` y `Secure` cuando la petición es HTTPS (también tras un
  proxy que envía `X-Forwarded-Proto: https`). `Auth::login()` regenera el id de sesión y el token CSRF.

**Timeout de sesión:**

- Controlado por `SESSION_LIFETIME` en `.env` (minutos de inactividad). Default: `60`.
- `Auth::check()` compara `$_SESSION['last_activity']` con `time()`; si expiró destruye solo la sesión de PHP (no revoca el
  dispositivo "recordarme", que `AuthMiddleware` reabre con la cookie). `Auth::startSession()` sube `session.gc_maxlifetime` para
  que el gc de PHP no borre la sesión antes del timeout.
- `$_SESSION['pw_fp']` guarda la huella SHA-256 del hash de contraseña; `Auth::check()` cierra la sesión si ya no coincide, así
  cambiar o restablecer la contraseña cierra las demás sesiones abiertas. Quien cambia su propia contraseña llama a
  `Auth::syncPasswordFingerprint()` para conservar la suya.

**Umbrales de autenticación (`.env`, leídos por `App\Helpers\AuthSettings`):** un valor ausente, no entero o
fuera de rango se ignora y se usa el default. Los 7 ajustables:

| Variable                 | Default | Rango  | Significado                                         |
| ------------------------ | ------- | ------ | --------------------------------------------------- |
| `LOGIN_MAX_PER_EMAIL`    | 5       | 1–50   | intentos de login por correo dentro de la ventana   |
| `LOGIN_MAX_PER_IP`       | 20      | 1–500  | intentos de login por IP dentro de la ventana       |
| `LOGIN_WINDOW_MINUTES`   | 15      | 1–1440 | ventana de conteo de intentos                       |
| `RESET_TOKEN_MINUTES`    | 60      | 5–1440 | vigencia del enlace de restablecimiento             |
| `RESET_COOLDOWN_MINUTES` | 5       | 1–60   | espera entre solicitudes de reset del mismo usuario |
| `RESET_MAX_PER_IP_HOUR`  | 5       | 1–100  | solicitudes de reset por IP y hora                  |
| `REMEMBER_LIFETIME`      | 14      | 1–90   | días de vigencia de "recordarme"                    |

**"Recordarme":**

- Cookie `remember_token` = `"{selector}:{validador}"` — httponly, samesite=Strict, vigencia `REMEMBER_LIFETIME` días.
- Una fila por dispositivo en `tb_sesiones_recordadas`; la BD guarda solo `hash('sha256', $validador)`.
- `AuthMiddleware` intenta `Auth::loginWithCookie()` antes de redirigir a `/auth`.
- `RememberToken::consume()` rota el validador solo de esa fila; un token inválido no modifica ninguna fila y
  únicamente borra la cookie del cliente. Un cambio de IP se registra en el log de actividad sin rechazar el acceso.
- `logout()` revoca solo el dispositivo actual. Cambiar una contraseña (`User::updatePassword()`, `updateUser()` con
  contraseña, restablecimiento) llama a `RememberToken::revokeAllFor()` y cierra todos los dispositivos.
- Las columnas `remember_token*` de `tb_usuarios` están obsoletas y sin uso.

**Flujo de restablecimiento de contraseña:**

- Token: `bin2hex(random_bytes(32))`; en `tb_usuarios.reset_token` se guarda su hash SHA-256 (`User::storeResetToken()` /
  `findByResetToken()` / `clearResetToken()`), con vigencia `RESET_TOKEN_MINUTES`.
- **El enlace nunca se muestra en pantalla**, ni con `APP_DEBUG=true` ni con `APP_ENV=local`. Con `APP_DEBUG=true`
  solo se escribe en `error_log` (para desarrollo sin SMTP); la vista `show-reset-link.php` ya no existe.
- La respuesta es la misma exista o no el correo. El envío SMTP se hace después de cerrar la respuesta
  (`Controller::redirectThenRun()`), para que el tiempo de respuesta no delate si el correo está registrado.
- Límites con `AuthAttempt::resetThrottled()`: espera `RESET_COOLDOWN_MINUTES` por usuario y `RESET_MAX_PER_IP_HOUR`
  por IP; las solicitudes sobre el límite se ignoran en silencio.
- `App\Services\EmailService::sendResetLink()` — PHPMailer + Gmail SMTP; escapa los datos en el HTML del correo y
  el plazo mostrado sale de `AuthSettings::resetTokenMinutes()`.
- Cambiar la propia contraseña desde el perfil exige la contraseña actual (`User::verifyCurrentPassword()`);
  un administrador cambia la de otro usuario sin pedirla.
- Cambiar el **correo** desde el perfil también exige la contraseña actual (campo `password_current_email`, visible
  solo si el correo cambia): una sesión robada no basta para apropiarse de la cuenta. No existe un endpoint para
  consultar si un correo ya está registrado; la unicidad se valida al guardar.
- `reset_token` se anula al cambiar la contraseña (`updatePassword()`, `updateUser()` con contraseña) y al cambiar
  el correo (`updateProfileInfo()`, `updateUser()`): un enlace enviado al correo anterior deja de valer.
- `UserController::update()` no deja cambiar el rol del único usuario cuyo rol concede `is_superadmin`
  (`User::isLastSuperadminUser()`); `Role::isLastSuperadminRole()` protege el lado de los roles.

**Rate limiting en login:**

- `AuthController::store()` registra cada intento en `tb_intentos_auth` **antes** de verificar nada
  (`AuthAttempt::register()`) y cuenta solo los intentos con id menor o igual al propio (`isBlocked()`): en una
  ráfaga paralela solo los primeros por id llegan a verificar la contraseña. El reloj es el de la BD.
- Más de `LOGIN_MAX_PER_EMAIL` intentos por correo o `LOGIN_MAX_PER_IP` por IP dentro de `LOGIN_WINDOW_MINUTES`
  bloquean sin verificar la contraseña. Un correo inexistente cuenta igual.
- Correo inexistente, contraseña incorrecta y bloqueo muestran el mismo mensaje, con salida a «¿Olvidaste tu
  contraseña?»; para los correos inexistentes se verifica contra un hash ficticio (`User::passwordMatches()`).
- Un login correcto borra los intentos de ese correo (`AuthAttempt::clear()`).
- **Limitación conocida:** quien conozca un correo puede bloquear su login durante la ventana enviando intentos
  fallidos; la salida del usuario legítimo es «¿Olvidaste tu contraseña?». Se aceptó a cambio de no filtrar qué
  correos existen.
- Las columnas `login_intentos` y `login_bloqueado_hasta` están obsoletas y sin uso.

**Toasts en vistas de auth:**

- Las vistas `login.php`, `forgot-password.php` y `reset-password.php` **no** contienen `<script>showToast()</script>`
  inline.
- `AuthController` llama a `$this->view(..., withMessages: true)` — `Controller::view()` incluye `messages.php` al
  final, que consume `$_SESSION['mensaje']` / `$_SESSION['icono']` y dispara `ToastUtils` desde JS.
