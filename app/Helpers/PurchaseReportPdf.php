<?php

namespace App\Helpers;

/**
 * Genera y emite directamente (inline) el comprobante PDF de una compra usando TCPDF.
 *
 * Encapsula toda la configuración de TCPDF y la construcción del HTML del comprobante,
 * manteniendo al controlador libre de lógica de presentación PDF.
 */

class PurchaseReportPdf
{
    /**
     * Construye y emite el PDF del comprobante de compra directamente al navegador (inline).
     *
     * @param array $purchase Datos de la compra obtenidos por Purchase::findWithDetails().
     * @param string $comprador Email del usuario que registró la compra (en sesión).
     * @param int $id ID de la compra (usado en el nombre de archivo).
     */
    public static function generate(array $purchase, string $comprador, int $id): void
    {
        $fecha = date('d/m/Y', strtotime($purchase['fecha_compra']));
        $total = (float)$purchase['precio_compra'] * (int)$purchase['cantidad'];

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, [215, 279], true, 'UTF-8', false);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->setAuthor('Sistema de Ventas');
        $pdf->setTitle('Comprobante de compra');
        $pdf->setSubject('Comprobante de compra');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setMargins(15, 15, 15);
        $pdf->setAutoPageBreak(true, 5);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setFont('Helvetica', '', 12);
        $pdf->AddPage();

        $logoPath = BASE_PATH . '/public/img/Logo.jpg';
        $montoLiteral = NumberToWords::convert($total);

        $html = self::buildHtml($logoPath, $id, $fecha, $purchase, $total, $montoLiteral, $comprador);

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->RoundedRect(15, 76, 186, 30, 3.50, '1111', 'D');

        $qrData = 'Comprobante de compra N°' . $purchase['nro_compra']
            . "\nProveedor: " . $purchase['nombre_proveedor']
            . "\nProducto: " . $purchase['nombre_producto']
            . "\nFecha: " . $fecha
            . "\nTotal: Bs. " . number_format($total, 2);

        $pdf->write2DBarcode($qrData, 'QRCODE,L', 160, 230, 40, 40, [
            'border' => 0,
            'vpadding' => '3',
            'hpadding' => '3',
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1,
        ]);

        $pdf->Output('comprobante_compra_' . $id . '.pdf', 'I');
    }

    /**
     * Construye el HTML del comprobante para ser renderizado por TCPDF.
     */
    private static function buildHtml(
        string $logoPath,
        int    $id,
        string $fecha,
        array  $purchase,
        float  $total,
        string $montoLiteral,
        string $comprador
    ): string
    {
        $nroCompra = htmlspecialchars($purchase['nro_compra'], ENT_QUOTES, 'UTF-8');
        $comprobante = htmlspecialchars($purchase['comprobante'], ENT_QUOTES, 'UTF-8');
        $nombreProd = htmlspecialchars($purchase['nombre_producto'], ENT_QUOTES, 'UTF-8');
        $codigoProd = htmlspecialchars($purchase['codigo'], ENT_QUOTES, 'UTF-8');
        $categoria = htmlspecialchars($purchase['nombre_categoria'], ENT_QUOTES, 'UTF-8');
        $proveedor = htmlspecialchars($purchase['nombre_proveedor'], ENT_QUOTES, 'UTF-8');
        $empresa = htmlspecialchars($purchase['empresa'] ?? '—', ENT_QUOTES, 'UTF-8');
        $celular = htmlspecialchars($purchase['celular'] ?? '—', ENT_QUOTES, 'UTF-8');
        $telefono = htmlspecialchars($purchase['telefono'] ?? '—', ENT_QUOTES, 'UTF-8');
        $direccion = htmlspecialchars($purchase['direccion'] ?? '—', ENT_QUOTES, 'UTF-8');
        $compradorSafe = htmlspecialchars($comprador, ENT_QUOTES, 'UTF-8');
        $cantidad = (int)$purchase['cantidad'];
        $precioUnitario = (float)$purchase['precio_compra'];

        return '
<table border="0" style="font-size: 10px">
    <tr>
        <td style="text-align: center; width: 180px">
            <img src="' . $logoPath . '" alt="Logo" width="70px"><br><br>
            <b>SISTEMA DE VENTAS</b><br>
            Tercer anillo interno, Av. Bush<br>
            3113646 - 78569885<br>
            SANTA CRUZ - BOLIVIA
        </td>
        <td style="width: 179px"></td>
        <td style="font-size: 14px; width: 300px"><br><br><br>
            <b>Nro. Compra: </b>' . $nroCompra . '<br><br>
            <b>COMPROBANTE DE COMPRA</b>
        </td>
    </tr>
</table>

<p style="text-align: center; font-size: 25px"><b>COMPROBANTE DE COMPRA</b></p>

<div>
    <table border="0" cellspacing="2">
        <tr>
            <td><b>Comprobante:</b> ' . $comprobante . '</td>
            <td><b>Fecha:</b> ' . $fecha . '</td>
        </tr>
        <tr>
            <td><b>Proveedor:</b> ' . $proveedor . '</td>
            <td><b>Empresa:</b> ' . $empresa . '</td>
        </tr>
        <tr>
            <td><b>Registrado por:</b> ' . $compradorSafe . '</td>
            <td><b>Celular:</b> ' . $celular . ' / ' . $telefono . '</td>
        </tr>
        <tr>
            <td colspan="2"><b>Dirección:</b> ' . $direccion . '</td>
        </tr>
    </table>
</div>

<br>

<table border="1" cellpadding="5" style="font-size: 12px">
    <tr style="text-align: center; background-color: #d6d6d6">
        <th style="width: 80px"><b>Código</b></th>
        <th style="width: 100px"><b>Categoría</b></th>
        <th style="width: 180px"><b>Producto</b></th>
        <th style="width: 80px"><b>Cantidad</b></th>
        <th style="width: 100px"><b>Precio Unit.</b></th>
        <th style="width: 120px"><b>Subtotal</b></th>
    </tr>
    <tr>
        <td style="text-align: center">' . $codigoProd . '</td>
        <td style="text-align: center">' . $categoria . '</td>
        <td>' . $nombreProd . '</td>
        <td style="text-align: center">' . $cantidad . '</td>
        <td style="text-align: center">Bs. ' . number_format($precioUnitario, 2) . '</td>
        <td style="text-align: center">Bs. ' . number_format($total, 2) . '</td>
    </tr>
    <tr style="background-color: #d6d6d6">
        <td colspan="4" style="text-align: right"><b>Total</b></td>
        <td style="text-align: center">Bs. ' . number_format($precioUnitario, 2) . '</td>
        <td style="text-align: center">Bs. ' . number_format($total, 2) . '</td>
    </tr>
</table>

<p style="text-align: right"><b>Monto Total: </b>Bs. ' . number_format($total, 2) . '</p>
<p><b>Son: </b>' . $montoLiteral . '</p>
<br>
======================================<br>
<br>
<table border="1" cellpadding="5" style="font-size: 11px;">
    <tr>
        <td style="width: 120px; background-color: #d6d6d6"><b>OBSERVACIONES:</b></td>
        <td style="width: 540px">Sin observaciones</td>
    </tr>
</table>
<br>
<p style="text-align: center">======================================<br>
<b>DOCUMENTO INTERNO</b><br>
<b>COMPROBANTE DE CONTROL DE COMPRA</b><br>
======================================</p>';
    }
}