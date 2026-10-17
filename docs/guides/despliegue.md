# Despliegue en producción

Guía para publicar el sistema fuera de XAMPP. Instalación local: [README](../../README.md).

> **Verificado:** `schema.sql` + `seeder_produccion.sql` en una BD vacía, y el ruteo de Apache con el docroot
> en `public/` y el sitio en la raíz del dominio. No se ha probado en un servidor real: la primera vez,
> valida cada paso y corrige esta guía.

## 1. Dónde alojarlo

| Opción                                             | Cuándo                                                               | Notas                                                        |
| -------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------ |
| **VPS Linux** (Ubuntu + Nginx + PHP-FPM + MariaDB) | Recomendada: control total, HTTPS con Let's Encrypt, backups propios | Pide más administración                                      |
| **Hosting compartido** (Hostinger, cPanel)         | El más simple y barato; Apache con `.htaccess`                       | Comprueba que puedes apuntar el docroot a `public/` (ver §3) |

Requisitos: PHP 8.2 o superior (CI prueba 8.2 y 8.3) con `pdo_mysql`, `gd`, `mbstring`, `json`;
MySQL 5.7+ / MariaDB 10.4+ (CI usa 10.11).

## 2. Qué subir

Solo el artefacto de producción, sin `docs/`, `specs/`, `tests/`, `.github/` ni los `.md` de desarrollo
(`.gitattributes` ya los marca con `export-ignore`):

```bash
git archive --format=tar.gz -o sistema.tar.gz 1.19.3   # tag sin prefijo v
```

En el servidor: `composer install --no-dev --optimize-autoloader`. No subas `vendor/` de desarrollo ni `.env`.

## 3. Docroot = `public/`

El servidor web debe servir **solo** `public/`. Fuera de él quedan `.env`, `app/`, `database/` y `vendor/`;
si el docroot es la raíz del proyecto, se exponen las credenciales.

- **VPS:** `root /var/www/sistema/public;` (ver §6).
- **Hosting compartido:** cambia el document root del dominio a `.../public` desde el panel. Si el panel no
  lo permite, deja el proyecto fuera de `public_html` y haz que `public_html` apunte a `public/` (enlace
  simbólico, si el proveedor lo permite). No copies el proyecto entero dentro de `public_html`.

`public/.htaccess` no fija `RewriteBase`, así que funciona igual en la raíz del dominio (producción) que en
un subdirectorio (XAMPP). En ese caso `APP_URL` debe llevar la ruta (`.../Sistema_de_Ventas_PHP/public`).

## 4. Base de datos

```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas CHARACTER SET utf8mb4;"
mysql -u root -p sistemadeventas < database/schema.sql
```

Crea un usuario de MySQL propio, con contraseña fuerte y permisos solo sobre esa BD (no uses `root`).

`schema.sql` solo crea las tablas. Carga después el catálogo que la aplicación necesita (3 roles, 27 permisos,
permisos por rol y 4 métodos de pago), **sin datos demo ni usuarios**:

```bash
mysql -u usuario_propio -p sistemadeventas < database/seeder_produccion.sql
```

No uses `database/seeder.sql` en producción: es para desarrollo y crea usuarios con contraseñas conocidas.

**Primer administrador** (la BD queda sin usuarios). Genera el hash sin dejar la contraseña en el historial
del shell y crea el usuario:

```bash
php -r 'echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), "\n";'   # escribe la contraseña y Enter
```

```sql
INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
VALUES ('Tu nombre', 'tu@correo.com', '<hash>',
        (SELECT id_rol FROM tb_roles WHERE rol = 'Administrador'));
```

Usa un correo en minúsculas y una contraseña de al menos 8 caracteres. Luego crea desde la app los
usuarios de Vendedor y Comprador.

**Migraciones:** una instalación nueva con `schema.sql` ya las incluye; no las ejecutes. Al actualizar,
aplica en orden solo las posteriores a tu versión (lista y reglas en el README, § Actualización). La `015`
se ejecuta una sola vez.

## 5. `.env` de producción

Cópialo de `.env.example` en el servidor; nunca va al repositorio.

```dotenv
DB_HOST=localhost
DB_NAME=sistemadeventas
DB_USER=usuario_propio
DB_PASS=contraseña_fuerte
APP_URL=https://tudominio.com        # sin /public si el docroot ya es public/
APP_TIMEZONE=America/La_Paz
APP_VERSION=1.19.3                    # súbela en cada despliegue: invalida la caché de CSS/JS
APP_DEBUG=false
```

- `APP_DEBUG=false` siempre. Con `true` el enlace de restablecimiento se escribe en el log de PHP.
- Correo (restablecer contraseña): `MAIL_*` con una **Contraseña de Aplicación** de Google.
- `chmod 640 .env` y propietario el usuario del servidor web.
- **Zona horaria:** `Database` fija `time_zone` de la sesión MySQL al desplazamiento de `APP_TIMEZONE`. En una
  BD nueva no hay nada que hacer. Si migras una BD con datos guardados en otra zona, esas filas se verán
  desplazadas y habría que convertirlas (`CONVERT_TZ`) antes de abrir.

## 6. Nginx (VPS)

Nginx ignora `.htaccess`, así que hay que replicar sus reglas, sobre todo impedir PHP en `uploads/`:

```nginx
server {
    listen 443 ssl http2;
    server_name tudominio.com;
    root /var/www/sistema/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    # Subidas: solo imágenes, jamás código
    location ^~ /uploads/ {
        location ~ \.(php[0-9]?|phtml|phar|pht|phps)$ { return 403; }
        autoindex off;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known) { deny all; }
}
server { listen 80; server_name tudominio.com; return 301 https://$host$request_uri; }
```

Con HTTPS directo la cookie de sesión sale con `Secure` automáticamente. Tras un proxy o balanceador, este
debe enviar `X-Forwarded-Proto: https`.

## 7. Permisos de archivos

- Código de solo lectura para el usuario web; escritura únicamente en `public/uploads/products/` (`755`).
- Comprueba que `public/uploads/products/.htaccess` llegó al servidor (está versionado).

## 8. Checklist antes de abrir

- [ ] `https://tudominio.com/.env` y `/app/…` responden 404/403 (docroot correcto)
- [ ] Un `.php` subido a `uploads/products/` no se ejecuta
- [ ] Cargado `seeder_produccion.sql` (no `seeder.sql`) y primer administrador creado
- [ ] `APP_DEBUG=false`, HTTPS activo, `APP_URL` con `https://`
- [ ] Login, venta de prueba, factura PDF y envío de correo de restablecimiento funcionan
- [ ] Primer backup hecho y **restauración probada**

## 9. Operación

- **Backups:** `mysqldump --single-transaction sistemadeventas | gzip` a diario (cron) con copia fuera del
  servidor, y copia de `public/uploads/`. Sin backup, un borrado es permanente.
- **Actualizar:** backup → subir el nuevo artefacto → `composer install --no-dev` → migraciones pendientes →
  subir `APP_VERSION` en `.env` → comprobar login y una venta.
- **Logs:** revisa el `error_log` de PHP y el log de actividad de la app (`/activity-log`).
- **Deploy por SSH:** el runner sube los archivos por `rsync`/`scp`; el servidor no hace `git pull` ni guarda
  credenciales del repositorio.
