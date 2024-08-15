<?php

$sql_proveedores = "SELECT * FROM tb_proveedores";
$query_proveedores = $pdo->query($sql_proveedores);
$query_proveedores->execute();
$proveedores_datos = $query_proveedores->fetchAll(PDO::FETCH_ASSOC);
