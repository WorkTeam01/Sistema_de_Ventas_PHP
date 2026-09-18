-- =============================================================
-- Migración 007 — Devoluciones de ventas
-- Añade las tablas de cabecera y detalle, y el permiso
-- manage_returns para Administrador y Vendedor.
-- Aplicar sobre BDs existentes que ya tienen schema.sql ejecutado.
-- Idempotente: no duplica tablas, permisos ni asignaciones.
-- =============================================================

CREATE TABLE IF NOT EXISTS `tb_devoluciones` (
  `id_devolucion`  int(11)        NOT NULL AUTO_INCREMENT,
  `nro_devolucion` int(11)        NOT NULL,
  `id_venta`       int(11)        NOT NULL,
  `id_usuario`     int(11)        DEFAULT NULL,
  `motivo`         varchar(255)   NOT NULL,
  `monto`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `fyh_creacion`   datetime       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_devolucion`),
  UNIQUE KEY `uq_devolucion_nro` (`nro_devolucion`),
  KEY `idx_devolucion_venta` (`id_venta`),
  KEY `idx_devolucion_usuario` (`id_usuario`),
  CONSTRAINT `tb_devoluciones_ibfk_1`
    FOREIGN KEY (`id_venta`) REFERENCES `tb_ventas` (`id_venta`)
    ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `tb_devoluciones_ibfk_2`
    FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

CREATE TABLE IF NOT EXISTS `tb_devolucion_items` (
  `id_detalle`      int(11)        NOT NULL AUTO_INCREMENT,
  `id_devolucion`   int(11)        NOT NULL,
  `id_producto`     int(11)        NOT NULL,
  `cantidad`        int(11)        NOT NULL,
  `precio_unitario` DECIMAL(10,2)  NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_dev_item_devolucion` (`id_devolucion`),
  KEY `idx_dev_item_producto` (`id_producto`),
  CONSTRAINT `tb_devolucion_items_ibfk_1`
    FOREIGN KEY (`id_devolucion`) REFERENCES `tb_devoluciones` (`id_devolucion`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tb_devolucion_items_ibfk_2`
    FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`)
    ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

INSERT INTO `tb_permisos` (`clave`, `descripcion`, `modulo`)
SELECT 'manage_returns', 'Gestionar devoluciones de ventas', 'ventas'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_permisos` WHERE `clave` = 'manage_returns'
);

-- Administrador: todos los permisos, incluido manage_returns.
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r CROSS JOIN `tb_permisos` p
WHERE r.rol = 'Administrador'
  AND p.clave = 'manage_returns'
  AND NOT EXISTS (
    SELECT 1
    FROM `tb_rol_permiso` rp
    WHERE rp.id_rol = r.id_rol
      AND rp.id_permiso = p.id_permiso
  );

-- Vendedor: permiso para registrar y consultar devoluciones.
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave = 'manage_returns'
WHERE r.rol = 'Vendedor'
  AND NOT EXISTS (
    SELECT 1
    FROM `tb_rol_permiso` rp
    WHERE rp.id_rol = r.id_rol
      AND rp.id_permiso = p.id_permiso
  );
