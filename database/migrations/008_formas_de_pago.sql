-- =============================================================
-- Migración 008 — Formas de pago + pago mixto
-- Crea las tablas de catálogo y líneas de pago, siembra los
-- 4 métodos por defecto, añade el permiso manage_payment_methods
-- al rol Administrador y ejecuta el backfill idempotente de
-- líneas de pago para ventas históricas (opcional, documentado).
-- Aplicar sobre BDs existentes que ya tienen schema.sql ejecutado.
-- Idempotente: no duplica tablas, métodos, permisos ni líneas de pago.
-- =============================================================

-- ------------------------------------------------------------
-- 1. Tablas
-- ------------------------------------------------------------
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

CREATE TABLE IF NOT EXISTS `tb_pagos` (
  `id_pago`        int(11)       NOT NULL AUTO_INCREMENT,
  `id_venta`       int(11)       NOT NULL,
  `id_metodo_pago` int(11)       NOT NULL,
  `monto`          DECIMAL(10,2) NOT NULL,
  `referencia`     varchar(100)  DEFAULT NULL,
  `detalle`        varchar(255)  DEFAULT NULL,
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

-- ------------------------------------------------------------
-- 2. Métodos sembrados (FR-4)
-- ------------------------------------------------------------
INSERT INTO `tb_metodos_pago` (`nombre`, `tipo`)
SELECT 'Efectivo', 'efectivo'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_metodos_pago` WHERE `nombre` = 'Efectivo'
);

INSERT INTO `tb_metodos_pago` (`nombre`, `tipo`)
SELECT 'Tarjeta', 'no_efectivo'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_metodos_pago` WHERE `nombre` = 'Tarjeta'
);

INSERT INTO `tb_metodos_pago` (`nombre`, `tipo`)
SELECT 'Transferencia bancaria', 'no_efectivo'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_metodos_pago` WHERE `nombre` = 'Transferencia bancaria'
);

INSERT INTO `tb_metodos_pago` (`nombre`, `tipo`)
SELECT 'QR', 'no_efectivo'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_metodos_pago` WHERE `nombre` = 'QR'
);

-- ------------------------------------------------------------
-- 3. Permiso manage_payment_methods (FR-16)
-- ------------------------------------------------------------
INSERT INTO `tb_permisos` (`clave`, `descripcion`, `modulo`)
SELECT 'manage_payment_methods', 'Gestionar métodos de pago', 'ventas'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_permisos` WHERE `clave` = 'manage_payment_methods'
);

-- Administrador: todos los permisos, incluido manage_payment_methods.
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r CROSS JOIN `tb_permisos` p
WHERE r.rol = 'Administrador'
  AND p.clave = 'manage_payment_methods'
  AND NOT EXISTS (
    SELECT 1
    FROM `tb_rol_permiso` rp
    WHERE rp.id_rol = r.id_rol
      AND rp.id_permiso = p.id_permiso
  );

-- ------------------------------------------------------------
-- 4. Backfill idempotente (FR-15, OPCIONAL)
--    Crea una línea Efectivo con monto = total para toda venta
--    sin líneas de pago. Re-ejecutar no duplica: el WHERE NOT
--    EXISTS salta ventas que ya tienen pagos. Ventas ya pagadas
--    o con líneas existentes quedan intactas.
--    Ejecutar solo si el negocio decide que lo histórico se
--    asume como efectivo.
-- ------------------------------------------------------------
INSERT INTO `tb_pagos` (`id_venta`, `id_metodo_pago`, `monto`)
SELECT v.id_venta, m.id_metodo_pago, v.total_pagado
FROM `tb_ventas` v
JOIN `tb_metodos_pago` m ON m.nombre = 'Efectivo'
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_pagos` p WHERE p.id_venta = v.id_venta
);
