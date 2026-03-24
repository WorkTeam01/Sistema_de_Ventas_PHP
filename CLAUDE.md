# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Descripción del Proyecto

Sistema de Ventas es un sistema de gestión de ventas en PHP/MySQL con control de inventario, facturación, gestión de clientes y acceso por roles. La interfaz usa AdminLTE 3.2.0 (Bootstrap 4).

## Ejecutar la Aplicación

Proyecto basado en XAMPP. No hay proceso de compilación — Apache sirve los archivos directamente.

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

**Acceder a la app:** `http://localhost/Sistema_de_Ventas_PHP/`

> Ajustar `$URL` en `app/config.php` si el nombre del directorio difiere.

**Configuración de base de datos (primera vez):**

Linux/macOS:

```bash
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < database/schema.sql
mysql -u root -p sistemadeventas < database/seeder.sql
```

El seeder crea usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`, `comprador@sistema.com` / `comprador123`.

Windows (desde CMD en `C:\xampp\mysql\bin\`):

```bat
mysql -u root -p -e "CREATE DATABASE sistemadeventas;"
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\schema.sql
mysql -u root -p sistemadeventas < C:\xampp\htdocs\Sistema_de_Ventas_PHP\database\seeders\roles.sql
```

## Configuración

Toda la configuración está en [app/config.php](app/config.php):

- Credenciales de base de datos: `SERVIDOR`, `USUARIO`, `PASSWORD`, `BD`
- URL base: `$URL = 'http://localhost/Sistema_de_Ventas_PHP'` — debe coincidir con la ruta real del servidor
- Zona horaria: `America/La_Paz`

No hay archivo `.env` — las credenciales están en `app/config.php`.

## Arquitectura

### Enrutamiento

El ruteo URL→archivo es **implícito** — las URLs mapean directamente a rutas de archivos PHP:

- `GET /almacen/` → `almacen/index.php`
- `POST /app/controllers/login/ingreso.php` → procesa el formulario de login

No hay router ni controlador frontal. Cada página es un archivo PHP independiente.

### Patrón MVC simplificado

- **Vistas**: Directorios de módulos en la raíz (`almacen/`, `ventas/`, `compras/`, etc.)
- **Controladores**: Lógica de negocio en `app/controllers/[modulo]/` — son incluidos/requeridos por las vistas
- **Sin capa de modelos**: Las consultas SQL se escriben directamente en los controladores usando PDO

### Autenticación y Autorización

Cada página protegida debe instanciar `AuthMiddleware` desde [app/controllers/middleware/AuthMiddleware.php](app/controllers/middleware/AuthMiddleware.php):

```php
require_once('../app/config.php');
require_once('../app/controllers/middleware/AuthMiddleware.php');

$auth = new AuthMiddleware($pdo, $URL);
// Un solo rol:
$usuario = $auth->verificarPermiso('Administrador');
// Múltiples roles:
$usuario = $auth->verificarRoles(['Administrador', 'Vendedor']);
```

Roles disponibles (almacenados en `tb_roles`): `Administrador`, `Vendedor`, `Comprador`.

### Sistema de Layout

Cada página incluye plantillas compartidas:

- [layout/sesion.php](layout/sesion.php) — valida que exista sesión activa, redirige al login si no
- [layout/parte1.php](layout/parte1.php) — head HTML, navbar, sidebar
- [layout/parte2.php](layout/parte2.php) — scripts de cierre, footer

### Acceso a Base de Datos

La conexión PDO se inicializa en `app/config.php` y está disponible como `$pdo`. Todas las consultas deben usar **sentencias preparadas** con placeholders `?` — nunca interpolar variables directamente en el string SQL:

```php
// CORRECTO
$query = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = ?");
$query->execute([$email]);

// INCORRECTO — vulnerable a SQL injection
$query = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = '$email'");
$query->execute();
```

### Generación de PDF

Las facturas de ventas usan TCPDF en [app/TCPDF-main/](app/TCPDF-main/). El controlador de factura está en [app/controllers/ventas/factura_venta.php](app/controllers/ventas/factura_venta.php).

## Estructura de Módulos

Cada módulo sigue el mismo patrón CRUD:

```
[modulo]/
├── index.php      # Vista de listado (DataTables)
├── create.php     # Formulario de creación
├── update.php     # Formulario de edición
├── delete.php     # Manejador de eliminación
└── show.php       # Vista de detalle (algunos módulos)

app/controllers/[modulo]/
├── listado_de_[modulo].php   # Obtener datos del listado
├── registro_de_[modulo].php  # Lógica de INSERT
├── actualizar_[modulo].php   # Lógica de UPDATE
└── eliminar_[modulo].php     # Lógica de DELETE
```

## Tablas Principales de Base de Datos

| Tabla            | Propósito                                                   |
| ---------------- | ----------------------------------------------------------- |
| `tb_almacen`     | Productos/inventario con stock, precios e imágenes          |
| `tb_ventas`      | Encabezados de venta (vinculados a `tb_carrito` para ítems) |
| `tb_carrito`     | Ítems de venta (producto y cantidad por venta)              |
| `tb_compras`     | Registros de compras a proveedores                          |
| `tb_clientes`    | Base de datos de clientes                                   |
| `tb_proveedores` | Base de datos de proveedores                                |
| `tb_usuarios`    | Usuarios con contraseñas hasheadas y FK de rol              |
| `tb_roles`       | Definiciones de roles                                       |
| `tb_categorias`  | Categorías de productos                                     |

El stock se actualiza automáticamente al crear/eliminar ventas y al registrar compras.

Convenciones de columnas de auditoría:
- `fyh_creacion` — `DEFAULT CURRENT_TIMESTAMP`; no insertar manualmente en los controllers
- `fyh_actualizacion` — queda `NULL` al crear; se actualiza sola con `ON UPDATE CURRENT_TIMESTAMP`
- Los precios se almacenan como `DECIMAL(10,2)`, no como VARCHAR

## Convenciones de Seguridad

- **SQL**: Siempre usar placeholders `?` con `execute([$var])`. Nunca interpolar variables en el string SQL.
- **Subida de archivos**: Validar extensión (whitelist: jpg, jpeg, png, webp), MIME type real con `mime_content_type()` y tamaño máximo (2MB) antes de `move_uploaded_file()`.
- **JavaScript**: Usar `json_encode()` para pasar strings PHP a variables JS — nunca interpolación directa con comillas simples.
- **Output HTML**: Usar `htmlspecialchars()` al mostrar datos de usuario en HTML para prevenir XSS.

## Convenciones de Frontend

- **DataTables** se inicializa en cada página de listado para búsqueda, ordenamiento y exportación (PDF/Excel/CSV/Imprimir)
- **SweetAlert2** se usa para todas las confirmaciones de eliminación y alertas de éxito/error
- **jQuery** es requerido y se carga vía la plantilla AdminLTE
- CSS personalizado en [public/css/](public/css/), JS personalizado en [public/js/](public/js/)
- Assets de AdminLTE servidos desde [public/templates/](public/templates/) — no modificar estos archivos

## Permisos de Archivos

```bash
chmod 755 almacen/img_productos/   # Directorio de carga de imágenes de productos
chmod 644 app/config.php
```
