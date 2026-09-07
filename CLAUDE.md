@AGENTS.md

# CLAUDE.md — Guía Local para Claude Code

> `AGENTS.md` (importado arriba) es la fuente única de arquitectura, convenciones,
> stack, BD, rutas, testing y prohibiciones. Aquí va **solo** lo operativo de
> Claude Code y el arranque local que no aplica a otros agentes.

---

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

Usuarios de prueba que crea el seeder:

- `admin@sistema.com` / `admin123`
- `vendedor@sistema.com` / `vendedor123`
- `comprador@sistema.com` / `comprador123`

---

## Configuración (.env)

Ver `.env.example` para el listado completo. Claves relevantes:

- `APP_DEBUG=true` activa modo desarrollo: muestra el link de restablecimiento en
  pantalla además de enviarlo por email. Usar `false` en producción.
- `MAIL_PASSWORD` debe ser una **Contraseña de Aplicación** de Google.
- `APP_CURRENCY_SYMBOL` — símbolo de moneda (default `Bs.`).
- `SESSION_LIFETIME` / `REMEMBER_LIFETIME` — minutos de inactividad / días de "recordarme".

`public/index.php` carga `.env` vía phpdotenv y expone `BASE_PATH`, `BASE_URL`,
`$pdo`, `$Año`, `$fechaHora`.

---

## Permisos de Archivos

```bash
chmod 755 public/uploads/products/   # directorio de carga de imágenes
```

---

## Testing (comandos)

```bash
composer test             # todas las suites
composer test:unit        # solo Unit (rápido, ideal pre-commit)
composer test:integration # solo Integration (SQLite in-memory)
composer test:coverage    # con reporte de cobertura (requiere PCOV o Xdebug)
```

> Convenciones de testing (suites, `RefreshDatabase`, sincronización del schema
> SQLite, qué no se testea, tests contra MariaDB real) están en
> [AGENTS.md](AGENTS.md#testing).

---

## Skills del proyecto

- `git-commit` (`.claude/skills/`) — usar **siempre** para commitear; nunca `git commit` directo.
  Commits atómicos por categoría lógica.
- `code-review` (`.claude/skills/`) — revisión antes de merge. ⚠️ Su checklist
  todavía arrastra referencias de otro repo (`SistemaReservasHospital`, ramas
  `feature/rfXX`, `develop`); adaptarlo a este proyecto es tarea pendiente.

## Flujo SDD

Features nuevas siguen Spec-Driven Development (`/sdd:constitution`, `/sdd:spec`,
`/sdd:clarify`, `/sdd:plan`, `/sdd:tasks`, `/sdd:implement <n>`, `/sdd:validate`,
`/sdd:change`). Artefactos en `docs/constitution.md`, `docs/roadmap.md` y
`specs/NNN-<slug>/`. Cuándo aplica y cuándo no: ver
[AGENTS.md § Planificación de features](AGENTS.md).

---

_Última actualización: 2026-09-07_
