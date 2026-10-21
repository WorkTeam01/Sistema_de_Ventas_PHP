-- Migración 016: unidades de medida y documentos comerciales internos.
-- Ejecutar UNA sola vez en bases que ya existen.

ALTER TABLE `tb_almacen`
  ADD COLUMN `unidad_medida` varchar(20) NOT NULL DEFAULT 'unidad' AFTER `codigo`;

ALTER TABLE `tb_ventas`
  ADD COLUMN `tipo_documento` varchar(20) NOT NULL DEFAULT 'boleta' AFTER `id_usuario`;

CREATE TABLE IF NOT EXISTS `tb_documentos_comerciales` (
  `id_documento` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('cotizacion','orden_compra') NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'borrador',
  `fecha_documento` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `observaciones` varchar(1000) DEFAULT NULL,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_documento`),
  KEY `idx_doc_tipo_fecha` (`tipo`, `fecha_documento`),
  KEY `idx_doc_cliente` (`id_cliente`),
  KEY `idx_doc_proveedor` (`id_proveedor`),
  KEY `idx_doc_usuario` (`id_usuario`),
  CONSTRAINT `fk_doc_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE NO ACTION,
  CONSTRAINT `fk_doc_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `tb_proveedores` (`id_proveedor`) ON DELETE NO ACTION,
  CONSTRAINT `fk_doc_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_documento_comercial_items` (
  `id_item` int(11) NOT NULL AUTO_INCREMENT,
  `id_documento` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `codigo_producto` varchar(255) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL DEFAULT 'unidad',
  `cantidad` int(11) NOT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `idx_doc_item_documento` (`id_documento`),
  KEY `idx_doc_item_producto` (`id_producto`),
  CONSTRAINT `fk_doc_item_documento` FOREIGN KEY (`id_documento`) REFERENCES `tb_documentos_comerciales` (`id_documento`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_item_producto` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_documentos_comerciales_historial` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_documento` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `usuario_nombre` varchar(250) NOT NULL,
  `accion` varchar(20) NOT NULL,
  `datos_anteriores` longtext DEFAULT NULL,
  `datos_nuevos` longtext NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `idx_doc_historial_documento` (`id_documento`, `fyh_creacion`),
  CONSTRAINT `fk_doc_historial_documento` FOREIGN KEY (`id_documento`) REFERENCES `tb_documentos_comerciales` (`id_documento`) ON DELETE NO ACTION,
  CONSTRAINT `fk_doc_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
