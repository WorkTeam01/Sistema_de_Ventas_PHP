# 🛍️ Sistema de Ventas - PHP & MySQL

Un sistema completo de gestión de ventas desarrollado con PHP, MySQL y AdminLTE, que incluye control de inventario, facturación, gestión de clientes y reportes.

## 🚀 Características Principales

### 📦 Módulos del Sistema
- **Almacén**: Gestión completa de productos e inventario
- **Ventas**: Proceso de ventas con carrito y facturación PDF
- **Compras**: Registro y control de compras a proveedores  
- **Clientes**: Base de datos de clientes con historial
- **Proveedores**: Gestión de proveedores y contactos
- **Usuarios**: Sistema de usuarios con roles y permisos
- **Reportes**: Dashboards y reportes en tiempo real

### 🎨 Interfaz de Usuario
- Diseño responsivo con **AdminLTE 3.2.0**
- Tema oscuro/claro personalizable
- Panel de control lateral con opciones de personalización
- Alertas y notificaciones con **SweetAlert2**

### 🛡️ Seguridad y Control de Acceso
- Sistema de autenticación con middleware
- Control de roles y permisos por módulo
- Validación de datos y protección contra inyección SQL
- Sesiones seguras y logout automático

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **Framework CSS**: Bootstrap 4 (AdminLTE)
- **Librerías**: 
  - TCPDF (generación de PDFs)
  - SweetAlert2 (notificaciones)
  - DataTables (tablas interactivas)

## 📋 Requisitos del Sistema

- **Servidor Web**: Apache 2.4+ o Nginx
- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Extensiones PHP requeridas**:
  - mysqli
  - pdo_mysql
  - gd
  - mbstring
  - json

## ⚙️ Instalación

### 1. Clonar el repositorio
```bash
git clone [URL-del-repositorio]
cd SistemaVentas
```

### 2. Configurar la base de datos
- Crear una base de datos MySQL
- Importar el archivo `sistemadeventas.sql`
- Configurar credenciales en `app/config.php`

### 3. Configurar permisos
```bash
chmod 755 almacen/img_productos/
chmod 644 app/config.php
```

### 4. Acceder al sistema
- URL: `http://localhost/SistemaVentas/`
- Usuario por defecto: (consultar base de datos)

## 📁 Estructura del Proyecto

```
SistemaVentas/
├── almacen/           # Gestión de productos
├── app/               # Configuración y librerías
│   ├── config.php     # Configuración de BD
│   ├── controllers/   # Controladores por módulo
│   └── TCPDF-main/    # Librería PDF
├── clientes/          # Módulo de clientes
├── compras/           # Módulo de compras
├── layout/            # Plantillas y layouts
├── proveedores/       # Gestión de proveedores
├── public/            # Recursos estáticos
│   ├── css/           # Estilos personalizados
│   ├── js/            # Scripts JavaScript
│   └── templates/     # AdminLTE
├── usuarios/          # Gestión de usuarios
└── ventas/            # Módulo de ventas
```

## 🔧 Configuración

### Base de Datos
Editar `app/config.php`:
```php
$servidor = "localhost";
$usuario = "tu_usuario";
$password = "tu_password";
$bd = "sistemadeventas";
```

### Personalización
El sistema incluye un panel de control lateral (control-sidebar) que permite:
- Cambiar entre tema claro/oscuro
- Personalizar colores de la navbar
- Configurar opciones del sidebar
- Ajustar estilos de texto

## 📊 Funcionalidades Detalladas

### Sistema de Ventas
- Carrito de compras interactivo
- Cálculo automático de totales e impuestos
- Generación de facturas en PDF
- Control de stock en tiempo real
- Historial de ventas completo

### Gestión de Inventario
- Registro de productos con imágenes
- Categorización y etiquetado
- Control de stock mínimo
- Alertas de inventario bajo
- Reportes de movimientos

### Sistema de Reportes
- Dashboard con métricas clave
- Reportes de ventas por período
- Análisis de productos más vendidos
- Reportes financieros
- Exportación a PDF/Excel

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abrir un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte y consultas:
- Abrir un issue en GitHub
- Revisar la documentación en `/docs`

## 🙏 Agradecimientos

Proyecto basado en los tutoriales del canal de YouTube **Hilari Web**.
