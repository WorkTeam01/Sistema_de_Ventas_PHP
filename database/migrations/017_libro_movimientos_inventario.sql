-- Libro inmutable de movimientos para conservar correcciones sin reescribir el historial.
-- Ejecutar UNA sola vez en instalaciones existentes, después de la migración 016.

CREATE TABLE IF NOT EXISTS `tb_movimientos_inventario` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `codigo_producto` varchar(255) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL DEFAULT 'unidad',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cantidad` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `usuario_nombre` varchar(250) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_movimiento_fecha_producto` (`fecha`, `id_producto`),
  KEY `idx_movimiento_producto_fecha` (`id_producto`, `fecha`),
  KEY `idx_movimiento_usuario` (`id_usuario`),
  CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tb_movimientos_inventario`
  (`id_producto`, `codigo_producto`, `nombre_producto`, `unidad_medida`, `fecha`,
   `cantidad`, `tipo`, `referencia`, `id_usuario`, `usuario_nombre`)
SELECT a.id_producto, p.codigo, p.nombre, p.unidad_medida, a.fyh_creacion,
       CASE WHEN a.tipo = 'entrada' THEN a.cantidad ELSE -a.cantidad END,
       CASE WHEN a.tipo = 'entrada' THEN 'Ajuste de entrada' ELSE 'Ajuste de salida' END,
       a.motivo, a.id_usuario, COALESCE(a.usuario_nombre, u.nombres, 'Usuario eliminado')
FROM tb_ajustes_stock a
INNER JOIN tb_almacen p ON p.id_producto = a.id_producto
LEFT JOIN tb_usuarios u ON u.id_usuario = a.id_usuario;

INSERT INTO `tb_movimientos_inventario`
  (`id_producto`, `codigo_producto`, `nombre_producto`, `unidad_medida`, `fecha`,
   `cantidad`, `tipo`, `referencia`, `id_usuario`, `usuario_nombre`)
SELECT c.id_producto, p.codigo, p.nombre, p.unidad_medida,
       CONCAT(c.fecha_compra, ' 00:00:00'), c.cantidad, 'Compra',
       c.comprobante, c.id_usuario, COALESCE(u.nombres, 'Usuario eliminado')
FROM tb_compras c
INNER JOIN tb_almacen p ON p.id_producto = c.id_producto
LEFT JOIN tb_usuarios u ON u.id_usuario = c.id_usuario;

INSERT INTO `tb_movimientos_inventario`
  (`id_producto`, `codigo_producto`, `nombre_producto`, `unidad_medida`, `fecha`,
   `cantidad`, `tipo`, `referencia`, `id_usuario`, `usuario_nombre`)
SELECT i.id_producto, p.codigo, p.nombre, p.unidad_medida, v.fyh_creacion,
       -i.cantidad, 'Venta', CAST(v.nro_venta AS CHAR), v.id_usuario,
       COALESCE(u.nombres, 'Usuario eliminado')
FROM tb_carrito i
INNER JOIN tb_ventas v ON v.nro_venta = i.nro_venta
INNER JOIN tb_almacen p ON p.id_producto = i.id_producto
LEFT JOIN tb_usuarios u ON u.id_usuario = v.id_usuario;

INSERT INTO `tb_movimientos_inventario`
  (`id_producto`, `codigo_producto`, `nombre_producto`, `unidad_medida`, `fecha`,
   `cantidad`, `tipo`, `referencia`, `id_usuario`, `usuario_nombre`)
SELECT i.id_producto, p.codigo, p.nombre, p.unidad_medida, d.fyh_creacion,
       i.cantidad, 'Devolución', CAST(d.nro_devolucion AS CHAR), d.id_usuario,
       COALESCE(u.nombres, 'Usuario eliminado')
FROM tb_devolucion_items i
INNER JOIN tb_devoluciones d ON d.id_devolucion = i.id_devolucion
INNER JOIN tb_almacen p ON p.id_producto = i.id_producto
LEFT JOIN tb_usuarios u ON u.id_usuario = d.id_usuario;

-- Estima el saldo de apertura que falta en los registros antiguos y lo coloca
-- en la fecha de alta del producto para cuadrar el libro con el stock actual.
INSERT INTO `tb_movimientos_inventario`
  (`id_producto`, `codigo_producto`, `nombre_producto`, `unidad_medida`, `fecha`,
   `cantidad`, `tipo`, `referencia`, `id_usuario`, `usuario_nombre`)
SELECT p.id_producto, p.codigo, p.nombre, p.unidad_medida,
       CONCAT(p.fecha_ingreso, ' 00:00:00'),
       p.stock - COALESCE(SUM(m.cantidad), 0),
       'Saldo inicial migrado', 'Saldo de apertura estimado durante la migración 017',
       p.id_usuario, COALESCE(u.nombres, 'Usuario eliminado')
FROM tb_almacen p
LEFT JOIN tb_movimientos_inventario m ON m.id_producto = p.id_producto
LEFT JOIN tb_usuarios u ON u.id_usuario = p.id_usuario
GROUP BY p.id_producto, p.codigo, p.nombre, p.unidad_medida, p.fecha_ingreso, p.stock,
         p.id_usuario, u.nombres
HAVING p.stock - COALESCE(SUM(m.cantidad), 0) <> 0;
