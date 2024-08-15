<?php

include_once '../../config.php';

$nombre_categoria = $_GET['nombre_categoria'];

$sentencia = $pdo->prepare("INSERT INTO tb_categorias
    (nombre_categoria, fyh_creacion) VALUES (:nombre_categoria, :fyh_creacion)");

$sentencia->bindParam('nombre_categoria', $nombre_categoria);
$sentencia->bindParam('fyh_creacion', $fechaHora);

if ($sentencia->execute()) {
    session_start();
    $_SESSION['mensaje'] = "La categoria se ha agregado correctamente";
    $_SESSION['icono'] = "success";
?>

    <script>
        location.href = "<?php echo $URL; ?>/categorias";
    </script>

<?php
} else {
    session_start();
    $_SESSION['mensaje'] = "Erro al agregar categoria";
    $_SESSION['icono'] = "error";
}
