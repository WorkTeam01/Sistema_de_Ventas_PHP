-- =============================================================
-- Migración 011 — Reserva del número de venta del POS
-- Cada vendedor obtiene su nro_venta de un AUTO_INCREMENT propio, así que
-- dos vendedores nunca reciben el mismo número ni comparten carrito.
-- Reejecutable. Solo agrega una tabla; 1.18.3 la ignora.
-- Reversible: DROP TABLE tb_pos_reservas;
-- =============================================================

CREATE TABLE IF NOT EXISTS `tb_pos_reservas` (
  `id_reserva`     int(11)  NOT NULL AUTO_INCREMENT,
  `id_usuario`     int(11)  NOT NULL,
  `fyh_creacion`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fyh_liberacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  KEY `idx_reserva_usuario` (`id_usuario`, `fyh_liberacion`),
  CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Arranque de la secuencia por encima de todo nro_venta ya usado (ventas y carritos
-- abiertos). Solo si la tabla está vacía y existe algún usuario.
INSERT INTO `tb_pos_reservas` (`id_reserva`, `id_usuario`, `fyh_liberacion`)
SELECT m.mx, u.uid, NOW()
FROM (SELECT GREATEST(
        IFNULL((SELECT MAX(`nro_venta`) FROM `tb_ventas`), 0),
        IFNULL((SELECT MAX(`nro_venta`) FROM `tb_carrito`), 0)) AS mx) AS m,
     (SELECT MIN(`id_usuario`) AS uid FROM `tb_usuarios`) AS u
WHERE m.mx > 0
  AND u.uid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tb_pos_reservas`);
