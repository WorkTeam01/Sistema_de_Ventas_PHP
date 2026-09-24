-- =============================================================
-- Migración 009 — Venta sin cliente (cliente opcional en tb_ventas)
-- Permite registrar ventas sin asociar un cliente (Consumidor final).
-- Aplicar sobre BDs existentes que ya tienen schema.sql ejecutado.
-- Idempotente: re-ejecutar MODIFY con el mismo tipo es estable
-- (no falla ni duplica). La FK tb_ventas_ibfk_1 (id_cliente →
-- tb_clientes ON DELETE NO ACTION) sigue siendo válida con columna
-- nullable.
-- Reversible: ALTER TABLE tb_ventas MODIFY id_cliente int(11) NOT NULL;
-- =============================================================

ALTER TABLE `tb_ventas`
  MODIFY `id_cliente` INT(11) DEFAULT NULL;

-- Las ventas registradas sin cliente quedan en NULL.
-- El sistema las muestra como "Consumidor final" en listado, detalle,
-- dashboard, factura PDF, activity log y reportes.