# Desarrollo local

> Puesta en marcha, base de datos, `.env` y permisos de archivos. Reglas de código en [AGENTS.md](../../AGENTS.md); despliegue en producción en [despliegue.md](despliegue.md).

## Ejecutar la Aplicación

Proyecto basado en XAMPP — Apache sirve los archivos directamente.

**Instalación de dependencias (primera vez):**

```bash
composer install
cp .env.example .env   # editar con credenciales reales
```

**Linux** — `/opt/lampp/htdocs/Sistema_de_Ventas_PHP/`

```bash
sudo /opt/lampp/lampp start
# o servicios individuales:
sudo /opt/lampp/bin/apachectl start
sudo /opt/lampp/bin/mysql start
```

**Windows** — `C:\xampp\htdocs\Sistema_de_Ventas_PHP\` — usar `xampp-control.exe`
o `C:\xampp\xampp_start.exe` (CMD como administrador).

**macOS** — `/Applications/XAMPP/htdocs/Sistema_de_Ventas_PHP/`

```bash
sudo /Applications/XAMPP/xamppfiles/xampp start
```

**Acceder a la app:** `http://localhost/Sistema_de_Ventas_PHP/public/`

> Ajustar `APP_URL` en `.env` si el nombre del directorio difiere. Debe incluir `/public`.
> El docroot de producción debe apuntar a `public/` — `docs/` y `specs/` no se despliegan.

---

## Configuración de Base de Datos

**Linux/macOS:**

```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < database/schema.sql
mysql -u root -p sistemadeventas < database/seeder.sql
```

**Windows** (desde `C:\xampp\mysql\bin\`): mismos comandos con rutas absolutas a
`database\schema.sql` y `database\seeder.sql`.

Para producción no uses `seeder.sql`: carga `database/seeder_produccion.sql` (solo catálogo, sin usuarios) y
sigue [despliegue.md](despliegue.md).

Usuarios de prueba que crea el seeder de desarrollo:

- `admin@sistema.com` / `admin123`
- `vendedor@sistema.com` / `vendedor123`
- `comprador@sistema.com` / `comprador123`

---

## Configuración (.env)

Ver `.env.example` para el listado completo. Claves relevantes:

- `APP_DEBUG=true` solo escribe el enlace de restablecimiento en el log de errores de PHP
  (`error_log`) además de enviarlo por email; **nunca** se muestra en pantalla. Usar `false` en producción.
- `MAIL_PASSWORD` debe ser una **Contraseña de Aplicación** de Google.
- `APP_CURRENCY_SYMBOL` — símbolo de moneda (default `Bs.`).
- `SESSION_LIFETIME` / `REMEMBER_LIFETIME` — minutos de inactividad (default 60) / días de "recordarme" (default 14).
- `LOGIN_MAX_PER_EMAIL`, `LOGIN_MAX_PER_IP`, `LOGIN_WINDOW_MINUTES`, `RESET_TOKEN_MINUTES`,
  `RESET_COOLDOWN_MINUTES`, `RESET_MAX_PER_IP_HOUR` — umbrales de login y restablecimiento (opcionales; un valor
  fuera de rango se ignora). Detalle y rangos en [autenticacion.md](autenticacion.md).

`public/index.php` carga `.env` vía phpdotenv y expone `BASE_PATH`, `BASE_URL`,
`$pdo`, `$Año`, `$fechaHora`.

---

## Permisos de Archivos

```bash
chmod 755 public/uploads/products/   # directorio de carga de imágenes
```

## Testing (comandos)

```bash
composer test             # todas las suites
composer test:unit        # solo Unit (rápido, ideal pre-commit)
composer test:integration # solo Integration (SQLite in-memory)
composer test:coverage    # con reporte de cobertura (requiere PCOV o Xdebug)
```

> Convenciones de testing (suites, `RefreshDatabase`, sincronización del schema
> SQLite, qué no se testea, tests contra MariaDB real) están en
> [testing.md](testing.md).
