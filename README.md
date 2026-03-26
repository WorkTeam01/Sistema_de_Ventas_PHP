<div align="center">

# Sistema de Ventas — PHP & MySQL

Sistema web de gestión de ventas con control de inventario, facturación en PDF, gestión de clientes/proveedores y control de acceso por roles.

![Versión](https://img.shields.io/badge/Versión-v1.1.0--wip-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![AdminLTE](https://img.shields.io/badge/AdminLTE-3.2.0-3c8dbc)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4-7952B3?logo=bootstrap&logoColor=white)
![Licencia](https://img.shields.io/badge/Licencia-MIT-green)

</div>

---

## Seguridad y Buenas Prácticas Implementadas

Este proyecto está siendo migrado progresivamente a una arquitectura MVC con PSR-4. Los módulos `auth`, `users` y `dashboard` ya están completamente migrados; el resto migra incrementalmente. A pesar de la transición, mantiene los estándares de seguridad web modernos:

- **Prevención de Inyecciones SQL**: 100% migrado a `PDO Prepared Statements` con _placeholders_ para parametrización.
- **Protección CSRF**: Intercepción de suplantaciones cruzadas mediante _tokens_ obligatorios en la sesión y formularios mutables.
- **Escudos XSS**: Renderizado condicionado de entidades HTML (`htmlspecialchars()`) para neutralizar ejecución de _scripts_ reflejados/almacenados.
- **Integridad Transaccional**: Operaciones de control de inventario/ventas están bajo control transaccional estricto (`PDO::beginTransaction()` / `commit` / `rollBack`), garantizando un stock 100% consistente ante fallas.
- **Validaciones Back-End**: Todo envío por _POST_ recibe depuración estricta en el servidor para forzar cast a valores numéricos, tipados seguros y sanitización antes del contacto con la BDD.
- **Encriptado Seguro**: Uso de API moderna de Hashes de contraseñas de PHP (`PASSWORD_DEFAULT` / BCRYPT).
- **Optimizaciones de UI**: Control Sidebar de AdminLTE implementado de forma 100% nativa con un script dedicado, integrando persistencia automatizada en `localStorage` y mecanismos Anti-FOUC para prevenir "flashes" blancos al navegar con la temática oscura.

---

## Módulos

| Módulo          | Descripción                                                             |
| --------------- | ----------------------------------------------------------------------- |
| **Almacén**     | Gestión de productos con stock, precios, imágenes y categorías          |
| **Ventas**      | Carrito de compras, cálculo de totales y generación de facturas PDF     |
| **Compras**     | Registro de compras a proveedores con actualización automática de stock |
| **Clientes**    | Base de datos de clientes con historial de compras                      |
| **Proveedores** | Gestión de proveedores y datos de contacto                              |
| **Usuarios**    | Administración de cuentas con roles y permisos                          |
| **Reportes**    | Dashboard por rol con exportación a PDF, Excel y CSV                    |

---

## Requisitos

- PHP 7.4 o superior (extensiones: `pdo_mysql`, `gd`, `mbstring`, `json`)
- MySQL 5.7+ / MariaDB 10.4+
- Apache 2.4+ (incluido en XAMPP)

---

## Instalación

### Dependencias (Composer)

```bash
composer install
```

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

### 2. Crear e importar la base de datos

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

### Credenciales por defecto

El seeder crea los siguientes usuarios de prueba:

| Rol           | Email                 | Contraseña   |
| ------------- | --------------------- | ------------ |
| Administrador | admin@sistema.com     | admin123     |
| Vendedor      | vendedor@sistema.com  | vendedor123  |
| Comprador     | comprador@sistema.com | comprador123 |

> **Importante:** Cambiar estas contraseñas antes de usar en producción.

### 3. Configurar la conexión

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
```

> `APP_URL` debe incluir `/public` — es la ruta al front controller.

### 4. Configurar permisos (Linux / macOS)

```bash
chmod 755 almacen/img_productos/
chmod 644 app/config.php
```

### 5. Iniciar el servidor

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
| --------------- | --------------------------------------------- |
| `Administrador` | Acceso completo a todos los módulos           |
| `Vendedor`      | Ventas, clientes y consulta de inventario     |
| `Comprador`     | Compras, proveedores y consulta de inventario |

---

## Stack Tecnológico

**Backend:** PHP con PDO (prepared statements), TCPDF para generación de facturas.

**Frontend:** AdminLTE 3.2.0 sobre Bootstrap 4, jQuery, DataTables, SweetAlert2.

**Base de datos:** MySQL con relaciones entre productos, ventas, compras, clientes y usuarios.

---

## Estructura del Proyecto

```
Sistema_de_Ventas_PHP/
├── app/
│   ├── config.php          # Bootstrap: Dotenv, BASE_URL, $pdo, $URL, $Año
│   ├── Controllers/        # Controladores MVC (AuthController, UserController, DashboardController)
│   ├── Core/               # Núcleo MVC (Router, Controller, Model, Database, Auth, Config)
│   ├── Middleware/         # Middlewares PSR-4 (AuthMiddleware, GuestMiddleware, AdminMiddleware)
│   ├── Models/             # Modelos de dominio (User, ...)
│   ├── controllers/        # Legacy procedural (módulos pendientes de migración)
│   └── TCPDF-main/         # Librería de generación de PDF
├── views/
│   ├── layout/             # Plantillas compartidas (parte1, parte2, mensajes, sesion)
│   ├── auth/               # Vista de login
│   ├── dashboard/          # Vista del dashboard
│   └── users/              # Vistas CRUD del módulo users
├── routes/
│   └── web.php             # Registro de rutas MVC
├── public/
│   ├── index.php           # Front controller
│   ├── css/                # Estilos personalizados
│   ├── js/                 # Scripts personalizados
│   └── templates/          # Assets AdminLTE
├── [modulo]/               # Módulos legacy pendientes de migración (almacen, ventas, etc.)
└── database/
    ├── schema.sql          # Estructura de tablas
    └── seeder.sql          # Datos iniciales
```

---

## Estado de Migración MVC

El proyecto mantiene un esquema híbrido mientras avanza la migración incremental:

| Módulo | Estado |
|---|---|
| `auth` (login/logout) | ✅ Migrado |
| `users` (usuarios) | ✅ Migrado |
| `dashboard` | ✅ Migrado |
| `roles` | 🔄 Pendiente |
| `categorias` | 🔄 Pendiente |
| `proveedores` | 🔄 Pendiente |
| `clientes` | 🔄 Pendiente |
| `almacen` | 🔄 Pendiente |
| `compras` | 🔄 Pendiente |
| `ventas` | 🔄 Pendiente |

**Núcleo MVC disponible:**
- `public/index.php` — front controller único; `.htaccess` redirige todo al Router
- `routes/web.php` — registro de rutas con middleware
- `App\Core\{Router, Controller, Model, Database, Auth, Config}` — clases base
- `App\Middleware\{AuthMiddleware, GuestMiddleware, AdminMiddleware}` — guards de ruta
- `views/layout/` — plantillas compartidas (`parte1`, `parte2`, `mensajes`, `sesion`)
- `BASE_URL` — constante global definida en `app/config.php`

---

## Contribuciones

1. Crear una rama: `git checkout -b feature/nombre-funcionalidad`
2. Realizar los cambios y hacer commit: `git commit -m 'Descripción del cambio'`
3. Push a la rama: `git push origin feature/nombre-funcionalidad`
4. Abrir un Pull Request

---

<div align="center">

## Créditos

Proyecto basado en los tutoriales del canal de YouTube **[Hilari Web](https://www.youtube.com/@hilariweb)**.

---

Distribuido bajo la [Licencia MIT](LICENSE).

</div>
