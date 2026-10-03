-- =============================================================
-- Migración 002 — Agregar id_usuario a tb_ventas
-- Registra el vendedor autor de cada venta para scoping RBAC.
-- Aplicar sobre BDs existentes donde la columna no existe.
-- Reejecutable: cada paso comprueba information_schema (sin la cláusula IF NOT EXISTS de ADD COLUMN,
-- que MySQL 8.4 no soporta).
-- Reversible: ALTER TABLE tb_ventas DROP FOREIGN KEY tb_ventas_ibfk_3, DROP COLUMN id_usuario;
-- =============================================================

SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_ventas'
                 AND `COLUMN_NAME` = 'id_usuario') > 0,
              'DO 0',
              'ALTER TABLE `tb_ventas` ADD COLUMN `id_usuario` INT(11) DEFAULT NULL AFTER `id_cliente`');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_ventas'
                 AND `CONSTRAINT_NAME` = 'tb_ventas_ibfk_3') > 0,
              'DO 0',
              'ALTER TABLE `tb_ventas` ADD CONSTRAINT `tb_ventas_ibfk_3`
                 FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios`(`id_usuario`)
                 ON DELETE SET NULL ON UPDATE CASCADE');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Las ventas sin id_usuario quedan en NULL.
-- En el sistema, un NULL significa "venta sin vendedor registrado":
--   - Admin puede verlas (tiene permiso view_sales_all).
--   - Vendedor no las ve en su scoping (filtro por id_usuario excluye NULL).
-- Para asignar ventas históricas al vendedor de prueba en entornos de desarrollo:
--   UPDATE tb_ventas SET id_usuario = <id_vendedor> WHERE id_usuario IS NULL;
