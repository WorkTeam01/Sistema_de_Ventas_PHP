<?php

$id_compra_get = $_GET['id'];

$sql_compra = "SELECT co.*, al.nombre, pro.nombre_proveedor, us.nombres
                FROM tb_compras co
                LEFT JOIN tb_almacen al ON co.id_producto = al.id_producto
                LEFT JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
                LEFT JOIN tb_usuarios us ON co.id_usuario = us.id_usuario
                WHERE co.id_compra = '$id_compra_get'";

$query_compra = $pdo->query($sql_compra);
$query_compra->execute();
$total_compras = $query_compra->rowCount();
$compras_dato = $query_compra->fetchAll(PDO::FETCH_ASSOC);

foreach ($compras_dato as $compra_dato) {
    $producto = $compra_dato['nombre'];
    $nro_compra = $compra_dato['nro_compra'];
    $fecha_compra = $compra_dato['fecha_compra'];
    $nombre_proveedor = $compra_dato['nombre_proveedor'];
    $comprobante = $compra_dato['comprobante'];
    $nombres = $compra_dato['nombres'];
    $precio_compra = $compra_dato['precio_compra'];
    $cantidad = $compra_dato['precio_compra'];
    $id_compra = $compra_dato['id_compra'];
}
