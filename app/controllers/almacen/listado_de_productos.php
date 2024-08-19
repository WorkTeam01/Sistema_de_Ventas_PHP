<?php

$sql_productos = "SELECT al.*, ca.nombre_categoria, us.email, us.nombres
                FROM tb_almacen al
                INNER JOIN tb_categorias ca on al.id_categoria = ca.id_categoria
                INNER JOIN tb_usuarios us on al.id_usuario = us.id_usuario";

$query_productos = $pdo->query($sql_productos);
$query_productos->execute();
$total_productos = $query_productos->rowCount() + 1;
$total_productos_dashboard = $query_productos->rowCount();
$productos_datos = $query_productos->fetchAll(PDO::FETCH_ASSOC);
