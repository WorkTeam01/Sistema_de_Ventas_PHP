<?php

namespace App\Helpers;

class ReportPdf
{
    /**
     * Genera y emite inline un PDF tabular en orientación landscape.
     *
     * @param string $titulo    Título principal del reporte.
     * @param string $subtitulo Rango de fechas u otro subtítulo.
     * @param array  $headers   Cabeceras de columna.
     * @param array  $rows      Filas de datos (arrays indexados, en el mismo orden que $headers).
     * @param array  $totals    Pares [label => valor] mostrados al pie. Vacío = sin totalizadores.
     * @param string $filename  Nombre del archivo sin extensión.
     * @param string $emisor    Nombre del usuario que genera el reporte.
     */
    public static function generate(
        string $titulo,
        string $subtitulo,
        array  $headers,
        array  $rows,
        array  $totals,
        string $filename,
        string $emisor = ''
    ): void {
        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->setAuthor('Sistema de Ventas');
        $pdf->setTitle($titulo);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setMargins(10, 10, 10);
        $pdf->setAutoPageBreak(true, 10);
        $pdf->setFont('Helvetica', '', 9);
        $pdf->AddPage();

        $html = self::buildHtml($titulo, $subtitulo, $headers, $rows, $totals, $emisor);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output($filename . '.pdf', 'I');
    }

    private static function buildHtml(
        string $titulo,
        string $subtitulo,
        array  $headers,
        array  $rows,
        array  $totals,
        string $emisor
    ): string {
        $th = '';
        foreach ($headers as $h) {
            $th .= '<th style="background-color:#d6d6d6;padding:4px;text-align:center"><b>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</b></th>';
        }

        $tbody = '';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach ($row as $cell) {
                $tbody .= '<td style="padding:3px">' . htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $tbody .= '</tr>';
        }

        $totalRows = '';
        foreach ($totals as $label => $valor) {
            $totalRows .= '<tr><td colspan="' . (count($headers) - 1) . '" style="text-align:right;padding:3px"><b>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</b></td>'
                . '<td style="padding:3px;text-align:right"><b>' . htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8') . '</b></td></tr>';
        }

        $emisorLine = $emisor !== '' ? '<p style="font-size:8px">Emitido por: ' . htmlspecialchars($emisor, ENT_QUOTES, 'UTF-8') . ' — ' . date('d/m/Y H:i') . '</p>' : '';

        return '
<p style="font-size:16px;text-align:center"><b>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</b></p>
<p style="font-size:10px;text-align:center">' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</p>
<table border="1" cellpadding="3" style="font-size:9px;width:100%">
  <tr>' . $th . '</tr>
  ' . $tbody . '
  ' . $totalRows . '
</table>
' . $emisorLine;
    }
}
