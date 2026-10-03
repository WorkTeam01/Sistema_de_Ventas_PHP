-- =============================================================
-- Migración 003 — Agregar permisos_version a tb_roles
-- Contador que se incrementa al sincronizar los permisos de un rol,
-- usado por Auth::check() para invalidar el caché de $_SESSION['permisos']
-- de los usuarios activos sin requerir re-login.
-- Aplicar sobre BDs existentes donde la columna no existe.
-- Reversible: ALTER TABLE tb_roles DROP COLUMN permisos_version;
-- =============================================================

ALTER TABLE `tb_roles`
  ADD COLUMN IF NOT EXISTS `permisos_version` INT(11) NOT NULL DEFAULT 0 AFTER `rol`;
