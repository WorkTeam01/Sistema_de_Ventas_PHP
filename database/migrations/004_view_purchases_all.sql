-- =============================================================
-- Migración 004 — Permiso view_purchases_all
-- Analogía de view_sales_all para compras: solo quien lo tenga ve
-- las compras de todos los usuarios; el resto ve solo las propias.
-- Aplicar sobre BDs existentes que ya tienen la migración 001.
-- Reversible: DELETE FROM tb_permisos WHERE clave = 'view_purchases_all';
-- =============================================================

INSERT INTO `tb_permisos` (`clave`, `descripcion`, `modulo`) VALUES
('view_purchases_all', 'Ver todas las compras (sin filtro de usuario)', 'compras');

-- Administrador ya recibe todos los permisos vía CROSS JOIN en la migración 001,
-- pero se agrega explícitamente por si esa migración ya fue aplicada antes de esta.
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave = 'view_purchases_all'
WHERE r.rol = 'Administrador'
  AND NOT EXISTS (
    SELECT 1 FROM `tb_rol_permiso` rp
    WHERE rp.id_rol = r.id_rol AND rp.id_permiso = p.id_permiso
  );
