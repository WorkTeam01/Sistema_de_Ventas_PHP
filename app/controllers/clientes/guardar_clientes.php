<?php

include_once '../../config.php';

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
