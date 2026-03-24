<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$id_carrito = $_POST['id_carrito'];

$sentencia = $pdo->prepare("DELETE FROM tb_carrito WHERE id_carrito = :id_carrito");

$sentencia->bindParam('id_carrito', $id_carrito);

if ($sentencia->execute()) { ?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas/create.php";
    </script>
<?php } else { ?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas/create.php";
    </script>
<?php
}
