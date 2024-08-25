<?php

$sql_ventas = "SELECT v.*, c.nombre_cliente, c.nit_ci_cliente
            FROM tb_ventas v
            INNER JOIN tb_clientes c on v.id_cliente = c.id_cliente";

$query_ventas = $pdo->query($sql_ventas);
$query_ventas->execute();
$contador_de_ventas = $query_ventas->rowCount() + 1;
$total_ventas = $query_ventas->rowCount();
$ventas_datos = $query_ventas->fetchAll(PDO::FETCH_ASSOC);
