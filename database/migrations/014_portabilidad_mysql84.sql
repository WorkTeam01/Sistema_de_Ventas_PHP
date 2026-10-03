-- =============================================================
-- Migración 014 — tb_almacen.imagen de TEXT con DEFAULT literal a VARCHAR(255)
-- MySQL 8.4 rechaza un DEFAULT literal en TEXT. Antes de convertir comprueba que
-- ninguna ruta supere 255 caracteres: si alguna lo hace aborta SIN modificar nada e
-- informa los id_producto (en modo no estricto el MODIFY truncaría en silencio).
-- Reejecutable: solo actúa mientras la columna siga siendo TEXT.
-- Reversible: ALTER TABLE tb_almacen MODIFY imagen text NOT NULL DEFAULT 'producto_default.png';
-- =============================================================

SET @es_text = (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
                WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_almacen'
                  AND `COLUMN_NAME` = 'imagen' AND `DATA_TYPE` = 'text');

SET @largas = IF(@es_text = 1,
                 (SELECT GROUP_CONCAT(`id_producto` ORDER BY `id_producto` SEPARATOR ',')
                  FROM `tb_almacen` WHERE CHAR_LENGTH(`imagen`) > 255),
                 NULL);

-- Aborta con un error cuyo texto lleva el informe (ver nota de la migración 012).
SET @sql = IF(@largas IS NULL, 'DO 0',
              CONCAT('SELECT 1 FROM `014 abortada - imagen de mas de 255 caracteres, id_producto: ',
                     LEFT(@largas, 40), '`'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@es_text = 1,
              "ALTER TABLE `tb_almacen` MODIFY `imagen` VARCHAR(255) NOT NULL DEFAULT 'producto_default.png'",
              'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
