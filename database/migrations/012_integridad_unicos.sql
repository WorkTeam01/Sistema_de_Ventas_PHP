-- =============================================================
-- Migración 012 — Unicidad de nro_venta / nro_compra
-- 1. Comprueba que los datos admiten las restricciones; si no, aborta SIN
--    modificar nada e informa las filas.
-- 2. Quita la FK tb_ventas.nro_venta → tb_carrito.nro_venta (apunta a una columna
--    no única; MySQL 8.4 la rechaza). Se localiza por la tabla que referencia,
--    no por su nombre.
-- 3. Crea UNIQUE uq_venta_nro y uq_compra_nro.
-- Reejecutable. Revertir: ALTER TABLE tb_ventas DROP INDEX uq_venta_nro;
--   ALTER TABLE tb_compras DROP INDEX uq_compra_nro;
-- =============================================================

-- Comprobaciones previas (todas antes de cualquier cambio)
SET @dup_ventas = (SELECT GROUP_CONCAT(`nro_venta` ORDER BY `nro_venta` SEPARATOR ',')
                   FROM (SELECT `nro_venta` FROM `tb_ventas` GROUP BY `nro_venta` HAVING COUNT(*) > 1) AS d);
SET @sin_lineas = (SELECT GROUP_CONCAT(v.`nro_venta` ORDER BY v.`nro_venta` SEPARATOR ',')
                   FROM `tb_ventas` v
                   WHERE NOT EXISTS (SELECT 1 FROM `tb_carrito` c WHERE c.`nro_venta` = v.`nro_venta`));
SET @dup_compras = (SELECT GROUP_CONCAT(`nro_compra` ORDER BY `nro_compra` SEPARATOR ',')
                    FROM (SELECT `nro_compra` FROM `tb_compras` GROUP BY `nro_compra` HAVING COUNT(*) > 1) AS d);

SET @abort_msg = NULLIF(CONCAT_WS(' | ',
    CONCAT('nro_venta repetido: ', LEFT(@dup_ventas, 25)),
    CONCAT('venta sin líneas: ', LEFT(@sin_lineas, 25)),
    CONCAT('nro_compra repetido: ', LEFT(@dup_compras, 25))), '');

-- Aborta forzando un error cuyo texto lleva el informe (el servidor trunca el nombre a ~125
-- caracteres; la lista completa la da database/preflight_004.sql). Se evita SIGNAL porque no es
-- preparable y obligaría a crear un procedimiento (privilegio CREATE ROUTINE).
SET @sql = IF(@abort_msg IS NULL, 'DO 0',
              CONCAT('SELECT 1 FROM `012 abortada - ', @abort_msg, '`'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK invertida hacia tb_carrito
SET @fk = (SELECT `CONSTRAINT_NAME` FROM `information_schema`.`KEY_COLUMN_USAGE`
           WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_ventas'
             AND `REFERENCED_TABLE_NAME` = 'tb_carrito' LIMIT 1);
SET @sql = IF(@fk IS NULL, 'DO 0', CONCAT('ALTER TABLE `tb_ventas` DROP FOREIGN KEY `', @fk, '`'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- UNIQUE(nro_venta)
SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`STATISTICS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_ventas'
                 AND `INDEX_NAME` = 'uq_venta_nro') > 0,
              'DO 0', 'ALTER TABLE `tb_ventas` ADD UNIQUE KEY `uq_venta_nro` (`nro_venta`)');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- UNIQUE(nro_compra)
SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`STATISTICS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_compras'
                 AND `INDEX_NAME` = 'uq_compra_nro') > 0,
              'DO 0', 'ALTER TABLE `tb_compras` ADD UNIQUE KEY `uq_compra_nro` (`nro_compra`)');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
