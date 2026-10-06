-- =============================================================
-- Migración 013 — Tokens de formulario, intentos de autenticación y sesiones recordadas
-- Aditiva y reejecutable. Solo agrega estructuras; 1.18.3 las ignora.
-- La primera vez anula los tokens de restablecimiento y de "recordarme" guardados en
-- claro en tb_usuarios (los de restablecimiento duran 1 h; quien usaba "recordarme"
-- inicia sesión una vez más). Las ejecuciones siguientes no los vuelven a tocar.
-- Las columnas login_intentos, login_bloqueado_hasta, remember_token y
-- remember_token_expiry quedan sin uso y se eliminan en una migración posterior.
-- Reversible: DROP TABLE tb_intentos_auth, tb_sesiones_recordadas;
--             ALTER TABLE tb_devoluciones DROP COLUMN token_formulario;
-- =============================================================

-- ¿Es la primera ejecución? (la tabla de sesiones recordadas aún no existe)
SET @primera = (SELECT COUNT(*) = 0 FROM `information_schema`.`TABLES`
                WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_sesiones_recordadas');

-- tb_devoluciones.token_formulario (idempotencia del formulario de devolución)
SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_devoluciones'
                 AND `COLUMN_NAME` = 'token_formulario') > 0,
              'DO 0',
              'ALTER TABLE `tb_devoluciones` ADD COLUMN `token_formulario` CHAR(32) NULL DEFAULT NULL AFTER `monto`');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`STATISTICS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_devoluciones'
                 AND `INDEX_NAME` = 'uq_devolucion_token') > 0,
              'DO 0',
              'ALTER TABLE `tb_devoluciones` ADD UNIQUE KEY `uq_devolucion_token` (`token_formulario`)');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

-- Misma longitud que tb_usuarios.email; una tabla creada con 150 se ensancha aquí.
ALTER TABLE `tb_intentos_auth` MODIFY `correo` varchar(250) NOT NULL;

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

UPDATE `tb_usuarios`
   SET `reset_token` = NULL, `reset_token_expiracion` = NULL,
       `remember_token` = NULL, `remember_token_expiry` = NULL
 WHERE @primera = 1;
