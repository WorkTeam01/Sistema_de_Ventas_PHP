<?php

$id_rol_get = $_GET['id'];

$sql_roles = "SELECT * FROM tb_roles WHERE id_rol = ?";
$query_roles = $pdo->prepare($sql_roles);
$query_roles->execute([$id_rol_get]);

$roles_datos = $query_roles->fetchAll(PDO::FETCH_ASSOC);

foreach ($roles_datos as $roles_dato) {
    $rol = $roles_dato['rol'];
}
