-- Comprobación previa al despliegue 004 (solo lectura: únicamente SELECT).
-- Correr en producción ANTES de las migraciones 011-014 y resolver a mano lo que informe.
-- Una BD lista para migrar devuelve cero filas.
--   mysql -u USUARIO -p NOMBRE_BD < database/preflight_004.sql

SELECT 'nro_venta repetido' AS comprobacion,
       GROUP_CONCAT(nro_venta ORDER BY nro_venta SEPARATOR ',') AS valores
FROM (SELECT nro_venta FROM tb_ventas GROUP BY nro_venta HAVING COUNT(*) > 1) AS d
HAVING COUNT(*) > 0
UNION ALL
SELECT 'venta sin lineas en tb_carrito',
       GROUP_CONCAT(v.nro_venta ORDER BY v.nro_venta SEPARATOR ',')
FROM tb_ventas v
WHERE NOT EXISTS (SELECT 1 FROM tb_carrito c WHERE c.nro_venta = v.nro_venta)
HAVING COUNT(*) > 0
UNION ALL
SELECT 'nro_compra repetido',
       GROUP_CONCAT(nro_compra ORDER BY nro_compra SEPARATOR ',')
FROM (SELECT nro_compra FROM tb_compras GROUP BY nro_compra HAVING COUNT(*) > 1) AS d
HAVING COUNT(*) > 0
UNION ALL
SELECT 'imagen de mas de 255 caracteres (id_producto)',
       GROUP_CONCAT(id_producto ORDER BY id_producto SEPARATOR ',')
FROM tb_almacen
WHERE CHAR_LENGTH(imagen) > 255
HAVING COUNT(*) > 0
UNION ALL
SELECT 'carrito abierto sin venta (nro_venta)',
       GROUP_CONCAT(DISTINCT c.nro_venta ORDER BY c.nro_venta SEPARATOR ',')
FROM tb_carrito c
WHERE NOT EXISTS (SELECT 1 FROM tb_ventas v WHERE v.nro_venta = c.nro_venta)
HAVING COUNT(*) > 0;
