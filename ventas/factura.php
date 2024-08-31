<?php

// Include the main TCPDF library (search for installation path).
require_once('../app/TCPDF-main/tcpdf.php');
require_once '../app/config.php';
require_once '../layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Comprador', 'Vendedor']);

include_once '../app/controllers/ventas/literal.php';

session_start();

if (isset($_SESSION['sesion_email'])) {
    // echo "Existe sesion de: " . $_SESSION['sesion_email'];
    $email_sesion = $_SESSION['sesion_email'];
    $sql = "SELECT nombres FROM tb_usuarios WHERE email = '$email_sesion'";
    $query = $pdo->prepare($sql);
    $query->execute();

    $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $usuario) {
        $nombres_sesion = $usuario['nombres'];
    }
}

$id_venta_get = $_GET['id'];
$nro_venta_get = $_GET['nro_venta'];

$sql_ventas = "SELECT v.*, c.nombre_cliente, c.nit_ci_cliente FROM tb_ventas v
                INNER JOIN tb_clientes c on v.id_cliente = c.id_cliente
                WHERE v.id_venta = '$id_venta_get'";

$query_ventas = $pdo->query($sql_ventas);
$query_ventas->execute();
$contador_de_ventas = $query_ventas->rowCount() + 1;
$total_ventas = $query_ventas->rowCount();
$ventas_datos = $query_ventas->fetchAll(PDO::FETCH_ASSOC);

foreach ($ventas_datos as $ventas_dato) {
    $fyh_creacion = $ventas_dato['fyh_creacion'];
    $nit_ci_cliente = $ventas_dato['nit_ci_cliente'];
    $nombre_cliente = $ventas_dato['nombre_cliente'];
    $total_pagado = $ventas_dato['total_pagado'];
}

// Convertir precio total a literal
$monto_literal = numeroletras($total_pagado);

$fecha = date("d/m/Y", strtotime($fyh_creacion));


// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, array(215, 279), true, 'UTF-8', false);

// set document information
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('Sistema de Ventas');
$pdf->setTitle('Factura de venta');
$pdf->setSubject('Factura de venta');
$pdf->setKeywords('Factura de venta');

// remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->setMargins(15, 15, 15);

// set auto page breaks
$pdf->setAutoPageBreak(TRUE, 5);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once(dirname(__FILE__) . '/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->setFont('Helvetica', '', 12);
// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// Set some content to print
$html = /*html*/ '
<table border="0" style="font-size: 10px">
    <tr>
        <td style="text-align: center; width: 180px">
        <img src="../public/img/Logo.jpg" alt="Imagen" width="70px"> <br><br>
            <b>SISTEMA DE VENTAS</b> <br>
            Tecer anillo interno, Av. Bush <br>
            3113646 - 78569885 <br>
            SANTA CRUZ - BOLIVIA
        </td>
        <td style="width: 179px"></td>
        <td style="font-size: 16px; width: 300px"><br><br><br>
            <b>NIT: </b> ' . $nit_ci_cliente . '<br>
            <b>Número factura: </b> ' . $id_venta_get . ' <br>
            <b>Número de autorización: </b>565586565
            <p style="text-align: center"><B>ORIGINAL</B></p>
        </td>
    </tr>
</table>

<p style="text-align: center; font-size: 25px"><b>FACTURA</b></p>

<div>
    <table border="0" cellspacing="2">
        <tr>
            <td><b>Fecha: </b> ' . $fecha . ' </td>
            <td></td>
            <td><b>NIT/CI: </b>' . $nit_ci_cliente . '</td>
        </tr>
        <tr>
            <td colspan="3"><b>Señor(a): </b>' . $nombre_cliente . '</td>
        </tr>
    </table>
</div>

<br>

<table border="1" cellpadding="5" style="font-size: 12px">
    <tr style="text-align: center; background-color: #d6d6d6">
        <th style="width: 40px"><b>Nro</b></th>
        <th style="width: 120px"><b>Producto</b></th>
        <th style="width: 260px"><b>Descripción</b></th>
        <th style="width: 65px"><b>Cantidad</b></th>
        <th style="width: 100px"><b>Precio Unitario</b></th>
        <th style="width: 74px"><b>Sub Total</b></th>
    </tr>
';

$contador_de_carritos = 0;
$cantidad_total = 0;
$total_precio_unitario = 0;
$precio_total = 0;
$sql_carrito = "SELECT car.*, al.id_producto, al.nombre, al.descripcion, al.precio_venta, al.stock 
                FROM tb_carrito car INNER JOIN tb_almacen al on car.id_producto = al.id_producto
                WHERE nro_venta = '$nro_venta_get' ORDER BY id_carrito ASC";
$query_carrito = $pdo->prepare($sql_carrito);
$query_carrito->execute();
$carrito_datos = $query_carrito->fetchAll(PDO::FETCH_ASSOC);

foreach ($carrito_datos as $carrito_dato) {
    $id_carrito = $carrito_dato['id_carrito'];
    $nombre_producto = $carrito_dato['nombre'];
    $descripcion_producto = $carrito_dato['descripcion'];
    $cantidad_productos = $carrito_dato['cantidad'];
    $precio_unitario = $carrito_dato['precio_venta'];
    $contador_de_carritos += 1;
    $cantidad_total = $cantidad_total + $cantidad_productos;
    $total_precio_unitario = $total_precio_unitario + floatval($precio_unitario);
    $sub_total = $cantidad_productos * $precio_unitario;
    $precio_total += $sub_total;
    $html .= /*html*/ '
    <tr>
        <td style="text-align: center">' . $contador_de_carritos . '</td>
        <td>' . $nombre_producto . '</td>
        <td>' . $descripcion_producto . '</td>
        <td style="text-align: center">' . $cantidad_productos . '</td>
        <td style="text-align: center">Bs. ' . $precio_unitario . '</td>
        <td style="text-align: center">Bs. ' . $sub_total . '</td>
    </tr>
    ';
}

$html .= /* html*/ '
    <tr style="background-color: #d6d6d6">
        <td colspan="3" style="text-align: right"><b>Total</b></td>
        <td style="text-align: center">' . $cantidad_total . '</td>
        <td style="text-align: center">Bs. ' . $total_precio_unitario . '</td>
        <td style="text-align: center">Bs. ' . $precio_total . '</td>
    </tr>
</table>

<p style="text-align: right"> <b>Monto Total: </b>Bs. ' . $precio_total . '</p>
<p><b>Son: </b>' . $monto_literal . '</p>
<br>
====================================== <br>
<b>Usuario: </b>' . $nombres_sesion . '
<p style="text-align: center"></p>
<p style="text-align: center">Esta factura contribuye al desarrollo del país, el uso ilícito de ésta será sancionado de acuerdo a la ley</p>
<p style="text-align: center">Gracias por su preferencia</p>
';

// Print text using writeHTMLCell()
$pdf->writeHTML($html, true, false, true, false, '');

// Draw rounded rectangle
$pdf->RoundedRect(15, 80, 186, 15, 3.50, '1111', 'D');

$style = array(
    'border' => 0,
    'vpadding' => '3',
    'hpadding' => '3',
    'fgcolor' => array(0, 0, 0),
    'bgcolor' => false, //array(255, 255, 255)
    'module_width' => 1,
    'module_height' => 1
);

$QR = 'Factura realizada por el sistema de ventas, al cliente: ' . $nombre_cliente . ', con nit/ci: ' . $nit_ci_cliente . ',
en fecha: ' . $fecha . ', con el monto total de: ' . $precio_total . '';
$pdf->write2DBarcode($QR, 'QRCODE,L', 170, 240, 35, 35, $style);

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('example_001.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
