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

ALTER TABLE `tb_carrito` ADD COLUMN IF NOT EXISTS `precio_unitario` DECIMAL(10,2) DEFAULT NULL AFTER `cantidad`;

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