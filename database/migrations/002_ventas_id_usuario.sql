-- =============================================================
-- Migración 002 — Agregar id_usuario a tb_ventas
-- Registra el vendedor autor de cada venta para scoping RBAC.
-- Aplicar sobre BDs existentes donde la columna no existe.
-- Reversible: ALTER TABLE tb_ventas DROP FOREIGN KEY tb_ventas_ibfk_3, DROP COLUMN id_usuario;
-- =============================================================

-- Agregar columna si no existe (idempotente)
ALTER TABLE `tb_ventas`
  ADD COLUMN IF NOT EXISTS `id_usuario` INT(11) DEFAULT NULL AFTER `id_cliente`,
  ADD CONSTRAINT `tb_ventas_ibfk_3`
    FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios`(`id_usuario`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Las ventas sin id_usuario quedan en NULL.
-- En el sistema, un NULL significa "venta sin vendedor registrado":
--   - Admin puede verlas (tiene permiso view_sales_all).
--   - Vendedor no las ve en su scoping (filtro por id_usuario excluye NULL).
-- Para asignar ventas históricas al vendedor de prueba en entornos de desarrollo:
--   UPDATE tb_ventas SET id_usuario = <id_vendedor> WHERE id_usuario IS NULL;
