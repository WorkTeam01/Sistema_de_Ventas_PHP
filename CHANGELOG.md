# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

### Agregado
- `database/schema.sql` con la estructura de todas las tablas
- `database/seeder.sql` con datos iniciales para todas las tablas: roles, categorías, proveedores, clientes, usuarios, productos, compras y ventas de ejemplo
- Usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`, `comprador@sistema.com` / `comprador123`
- `UNIQUE KEY` en `tb_usuarios.email` para evitar emails duplicados
- `UNIQUE KEY` en `tb_almacen.codigo` para evitar códigos de producto duplicados
- `app/config.example.php` como plantilla de configuración sin credenciales

### Cambiado
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
- Typo `lamppstart` → `lampp start` en README.md y CLAUDE.md
- URL de ejemplo incorrecta en CLAUDE.md (`sistemaventas` → `Sistema_de_Ventas_PHP`)
