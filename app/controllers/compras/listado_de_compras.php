<?php

$sql_compras = "SELECT co.id_compra, co.nro_compra, co.comprobante, co.precio_compra, co.cantidad, co.fecha_compra, 
                al.*, pro.nombre_proveedor, pro.celular, pro.telefono, pro.empresa, pro.email, pro.direccion, us.nombres, us.email, cat.nombre_categoria
                FROM tb_compras co
                INNER JOIN tb_almacen al ON co.id_producto = al.id_producto
                INNER JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
                INNER JOIN tb_usuarios us ON co.id_usuario = us.id_usuario
                INNER JOIN tb_categorias cat ON al.id_categoria = cat.id_categoria";

$query_compras = $pdo->query($sql_compras);
$query_compras->execute();
$contador_de_compras = $query_compras->rowCount() + 1;
$compras_datos = $query_compras->fetchAll(PDO::FETCH_ASSOC);
