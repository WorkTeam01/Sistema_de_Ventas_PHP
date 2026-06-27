-- =============================================================
-- Migración 001 — Permisos granulares (RBAC)
-- Fase 1: crear tb_permisos + tb_rol_permiso y sembrar los 24 permisos.
-- Aplicar sobre BDs existentes que ya tienen schema.sql ejecutado.
-- Reversible: DROP TABLE tb_rol_permiso, tb_permisos;
-- =============================================================

CREATE TABLE IF NOT EXISTS `tb_permisos` (
  `id_permiso`   INT(11)      NOT NULL AUTO_INCREMENT,
  `clave`        VARCHAR(60)  NOT NULL,
  `descripcion`  VARCHAR(150) NOT NULL,
  `modulo`       VARCHAR(40)  NOT NULL,
  `fyh_creacion` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permiso_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_rol_permiso` (
  `id_rol`     INT(11) NOT NULL,
  `id_permiso` INT(11) NOT NULL,
  PRIMARY KEY (`id_rol`, `id_permiso`),
  CONSTRAINT `fk_rp_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `tb_roles`(`id_rol`)     ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `tb_permisos`(`id_permiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permisos
INSERT INTO `tb_permisos` (`clave`, `descripcion`, `modulo`) VALUES
('is_superadmin',            'Rol superusuario del sistema',                 'sistema'),
('view_dashboard',           'Ver dashboard',                                'dashboard'),
('manage_users',             'Gestionar usuarios',                           'usuarios'),
('manage_roles',             'Gestionar roles',                              'roles'),
('view_categories',          'Ver categorías',                               'categorias'),
('manage_categories',        'Gestionar categorías',                         'categorias'),
('view_suppliers',           'Ver proveedores',                              'proveedores'),
('manage_suppliers',         'Gestionar proveedores',                        'proveedores'),
('view_clients',             'Ver clientes',                                 'clientes'),
('manage_clients',           'Gestionar clientes',                           'clientes'),
('view_products',            'Ver productos',                                'productos'),
('manage_products',          'Gestionar productos',                          'productos'),
('view_purchases',           'Ver compras',                                  'compras'),
('manage_purchases',         'Gestionar compras',                            'compras'),
('view_sales',               'Ver ventas',                                   'ventas'),
('manage_sales',             'Gestionar ventas',                             'ventas'),
('view_sales_all',           'Ver todas las ventas (sin filtro de usuario)', 'ventas'),
('view_reports',             'Ver sección de reportes',                      'reportes'),
('view_sales_report',        'Ver reporte de ventas',                        'reportes'),
('view_purchases_report',    'Ver reporte de compras',                       'reportes'),
('view_top_products_report', 'Ver reporte de top productos',                 'reportes'),
('view_clients_report',      'Ver reporte de clientes',                      'reportes'),
('view_activity_log',        'Ver log de actividad',                         'auditoria'),
('manage_inventory',         'Ajustar inventario',                           'inventario');

-- Administrador: todos los permisos (incluyendo is_superadmin)
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r CROSS JOIN `tb_permisos` p
WHERE r.rol = 'Administrador';

-- Vendedor
INSERT INTO `tb_rol_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM `tb_roles` r JOIN `tb_permisos` p ON p.clave IN (
    'view_dashboard',
    'view_clients', 'manage_clients',
    'view_products',
    'view_sales', 'manage_sales',
    'view_reports', 'view_sales_report', 'view_top_products_report'
)
WHERE r.rol = 'Vendedor';

-- Comprador
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
