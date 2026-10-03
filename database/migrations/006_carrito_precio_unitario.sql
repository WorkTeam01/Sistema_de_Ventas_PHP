-- =============================================================
-- Migración 006 — Precio histórico por línea de venta
-- Añade tb_carrito.precio_unitario y hace el backfill idempotente
-- de las líneas de ventas ya finalizadas con el precio de catálogo
-- actual del producto como mejor aproximación disponible.
-- Aplicar sobre BDs existentes que ya tienen schema.sql previo.
-- Idempotente: relanzarlo solo toca filas NULL de ventas finalizadas;
-- las ya pobladas no se pisan. NO recalcula tb_ventas.total_pagado.
-- Reversible: ALTER TABLE tb_carrito DROP COLUMN precio_unitario;
-- =============================================================

SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_carrito'
                 AND `COLUMN_NAME` = 'precio_unitario') > 0,
              'DO 0',
              'ALTER TABLE `tb_carrito` ADD COLUMN `precio_unitario` DECIMAL(10,2) DEFAULT NULL AFTER `cantidad`');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Solo filas de ventas finalizadas y aún sin precio (idempotente)
UPDATE `tb_carrito`
SET `precio_unitario` = (SELECT `precio_venta` FROM `tb_almacen`
                         WHERE `id_producto` = `tb_carrito`.`id_producto`)
WHERE `precio_unitario` IS NULL
  AND `nro_venta` IN (SELECT `nro_venta` FROM `tb_ventas`);

-- Defensa: producto sin fila (no debería ocurrir por la FK) → 0.00
UPDATE `tb_carrito`
SET `precio_unitario` = 0.00
WHERE `precio_unitario` IS NULL
  AND `nro_venta` IN (SELECT `nro_venta` FROM `tb_ventas`);