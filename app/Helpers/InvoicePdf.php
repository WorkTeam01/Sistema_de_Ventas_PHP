<?php

namespace App\Helpers;

/**
 * Genera y emite directamente (inline) la factura PDF de una venta usando TCPDF.
 *
 * Encapsula toda la configuración de TCPDF, la construcción del HTML de la factura
 * y la adición del código QR, manteniendo al controlador libre de lógica de presentación PDF.
 */
class InvoicePdf
{
    /**
     * Construye y emite el PDF de la factura directamente al navegador (inline).
     *
     * @param array $sale Datos de la venta (nro_venta, nombre_cliente, nit_ci_cliente,
     *                         fyh_creacion, items[]).
     * @param array $totals Totales calculados por Sale::computeInvoiceTotals():
     *                         precio_total, cantidad_total, total_unitarios.
     * @param string $vendedor Nombre del vendedor en sesión.
     * @param int $id ID de la venta (usado en nombre de archivo y número de factura).
     */
    public static function generate(array $sale, array $totals, string $vendedor, int $id): void
    {
        $nombreCliente = $sale['nombre_cliente'];
        $nitCiCliente = $sale['nit_ci_cliente'];
        $fecha = date('d/m/Y', strtotime($sale['fyh_creacion']));
        $items = $sale['items'];
        $precioTotal = $totals['precio_total'];
        $cantidadTotal = $totals['cantidad_total'];
        $totalUnitarios = $totals['total_unitarios'];
        $montoLiteral = NumberToWords::convert($precioTotal);

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, [215, 279], true, 'UTF-8', false);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->setAuthor('Sistema de Ventas');
        $pdf->setTitle('Factura de venta');
        $pdf->setSubject('Factura de venta');
        $pdf->setKeywords('Factura de venta');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setMargins(15, 15, 15);
        $pdf->setAutoPageBreak(true, 5);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setFont('Helvetica', '', 12);
        $pdf->AddPage();

        $logoPath = BASE_PATH . '/public/img/Logo.jpg';

        $html = self::buildHtml(
            $logoPath,
            $id,
            $fecha,
            $nombreCliente,
            $nitCiCliente,
            $items,
            $precioTotal,
            $cantidadTotal,
            $totalUnitarios,
            $montoLiteral,
            $vendedor
        );

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->RoundedRect(15, 80, 186, 15, 3.50, '1111', 'D');

        $qrData = 'Factura del sistema de venta'
            . "\nCliente: " . $nombreCliente
            . "\nNIT/CI: " . $nitCiCliente
            . "\nFecha: "  . $fecha
            . "\nMonto: " . APP_CURRENCY_SYMBOL . " " . number_format($precioTotal, 2);

        $pdf->write2DBarcode($qrData, 'QRCODE,L', 160, 230, 40, 40, [
            'border' => 0,
            'vpadding' => '3',
            'hpadding' => '3',
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1,
        ]);

        $pdf->Output('factura_venta_' . $id . '.pdf', 'I');
    }

    /**
     * Construye el HTML de la factura para ser renderizado por TCPDF.
     *
     * @param string $logoPath Ruta absoluta al logo.
     * @param int $id ID de la venta.
     * @param string $fecha Fecha formateada.
     * @param string $nombreCliente Nombre del cliente.
     * @param string $nitCiCliente NIT/CI del cliente.
     * @param array $items Ítems del carrito.
     * @param float $precioTotal Monto total de la factura.
     * @param int $cantidadTotal Suma de cantidades.
     * @param float $totalUnitarios Suma de precios unitarios.
     * @param string $montoLiteral Monto en letras.
     * @param string $vendedor Nombre del vendedor.
     * @return string HTML listo para writeHTML().
     */
    private static function buildHtml(
        string $logoPath,
        int    $id,
        string $fecha,
        string $nombreCliente,
        string $nitCiCliente,
        array  $items,
        float  $precioTotal,
        int    $cantidadTotal,
        float  $totalUnitarios,
        string $montoLiteral,
        string $vendedor
    ): string {
        $html = '
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
        <td style="font-size: 16px; width: 300px"><br><br><br><br>
            <b>Número factura: </b>' . $id . '<br>
            <b>Número de autorización: </b>565586565
            <p style="text-align: center"><b>ORIGINAL</b></p>
        </td>
    </tr>
</table>

<p style="text-align: center; font-size: 25px"><b>FACTURA</b></p>

<div>
    <table border="0" cellspacing="2">
        <tr>
            <td><b>Fecha: </b>' . $fecha . '</td>
            <td></td>
            <td><b>NIT/CI: </b>' . htmlspecialchars($nitCiCliente, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
            <td colspan="3"><b>Señor(a): </b>' . htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8') . '</td>
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

        $contador = 0;
        foreach ($items as $item) {
            $contador++;
            $cantidad = (int)$item['cantidad'];
            $precioUnitario = (float)$item['precio_venta'];
            $subTotal = $cantidad * $precioUnitario;

            $html .= '
    <tr>
        <td style="text-align: center">' . $contador . '</td>
        <td>' . htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') . '</td>
        <td style="text-align: center">' . $cantidad . '</td>
        <td style="text-align: center">' . APP_CURRENCY_SYMBOL . ' ' . number_format($precioUnitario, 2) . '</td>
        <td style="text-align: center">' . APP_CURRENCY_SYMBOL . ' ' . number_format($subTotal, 2) . '</td>
    </tr>';
        }

        $html .= '
    <tr style="background-color: #d6d6d6">
        <td colspan="3" style="text-align: right"><b>Total</b></td>
        <td style="text-align: center">' . $cantidadTotal . '</td>
        <td style="text-align: center">' . APP_CURRENCY_SYMBOL . ' ' . number_format($totalUnitarios, 2) . '</td>
        <td style="text-align: center">' . APP_CURRENCY_SYMBOL . ' ' . number_format($precioTotal, 2) . '</td>
    </tr>
</table>

<p style="text-align: right"><b>Monto Total: </b>' . APP_CURRENCY_SYMBOL . ' ' . number_format($precioTotal, 2) . '</p>
<p><b>Son: </b>' . $montoLiteral . '</p>
<br>
======================================<br>
<b>Usuario: </b>' . htmlspecialchars($vendedor, ENT_QUOTES, 'UTF-8') . '
<p style="text-align: center"></p>
<p style="text-align: center">Esta factura contribuye al desarrollo del país, el uso ilícito de ésta será sancionado de acuerdo a la ley</p>
<p style="text-align: center">Gracias por su preferencia</p>';

        return $html;
    }
}
