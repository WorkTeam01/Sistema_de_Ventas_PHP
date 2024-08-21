<?php

include_once '../../config.php';

$id_compra_get = $_GET['id_compra'];

$sentencia = $pdo->prepare("DELETE FROM tb_compras WHERE id_compra = :id_compra");

$sentencia->bindParam('id_compra', $id_compra_get);

if ($sentencia->execute()) {
    session_start();
    $_SESSION['mensaje'] = "La compra se ha eliminado correctamente";
    $_SESSION['icono'] = "success";
?>
    <script>
        location.href = "<?php echo $URL; ?>/compras";
    </script>
<?php } else {
    session_start();
    $_SESSION['mensaje'] = "Error al eliminar la compra";
    $_SESSION['icono'] = "error";
?>
    <script>
        location.href = "<?php echo $URL; ?>/compras";
    </script>
<?php
}
