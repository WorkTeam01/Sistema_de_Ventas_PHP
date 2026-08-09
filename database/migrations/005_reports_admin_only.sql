-- =============================================================
-- Migración 005 — Reportes exclusivos de Administrador
-- Vendedor tenía sembrados view_reports, view_sales_report y
-- view_top_products_report desde la migración 001; se retiran para
-- que la sección de Reportes quede restringida solo a Administrador.
-- Aplicar sobre BDs existentes que ya tienen la migración 001.
-- Reversible: volver a insertar las filas eliminadas para el rol Vendedor.
-- =============================================================

DELETE rp FROM `tb_rol_permiso` rp
JOIN `tb_roles` r ON r.id_rol = rp.id_rol
JOIN `tb_permisos` p ON p.id_permiso = rp.id_permiso
WHERE r.rol = 'Vendedor'
  AND p.clave IN ('view_reports', 'view_sales_report', 'view_top_products_report');
