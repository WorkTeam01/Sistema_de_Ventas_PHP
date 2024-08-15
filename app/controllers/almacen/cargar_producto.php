<?php

$id_producto_get = $_GET['id'];

$sql_producto = "SELECT *, ca.nombre_categoria, us.email
                FROM tb_almacen al
                INNER JOIN tb_categorias ca on al.id_categoria = ca.id_categoria
                INNER JOIN tb_usuarios us on al.id_usuario = us.id_usuario
                WHERE al.id_producto = '$id_producto_get'";

$query_producto = $pdo->query($sql_producto);
$query_producto->execute();
$total_productos = $query_producto->rowCount() + 1;
$productos_dato = $query_producto->fetchAll(PDO::FETCH_ASSOC);

foreach ($productos_dato as $producto_dato) {
    $codigo = $producto_dato['codigo'];
    $categoria = $producto_dato['nombre_categoria'];
    $nombre = $producto_dato['nombre'];
    $usuario = $producto_dato['email'];
    $descripcion = $producto_dato['descripcion'];
    $stock = $producto_dato['stock'];
    $stock_minimo = $producto_dato['stock_minimo'];
    $stock_maximo = $producto_dato['stock_maximo'];
    $precio_compra = $producto_dato['precio_compra'];
    $precio_venta = $producto_dato['precio_venta'];
    $fecha_ingreso = $producto_dato['fecha_ingreso'];
    $imagen = $producto_dato['imagen'];
    $id_usuario = $producto_dato['id_usuario'];
}
