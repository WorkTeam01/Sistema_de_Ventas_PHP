<?php

$sql_clientes = "SELECT * FROM tb_clientes";

$query_clientes = $pdo->query($sql_clientes);
$query_clientes->execute();
$total_clientes = $query_clientes->rowCount();
$clientes_datos = $query_clientes->fetchAll(PDO::FETCH_ASSOC);

foreach ($clientes_datos as $clientes_dato) {
    $id_cliente = $clientes_dato['id_cliente'];
}
