<div align="center">

# Sistema de Ventas — PHP & MySQL

Sistema web de gestión de ventas para pequeñas y medianas empresas. Cubre el ciclo completo: compras a proveedores, control de inventario, punto de venta con facturación PDF y reportes por período.

![Versión](https://img.shields.io/badge/Versión-1.15.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![AdminLTE](https://img.shields.io/badge/AdminLTE-3.2.0-3c8dbc)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4-7952B3?logo=bootstrap&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-11.x-6C6EAA?logo=php&logoColor=white)
![Tests](https://github.com/WorkTeam01/Sistema_de_Ventas_PHP/actions/workflows/tests.yml/badge.svg)
![Licencia](https://img.shields.io/badge/Licencia-MIT-green)

</div>

---

![Dashboard](docs/screenshot-dashboard.png)

---

## Características

| Módulo          | Descripción                                                                                                       |
| --------------- | ----------------------------------------------------------------------------------------------------------------- |
| **Almacén**     | Gestión de productos con stock, precios, imágenes y categorías                                                    |
| **Ventas**      | POS wizard (Cliente → Carrito → Pago), creación inline de clientes y facturas PDF                                 |
| **Compras**     | Registro de compras a proveedores con actualización automática de stock                                           |
| **Inventario**  | Alertas de stock bajo, barras de progreso y ajustes manuales con historial                                        |
| **Reportes**    | Ventas, compras, top productos y clientes por período; export PDF / CSV / Excel                                   |
| **Auditoría**   | Registro de operaciones sensibles (creación, edición, eliminación, login, exportaciones y más) con KPIs y filtros |
| **Clientes**    | Base de datos de clientes con historial de compras                                                                |
| **Proveedores** | Gestión de proveedores y datos de contacto                                                                        |
| **Usuarios**    | Administración de cuentas con roles y permisos granulares                                                         |
| **Permisos**    | Catálogo de permisos y asignación por rol (RBAC) desde la UI, sin editar el seeder                                |
| **Perfil**      | Edición de datos y cambio de contraseña para cualquier rol                                                        |

---

## Stack Tecnológico

| Capa              | Tecnología                                                                     |
| ----------------- | ------------------------------------------------------------------------------ |
| **Backend**       | PHP 8.x — MVC custom con PSR-4 vía Composer (sin framework)                    |
| **Base de datos** | MySQL 5.7+ / MariaDB 10.4+ con PDO y prepared statements                       |
| **Frontend**      | AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2                   |
| **PDF**           | TCPDF (`tecnickcom/tcpdf`) — facturas y reportes                               |
| **Email**         | PHPMailer (`phpmailer/phpmailer`) — SMTP Gmail con App Password                |
| **Testing**       | PHPUnit 11.x — suites Unit e Integration (SQLite in-memory); CI GitHub Actions |

---

## Requisitos

- PHP 8.x con extensiones `pdo_mysql`, `gd`, `mbstring`, `json`
- MySQL 5.7+ / MariaDB 10.4+
- Apache 2.4+ (incluido en XAMPP)
- Composer

---

## Instalación

### 1. Clonar el repositorio

```bash
# Linux
git clone <url> /opt/lampp/htdocs/Sistema_de_Ventas_PHP

# Windows
git clone <url> C:\xampp\htdocs\Sistema_de_Ventas_PHP

# macOS
git clone <url> /Applications/XAMPP/htdocs/Sistema_de_Ventas_PHP
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Crear e importar la base de datos

**Linux / macOS:**

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

El seeder crea los siguientes usuarios de prueba:

| Rol           | Email                 | Contraseña   |
| ------------- | --------------------- | ------------ |
| Administrador | admin@sistema.com     | admin123     |
| Vendedor      | vendedor@sistema.com  | vendedor123  |
| Comprador     | comprador@sistema.com | comprador123 |

> Cambiar estas contraseñas antes de usar en producción.

### 4. Configurar variables de entorno

```bash
cp .env.example .env
```

Variables mínimas:

```dotenv
DB_HOST=localhost
DB_NAME=sistemadeventas
DB_USER=root
DB_PASS=
APP_URL=http://localhost/Sistema_de_Ventas_PHP/public
APP_TIMEZONE=America/La_Paz
APP_DEBUG=false
SESSION_LIFETIME=60
REMEMBER_LIFETIME=14
```

Para habilitar el restablecimiento de contraseña por email:

```dotenv
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=xxxx_xxxx_xxxx_xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="Sistema de Ventas"
```

> `APP_URL` debe incluir `/public`. `MAIL_PASSWORD` debe ser una **Contraseña de Aplicación** de Google, no la contraseña de la cuenta.

### 5. Permisos de directorio (Linux / macOS)

```bash
chmod 755 public/uploads/products/
```

### 6. Iniciar el servidor

**Linux:** `sudo /opt/lampp/lampp start`

**Windows:** Abrir `xampp-control.exe` e iniciar Apache y MySQL.

**macOS:** `sudo /Applications/XAMPP/xamppfiles/xampp start`

Acceder en: `http://localhost/Sistema_de_Ventas_PHP/public/`

---

## Control de Acceso

El sistema usa RBAC granular: cada ruta declara el permiso que requiere y el acceso se evalúa en tiempo de ejecución, sin comparaciones de nombre de rol hardcodeadas. Los permisos se administran desde la UI (`/permissions` para el catálogo, `/roles/permisos/{id}` para asignarlos a un rol) — los cambios se aplican a los usuarios activos de ese rol en su siguiente request, sin necesidad de re-login.

| Rol             | Acceso                                                     |
| --------------- | ---------------------------------------------------------- |
| `Administrador` | Acceso completo a todos los módulos                        |
| `Vendedor`      | Ventas, clientes, productos (lectura) y reportes de ventas |
| `Comprador`     | Compras, proveedores, productos (lectura) y categorías     |

---

## Seguridad

- **SQL Injection** — 100% PDO con prepared statements; enteros interpolados con cast `(int)` explícito
- **CSRF** — token obligatorio en todos los formularios POST y endpoints AJAX
- **XSS** — `htmlspecialchars()` en todos los outputs HTML
- **Contraseñas** — `password_hash()` / `password_verify()` (BCRYPT); mínimo 6 caracteres en todos los flujos
- **Stock negativo** — decremento con `AND stock >= ?` dentro de transacción; rollback si `rowCount() === 0`
- **Totales** — calculados server-side desde la BD dentro de la transacción; el valor del POST se ignora
- **Rate limiting** — 5 intentos fallidos bloquean la cuenta 15 minutos
- **"Recordarme"** — cookie httponly/samesite=Strict con token SHA-256 rotado en cada auto-login
- **Timeout de sesión** — expiración por inactividad configurable (default: 60 minutos)
- **Restablecimiento de contraseña** — token `bin2hex(random_bytes(32))`, expiración 1 hora, un solo uso

---

## Testing

```bash
composer test             # todas las suites
composer test:unit        # Unit — lógica pura, sin BD (rápido, ideal pre-commit)
composer test:integration # Integration — SQLite in-memory
composer test:coverage    # con reporte de cobertura (requiere PCOV)
```

CI con GitHub Actions en PHP 8.2 y 8.3. Ver [CLAUDE.md](CLAUDE.md#testing) para convenciones de testing.

---

## Documentación para Desarrolladores

| Archivo                            | Propósito                                                      |
| ---------------------------------- | -------------------------------------------------------------- |
| [AGENT.md](AGENT.md)               | Arquitectura MVC, stack, convenciones de código, prohibiciones |
| [CLAUDE.md](CLAUDE.md)             | Instrucciones operacionales locales (XAMPP, BD, rutas)         |
| [PROMPTS.md](PROMPTS.md)           | Plantillas de prompts para agentes IA                          |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Flujo de contribución: PRs, commits, testing                   |
| [CHANGELOG.md](CHANGELOG.md)       | Historial de versiones                                         |

> Lee [AGENT.md](AGENT.md) antes de contribuir — es la fuente de verdad del proyecto.

---

## Contribuciones

Lee [CONTRIBUTING.md](CONTRIBUTING.md) para el flujo completo. En resumen: abre un issue → fork → rama → código siguiendo [AGENT.md](AGENT.md) → PR con descripción clara.

---

<div align="center">

Distribuido bajo la **[Licencia MIT](LICENSE)**.

</div>
