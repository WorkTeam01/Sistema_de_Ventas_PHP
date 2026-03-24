<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$nro_venta = $_POST['nro_venta'];
$id_cliente = $_POST['id_cliente'];
$total_a_cancelar = $_POST['total_a_cancelar'];

if (!is_numeric($total_a_cancelar)) {
    die("Error: El total de la venta debe ser un número.");
}

$pdo->beginTransaction();

try {
    $sentencia = $pdo->prepare("INSERT INTO tb_ventas
                (nro_venta, id_cliente, total_pagado, fyh_creacion) 
        VALUES  (:nro_venta, :id_cliente, :total_pagado, :fyh_creacion)");

    $sentencia->bindParam('nro_venta', $nro_venta);
    $sentencia->bindParam('id_cliente', $id_cliente);
    $sentencia->bindParam('total_pagado', $total_a_cancelar);
    $sentencia->bindParam('fyh_creacion', $fechaHora);

    if ($sentencia->execute()) {

        // Actualizando stock iterando el carrito
        $sql_carrito = "SELECT id_producto, cantidad FROM tb_carrito WHERE nro_venta = :nro_venta";
        $query_carrito = $pdo->prepare($sql_carrito);
        $query_carrito->bindParam('nro_venta', $nro_venta);
        $query_carrito->execute();
        $carrito_datos = $query_carrito->fetchAll(PDO::FETCH_ASSOC);

        foreach ($carrito_datos as $item) {
            $id_producto_carrito = $item['id_producto'];
            $cantidad_carrito = $item['cantidad'];

            $sentencia_stock = $pdo->prepare("UPDATE tb_almacen SET stock = stock - :cantidad WHERE id_producto = :id_producto");
            $sentencia_stock->bindParam('cantidad', $cantidad_carrito);
            $sentencia_stock->bindParam('id_producto', $id_producto_carrito);
            
            if(!$sentencia_stock->execute()){
                throw new Exception("Error al actualizar stock del producto ID: $id_producto_carrito");
            }
        }

        $pdo->commit();

        session_start();
        $_SESSION['mensaje'] = 'La venta se registró exitosamente';
        $_SESSION['icono'] = 'success';
?>
        <script>
            location.href = "<?php echo $URL; ?>/ventas";
        </script>
<?php
    } else {
        throw new Exception("Error al insertar encabezado de la venta");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    session_start();
    $_SESSION['mensaje'] = 'Error al crear la venta';
    $_SESSION['icono'] = 'error';
?>
    <script>
        location.href = "<?php echo $URL; ?>/ventas/create.php";
    </script>
<?php
}
