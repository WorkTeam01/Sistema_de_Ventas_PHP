<?php

include_once '../../config.php';

$id_venta = $_GET['id_venta'];
$nro_venta = $_GET['nro_venta'];

$pdo->beginTransaction();

$sentencia = $pdo->prepare("DELETE FROM tb_ventas WHERE id_venta = :id_venta");

$sentencia->bindParam('id_venta', $id_venta);

if ($sentencia->execute()) {
    $sentencia2 = $pdo->prepare("DELETE FROM tb_carrito WHERE nro_venta = :nro_venta");
    $sentencia2->bindParam('nro_venta', $nro_venta);
    $sentencia2->execute();

    $pdo->commit();
    session_start();
    $_SESSION['mensaje'] = "Venta eliminada con éxito";
    $_SESSION['icono'] = 'success';
?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas";
    </script>
<?php } else {
    echo "Error al eliminar la venta";
    session_start();
    $_SESSION['error'] = "Error al eliminar la venta";
    $_SESSION['icono'] = "error";
?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas";
    </script>
<?php }
$pdo->rollBack();
