-- =============================================================
-- Migración 011 — Reserva del número de venta del POS
-- Cada vendedor obtiene su nro_venta de un AUTO_INCREMENT propio, así que
-- dos vendedores nunca reciben el mismo número ni comparten carrito.
-- id_usuario es NULL tras borrar al usuario (ON DELETE SET NULL): la fila se conserva
-- para que la secuencia nunca retroceda.
-- Reejecutable: corrige la FK de una tabla ya creada y vuelve a subir la secuencia por
-- encima de todo nro_venta usado, también tras volver temporalmente a 1.18.3.
-- Solo agrega una tabla; 1.18.3 la ignora.
-- Reversible: DROP TABLE tb_pos_reservas;
-- =============================================================

CREATE TABLE IF NOT EXISTS `tb_pos_reservas` (
  `id_reserva`     int(11)  NOT NULL AUTO_INCREMENT,
  `id_usuario`     int(11)  DEFAULT NULL,
  `fyh_creacion`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_liberacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  KEY `idx_reserva_usuario` (`id_usuario`, `fyh_liberacion`),
  CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabla creada con la FK sin ON DELETE: se reemplaza por la que anula el usuario borrado.
SET @fk = (SELECT `CONSTRAINT_NAME` FROM `information_schema`.`REFERENTIAL_CONSTRAINTS`
           WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_pos_reservas'
             AND `REFERENCED_TABLE_NAME` = 'tb_usuarios' AND `DELETE_RULE` <> 'SET NULL' LIMIT 1);
SET @sql = IF(@fk IS NULL, 'DO 0', CONCAT('ALTER TABLE `tb_pos_reservas` DROP FOREIGN KEY `', @fk, '`'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `tb_pos_reservas` MODIFY `id_usuario` int(11) DEFAULT NULL;

SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`REFERENTIAL_CONSTRAINTS`
               WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_pos_reservas'
                 AND `REFERENCED_TABLE_NAME` = 'tb_usuarios') > 0,
              'DO 0',
              'ALTER TABLE `tb_pos_reservas` ADD CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fila de arranque, liberada y sin usuario: la secuencia queda por encima de todo nro_venta ya
-- usado (ventas y carritos abiertos). Se agrega solo si el máximo supera al de las reservas, que
-- ocurre al migrar y cuando 1.18.3 (que numera con MAX+1 e ignora esta tabla) vendió por encima.
INSERT INTO `tb_pos_reservas` (`id_reserva`, `id_usuario`, `fyh_liberacion`)
SELECT m.mx, NULL, NOW()
FROM (SELECT GREATEST(
        IFNULL((SELECT MAX(`nro_venta`) FROM `tb_ventas`), 0),
        IFNULL((SELECT MAX(`nro_venta`) FROM `tb_carrito`), 0)) AS mx) AS m
WHERE m.mx > IFNULL((SELECT MAX(`id_reserva`) FROM `tb_pos_reservas`), 0);
