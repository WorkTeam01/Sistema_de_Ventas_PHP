# CLAUDE.md — Guía Local para Claude Code

> Instrucciones operacionales para trabajar con Sistema de Ventas en Claude Code.
>
> Para **arquitectura, convenciones de código, stack tecnológico y prohibiciones explícitas**, ver [AGENT.md](AGENT.md).

---

## Ejecutar la Aplicación

Proyecto basado en XAMPP — Apache sirve los archivos directamente.

**Instalación de dependencias (primera vez):**

```bash
composer install
cp .env.example .env
# Editar .env con las credenciales reales
```

**Linux** — directorio del proyecto: `/opt/lampp/htdocs/Sistema_de_Ventas_PHP/`

```bash
sudo /opt/lampp/lampp start
# o servicios individuales:
sudo /opt/lampp/bin/apachectl start
sudo /opt/lampp/bin/mysql start
```

**Windows** — directorio del proyecto: `C:\xampp\htdocs\Sistema_de_Ventas_PHP\`

```bat
# Usar el panel de control XAMPP (xampp-control.exe) o desde CMD como administrador:
C:\xampp\xampp_start.exe
```

**macOS** — directorio del proyecto: `/Applications/XAMPP/htdocs/Sistema_de_Ventas_PHP/`

```bash
sudo /Applications/XAMPP/xamppfiles/xampp start
# o servicios individuales:
sudo /Applications/XAMPP/xamppfiles/bin/apachectl start
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
```

**Acceder a la app:** `http://localhost/Sistema_de_Ventas_PHP/public/`

> Ajustar `APP_URL` en `.env` si el nombre del directorio difiere. El valor debe incluir `/public`.

---

## Configuración de Base de Datos

**Linux/macOS:**

```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < database/schema.sql
mysql -u root -p sistemadeventas < database/seeder.sql
```

**Windows** (desde `C:\xampp\mysql\bin\`):

```bat
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\schema.sql
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\seeder.sql
```

El seeder crea usuarios de prueba:

- `admin@sistema.com` / `admin123`
- `vendedor@sistema.com` / `vendedor123`
- `comprador@sistema.com` / `comprador123`

---

## Configuración (.env)

```env
DB_HOST=localhost
DB_NAME=sistemadeventas
DB_USER=root
DB_PASS=root
APP_URL=http://localhost/Sistema_de_Ventas_PHP/public
APP_TIMEZONE=America/La_Paz
APP_DEBUG=false
SESSION_LIFETIME=60
REMEMBER_LIFETIME=14
APP_CURRENCY_SYMBOL=Bs.

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=xxxx_xxxx_xxxx_xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="Sistema de Ventas"
```

> `APP_DEBUG=true` activa el modo desarrollo: muestra el link de restablecimiento en pantalla
> en lugar de (solo) enviarlo por email. Usar `false` en producción.
>
> `MAIL_PASSWORD` debe ser una **Contraseña de Aplicación** de Google — no la contraseña de tu cuenta.

`public/index.php` carga `.env` vía phpdotenv y expone:

- `BASE_PATH` — ruta absoluta al directorio raíz
- `BASE_URL` — URL base sin trailing slash (disponible globalmente)
- `$pdo`, `$Año`, `$fechaHora` — compatibilidad

---

## Permisos de Archivos

```bash
chmod 755 public/uploads/products/  # Directorio de carga de imágenes
```

---

## Testing

```bash
composer test             # todas las suites
composer test:unit        # solo Unit (rápido, sin BD — ideal antes de un commit)
composer test:integration # solo Integration (SQLite in-memory)
composer test:coverage    # con reporte de cobertura (requiere PCOV o Xdebug)
```

> Convenciones de testing (suites, `RefreshDatabase`, sincronización del schema SQLite, qué no se testea) están en
> [AGENT.md](AGENT.md#testing) — no se repiten aquí para evitar que se desincronicen.

---

## Archivos de Referencia

| Archivo                      | Propósito                                                                                                       |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------- |
| [AGENT.md](AGENT.md)         | Arquitectura MVC, estructura de directorios, BD, rutas, convenciones de código, stack, prohibiciones explícitas |
| [PROMPTS.md](PROMPTS.md)     | Plantillas de prompts para migración, debugging, code review                                                    |
| [CHANGELOG.md](CHANGELOG.md) | Historial de versiones                                                                                          |

---

_Última actualización: 2026-08-09 — 1.16.0_
