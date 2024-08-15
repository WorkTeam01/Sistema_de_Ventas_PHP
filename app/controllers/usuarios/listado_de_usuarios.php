<?php

$sql_usuarios = "SELECT us.id_usuario, us.nombres, us.email, rol.rol FROM tb_usuarios us
                INNER JOIN tb_roles rol on us.id_rol = rol.id_rol";
$query_usuarios = $pdo->query($sql_usuarios);
$query_usuarios->execute();
$total_user = $query_usuarios->rowCount();
$usuarios_datos = $query_usuarios->fetchAll(PDO::FETCH_ASSOC);
