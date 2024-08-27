<?php

$sql_clientes = "SELECT * FROM tb_clientes WHERE id_cliente = '$id_cliente'";

$query_clientes = $pdo->query($sql_clientes);
$query_clientes->execute();
$total_clientes = $query_clientes->rowCount();
$clientes_datos = $query_clientes->fetchAll(PDO::FETCH_ASSOC);

foreach ($clientes_datos as $clientes_dato) {
    $id_cliente = $clientes_dato['id_cliente'];
    $nombre_cliente = $clientes_dato['nombre_cliente'];
    $nit_ci_cliente = $clientes_dato['nit_ci_cliente'];
    $celular_cliente = $clientes_dato['celular_cliente'];
    $email_cliente = $clientes_dato['celular_cliente'];
}
