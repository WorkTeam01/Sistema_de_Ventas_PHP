<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$codigo = trim($_POST['codigo']);
$id_categoria = $_POST['id_categoria'];
$nombre = trim($_POST['nombre']);
$id_usuario = $_POST['id_usuario'];
$descripcion = trim($_POST['descripcion']);
$stock = $_POST['stock'];
$stock_minimo = $_POST['stock_minimo'];
$stock_maximo = $_POST['stock_maximo'];
$precio_compra = $_POST['precio_compra'];
$precio_venta = $_POST['precio_venta'];
$fecha_ingreso = $_POST['fecha_ingreso'];

if (!is_numeric($stock) || !is_numeric($stock_minimo) || !is_numeric($stock_maximo) || !is_numeric($precio_compra) || !is_numeric($precio_venta)) {
    session_start();
    $_SESSION['mensaje'] = 'Los valores de stock y precios deben ser numéricos.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/almacen/create.php');
    exit();
}

$extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
$mimes_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$mime_real = mime_content_type($_FILES['image']['tmp_name']);
$tamanio_max = 2 * 1024 * 1024;

if (!in_array($extension, $extensiones_permitidas) || !in_array($mime_real, $mimes_permitidos) || $_FILES['image']['size'] > $tamanio_max) {
    session_start();
    $_SESSION['mensaje'] = 'Imagen no válida. Solo se permiten JPG, PNG o WEBP de hasta 2MB.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/almacen/create.php');
    exit();
}

$nombreDelArchivo = date("Y-m-d-h-i-s");
$filename = $nombreDelArchivo . "__" . $extension;
$location = "../../../almacen/img_productos/" . $filename;

move_uploaded_file($_FILES['image']['tmp_name'], $location);

$sentencia = $pdo->prepare("INSERT INTO tb_almacen
            (codigo, nombre, descripcion, stock, stock_minimo, stock_maximo, precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria, fyh_creacion) 
    VALUES  (:codigo, :nombre, :descripcion, :stock, :stock_minimo, :stock_maximo, :precio_compra, :precio_venta, :fecha_ingreso, :imagen, :id_usuario, :id_categoria, :fyh_creacion)");

$sentencia->bindParam('codigo', $codigo);
$sentencia->bindParam('nombre', $nombre);
$sentencia->bindParam('descripcion', $descripcion);
$sentencia->bindParam('stock', $stock);
$sentencia->bindParam('stock_minimo', $stock_minimo);
$sentencia->bindParam('stock_maximo', $stock_maximo);
$sentencia->bindParam('precio_compra', $precio_compra);
$sentencia->bindParam('precio_venta', $precio_venta);
$sentencia->bindParam('fecha_ingreso', $fecha_ingreso);
$sentencia->bindParam('imagen', $filename);
$sentencia->bindParam('id_usuario', $id_usuario);
$sentencia->bindParam('id_categoria', $id_categoria);
$sentencia->bindParam('fyh_creacion', $fechaHora);

if ($sentencia->execute()) {
    session_start();
    $_SESSION['mensaje'] = 'EL producto se registró exitosamente';
    $_SESSION['icono'] = 'success';
    header('Location: ' . $URL . '/almacen');
} else {
    session_start();
    $_SESSION['mensaje'] = 'Error al crear el producto';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/almacen/create.php');
}
