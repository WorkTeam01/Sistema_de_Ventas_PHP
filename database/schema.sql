SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Tabla: tb_almacen
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_almacen` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL DEFAULT 'unidad',
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `stock_minimo` int(11) DEFAULT NULL,
  `stock_maximo` int(11) DEFAULT NULL,
  `precio_compra` DECIMAL(10,2) NOT NULL,
  `precio_venta` DECIMAL(10,2) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `imagen` varchar(255) NOT NULL DEFAULT 'producto_default.png',
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_categoria` (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_carrito
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_carrito` (
  `id_carrito` int(11) NOT NULL AUTO_INCREMENT,
  `nro_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` DECIMAL(10,2) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_carrito`),
  KEY `id_venta` (`nro_venta`),
  KEY `id_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_categorias
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(255) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_clientes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cliente` varchar(255) NOT NULL,
  `nit_ci_cliente` varchar(255) NOT NULL,
  `celular_cliente` varchar(50) NOT NULL,
  `email_cliente` varchar(255) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `nit_ci_cliente` (`nit_ci_cliente`),
  UNIQUE KEY `email_cliente` (`email_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_compras
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_compras` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `nro_compra` int(11) NOT NULL,
  `fecha_compra` date NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `precio_compra` DECIMAL(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_compra`),
  UNIQUE KEY `uq_compra_nro` (`nro_compra`),
  KEY `id_producto` (`id_producto`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_proveedores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_proveedor` varchar(255) NOT NULL,
  `celular` varchar(50) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `empresa` varchar(255) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(255) NOT NULL,
  `permisos_version` INT(11) NOT NULL DEFAULT 0,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_usuarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password_user` text NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiracion` datetime DEFAULT NULL,
  `remember_token` varchar(64) NULL DEFAULT NULL,
  `remember_token_expiry` datetime NULL DEFAULT NULL,
  `login_intentos` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `login_bloqueado_hasta` datetime NULL DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `id_rol` (`id_rol`),
  KEY `idx_usuarios_remember_token` (`remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_ventas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_ventas` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `nro_venta` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `tipo_documento` varchar(20) NOT NULL DEFAULT 'boleta',
  `total_pagado` DECIMAL(10,2) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`),
  KEY `id_cliente` (`id_cliente`),
  UNIQUE KEY `uq_venta_nro` (`nro_venta`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_activity_log (auditoría de operaciones sensibles)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_activity_log` (
  `id_log`            int(11)       NOT NULL AUTO_INCREMENT,
  `id_usuario`        int(11)       DEFAULT NULL,
  `usuario_nombre`    varchar(250)  NOT NULL,
  `accion`            varchar(50)   NOT NULL,
  `entidad`           varchar(50)   NOT NULL,
  `entidad_id`        int(11)       DEFAULT NULL,
  `descripcion`       varchar(255)  DEFAULT NULL,
  `datos_anteriores`  text          DEFAULT NULL,
  `datos_nuevos`      text          DEFAULT NULL,
  `ip_address`        varchar(45)   DEFAULT NULL,
  `fyh_creacion`      datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_usuario` (`id_usuario`),
  KEY `idx_log_entidad` (`entidad`, `entidad_id`),
  KEY `idx_log_fecha`   (`fyh_creacion`),
  KEY `idx_log_accion`  (`accion`),
  CONSTRAINT `tb_activity_log_ibfk_1`
    FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_ajustes_stock (auditoría de ajustes de inventario)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_ajustes_stock` (
  `id_ajuste`       int(11)        NOT NULL AUTO_INCREMENT,
  `id_producto`     int(11)        NOT NULL,
  `tipo`            enum('entrada','salida') NOT NULL,
  `cantidad`        int(11)        NOT NULL,
  `stock_anterior`  int(11)        NOT NULL,
  `stock_posterior` int(11)        NOT NULL,
  `motivo`          varchar(255)   NOT NULL,
  `id_usuario`      int(11)        DEFAULT NULL,
  `usuario_nombre`  varchar(150)   DEFAULT NULL,
  `fyh_creacion`    datetime       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ajuste`),
  KEY `idx_ajuste_producto` (`id_producto`),
  KEY `idx_ajuste_tipo`     (`tipo`),
  KEY `idx_ajuste_fecha`    (`fyh_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: tb_devoluciones
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_devoluciones` (
  `id_devolucion`  int(11)        NOT NULL AUTO_INCREMENT,
  `nro_devolucion`  int(11)        NOT NULL,
  `id_venta`       int(11)        NOT NULL,
  `id_usuario`     int(11)        DEFAULT NULL,
  `motivo`         varchar(255)   NOT NULL,
  `monto`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `token_formulario` CHAR(32)     NULL DEFAULT NULL,
  `fyh_creacion`   datetime       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_devolucion`),
  UNIQUE KEY `uq_devolucion_nro` (`nro_devolucion`),
  UNIQUE KEY `uq_devolucion_token` (`token_formulario`),
  KEY `idx_devolucion_venta` (`id_venta`),
  KEY `idx_devolucion_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_devolucion_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_devolucion_items` (
  `id_detalle`     int(11)        NOT NULL AUTO_INCREMENT,
  `id_devolucion`  int(11)        NOT NULL,
  `id_producto`    int(11)        NOT NULL,
  `cantidad`       int(11)        NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_dev_item_devolucion` (`id_devolucion`),
  KEY `idx_dev_item_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------
-- Tabla: tb_metodos_pago (catálogo configurable de métodos de cobro)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_metodos_pago` (
  `id_metodo_pago`    int(11)      NOT NULL AUTO_INCREMENT,
  `nombre`            varchar(60)  NOT NULL,
  `tipo`              enum('efectivo','no_efectivo') NOT NULL DEFAULT 'no_efectivo',
  `activo`            tinyint(1)   NOT NULL DEFAULT 1,
  `fyh_creacion`      datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_actualizacion` datetime     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_metodo_pago`),
  UNIQUE KEY `uq_metodo_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: tb_pagos (una línea de pago por método por venta)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_pagos` (
  `id_pago`        int(11)       NOT NULL AUTO_INCREMENT,
  `id_venta`       int(11)       NOT NULL,
  `id_metodo_pago` int(11)       NOT NULL,
  `monto`          DECIMAL(10,2) NOT NULL,
  `fyh_creacion`   datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago`),
  UNIQUE KEY `uq_pago_venta_metodo` (`id_venta`, `id_metodo_pago`),
  KEY `idx_pago_venta` (`id_venta`),
  KEY `idx_pago_metodo` (`id_metodo_pago`),
  CONSTRAINT `fk_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `tb_ventas` (`id_venta`)
    ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_pago_metodo` FOREIGN KEY (`id_metodo_pago`) REFERENCES `tb_metodos_pago` (`id_metodo_pago`)
    ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Documentos comerciales internos: cotizaciones y órdenes de compra
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Libro inmutable de movimientos de inventario
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Tabla: tb_permisos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_permisos` (
  `id_permiso`   INT(11)      NOT NULL AUTO_INCREMENT,
  `clave`        VARCHAR(60)  NOT NULL,
  `descripcion`  VARCHAR(150) NOT NULL,
  `modulo`       VARCHAR(40)  NOT NULL,
  `fyh_creacion` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permiso_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: tb_rol_permiso
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_rol_permiso` (
  `id_rol`     INT(11) NOT NULL,
  `id_permiso` INT(11) NOT NULL,
  PRIMARY KEY (`id_rol`, `id_permiso`),
  CONSTRAINT `fk_rp_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `tb_roles`(`id_rol`)     ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `tb_permisos`(`id_permiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: tb_pos_reservas (id_reserva ES el nro_venta del POS)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_pos_reservas` (
  `id_reserva`     int(11)  NOT NULL AUTO_INCREMENT,
  `id_usuario`     int(11)  DEFAULT NULL,
  `fyh_creacion`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_liberacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  KEY `idx_reserva_usuario` (`id_usuario`, `fyh_liberacion`),
  CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Tabla: tb_intentos_auth (intentos de login y de restablecimiento)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_intentos_auth` (
  `id_intento`   int(11)      NOT NULL AUTO_INCREMENT,
  `tipo`         varchar(10)  NOT NULL,
  `ip`           varchar(45)  NOT NULL,
  `correo`       varchar(250) NOT NULL,
  `id_usuario`   int(11)      DEFAULT NULL,
  `fyh_creacion` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_intento`),
  KEY `idx_intento_correo` (`tipo`, `correo`, `fyh_creacion`),
  KEY `idx_intento_ip` (`tipo`, `ip`, `fyh_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Tabla: tb_sesiones_recordadas (un "recordarme" por dispositivo)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_sesiones_recordadas` (
  `id_sesion`      int(11)      NOT NULL AUTO_INCREMENT,
  `id_usuario`     int(11)      NOT NULL,
  `selector`       char(24)     NOT NULL,
  `token_hash`     char(64)     NOT NULL,
  `fyh_expira`     datetime     NOT NULL,
  `ip`             varchar(45)  NOT NULL,
  `user_agent`     varchar(255) DEFAULT NULL,
  `fyh_creacion`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_ultimo_uso` datetime     DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `uq_sesion_selector` (`selector`),
  KEY `idx_sesion_usuario` (`id_usuario`),
  CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Restricciones (Foreign Keys)
-- --------------------------------------------------------
ALTER TABLE `tb_almacen`
  ADD CONSTRAINT `tb_almacen_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_almacen_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `tb_carrito`
  ADD CONSTRAINT `tb_carrito_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `tb_compras`
  ADD CONSTRAINT `tb_compras_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `tb_proveedores` (`id_proveedor`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_compras_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_compras_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `tb_usuarios`
  ADD CONSTRAINT `tb_usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `tb_roles` (`id_rol`) ON UPDATE CASCADE;

ALTER TABLE `tb_ventas`
  ADD CONSTRAINT `tb_ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `tb_ventas_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tb_ajustes_stock`
  ADD CONSTRAINT `tb_ajustes_stock_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_ajustes_stock_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tb_devoluciones`
  ADD CONSTRAINT `tb_devoluciones_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `tb_ventas` (`id_venta`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_devoluciones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tb_devolucion_items`
  ADD CONSTRAINT `tb_devolucion_items_ibfk_1` FOREIGN KEY (`id_devolucion`) REFERENCES `tb_devoluciones` (`id_devolucion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_devolucion_items_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
