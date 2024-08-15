<?php

$sql_compras = "SELECT co.*, al.nombre, pro.nombre_proveedor, us.nombres
                FROM tb_compras co
                LEFT JOIN tb_almacen al ON co.id_producto = al.id_producto
                LEFT JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
                LEFT JOIN tb_usuarios us ON co.id_usuario = us.id_usuario
                GROUP BY co.id_usuario";

$query_compras = $pdo->query($sql_compras);
$query_compras->execute();
$total_compras = $query_compras->rowCount();
$compras_datos = $query_compras->fetchAll(PDO::FETCH_ASSOC);
