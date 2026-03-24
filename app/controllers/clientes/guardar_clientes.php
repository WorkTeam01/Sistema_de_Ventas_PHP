<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$nombre_cliente = $_POST['nombre_cliente'];
$nit_ci_cliente = $_POST['nit_ci_cliente'];
$celular_cliente = $_POST['celular_cliente'];
$email_cliente = $_POST['email_cliente'];

$sentencia = $pdo->prepare("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente, fyh_creacion) 
    VALUES  (:nombre_cliente, :nit_ci_cliente, :celular_cliente, :email_cliente, :fyh_creacion)");

$sentencia->bindParam('nombre_cliente', $nombre_cliente);
$sentencia->bindParam('nit_ci_cliente', $nit_ci_cliente);
$sentencia->bindParam('celular_cliente', $celular_cliente);
$sentencia->bindParam('email_cliente', $email_cliente);
$sentencia->bindParam('fyh_creacion', $fechaHora);

if ($sentencia->execute()) { ?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas/create.php";
    </script>
<?php
} else {
    session_start();
    $_SESSION['mensaje'] = 'Error al crear la compra';
    $_SESSION['icono'] = 'error';
?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas/create.php";
    </script>
<?php
}
