<?php

$id_compra_get = $_GET['id'];

$sql_compra = "SELECT co.id_compra, co.nro_compra, co.comprobante, co.precio_compra as precio_compra_producto, co.cantidad, co.fecha_compra, 
                al.*, pro.id_proveedor, pro.nombre_proveedor, pro.celular, pro.telefono, pro.empresa, pro.email, pro.direccion, us.nombres, us.email, cat.nombre_categoria
                FROM tb_compras co
                INNER JOIN tb_almacen al ON co.id_producto = al.id_producto
                INNER JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
                INNER JOIN tb_usuarios us ON co.id_usuario = us.id_usuario
                INNER JOIN tb_categorias cat ON al.id_categoria = cat.id_categoria
                WHERE co.id_compra = '$id_compra_get'";

$query_compra = $pdo->query($sql_compra);
$query_compra->execute();
$compras_dato = $query_compra->fetchAll(PDO::FETCH_ASSOC);

foreach ($compras_dato as $compra_dato) {
    $id_compra = $compra_dato['id_compra'];
    $nro_compra = $compra_dato['nro_compra'];
    $comprobante = $compra_dato['comprobante'];
    $precio_compra = $compra_dato['precio_compra_producto'];
    $cantidad = $compra_dato['cantidad'];
    $fecha_compra = $compra_dato['fecha_compra'];

    $id_producto_tabla = $compra_dato['id_producto'];
    $codigo = $compra_dato['codigo'];
    $nombre_categoria = $compra_dato['nombre_categoria'];
    $nombre_producto = $compra_dato['nombre'];
    $nombre_usuario = $compra_dato['nombres'];
    $descripcion_producto = $compra_dato['descripcion'];
    $stock = $compra_dato['stock'];
    $stock_minimo = $compra_dato['stock_minimo'];
    $stock_maximo = $compra_dato['stock_maximo'];
    $precio_compra_producto = $compra_dato['precio_compra'];
    $precio_venta = $compra_dato['precio_venta'];
    $fecha_ingreso = $compra_dato['fecha_ingreso'];
    $imagen = $compra_dato['imagen'];

    $id_proveedor_tabla = $compra_dato['id_proveedor'];
    $nombre_proveedor_tabla = $compra_dato['nombre_proveedor'];
    $celular_proveedor = $compra_dato['celular'];
    $telefono_proveedor = $compra_dato['telefono'];
    $empresa_proveedor = $compra_dato['empresa'];
    $email_proveedor = $compra_dato['email'];
    $direccion_proveedor = $compra_dato['direccion'];
}
