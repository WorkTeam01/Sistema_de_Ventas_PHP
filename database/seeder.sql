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
-- verifica AuthMiddleware y el sidebar (layout/parte1.php).
-- -------------------------------------------------------------
INSERT INTO `tb_roles` (`id_rol`, `rol`) VALUES
(1, 'Administrador'),
(2, 'Vendedor'),
(3, 'Comprador');

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
(1, 'Carlos Mamani', '71234567', '22345678', 'Distribuidora El Sol', 'carlos@elsol.com', 'Av. Montes 123, La Paz'),
(2, 'Rosa Quispe', '76543210', NULL, 'Comercial Andina', 'rosa@andina.com', 'Calle Murillo 456, Cochabamba'),
(3, 'Jorge Flores', '79876543', '44123456', 'Importadora Norte', NULL, 'Av. Beni 789, Santa Cruz');

-- -------------------------------------------------------------
-- tb_clientes
-- -------------------------------------------------------------
INSERT INTO `tb_clientes` (`id_cliente`, `nombre_cliente`, `nit_ci_cliente`, `celular_cliente`, `email_cliente`) VALUES
(1, 'Ana Pérez', '7654321', '70000001', 'ana.perez@email.com'),
(2, 'Luis García', '8765432', '70000002', 'luis.garcia@email.com'),
(3, 'María López', '9876543', '70000003', 'maria.lopez@email.com'),
(4, 'Juan Choque', '1234567', '70000004', 'juan.choque@email.com');

-- -------------------------------------------------------------
-- tb_usuarios (depende de tb_roles)
-- -------------------------------------------------------------
INSERT INTO `tb_usuarios` (`id_usuario`, `nombres`, `email`, `password_user`, `token`, `id_rol`) VALUES
(1, 'Administrador', 'admin@sistema.com', '$2y$10$6GM7OTq8e0SMkEAXhiDzEO/LDLD52y3IFTwC/vBmuRKCIXT9u9oQy', NULL, 1),
(2, 'Vendedor', 'vendedor@sistema.com', '$2y$10$IIRB7YVUFOfeQguXHq2Su.5Q8zBcKc2heS6kQ0LIZYU1hWH3lASCO', NULL, 2),
(3, 'Comprador', 'comprador@sistema.com', '$2y$10$E1hPyjykhVZvPcn0NwhNXuI8u.0TeX7jB7cEgO2iUkK2Y/sfCQQl6', NULL, 3);

-- -------------------------------------------------------------
-- tb_almacen (depende de tb_usuarios y tb_categorias)
-- -------------------------------------------------------------
INSERT INTO `tb_almacen` (`id_producto`, `codigo`, `nombre`, `descripcion`, `stock`, `stock_minimo`, `stock_maximo`, `precio_compra`, `precio_venta`, `fecha_ingreso`, `id_usuario`, `id_categoria`) VALUES
(1, 'ELEC-001', 'Laptop HP 15"', 'Laptop HP 15 pulgadas, 8GB RAM, 256GB SSD', 10, 2, 20, 2500.00, 3200.00, '2026-01-10', 1, 1),
(2, 'ELEC-002', 'Mouse Inalámbrico', 'Mouse inalámbrico USB 2.4GHz', 50, 10, 100, 25.00, 45.00, '2026-01-10', 1, 1),
(3, 'ALIM-001', 'Arroz Premium 5kg', 'Arroz grano largo premium', 100, 20, 200, 30.00, 45.00, '2026-01-15', 1, 2),
(4, 'ROPA-001', 'Camiseta Polo', 'Camiseta polo talla M, algodón 100%', 30, 5, 60, 50.00, 85.00, '2026-01-20', 1, 3),
(5, 'HERR-001', 'Destornillador Set', 'Set de 6 destornilladores planos y estrella', 25, 5, 50, 35.00, 60.00, '2026-02-01', 1, 4);

-- -------------------------------------------------------------
-- tb_carrito (depende de tb_almacen)
-- nro_venta agrupa los ítems de una misma venta
-- -------------------------------------------------------------
INSERT INTO `tb_carrito` (`id_carrito`, `nro_venta`, `id_producto`, `cantidad`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 2),
(3, 2, 3, 3),
(4, 2, 4, 1);

-- -------------------------------------------------------------
-- tb_ventas (depende de tb_clientes y tb_carrito.nro_venta)
-- -------------------------------------------------------------
INSERT INTO `tb_ventas` (`id_venta`, `nro_venta`, `id_cliente`, `total_pagado`) VALUES
(1, 1, 1, 3290.00),
(2, 2, 2, 220.00);

-- -------------------------------------------------------------
-- tb_compras (depende de tb_almacen, tb_proveedores, tb_usuarios)
-- -------------------------------------------------------------
INSERT INTO `tb_compras` (`id_compra`, `id_producto`, `nro_compra`, `fecha_compra`, `id_proveedor`, `comprobante`, `id_usuario`, `precio_compra`, `cantidad`) VALUES
(1, 1, 1001, '2026-01-05', 1, 'FAC-0001', 3, 2500.00, 12),
(2, 2, 1002, '2026-01-05', 1, 'FAC-0002', 3, 25.00, 60),
(3, 3, 1003, '2026-01-12', 2, 'FAC-0003', 3, 30.00, 120),
(4, 4, 1004, '2026-01-18', 2, 'FAC-0004', 3, 50.00, 35),
(5, 5, 1005, '2026-01-28', 3, 'FAC-0005', 3, 35.00, 30);
