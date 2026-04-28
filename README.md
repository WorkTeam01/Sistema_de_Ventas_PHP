<div align="center">

# Sistema de Ventas — PHP & MySQL

Sistema web de gestión de ventas con control de inventario, facturación en PDF, gestión de clientes/proveedores y
control de acceso por roles.

![Versión](https://img.shields.io/badge/Versión-1.6.1-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![AdminLTE](https://img.shields.io/badge/AdminLTE-3.2.0-3c8dbc)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4-7952B3?logo=bootstrap&logoColor=white)
![Licencia](https://img.shields.io/badge/Licencia-MIT-green)

</div>

---

## Seguridad y Buenas Prácticas Implementadas

Este proyecto implementa una arquitectura MVC con PSR-4. Todos los módulos están completamente migrados. Mantiene los
estándares de seguridad web modernos:

- **Prevención de Inyecciones SQL**: 100% migrado a `PDO Prepared Statements` con _placeholders_ para parametrización.
- **Protección CSRF**: Intercepción de suplantaciones cruzadas mediante _tokens_ obligatorios en la sesión y formularios
  mutables.
- **Escudos XSS**: Renderizado condicionado de entidades HTML (`htmlspecialchars()`) para neutralizar ejecución de
  _scripts_ reflejados/almacenados.
- **Integridad Transaccional**: Operaciones de control de inventario/ventas están bajo control transaccional estricto (
  `PDO::beginTransaction()` / `commit` / `rollBack`), garantizando un stock 100% consistente ante fallas.
- **Validaciones Back-End**: Todo envío por _POST_ recibe depuración estricta en el servidor para forzar cast a valores
  numéricos, tipados seguros y sanitización antes del contacto con la BDD.
- **Encriptado Seguro**: Uso de API moderna de Hashes de contraseñas de PHP (`PASSWORD_DEFAULT` / BCRYPT).
- **Restablecimiento de Contraseña**: Flujo completo con tokens seguros (`bin2hex(random_bytes(32))`), expiración
  de 1 hora, invalidación de un solo uso y envío por email vía PHPMailer + Gmail SMTP.
- **Optimizaciones de UI**: Control Sidebar de AdminLTE implementado de forma 100% nativa con un script dedicado,
  integrando persistencia automatizada en `localStorage` y mecanismos Anti-FOUC para prevenir "flashes" blancos al
  navegar con la temática oscura.

---

## Módulos

| Módulo          | Descripción                                                              |
|-----------------|--------------------------------------------------------------------------|
| **Almacén**     | Gestión de productos con stock, precios, imágenes y categorías           |
| **Ventas**      | POS wizard (Cliente → Carrito → Pago), creación inline de clientes, cálculo de totales y facturas PDF |
| **Compras**     | Registro de compras a proveedores con actualización automática de stock  |
| **Clientes**    | Base de datos de clientes con historial de compras                       |
| **Proveedores** | Gestión de proveedores y datos de contacto                               |
| **Usuarios**    | Administración de cuentas con roles y permisos                           |
| **Perfil**      | Perfil propio para todos los roles: editar datos y cambiar contraseña    |

---

## Requisitos

- PHP 8.x (extensiones: `pdo_mysql`, `gd`, `mbstring`, `json`)
- MySQL 5.7+ / MariaDB 10.4+
- Apache 2.4+ (incluido en XAMPP)

---

## Instalación

### 1. Clonar el repositorio

Colocar el proyecto dentro del directorio `htdocs` de XAMPP:

```bash
# Linux
git clone <url> /opt/lampp/htdocs/Sistema_de_Ventas_PHP

# Windows
git clone <url> C:\xampp\htdocs\Sistema_de_Ventas_PHP

# macOS
git clone <url> /Applications/XAMPP/htdocs/Sistema_de_Ventas_PHP
```

### 2. Instalar dependencias (Composer)

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

#### Credenciales por defecto

El seeder crea los siguientes usuarios de prueba:

| Rol           | Email                 | Contraseña   |
|---------------|-----------------------|--------------|
| Administrador | admin@sistema.com     | admin123     |
| Vendedor      | vendedor@sistema.com  | vendedor123  |
| Comprador     | comprador@sistema.com | comprador123 |

> **Importante:** Cambiar estas contraseñas antes de usar en producción.

### 4. Configurar la conexión

Copiar el archivo de entorno de ejemplo y editarlo con tus credenciales:

```bash
cp .env.example .env
```

Variables mínimas en `.env`:

```dotenv
DB_HOST=localhost
DB_NAME=sistemadeventas
DB_USER=root
DB_PASS=
APP_URL=http://localhost/Sistema_de_Ventas_PHP/public
APP_TIMEZONE=America/La_Paz
APP_DEBUG=false
```

Para habilitar el restablecimiento de contraseña por email, agregar también:

```dotenv
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=xxxx_xxxx_xxxx_xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="Sistema de Ventas"
```

> `APP_URL` debe incluir `/public` — es la ruta al front controller.
> `MAIL_PASSWORD` debe ser una **Contraseña de Aplicación** de Google (no la contraseña de la cuenta).

### 5. Configurar permisos (Linux / macOS)

```bash
chmod 755 public/uploads/products/
```

### 6. Iniciar el servidor

**Linux:**

```bash
sudo /opt/lampp/lampp start
```

**Windows:** Abrir `xampp-control.exe` e iniciar Apache y MySQL.

**macOS:**

```bash
sudo /Applications/XAMPP/xamppfiles/xampp start
```

Acceder en: `http://localhost/Sistema_de_Ventas_PHP/public/`

---

## Control de Acceso por Roles

El sistema cuenta con tres roles. Cada módulo restringe el acceso según el rol del usuario autenticado:

| Rol             | Acceso                                        |
|-----------------|-----------------------------------------------|
| `Administrador` | Acceso completo a todos los módulos           |
| `Vendedor`      | Ventas, clientes y consulta de inventario     |
| `Comprador`     | Compras, proveedores y consulta de inventario |

---

## Stack Tecnológico

**Backend:** PHP con PDO (prepared statements), TCPDF (`tecnickcom/tcpdf`) para generación de facturas PDF, PHPMailer (
`phpmailer/phpmailer`) para envío de emails.

**Frontend:** AdminLTE 3.2.0 sobre Bootstrap 4, jQuery, DataTables, SweetAlert2.

**Base de datos:** MySQL con relaciones entre productos, ventas, compras, clientes y usuarios.

---

## Estructura del Proyecto

```
Sistema_de_Ventas_PHP/
├── app/
│   ├── Controllers/        # Controladores MVC (Auth, Dashboard, User, Role, Category, Supplier, Client, Product, Purchase, Sale)
│   ├── Core/               # Núcleo MVC (Router, Controller, Model, Database, Auth, Config)
│   ├── Helpers/            # Helpers PSR-4 (NumberToWords, InvoicePdf, PurchaseReportPdf)
│   ├── Middleware/         # Middlewares PSR-4 (AuthMiddleware, GuestMiddleware, AdminMiddleware, SellerMiddleware)
│   ├── Models/             # Modelos de dominio (User, Role, Category, Supplier, Client, Product, Purchase, Sale, CartItem)
│   └── Services/           # Servicios PSR-4 (EmailService — PHPMailer SMTP)
├── views/
│   ├── layouts/            # Plantillas compartidas (header, footer, messages) + partials/_sidebar.php
│   ├── errors/             # Páginas de error standalone (404, 403, 500)
│   ├── auth/               # Vistas de autenticación (login, forgot-password, reset-password, show-reset-link)
│   ├── dashboard/          # Vista del dashboard
│   ├── users/              # Vistas CRUD del módulo users
│   ├── roles/              # Módulo roles — patrón modal + AJAX (solo index.php)
│   ├── categories/         # Módulo categories — patrón modal + AJAX (solo index.php)
│   ├── suppliers/          # Vistas CRUD del módulo suppliers + partial/_modals.php
│   ├── clients/            # Vistas CRUD del módulo clients
│   ├── products/           # Vistas CRUD del módulo almacen (index, create, edit, show, delete)
│   ├── purchases/          # Vistas CRUD del módulo compras (index, create, edit, show)
│   └── sales/              # Vistas del módulo ventas (index, create, show, delete, invoice vía TCPDF)
├── routes/
│   └── web.php             # Registro de rutas MVC
├── public/
│   ├── index.php           # Front controller (bootstrap: Dotenv, BASE_URL, BASE_PATH, $pdo)
│   ├── css/
│   │   ├── core/           # Utilitarios globales (ui-components.css)
│   │   └── modules/        # Estilos por módulo (auth/login.css, …)
│   ├── js/
│   │   ├── core/           # Utilitarios globales (sweetalert-utils.js, control_sidebar.js)
│   │   └── modules/        # Scripts por módulo (auth/login.js, users/users-index.js, …)
│   ├── uploads/products/   # Imágenes de productos (producto_default.png trackeado; resto ignorado)
│   └── templates/          # Assets AdminLTE
└── database/
    ├── schema.sql          # Estructura de tablas
    └── seeder.sql          # Datos iniciales
```

---

## 📖 Documentación para Desarrolladores

| Archivo                            | Propósito                                                   |
|------------------------------------|-------------------------------------------------------------|
| [AGENT.md](AGENT.md)               | 🏗️ Arquitectura MVC, stack, convenciones, prohibiciones    |
| [CLAUDE.md](CLAUDE.md)             | 🛠️ Instrucciones operacionales locales (XAMPP, BD, rutas)  |
| [PROMPTS.md](PROMPTS.md)           | 📝 Plantillas de prompts efectivos para agentes IA          |
| [CONTRIBUTING.md](CONTRIBUTING.md) | 🤝 Guía para colaboradores — flujo de PRs, commits, testing |

> **Requisito:** Lee [AGENT.md](AGENT.md) antes de contribuir. Es la fuente de verdad del proyecto.

---

## 🤝 Contribuciones

¿Te gustaría colaborar? ¡Excelente! Sigue estos pasos:

1. **Lee primero** [CONTRIBUTING.md](CONTRIBUTING.md) — contiene todo lo necesario
2. **Abre un issue** describiendo tu propuesta (feature, bug fix, docs)
3. **Fork + Branch:** `git checkout -b feature/nombre-funcionalidad`
4. **Código:** Sigue convenciones de [AGENT.md](AGENT.md)
5. **Commit:** Usa formato convencional → `feat(scope): description`
6. **Push + PR:** Abre pull request con descripción clara

**Código de conducta:** Sé respetuoso. Esperamos comentarios constructivos en las PRs.

---

<div align="center">

## 📄 Licencia

Proyecto de código abierto distribuido bajo la **[Licencia MIT](LICENSE)**.

Eres libre de usar, modificar y distribuir este proyecto con fines educativos y comerciales.

</div>
