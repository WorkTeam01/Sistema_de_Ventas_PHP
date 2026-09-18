-- =============================================================
-- Seeder general — Sistema de Ventas
-- Datos iniciales para desarrollo y pruebas.
-- Ejecutar después de database/schema.sql
--
-- Credenciales de usuarios de prueba:
--   admin@sistema.com      / admin123
--   vendedor@sistema.com   / vendedor123
--   comprador@sistema.com  / comprador123
-- =============================================================

-- El seeder reemplaza completamente los datos de la aplicación.
-- DELETE se ejecuta en orden hijo→padre porque MySQL no permite
-- TRUNCATE sobre tablas referenciadas por claves foráneas.
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `tb_devolucion_items`;
DELETE FROM `tb_devoluciones`;
DELETE FROM `tb_ajustes_stock`;
DELETE FROM `tb_activity_log`;
DELETE FROM `tb_ventas`;
DELETE FROM `tb_carrito`;
DELETE FROM `tb_compras`;
DELETE FROM `tb_almacen`;
DELETE FROM `tb_rol_permiso`;
DELETE FROM `tb_permisos`;
DELETE FROM `tb_usuarios`;
DELETE FROM `tb_clientes`;
DELETE FROM `tb_proveedores`;
DELETE FROM `tb_categorias`;
DELETE FROM `tb_roles`;
ALTER TABLE `tb_devolucion_items` AUTO_INCREMENT = 1;
ALTER TABLE `tb_devoluciones` AUTO_INCREMENT = 1;
ALTER TABLE `tb_ajustes_stock` AUTO_INCREMENT = 1;
ALTER TABLE `tb_activity_log` AUTO_INCREMENT = 1;
ALTER TABLE `tb_ventas` AUTO_INCREMENT = 1;
ALTER TABLE `tb_carrito` AUTO_INCREMENT = 1;
ALTER TABLE `tb_compras` AUTO_INCREMENT = 1;
ALTER TABLE `tb_almacen` AUTO_INCREMENT = 1;
ALTER TABLE `tb_permisos` AUTO_INCREMENT = 1;
ALTER TABLE `tb_usuarios` AUTO_INCREMENT = 1;
ALTER TABLE `tb_clientes` AUTO_INCREMENT = 1;
ALTER TABLE `tb_proveedores` AUTO_INCREMENT = 1;
ALTER TABLE `tb_categorias` AUTO_INCREMENT = 1;
ALTER TABLE `tb_roles` AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- tb_roles
-- Los nombres deben coincidir exactamente con los valores que
-- verifica AuthMiddleware y el sidebar.
-- -------------------------------------------------------------
INSERT INTO `tb_roles` (`id_rol`, `rol`) VALUES
(1, 'Administrador'),
(2, 'Vendedor'),
(3, 'Comprador');

-- -------------------------------------------------------------
-- tb_permisos (26 permisos del sistema RBAC)
-- -------------------------------------------------------------
INSERT INTO `tb_permisos` (`clave`, `descripcion`, `modulo`) VALUES
('is_superadmin',            'Rol superusuario del sistema',             'sistema'),
('view_dashboard',           'Ver dashboard',                            'dashboard'),
('manage_users',             'Gestionar usuarios',                       'usuarios'),
('manage_roles',             'Gestionar roles',                          'roles'),
('view_categories',          'Ver categorías',                           'categorias'),
('manage_categories',        'Gestionar categorías',                     'categorias'),
('view_suppliers',           'Ver proveedores',                          'proveedores'),
('manage_suppliers',         'Gestionar proveedores',                    'proveedores'),
('view_clients',             'Ver clientes',                             'clientes'),
('manage_clients',           'Gestionar clientes',                       'clientes'),
('view_products',            'Ver productos',                            'productos'),
('manage_products',          'Gestionar productos',                      'productos'),
('view_purchases',           'Ver compras',                              'compras'),
('manage_purchases',         'Gestionar compras',                        'compras'),
('view_sales',               'Ver ventas',                               'ventas'),
('manage_sales',             'Gestionar ventas',                         'ventas'),
('view_sales_all',           'Ver todas las ventas (sin filtro de usuario)', 'ventas'),
('view_purchases_all',       'Ver todas las compras (sin filtro de usuario)', 'compras'),
('view_reports',             'Ver sección de reportes',                  'reportes'),
('view_sales_report',        'Ver reporte de ventas',                    'reportes'),
('view_purchases_report',    'Ver reporte de compras',                   'reportes'),
('view_top_products_report', 'Ver reporte de top productos',             'reportes'),
('view_clients_report',      'Ver reporte de clientes',                  'reportes'),
('view_activity_log',        'Ver log de actividad',                     'auditoria'),
('manage_inventory',         'Ajustar inventario',                       'inventario'),
('manage_returns',           'Gestionar devoluciones de ventas',         'ventas');

-- -------------------------------------------------------------
-- tb_rol_permiso — Administrador (todos los permisos, incluyendo is_superadmin)
-- Única línea en el sistema que referencia el nombre 'Administrador'.
-- Tras ejecutar este seeder, el sistema opera 100% por permisos.
-- -------------------------------------------------------------
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r CROSS JOIN `tb_permisos` p
WHERE r.rol = 'Administrador';

-- -------------------------------------------------------------
-- tb_rol_permiso — Vendedor
-- -------------------------------------------------------------
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave IN (
    'view_dashboard',
    'view_categories',
    'view_clients', 'manage_clients',
    'view_products',
    'view_sales', 'manage_sales',
    'manage_returns'
)
WHERE r.rol = 'Vendedor';

-- -------------------------------------------------------------
-- tb_rol_permiso — Comprador
-- -------------------------------------------------------------
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave IN (
    'view_dashboard',
    'view_categories', 'manage_categories',
    'view_suppliers', 'manage_suppliers',
    'view_products', 'manage_products',
    'view_purchases', 'manage_purchases'
)
WHERE r.rol = 'Comprador';

-- -------------------------------------------------------------
-- tb_categorias
-- -------------------------------------------------------------
INSERT INTO `tb_categorias` (`id_categoria`, `nombre_categoria`) VALUES
(1, 'Electrónica'),
(2, 'Alimentos'),
(3, 'Ropa'),
(4, 'Herramientas');

-- -------------------------------------------------------------
-- tb_proveedores
-- -------------------------------------------------------------
INSERT INTO `tb_proveedores` (`id_proveedor`, `nombre_proveedor`, `celular`, `telefono`, `empresa`, `email`, `direccion`) VALUES
(1, 'Carlos Mamani',  '71234567', '22345678', 'Distribuidora El Sol',  'carlos@elsol.com',   'Av. Montes 123, La Paz'),
(2, 'Rosa Quispe',    '76543210', NULL,        'Comercial Andina',      'rosa@andina.com',    'Calle Murillo 456, Cochabamba'),
(3, 'Jorge Flores',   '79876543', '44123456', 'Importadora Norte',     NULL,                  'Av. Beni 789, Santa Cruz');

-- -------------------------------------------------------------
-- tb_clientes
-- -------------------------------------------------------------
INSERT INTO `tb_clientes` (`id_cliente`, `nombre_cliente`, `nit_ci_cliente`, `celular_cliente`, `email_cliente`) VALUES
(1, 'Ana Pérez',      '7654321',  '70000001', 'ana.perez@email.com'),
(2, 'Luis García',    '8765432',  '70000002', 'luis.garcia@email.com'),
(3, 'María López',    '9876543',  '70000003', 'maria.lopez@email.com'),
(4, 'Juan Choque',    '1234567',  '70000004', 'juan.choque@email.com'),
(5, 'Pedro Condori',  '2345678',  '70000005', 'pedro.condori@email.com'),
(6, 'Sofía Mamani',   '3456789',  '70000006', 'sofia.mamani@email.com'),
(7, 'Roberto Vargas', '4567890',  '70000007', 'roberto.vargas@email.com'),
(8, 'Carmen Quispe',  '5678901',  '70000008', 'carmen.quispe@email.com');

-- -------------------------------------------------------------
-- tb_usuarios (depende de tb_roles)
-- -------------------------------------------------------------
INSERT INTO `tb_usuarios` (`id_usuario`, `nombres`, `email`, `password_user`, `reset_token`, `reset_token_expiracion`, `id_rol`) VALUES
(1, 'Administrador', 'admin@sistema.com',      '$2y$10$6GM7OTq8e0SMkEAXhiDzEO/LDLD52y3IFTwC/vBmuRKCIXT9u9oQy', NULL, NULL, 1),
(2, 'Vendedor',      'vendedor@sistema.com',   '$2y$10$IIRB7YVUFOfeQguXHq2Su.5Q8zBcKc2heS6kQ0LIZYU1hWH3lASCO', NULL, NULL, 2),
(3, 'Comprador',     'comprador@sistema.com',  '$2y$10$E1hPyjykhVZvPcn0NwhNXuI8u.0TeX7jB7cEgO2iUkK2Y/sfCQQl6', NULL, NULL, 3);

-- -------------------------------------------------------------
-- tb_almacen (depende de tb_usuarios y tb_categorias)
-- -------------------------------------------------------------
INSERT INTO `tb_almacen` (`id_producto`, `codigo`, `nombre`, `descripcion`, `stock`, `stock_minimo`, `stock_maximo`, `precio_compra`, `precio_venta`, `fecha_ingreso`, `id_usuario`, `id_categoria`) VALUES
(1,  'ELEC-001', 'Laptop HP 15"',        'Laptop HP 15 pulgadas, 8GB RAM, 256GB SSD',        10, 2,  20,  2500.00, 3200.00, DATE_SUB(CURDATE(), INTERVAL 251 DAY), 1, 1),
(2,  'ELEC-002', 'Mouse Inalámbrico',    'Mouse inalámbrico USB 2.4GHz',                     50, 10, 100,   25.00,   45.00, DATE_SUB(CURDATE(), INTERVAL 251 DAY), 1, 1),
(3,  'ALIM-001', 'Arroz Premium 5kg',    'Arroz grano largo premium',                       100, 20, 200,   30.00,   45.00, DATE_SUB(CURDATE(), INTERVAL 246 DAY), 1, 2),
(4,  'ROPA-001', 'Camiseta Polo',        'Camiseta polo talla M, algodón 100%',              30,  5,  60,   50.00,   85.00, DATE_SUB(CURDATE(), INTERVAL 241 DAY), 1, 3),
(5,  'HERR-001', 'Destornillador Set',   'Set de 6 destornilladores planos y estrella',      25,  5,  50,   35.00,   60.00, DATE_SUB(CURDATE(), INTERVAL 229 DAY), 1, 4),
(6,  'ELEC-003', 'Teclado Mecánico',     'Teclado mecánico retroiluminado RGB, switch Blue', 20,  5,  40,  120.00,  195.00, DATE_SUB(CURDATE(), INTERVAL 225 DAY), 1, 1),
(7,  'ELEC-004', 'Monitor 24" Full HD',  'Monitor LED 24 pulgadas 1080p, 75Hz',              8,   2,  15, 1100.00, 1500.00, DATE_SUB(CURDATE(), INTERVAL 220 DAY), 1, 1),
(8,  'ALIM-002', 'Aceite Vegetal 1L',    'Aceite vegetal refinado, botella 1 litro',        150, 30, 300,   12.00,   18.00, DATE_SUB(CURDATE(), INTERVAL 215 DAY), 1, 2),
(9,  'ROPA-002', 'Pantalón Jeans',       'Pantalón jeans clásico, talla 32, corte recto',   40,  8,  80,   80.00,  130.00, DATE_SUB(CURDATE(), INTERVAL 201 DAY), 1, 3),
(10, 'HERR-002', 'Taladro Eléctrico',    'Taladro eléctrico 500W con maletín y brocas',     15,  3,  25,  280.00,  420.00, DATE_SUB(CURDATE(), INTERVAL 197 DAY), 1, 4);

-- -------------------------------------------------------------
-- tb_carrito (depende de tb_almacen)
-- nro_venta agrupa los ítems de una misma venta
-- -------------------------------------------------------------
INSERT INTO `tb_carrito` (`id_carrito`, `nro_venta`, `id_producto`, `cantidad`, `fyh_creacion`) VALUES
-- Período histórico 1
(1,  1,  1, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 243 DAY), '10:15:00')),
(2,  1,  2, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 243 DAY), '10:15:00')),
(3,  2,  3, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 239 DAY), '14:30:00')),
(4,  2,  4, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 239 DAY), '14:30:00')),
(5,  3,  2, 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 233 DAY), '09:00:00')),
(6,  3,  3, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 233 DAY), '09:00:00')),
-- Período histórico 2
(7,  4,  1, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 227 DAY), '11:20:00')),
(8,  4,  6, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 227 DAY), '11:20:00')),
(9,  5,  5, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 220 DAY), '16:45:00')),
(10, 5,  8, 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 220 DAY), '16:45:00')),
(11, 6,  7, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 213 DAY), '10:00:00')),
(12, 6,  9, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 213 DAY), '10:00:00')),
(13, 7,  4, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 205 DAY), '13:30:00')),
(14, 7,  8, 6, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 205 DAY), '13:30:00')),
-- Período histórico 3
(15, 8,  6, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 198 DAY), '09:15:00')),
(16, 8,  2, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 198 DAY), '09:15:00')),
(17, 9,  9, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 190 DAY), '15:00:00')),
(18, 9, 10, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 190 DAY), '15:00:00')),
(19, 10, 3, 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 182 DAY), '11:45:00')),
(20, 10, 8, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 182 DAY), '11:45:00')),
-- Período histórico 4
(21, 11, 1, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 169 DAY), '10:30:00')),
(22, 11, 7, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 169 DAY), '10:30:00')),
(23, 12, 5, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 161 DAY), '14:00:00')),
(24, 12, 6, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 161 DAY), '14:00:00')),
(25, 13, 4, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 153 DAY), '09:30:00')),
(26, 13, 9, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 153 DAY), '09:30:00')),
(27, 14, 2, 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 146 DAY), '16:15:00')),
(28, 14, 3, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 146 DAY), '16:15:00')),
-- Período histórico 5
(29,  15, 1, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 136 DAY), '10:10:00')),
(30,  15, 2, 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 136 DAY), '10:10:00')),
(31,  16, 2, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 127 DAY), '15:20:00')),
(32,  16, 3, 6, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 127 DAY), '15:20:00')),
(33,  17, 4, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 114 DAY), '11:05:00')),
(34,  17, 5, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 114 DAY), '11:05:00')),
-- Período histórico 6
(35,  18, 6, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 107 DAY), '09:40:00')),
(36,  18, 7, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 107 DAY), '09:40:00')),
(37,  19, 8, 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 94 DAY), '14:25:00')),
(38,  19, 9, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 94 DAY), '14:25:00')),
(39,  20, 10, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 82 DAY), '16:10:00')),
(40,  20, 1, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 82 DAY), '16:10:00')),
-- Período histórico 7
(41,  21, 1, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 73 DAY), '10:35:00')),
(42,  21, 3, 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 73 DAY), '10:35:00')),
(43,  22, 2, 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 62 DAY), '13:50:00')),
(44,  22, 8, 10, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 62 DAY), '13:50:00')),
(45,  23, 7, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 51 DAY), '09:15:00')),
(46,  23, 9, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 51 DAY), '09:15:00')),
-- Período histórico 8
(47,  24, 4, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 45 DAY), '11:45:00')),
(48,  24, 6, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 45 DAY), '11:45:00')),
(49,  25, 1, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 34 DAY), '15:05:00')),
(50,  25, 2, 8, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 34 DAY), '15:05:00')),
(51,  26, 5, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 22 DAY), '10:20:00')),
(52,  26, 10, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 22 DAY), '10:20:00')),
-- Período histórico 9
(53,  27, 3, 6, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 16 DAY), '09:30:00')),
(54,  27, 8, 12, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 16 DAY), '09:30:00')),
(55,  28, 6, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '14:40:00')),
(56,  28, 7, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '14:40:00')),
(57,  29, 9, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '16:25:00')),
(58,  29, 10, 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '16:25:00'));

-- -------------------------------------------------------------
-- tb_ventas (depende de tb_clientes y tb_carrito.nro_venta)
-- total_pagado = suma de (precio_unitario * cantidad) por nro_venta
-- -------------------------------------------------------------
-- id_usuario = 2 (Vendedor) — vendedor de prueba como autor de todas las ventas seed
INSERT INTO `tb_ventas` (`id_venta`, `nro_venta`, `id_cliente`, `id_usuario`, `total_pagado`, `fyh_creacion`) VALUES
-- Período histórico 1
(1,  1,  1, 2, 3290.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 243 DAY), '10:15:00')),  -- Laptop(3200) + 2xMouse(90)
(2,  2,  2, 2,  220.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 239 DAY), '14:30:00')),  -- 3xArroz(135) + Camiseta(85)
(3,  3,  3, 2,  315.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 233 DAY), '09:00:00')),  -- 5xMouse(225) + 2xArroz(90)
-- Período histórico 2
(4,  4,  4, 2, 3395.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 227 DAY), '11:20:00')),  -- Laptop(3200) + Teclado(195)
(5,  5,  5, 2,  192.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 220 DAY), '16:45:00')),  -- 2xDestornillador(120) + 4xAceite(72)
(6,  6,  6, 2, 1760.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 213 DAY), '10:00:00')),  -- Monitor(1500) + 2xJeans(260)
(7,  7,  7, 2,  363.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 205 DAY), '13:30:00')),  -- 3xCamiseta(255) + 6xAceite(108)
-- Período histórico 3
(8,  8,  8, 2,  525.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 198 DAY), '09:15:00')),  -- 2xTeclado(390) + 3xMouse(135)
(9,  9,  1, 2,  550.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 190 DAY), '15:00:00')),  -- Jeans(130) + Taladro(420)
(10, 10, 2, 2,  279.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 182 DAY), '11:45:00')),  -- 5xArroz(225) + 3xAceite(54)
-- Período histórico 4
(11, 11, 3, 2, 7900.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 169 DAY), '10:30:00')),  -- 2xLaptop(6400) + Monitor(1500)
(12, 12, 4, 2,  450.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 161 DAY), '14:00:00')),  -- Destornillador(60) + 2xTeclado(390)
(13, 13, 5, 2,  300.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 153 DAY), '09:30:00')),  -- 2xCamiseta(170) + Jeans(130)
(14, 14, 6, 2,  270.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 146 DAY), '16:15:00')),  -- 4xMouse(180) + 2xArroz(90)
-- Período histórico 5
(15, 15, 7, 2, 3380.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 136 DAY), '10:10:00')),  -- Laptop(3200) + 4xMouse(180)
(16, 16, 8, 2,  360.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 127 DAY), '15:20:00')),  -- 2xMouse(90) + 6xArroz(270)
(17, 17, 1, 2,  205.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 114 DAY), '11:05:00')),  -- Camiseta(85) + 2xDestornillador(120)
-- Período histórico 6
(18, 18, 2, 2, 4695.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 107 DAY), '09:40:00')),  -- Teclado(195) + 3xMonitor(4500)
(19, 19, 3, 2,  350.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 94 DAY), '14:25:00')),  -- 5xAceite(90) + 2xJeans(260)
(20, 20, 4, 2, 3620.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 82 DAY), '16:10:00')),  -- Taladro(420) + Laptop(3200)
-- Período histórico 7
(21, 21, 5, 2, 3425.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 73 DAY), '10:35:00')),  -- Laptop(3200) + 5xArroz(225)
(22, 22, 6, 2,  360.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 62 DAY), '13:50:00')),  -- 4xMouse(180) + 10xAceite(180)
(23, 23, 7, 2, 1890.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 51 DAY), '09:15:00')),  -- Monitor(1500) + 3xJeans(390)
-- Período histórico 8
(24, 24, 8, 2,  365.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 45 DAY), '11:45:00')),  -- 2xCamiseta(170) + Teclado(195)
(25, 25, 1, 2, 3560.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 34 DAY), '15:05:00')),  -- Laptop(3200) + 8xMouse(360)
(26, 26, 2, 2,  540.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 22 DAY), '10:20:00')),  -- 2xDestornillador(120) + Taladro(420)
-- Período histórico 9
(27, 27, 3, 2,  486.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 16 DAY), '09:30:00')),  -- 6xArroz(270) + 12xAceite(216)
(28, 28, 4, 2, 3195.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '14:40:00')),  -- Teclado(195) + 2xMonitor(3000)
(29, 29, 5, 2,  680.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '16:25:00'));  -- 2xJeans(260) + Taladro(420)

-- Precio histórico por línea de venta (spec 002).
UPDATE `tb_carrito` SET `precio_unitario` = CASE `id_producto`
    WHEN 1 THEN 3200.00
    WHEN 2 THEN 45.00
    WHEN 3 THEN 45.00
    WHEN 4 THEN 85.00
    WHEN 5 THEN 60.00
    WHEN 6 THEN 195.00
    WHEN 7 THEN 1500.00
    WHEN 8 THEN 18.00
    WHEN 9 THEN 130.00
    WHEN 10 THEN 420.00
END
WHERE `nro_venta` IN (SELECT `nro_venta` FROM `tb_ventas`);

-- -------------------------------------------------------------
-- tb_devoluciones (depende de tb_ventas y tb_usuarios)
-- Devolución parcial de la venta 14: 1 Mouse + 1 Arroz.
-- -------------------------------------------------------------
INSERT INTO `tb_devoluciones`
    (`id_devolucion`, `nro_devolucion`, `id_venta`, `id_usuario`, `motivo`, `monto`, `fyh_creacion`)
VALUES
(1, 1, 14, 2, 'Producto devuelto por el cliente', 90.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 145 DAY), '10:00:00'));

INSERT INTO `tb_devolucion_items`
    (`id_detalle`, `id_devolucion`, `id_producto`, `cantidad`, `precio_unitario`)
VALUES
(1, 1, 2, 1, 45.00),
(2, 1, 3, 1, 45.00);

-- Reingreso de las dos unidades devueltas para mantener stock y devolución coherentes.
UPDATE `tb_almacen` SET `stock` = `stock` + 1 WHERE `id_producto` IN (2, 3);

-- Devoluciones adicionales para mostrar el comportamiento acumulativo del demo.
INSERT INTO `tb_devoluciones`
    (`id_devolucion`, `nro_devolucion`, `id_venta`, `id_usuario`, `motivo`, `monto`, `fyh_creacion`)
VALUES
(2, 2, 22, 2, 'Envases de aceite entregados por error', 36.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 60 DAY), '10:30:00')),
(3, 3, 28, 2, 'Monitor con daño durante el transporte', 1500.00, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:20:00'));

INSERT INTO `tb_devolucion_items`
    (`id_detalle`, `id_devolucion`, `id_producto`, `cantidad`, `precio_unitario`)
VALUES
(3, 2, 8, 2, 18.00),
(4, 3, 7, 1, 1500.00);

UPDATE `tb_almacen` SET `stock` = `stock` + 2 WHERE `id_producto` = 8;
UPDATE `tb_almacen` SET `stock` = `stock` + 1 WHERE `id_producto` = 7;

-- -------------------------------------------------------------
-- tb_compras (depende de tb_almacen, tb_proveedores, tb_usuarios)
-- -------------------------------------------------------------
INSERT INTO `tb_compras` (`id_compra`, `id_producto`, `nro_compra`, `fecha_compra`, `id_proveedor`, `comprobante`, `id_usuario`, `precio_compra`, `cantidad`) VALUES
-- Período histórico 1
(1,  1, 1001, DATE_SUB(CURDATE(), INTERVAL 256 DAY), 1, 'FAC-0001', 3, 2500.00, 12),
(2,  2, 1002, DATE_SUB(CURDATE(), INTERVAL 256 DAY), 1, 'FAC-0002', 3,   25.00, 60),
(3,  3, 1003, DATE_SUB(CURDATE(), INTERVAL 249 DAY), 2, 'FAC-0003', 3,   30.00, 120),
(4,  4, 1004, DATE_SUB(CURDATE(), INTERVAL 243 DAY), 2, 'FAC-0004', 3,   50.00, 35),
(5,  5, 1005, DATE_SUB(CURDATE(), INTERVAL 233 DAY), 3, 'FAC-0005', 3,   35.00, 30),
-- Período histórico 2
(6,  6, 1006, DATE_SUB(CURDATE(), INTERVAL 227 DAY), 1, 'FAC-0006', 3,  120.00, 25),
(7,  7, 1007, DATE_SUB(CURDATE(), INTERVAL 227 DAY), 1, 'FAC-0007', 3, 1100.00, 10),
(8,  8, 1008, DATE_SUB(CURDATE(), INTERVAL 220 DAY), 2, 'FAC-0008', 3,   12.00, 200),
(9,  2, 1009, DATE_SUB(CURDATE(), INTERVAL 210 DAY), 1, 'FAC-0009', 3,   25.00, 50),
(10, 3, 1010, DATE_SUB(CURDATE(), INTERVAL 205 DAY), 2, 'FAC-0010', 3,   30.00, 100),
-- Período histórico 3
(11, 9,  1011, DATE_SUB(CURDATE(), INTERVAL 201 DAY), 2, 'FAC-0011', 3,  80.00, 50),
(12, 10, 1012, DATE_SUB(CURDATE(), INTERVAL 197 DAY), 3, 'FAC-0012', 3, 280.00, 20),
(13, 4,  1013, DATE_SUB(CURDATE(), INTERVAL 187 DAY), 2, 'FAC-0013', 3,  50.00, 40),
(14, 6,  1014, DATE_SUB(CURDATE(), INTERVAL 180 DAY), 1, 'FAC-0014', 3, 120.00, 20),
-- Período histórico 4
(15, 1,  1015, DATE_SUB(CURDATE(), INTERVAL 170 DAY), 1, 'FAC-0015', 3, 2500.00, 8),
(16, 8,  1016, DATE_SUB(CURDATE(), INTERVAL 163 DAY), 2, 'FAC-0016', 3,   12.00, 150),
(17, 5,  1017, DATE_SUB(CURDATE(), INTERVAL 156 DAY), 3, 'FAC-0017', 3,   35.00, 25),
(18, 9,  1018, DATE_SUB(CURDATE(), INTERVAL 149 DAY), 2, 'FAC-0018', 3,   80.00, 30),
-- Período histórico 5
(19, 7,  1019, DATE_SUB(CURDATE(), INTERVAL 138 DAY), 3, 'FAC-0019', 3, 1100.00, 25),
(20, 8,  1020, DATE_SUB(CURDATE(), INTERVAL 131 DAY), 2, 'FAC-0020', 3,   12.00, 180),
(21, 10, 1021, DATE_SUB(CURDATE(), INTERVAL 123 DAY), 3, 'FAC-0021', 3,  280.00, 15),
(22, 2,  1022, DATE_SUB(CURDATE(), INTERVAL 115 DAY), 1, 'FAC-0022', 3,   25.00, 70),
-- Período histórico 6
(23, 3,  1023, DATE_SUB(CURDATE(), INTERVAL 106 DAY), 2, 'FAC-0023', 3,   30.00, 140),
(24, 4,  1024, DATE_SUB(CURDATE(), INTERVAL 98 DAY), 2, 'FAC-0024', 3,   50.00, 30),
(25, 9,  1025, DATE_SUB(CURDATE(), INTERVAL 89 DAY), 2, 'FAC-0025', 3,   80.00, 45),
(26, 1,  1026, DATE_SUB(CURDATE(), INTERVAL 81 DAY), 1, 'FAC-0026', 3, 2500.00, 6),
-- Período histórico 7
(27, 6,  1027, DATE_SUB(CURDATE(), INTERVAL 75 DAY), 1, 'FAC-0027', 3,  120.00, 18),
(28, 5,  1028, DATE_SUB(CURDATE(), INTERVAL 66 DAY), 3, 'FAC-0028', 3,   35.00, 35),
(29, 8,  1029, DATE_SUB(CURDATE(), INTERVAL 57 DAY), 2, 'FAC-0029', 3,   12.00, 220),
(30, 10, 1030, DATE_SUB(CURDATE(), INTERVAL 49 DAY), 3, 'FAC-0030', 3,  280.00, 12),
-- Período histórico 8
(31, 7,  1031, DATE_SUB(CURDATE(), INTERVAL 43 DAY), 1, 'FAC-0031', 3, 1100.00, 8),
(32, 2,  1032, DATE_SUB(CURDATE(), INTERVAL 36 DAY), 1, 'FAC-0032', 3,   25.00, 80),
(33, 3,  1033, DATE_SUB(CURDATE(), INTERVAL 27 DAY), 2, 'FAC-0033', 3,   30.00, 160),
(34, 9,  1034, DATE_SUB(CURDATE(), INTERVAL 19 DAY), 2, 'FAC-0034', 3,   80.00, 40),
-- Período histórico 9
(35, 1,  1035, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 1, 'FAC-0035', 3, 2500.00, 5),
(36, 4,  1036, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 2, 'FAC-0036', 3,   50.00, 25),
(37, 6,  1037, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 1, 'FAC-0037', 3,  120.00, 15),
(38, 10, 1038, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 3, 'FAC-0038', 3,  280.00, 10);
