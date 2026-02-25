<div align="center">

# Sistema de Ventas — PHP & MySQL

Sistema web de gestión de ventas con control de inventario, facturación en PDF, gestión de clientes/proveedores y control de acceso por roles.

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![AdminLTE](https://img.shields.io/badge/AdminLTE-3.2.0-3c8dbc)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4-7952B3?logo=bootstrap&logoColor=white)
![Licencia](https://img.shields.io/badge/Licencia-MIT-green)

</div>

---

## Módulos

| Módulo | Descripción |
|--------|-------------|
| **Almacén** | Gestión de productos con stock, precios, imágenes y categorías |
| **Ventas** | Carrito de compras, cálculo de totales y generación de facturas PDF |
| **Compras** | Registro de compras a proveedores con actualización automática de stock |
| **Clientes** | Base de datos de clientes con historial de compras |
| **Proveedores** | Gestión de proveedores y datos de contacto |
| **Usuarios** | Administración de cuentas con roles y permisos |
| **Reportes** | Dashboard por rol con exportación a PDF, Excel y CSV |

---

## Requisitos

- PHP 7.4 o superior (extensiones: `pdo_mysql`, `gd`, `mbstring`, `json`)
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

### 2. Crear e importar la base de datos

**Linux / macOS:**
```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < sistemadeventas.sql
```

**Windows** (desde `C:\xampp\mysql\bin\`):
```bat
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\sistemadeventas.sql
```

### 3. Configurar la conexión

Editar [app/config.php](app/config.php):

```php
define('SERVIDOR', 'localhost');
define('USUARIO', 'root');
define('PASSWORD', '');         // Contraseña de MySQL
define('BD', 'sistemadeventas');

$URL = 'http://localhost/Sistema_de_Ventas_PHP'; // Ajustar al nombre del directorio
```

### 4. Configurar permisos (Linux / macOS)

```bash
chmod 755 almacen/img_productos/
chmod 644 app/config.php
```

### 5. Iniciar el servidor

**Linux:**
```bash
sudo /opt/lampp/lamppstart
```

**Windows:** Abrir `xampp-control.exe` e iniciar Apache y MySQL.

**macOS:**
```bash
sudo /Applications/XAMPP/xamppfiles/xampp start
```

Acceder en: `http://localhost/Sistema_de_Ventas_PHP/`

---

## Control de Acceso por Roles

El sistema cuenta con tres roles. Cada módulo restringe el acceso según el rol del usuario autenticado:

| Rol | Acceso |
|-----|--------|
| `Administrador` | Acceso completo a todos los módulos |
| `Vendedor` | Ventas, clientes y consulta de inventario |
| `Comprador` | Compras, proveedores y consulta de inventario |

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
│   ├── config.php          # Conexión PDO y configuración global
│   ├── controllers/        # Lógica de negocio por módulo
│   │   └── middleware/     # AuthMiddleware (autenticación y roles)
│   └── TCPDF-main/         # Librería de generación de PDF
├── layout/                 # Plantillas compartidas (header, footer, sesión)
├── public/
│   ├── css/                # Estilos personalizados
│   ├── js/                 # Scripts personalizados
│   └── templates/          # AdminLTE (no modificar)
├── [modulo]/               # Vista de cada módulo (almacen, ventas, etc.)
├── sistemadeventas.sql     # Esquema e datos iniciales de la base de datos
└── index.php               # Dashboard principal
```

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
