# Plan: Restablecimiento de Contraseña

> Generado con claude-opus-4-6 — 2026-04-10
> Basado en la Plantilla 1 (Generar código nuevo) de PROMPTS.md
> Portado desde sistema-hielo-cambita

---

## Prompt utilizado

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo activo: auth (autenticación — login y restablecimiento de contraseña)

Estructura de archivos relevante:
- app/Controllers/AuthController.php
- app/Core/Auth.php (manejo de sesión, CSRF)
- app/Models/User.php (modelo de usuarios)
- views/auth/login.php
- public/js/modules/auth/login.js
- routes/web.php
- database/schema.sql

Estado actual del módulo auth:
- Login funcional con CSRF, validación jQuery Validate, password_verify()
- Auth::check(), login(), logout(), generateCsrfToken(), validateCsrfToken()
- tb_usuarios tiene: id_usuario, nombres, email, password_user, token (NULL, sin usar), id_rol, fyh_creacion, fyh_actualizacion
- NO existe restablecimiento de contraseña
- NO existe EmailService ni PHPMailer en composer.json
- Solo views/auth/login.php y public/js/modules/auth/login.js

Referencia tomada de sistema-hielo-cambita (mismo stack, mismo autor):
- PasswordResetController con métodos: requestReset(), sendResetLink(), showResetForm($token), resetPassword()
- EmailService usando PHPMailer + Gmail SMTP con App Password, modo dev muestra link en pantalla
- Token: bin2hex(random_bytes(32)), expiración 1 hora
- Columnas en usuario: reset_token VARCHAR(255) NULL, reset_token_expiracion DATETIME NULL
- Vistas: forgot-password.php, reset-password.php, show-reset-link.php (modo dev)
- JS: forgot-password.js (jQuery Validate), reset-password.js (Validate + strength meter)
- Middleware GuestMiddleware protege rutas /forgot-password y /reset-password
- Modo desarrollo detectado via APP_DEBUG=true o APP_ENV=local en .env

Convenciones del proyecto Sistema_de_Ventas_PHP:
- Tablas: tb_usuarios, tb_roles, etc. (prefijo tb_)
- Columnas: fyh_creacion, fyh_actualizacion (no created_at/updated_at)
- PK: id_usuario (no id)
- password_user (no password)
- nombres (no nombre)
- AlertUtils / ToastUtils para notificaciones
- CSRF token obligatorio en formularios POST
- Validación frontend (jQuery) Y backend (PHP)
- Nunca concatenar variables en SQL — usar placeholders ? con execute([$var])
- Namespace: App\Controllers, App\Models, App\Core, App\Middleware
- Rutas auth actuales: GET /auth, POST /auth/login, GET /auth/logout

[Tarea]
Implementar el flujo completo de restablecimiento de contraseña (forgot password + reset password)
para Sistema_de_Ventas_PHP, portando el sistema probado de sistema-hielo-cambita y adaptando las
convenciones de nomenclatura del proyecto (tb_usuarios, id_usuario, password_user, nombres, fyh_* timestamps).

Criterios de aceptación:
- Enlace "¿Olvidaste tu contraseña?" en la vista de login
- Token seguro (bin2hex(random_bytes(32))), guardado en BD con expiración de 1 hora
- Modo dev (APP_DEBUG=true): muestra link en pantalla
- Modo prod: envía email via PHPMailer + Gmail SMTP
- Formulario con campo nueva contraseña + confirmar + indicador de fortaleza
- Validación de token, actualiza password_hash(), limpia el token
- Redirige a /auth con mensaje de éxito
- CSRF token en todos los formularios POST
- AlertUtils / ToastUtils para notificaciones en JS

[Restricciones]
- Adaptarse a convenciones: tb_usuarios, id_usuario, password_user, nombres, fyh_*
- Rutas nuevas en /auth/... para consistencia
- EmailService en app/Services/EmailService.php
- PHPMailer via composer require phpmailer/phpmailer
- No modificar el flujo de login existente
- No introducir librerías más allá de PHPMailer
- AlertUtils / ToastUtils — nunca Swal.fire() ni alert() directamente
- CSRF obligatorio en POST
- Nunca concatenar variables en SQL

[Formato de salida]
Plan de implementación con: cambios BD, composer, .env, archivos, rutas, pasos en orden, checklist testing.
```

---

## Plan de implementación

### 1. Cambios en base de datos

```sql
ALTER TABLE tb_usuarios
  DROP COLUMN token,
  ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN reset_token_expiracion DATETIME NULL DEFAULT NULL;
```

Actualizar también `database/schema.sql` para reflejar la estructura final.

---

### 2. Instalación de dependencia

```bash
composer require phpmailer/phpmailer
```

---

### 3. Variables .env a agregar

```env
APP_DEBUG=true
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=xxxx_xxxx_xxxx_xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="Sistema de Ventas"
```

Agregar también a `.env.example` (sin valores reales).

---

### 4. Archivos a crear o modificar

| Acción    | Archivo                                         |
|-----------|-------------------------------------------------|
| Modificar | `database/schema.sql`                           |
| Modificar | `.env.example`                                  |
| Modificar | `app/Models/User.php`                           |
| Modificar | `app/Controllers/AuthController.php`            |
| Modificar | `routes/web.php`                                |
| Modificar | `views/auth/login.php`                          |
| Crear     | `app/Services/EmailService.php`                 |
| Crear     | `views/auth/forgot-password.php`                |
| Crear     | `views/auth/reset-password.php`                 |
| Crear     | `views/auth/show-reset-link.php`                |
| Crear     | `public/js/modules/auth/forgot-password.js`     |
| Crear     | `public/js/modules/auth/reset-password.js`      |
| Crear     | `public/css/modules/auth/reset-password.css`    |

---

### 5. Descripción por archivo

**`app/Models/User.php`** — Agregar métodos:
- `findByEmail(string $email): ?array` — Busca usuario por email
- `storeResetToken(int $id_usuario, string $token, string $expiracion): bool` — Guarda reset_token y reset_token_expiracion
- `findByResetToken(string $token): ?array` — Busca usuario por reset_token donde reset_token_expiracion > NOW()
- `updatePassword(int $id_usuario, string $hashedPassword): bool` — Actualiza password_user
- `clearResetToken(int $id_usuario): bool` — Pone reset_token y reset_token_expiracion a NULL

**`app/Services/EmailService.php`** — Namespace `App\Services`:
- `__construct()` — Configura PHPMailer con variables de entorno MAIL_*
- `sendResetLink(string $email, string $nombres, string $resetUrl): bool` — Envía email HTML con enlace. Retorna true/false. Errores via error_log() silencioso.

**`app/Controllers/AuthController.php`** — Agregar 4 métodos (no tocar los existentes):
- `forgotPassword()` — GET: muestra vista forgot-password.php
- `sendResetLink()` — POST: valida CSRF + email, genera token `bin2hex(random_bytes(32))`, expiracion `date('Y-m-d H:i:s', strtotime('+1 hour'))`, guarda via User::storeResetToken(). Si APP_DEBUG=true → vista show-reset-link. Si no → EmailService::sendResetLink(). Redirige con mensaje flash genérico.
- `showResetForm(string $token)` — GET: valida token via User::findByResetToken(). Si inválido/expirado → /auth con error. Si válido → vista reset-password.php.
- `resetPassword()` — POST: valida CSRF, token, password (min 8), confirmación. password_hash($password, PASSWORD_DEFAULT). User::updatePassword(). User::clearResetToken(). Redirige a /auth con éxito.

**`views/auth/forgot-password.php`** — Card centrada estilo login:
- Campo email + botón enviar
- CSRF hidden
- Enlace "Volver al login" → /auth

**`views/auth/reset-password.php`** — Card centrada estilo login:
- Token en hidden input
- CSRF hidden
- Campo nueva contraseña + visibilidad toggle
- Campo confirmar contraseña + visibilidad toggle
- Barra indicadora de fortaleza (4 niveles)
- Enlace "Volver al login" → /auth

**`views/auth/show-reset-link.php`** — Solo modo dev:
- Input readonly con el link de restablecimiento
- Botón copiar al portapapeles
- Aviso "Solo visible en modo desarrollo (APP_DEBUG=true)"
- Enlace de vuelta al login

**`public/js/modules/auth/forgot-password.js`** — jQuery Validate:
- email: required + email format
- Submit con spinner en botón
- ToastUtils para flash messages

**`public/js/modules/auth/reset-password.js`** — jQuery Validate + strength meter:
- password: required, minlength: 8
- password_confirm: required, equalTo: '#password'
- Strength meter en keyup: evalúa longitud + mayúsculas + números + especiales → actualiza barra CSS
- Visibilidad toggle en ambos campos

**`public/css/modules/auth/reset-password.css`**:
- Estilos barra de fortaleza: 4 segmentos, colores danger/warning/info/success

---

### 6. Rutas a agregar en `routes/web.php`

```php
// Password Reset
$router->get('/auth/forgot-password', 'AuthController@forgotPassword', ['guest']);
$router->post('/auth/forgot-password', 'AuthController@sendResetLink', ['guest']);
$router->get('/auth/reset-password/{token}', 'AuthController@showResetForm', ['guest']);
$router->post('/auth/reset-password', 'AuthController@resetPassword', ['guest']);
```

---

### 7. Pasos de implementación en orden

1. `composer require phpmailer/phpmailer`
2. Ejecutar ALTER TABLE en BD de desarrollo
3. Actualizar `database/schema.sql`
4. Agregar variables MAIL_* y APP_DEBUG a `.env` y `.env.example`
5. Agregar 5 métodos a `app/Models/User.php`
6. Crear `app/Services/EmailService.php`
7. Agregar 4 métodos a `app/Controllers/AuthController.php`
8. Agregar 4 rutas en `routes/web.php`
9. Crear `views/auth/forgot-password.php`
10. Crear `views/auth/reset-password.php`
11. Crear `views/auth/show-reset-link.php`
12. Crear `public/js/modules/auth/forgot-password.js`
13. Crear `public/js/modules/auth/reset-password.js`
14. Crear `public/css/modules/auth/reset-password.css`
15. Agregar enlace "¿Olvidaste tu contraseña?" en `views/auth/login.php`

---

### 8. Checklist de testing manual

- [ ] **Flujo feliz (modo dev)**: Login → "¿Olvidaste tu contraseña?" → email válido → link en pantalla → nueva contraseña → éxito → login con nueva contraseña
- [ ] **Email inexistente**: Muestra mensaje genérico "Si el email existe, recibirás un enlace" (no revelar existencia)
- [ ] **Token expirado**: Modificar reset_token_expiracion a fecha pasada → acceder al link → error "El enlace ha expirado"
- [ ] **Token inválido**: Acceder a `/auth/reset-password/tokeninvalido` → redirige con error
- [ ] **Token usado**: Tras resetear, intentar el mismo link → error (token ya limpiado)
- [ ] **Múltiples solicitudes**: Dos solicitudes seguidas → segundo token reemplaza al primero, el primer link no funciona
- [ ] **Validación frontend**: Campos vacíos, password < 8 chars, passwords no coinciden → jQuery Validate muestra errores inline
- [ ] **Validación backend**: POST directo sin JS con datos inválidos → PHP rechaza con error
- [ ] **CSRF**: POST sin token CSRF → rechazado
- [ ] **Indicador fortaleza**: Escribir password → barra cambia de color según complejidad
- [ ] **GuestMiddleware**: Estando logueado, acceder a /auth/forgot-password → redirige a / (dashboard)
- [ ] **Modo producción** (APP_DEBUG=false): No muestra link, intenta enviar email via SMTP