<?php

include_once '../../config.php';

$id_compra_get = $_GET['id_compra'];
$id_producto_get = $_GET['id_producto'];
$cantidad_compra = $_GET['cantidad_compra'];
$stock_actual = $_GET['stock_actual'];

// echo $id_compra_get . " - " . $id_producto_get . " - " . $cantidad_compra . " - " . $stock_actual;

$pdo->beginTransaction();

$sentencia = $pdo->prepare("DELETE FROM tb_compras WHERE id_compra = :id_compra");

$sentencia->bindParam('id_compra', $id_compra_get);

if ($sentencia->execute()) {

    // Actualizar el stock desde la compra
    $stock = $stock_actual - $cantidad_compra;
    $sentencia = $pdo->prepare("UPDATE tb_almacen SET stock = :stock WHERE id_producto = :id_producto");
    $sentencia->bindParam('stock', $stock);
    $sentencia->bindParam('id_producto', $id_producto_get);
    $sentencia->execute();

    $pdo->commit();

    session_start();
    $_SESSION['mensaje'] = "La compra se ha eliminado correctamente";
    $_SESSION['icono'] = "success";
?>
    <script>
        location.href = "<?php echo $URL; ?>/compras";
    </script>
<?php } else {

    $pdo->rollBack();

    session_start();
    $_SESSION['mensaje'] = "Error al eliminar la compra";
    $_SESSION['icono'] = "error";
?>
    <script>
        location.href = "<?php echo $URL; ?>/compras";
    </script>
<?php
}
