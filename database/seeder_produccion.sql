-- =============================================================
-- Seeder de producción — Sistema de Ventas
-- Solo el catálogo que la aplicación necesita para funcionar: roles, permisos,
-- permisos por rol y métodos de pago. Sin datos demo ni usuarios.
-- Ejecutar UNA vez, sobre una base recién creada con database/schema.sql.
-- El primer administrador se crea aparte (docs/despliegue.md § Base de datos).
-- =============================================================

-- -------------------------------------------------------------
-- tb_roles
-- Los nombres deben coincidir exactamente con los valores que
-- verifica AuthMiddleware y el sidebar.
-- -------------------------------------------------------------
INSERT INTO `tb_roles` (`id_rol`, `rol`) VALUES
(1, 'Administrador'),
(2, 'Vendedor'),
(3, 'Comprador');

-- -------------------------------------------------------------
-- tb_permisos (27 permisos del sistema RBAC)
-- -------------------------------------------------------------
INSERT INTO `tb_permisos` (`clave`, `descripcion`, `modulo`) VALUES
('is_superadmin',            'Rol superusuario del sistema',             'sistema'),
('view_dashboard',           'Ver dashboard',                            'dashboard'),
('manage_users',             'Gestionar usuarios',                       'usuarios'),
('manage_roles',             'Gestionar roles',                          'roles'),
('view_categories',          'Ver categorías',                           'categorias'),
('manage_categories',        'Gestionar categorías',                     'categorias'),
('view_suppliers',           'Ver proveedores',                          'proveedores'),
('manage_suppliers',         'Gestionar proveedores',                    'proveedores'),
('view_clients',             'Ver clientes',                             'clientes'),
('manage_clients',           'Gestionar clientes',                       'clientes'),
('view_products',            'Ver productos',                            'productos'),
('manage_products',          'Gestionar productos',                      'productos'),
('view_purchases',           'Ver compras',                              'compras'),
('manage_purchases',         'Gestionar compras',                        'compras'),
('view_sales',               'Ver ventas',                               'ventas'),
('manage_sales',             'Gestionar ventas',                         'ventas'),
('view_sales_all',           'Ver todas las ventas (sin filtro de usuario)', 'ventas'),
('view_purchases_all',       'Ver todas las compras (sin filtro de usuario)', 'compras'),
('view_reports',             'Ver sección de reportes',                  'reportes'),
('view_sales_report',        'Ver reporte de ventas',                    'reportes'),
('view_purchases_report',    'Ver reporte de compras',                   'reportes'),
('view_top_products_report', 'Ver reporte de top productos',             'reportes'),
('view_clients_report',      'Ver reporte de clientes',                  'reportes'),
('view_activity_log',        'Ver log de actividad',                     'auditoria'),
('manage_inventory',         'Ajustar inventario',                       'inventario'),
('manage_returns',           'Gestionar devoluciones de ventas',         'ventas'),
('manage_payment_methods',   'Gestionar métodos de pago',                'ventas');

-- -------------------------------------------------------------
-- tb_rol_permiso — Administrador (todos los permisos, incluyendo is_superadmin)
-- Única línea en el sistema que referencia el nombre 'Administrador'.
-- Tras ejecutar este seeder, el sistema opera 100% por permisos.
-- -------------------------------------------------------------
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r CROSS JOIN `tb_permisos` p
WHERE r.rol = 'Administrador';

-- -------------------------------------------------------------
-- tb_rol_permiso — Vendedor
-- -------------------------------------------------------------
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave IN (
    'view_dashboard',
    'view_categories',
    'view_clients', 'manage_clients',
    'view_products',
    'view_sales', 'manage_sales',
    'manage_returns'
)
WHERE r.rol = 'Vendedor';

-- -------------------------------------------------------------
-- tb_rol_permiso — Comprador
-- -------------------------------------------------------------
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave IN (
    'view_dashboard',
    'view_categories', 'manage_categories',
    'view_suppliers', 'manage_suppliers',
    'view_products', 'manage_products',
    'view_purchases', 'manage_purchases'
)
WHERE r.rol = 'Comprador';

-- -------------------------------------------------------------
-- tb_metodos_pago — catálogo sembrado (los 4 métodos por defecto)
-- El grant a Administrador ya ocurre por el CROSS JOIN de permisos.
-- -------------------------------------------------------------
INSERT INTO `tb_metodos_pago` (`id_metodo_pago`, `nombre`, `tipo`) VALUES
(1, 'Efectivo',              'efectivo'),
(2, 'Tarjeta',               'no_efectivo'),
(3, 'Transferencia bancaria', 'no_efectivo'),
(4, 'QR',                    'no_efectivo');
