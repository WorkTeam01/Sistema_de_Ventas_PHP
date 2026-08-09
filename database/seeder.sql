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
-- tb_permisos (24 permisos del sistema RBAC)
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
('manage_inventory',         'Ajustar inventario',                       'inventario');

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
    'view_sales', 'manage_sales'
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
(1,  'ELEC-001', 'Laptop HP 15"',        'Laptop HP 15 pulgadas, 8GB RAM, 256GB SSD',        10, 2,  20,  2500.00, 3200.00, '2026-01-10', 1, 1),
(2,  'ELEC-002', 'Mouse Inalámbrico',    'Mouse inalámbrico USB 2.4GHz',                     50, 10, 100,   25.00,   45.00, '2026-01-10', 1, 1),
(3,  'ALIM-001', 'Arroz Premium 5kg',    'Arroz grano largo premium',                       100, 20, 200,   30.00,   45.00, '2026-01-15', 1, 2),
(4,  'ROPA-001', 'Camiseta Polo',        'Camiseta polo talla M, algodón 100%',              30,  5,  60,   50.00,   85.00, '2026-01-20', 1, 3),
(5,  'HERR-001', 'Destornillador Set',   'Set de 6 destornilladores planos y estrella',      25,  5,  50,   35.00,   60.00, '2026-02-01', 1, 4),
(6,  'ELEC-003', 'Teclado Mecánico',     'Teclado mecánico retroiluminado RGB, switch Blue', 20,  5,  40,  120.00,  195.00, '2026-02-05', 1, 1),
(7,  'ELEC-004', 'Monitor 24" Full HD',  'Monitor LED 24 pulgadas 1080p, 75Hz',              8,   2,  15, 1100.00, 1500.00, '2026-02-10', 1, 1),
(8,  'ALIM-002', 'Aceite Vegetal 1L',    'Aceite vegetal refinado, botella 1 litro',        150, 30, 300,   12.00,   18.00, '2026-02-15', 1, 2),
(9,  'ROPA-002', 'Pantalón Jeans',       'Pantalón jeans clásico, talla 32, corte recto',   40,  8,  80,   80.00,  130.00, '2026-03-01', 1, 3),
(10, 'HERR-002', 'Taladro Eléctrico',    'Taladro eléctrico 500W con maletín y brocas',     15,  3,  25,  280.00,  420.00, '2026-03-05', 1, 4);

-- -------------------------------------------------------------
-- tb_carrito (depende de tb_almacen)
-- nro_venta agrupa los ítems de una misma venta
-- -------------------------------------------------------------
INSERT INTO `tb_carrito` (`id_carrito`, `nro_venta`, `id_producto`, `cantidad`, `fyh_creacion`) VALUES
-- Enero 2026
(1,  1,  1, 1, '2026-01-18 10:15:00'),
(2,  1,  2, 2, '2026-01-18 10:15:00'),
(3,  2,  3, 3, '2026-01-22 14:30:00'),
(4,  2,  4, 1, '2026-01-22 14:30:00'),
(5,  3,  2, 5, '2026-01-28 09:00:00'),
(6,  3,  3, 2, '2026-01-28 09:00:00'),
-- Febrero 2026
(7,  4,  1, 1, '2026-02-03 11:20:00'),
(8,  4,  6, 1, '2026-02-03 11:20:00'),
(9,  5,  5, 2, '2026-02-10 16:45:00'),
(10, 5,  8, 4, '2026-02-10 16:45:00'),
(11, 6,  7, 1, '2026-02-17 10:00:00'),
(12, 6,  9, 2, '2026-02-17 10:00:00'),
(13, 7,  4, 3, '2026-02-25 13:30:00'),
(14, 7,  8, 6, '2026-02-25 13:30:00'),
-- Marzo 2026
(15, 8,  6, 2, '2026-03-04 09:15:00'),
(16, 8,  2, 3, '2026-03-04 09:15:00'),
(17, 9,  9, 1, '2026-03-12 15:00:00'),
(18, 9, 10, 1, '2026-03-12 15:00:00'),
(19, 10, 3, 5, '2026-03-20 11:45:00'),
(20, 10, 8, 3, '2026-03-20 11:45:00'),
-- Abril 2026
(21, 11, 1, 2, '2026-04-02 10:30:00'),
(22, 11, 7, 1, '2026-04-02 10:30:00'),
(23, 12, 5, 1, '2026-04-10 14:00:00'),
(24, 12, 6, 2, '2026-04-10 14:00:00'),
(25, 13, 4, 2, '2026-04-18 09:30:00'),
(26, 13, 9, 1, '2026-04-18 09:30:00'),
(27, 14, 2, 4, '2026-04-25 16:15:00'),
(28, 14, 3, 2, '2026-04-25 16:15:00');

-- -------------------------------------------------------------
-- tb_ventas (depende de tb_clientes y tb_carrito.nro_venta)
-- total_pagado = suma de (precio_venta * cantidad) por nro_venta
-- -------------------------------------------------------------
-- id_usuario = 2 (Vendedor) — vendedor de prueba como autor de todas las ventas seed
INSERT INTO `tb_ventas` (`id_venta`, `nro_venta`, `id_cliente`, `id_usuario`, `total_pagado`, `fyh_creacion`) VALUES
-- Enero 2026
(1,  1,  1, 2, 3290.00, '2026-01-18 10:15:00'),  -- Laptop(3200) + 2xMouse(90)
(2,  2,  2, 2,  220.00, '2026-01-22 14:30:00'),  -- 3xArroz(135) + Camiseta(85)
(3,  3,  3, 2,  315.00, '2026-01-28 09:00:00'),  -- 5xMouse(225) + 2xArroz(90)
-- Febrero 2026
(4,  4,  4, 2, 3395.00, '2026-02-03 11:20:00'),  -- Laptop(3200) + Teclado(195)
(5,  5,  5, 2,  192.00, '2026-02-10 16:45:00'),  -- 2xDestornillador(120) + 4xAceite(72)
(6,  6,  6, 2, 1760.00, '2026-02-17 10:00:00'),  -- Monitor(1500) + 2xJeans(260)
(7,  7,  7, 2,  363.00, '2026-02-25 13:30:00'),  -- 3xCamiseta(255) + 6xAceite(108)
-- Marzo 2026
(8,  8,  8, 2,  525.00, '2026-03-04 09:15:00'),  -- 2xTeclado(390) + 3xMouse(135)
(9,  9,  1, 2,  550.00, '2026-03-12 15:00:00'),  -- Jeans(130) + Taladro(420)
(10, 10, 2, 2,  279.00, '2026-03-20 11:45:00'),  -- 5xArroz(225) + 3xAceite(54)
-- Abril 2026
(11, 11, 3, 2, 7900.00, '2026-04-02 10:30:00'),  -- 2xLaptop(6400) + Monitor(1500)
(12, 12, 4, 2,  450.00, '2026-04-10 14:00:00'),  -- Destornillador(60) + 2xTeclado(390)
(13, 13, 5, 2,  300.00, '2026-04-18 09:30:00'),  -- 2xCamiseta(170) + Jeans(130)
(14, 14, 6, 2,  270.00, '2026-04-25 16:15:00');  -- 4xMouse(180) + 2xArroz(90)

-- -------------------------------------------------------------
-- tb_compras (depende de tb_almacen, tb_proveedores, tb_usuarios)
-- -------------------------------------------------------------
INSERT INTO `tb_compras` (`id_compra`, `id_producto`, `nro_compra`, `fecha_compra`, `id_proveedor`, `comprobante`, `id_usuario`, `precio_compra`, `cantidad`) VALUES
-- Enero 2026
(1,  1, 1001, '2026-01-05', 1, 'FAC-0001', 3, 2500.00, 12),
(2,  2, 1002, '2026-01-05', 1, 'FAC-0002', 3,   25.00, 60),
(3,  3, 1003, '2026-01-12', 2, 'FAC-0003', 3,   30.00, 120),
(4,  4, 1004, '2026-01-18', 2, 'FAC-0004', 3,   50.00, 35),
(5,  5, 1005, '2026-01-28', 3, 'FAC-0005', 3,   35.00, 30),
-- Febrero 2026
(6,  6, 1006, '2026-02-03', 1, 'FAC-0006', 3,  120.00, 25),
(7,  7, 1007, '2026-02-03', 1, 'FAC-0007', 3, 1100.00, 10),
(8,  8, 1008, '2026-02-10', 2, 'FAC-0008', 3,   12.00, 200),
(9,  2, 1009, '2026-02-20', 1, 'FAC-0009', 3,   25.00, 50),
(10, 3, 1010, '2026-02-25', 2, 'FAC-0010', 3,   30.00, 100),
-- Marzo 2026
(11, 9,  1011, '2026-03-01', 2, 'FAC-0011', 3,  80.00, 50),
(12, 10, 1012, '2026-03-05', 3, 'FAC-0012', 3, 280.00, 20),
(13, 4,  1013, '2026-03-15', 2, 'FAC-0013', 3,  50.00, 40),
(14, 6,  1014, '2026-03-22', 1, 'FAC-0014', 3, 120.00, 20),
-- Abril 2026
(15, 1,  1015, '2026-04-01', 1, 'FAC-0015', 3, 2500.00, 8),
(16, 8,  1016, '2026-04-08', 2, 'FAC-0016', 3,   12.00, 150),
(17, 5,  1017, '2026-04-15', 3, 'FAC-0017', 3,   35.00, 25),
(18, 9,  1018, '2026-04-22', 2, 'FAC-0018', 3,   80.00, 30);
