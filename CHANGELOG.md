# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

## [1.0.0] - 2026-03-24

### Agregado
- `database/schema.sql` con la estructura de todas las tablas
- `database/seeder.sql` con datos iniciales para todas las tablas: roles, categorías, proveedores, clientes, usuarios, productos, compras y ventas de ejemplo
- Usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`, `comprador@sistema.com` / `comprador123`
- `UNIQUE KEY` en `tb_usuarios.email` para evitar emails duplicados
- `UNIQUE KEY` en `tb_almacen.codigo` para evitar códigos de producto duplicados
- `app/config.example.php` como plantilla de configuración sin credenciales
- **Protección CSRF**: Generación global de token y validación estricta para todos los endpoints POST de mutación.
- **Validación Fuerte del Servidor**: Filtros integrados (`is_numeric`, `filter_var`) en controladores críticos de usuarios, almacén y ventas.

### Cambiado
- **Refactorización de Verbos HTTP**: Las mutaciones lógicas como la creación de ventas ahora requieren estrictamente `POST` mitigando vulnerabilidades.
- **Transacciones PDO Íntegras**: El carrito y la cabecera de la venta se procesan bajo un único bloque `$pdo->beginTransaction()` garantizando consistencia del stock.
- `fyh_creacion` ahora tiene `DEFAULT CURRENT_TIMESTAMP` en todas las tablas — ya no es necesario insertarla manualmente
- `fyh_actualizacion` ahora es `DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP` en todas las tablas — queda NULL al crear y se actualiza automáticamente al modificar
- `tb_almacen.precio_compra` y `tb_almacen.precio_venta`: `VARCHAR(255)` → `DECIMAL(10,2)`
- `tb_compras.precio_compra`: `VARCHAR(50)` → `DECIMAL(10,2)`
- `tb_ventas.total_pagado`: `INT(11)` → `DECIMAL(10,2)` (corrige pérdida de decimales)
- `tb_usuarios.token`: `NOT NULL` → `DEFAULT NULL`
- `tb_proveedores.email`: `VARCHAR(50)` → `VARCHAR(254)` (estándar RFC 5321)
- `AUTO_INCREMENT` reseteado a 1 en todas las tablas
- `app/config.php` excluido del repositorio vía `.gitignore`

### Corregido
- Advertencias en consola del navegador reparadas al forzar `autocomplete="off"` en los formularios de `login/index.php`.
- Estandarización de `layout/mensajes.php` para usar Toasts globales de SweetAlert2 en lugar del modal intrusivo.
- Campos de contraseña corregidos de `type="text"` a `type="password"` en `usuarios/create.php` y `usuarios/update.php` — la contraseña ya no se muestra en texto plano
- `htmlspecialchars()` aplicado en todas las vistas donde se muestran datos de usuarios/BD en HTML: `almacen/index.php`, `almacen/show.php`, `almacen/update.php`, `ventas/index.php`, `compras/index.php`, `proveedores/index.php` y `usuarios/update.php` — previene XSS almacenado
- Confirmaciones SweetAlert2 agregadas antes de eliminar registros en `almacen/index.php`, `ventas/index.php`, `compras/index.php` y `usuarios/index.php` — los botones de eliminar ya no navegan directamente sin confirmación
- Typo `lamppstart` → `lampp start` en README.md y CLAUDE.md
- URL de ejemplo incorrecta en CLAUDE.md (`sistemaventas` → `Sistema_de_Ventas_PHP`)
- SQL injection en `layout/sesion.php`, `app/controllers/login/ingreso.php`, `app/controllers/roles/update_roles.php`, `app/controllers/clientes/cargar_cliente.php`, `ventas/factura.php`, `ventas/show.php`, `ventas/index.php`, `ventas/delete.php` y `ventas/create.php` — se reemplazó interpolación directa de variables en SQL por placeholders `?` con `execute([$var])`
- Subida de imágenes sin validación en `app/controllers/almacen/create.php` y `update.php` — se agregó whitelist de extensiones (jpg, jpeg, png, webp), validación de MIME type real y límite de 2MB
- Variables PHP interpoladas directamente en bloques JavaScript en `ventas/create.php` — se reemplazó por `json_encode()` para prevenir errores con caracteres especiales
- **Control Sidebar:** Funcionalidad original manual reemplazada por el archivo `public/js/control_sidebar.js` 100% nativo de la API de AdminLTE, traducido al español, con guardado persistente en `localStorage`.
- **FOUC (Flash of Unstyled Content):** Parpadeos visuales al navegar con temas oscuros prevenidos mediante una pequeña inyección JS al inicio del `<body>` en `layout/parte1.php`.
- Reposicionado de la barra lateral de configuración a `position: fixed` para evitar pérdida visual al hacer scroll excesivo.
