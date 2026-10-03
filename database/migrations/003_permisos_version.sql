-- =============================================================
-- Migración 003 — Agregar permisos_version a tb_roles
-- Contador que se incrementa al sincronizar los permisos de un rol,
-- usado por Auth::check() para invalidar el caché de $_SESSION['permisos']
-- de los usuarios activos sin requerir re-login.
-- Aplicar sobre BDs existentes donde la columna no existe.
-- Reejecutable: comprueba information_schema (sin la cláusula IF NOT EXISTS de ADD COLUMN,
-- que MySQL 8.4 no soporta).
-- Reversible: ALTER TABLE tb_roles DROP COLUMN permisos_version;
-- =============================================================

SET @sql = IF((SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
               WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tb_roles'
                 AND `COLUMN_NAME` = 'permisos_version') > 0,
              'DO 0',
              'ALTER TABLE `tb_roles` ADD COLUMN `permisos_version` INT(11) NOT NULL DEFAULT 0 AFTER `rol`');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
