<?php

$sql_roles = "SELECT * FROM tb_roles";
$query_roles = $pdo->query($sql_roles);
$query_roles->execute();
$total_roles = $query_roles->rowCount();
$roles_datos = $query_roles->fetchAll(PDO::FETCH_ASSOC);
