<?php

$id_venta_get = $_GET['id'];

$sql_ventas = "SELECT v.*, c.nombre_cliente, c.nit_ci_cliente FROM tb_ventas v
                INNER JOIN tb_clientes c on v.id_cliente = c.id_cliente
                WHERE v.id_venta = '$id_venta_get'";

$query_ventas = $pdo->query($sql_ventas);
$query_ventas->execute();
$contador_de_ventas = $query_ventas->rowCount() + 1;
$total_ventas = $query_ventas->rowCount();
$ventas_datos = $query_ventas->fetchAll(PDO::FETCH_ASSOC);

foreach ($ventas_datos as $ventas_dato) {
    $nro_venta = $ventas_dato['nro_venta'];
    $id_cliente = $ventas_dato['id_cliente'];
}
