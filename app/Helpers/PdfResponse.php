<?php

namespace App\Helpers;

/**
 * Respuesta HTTP de un PDF con cabeceras propias. TCPDF::Output('I') escribe las suyas
 * (Cache-Control: private, must-revalidate y Pragma: public), así que el documento se genera
 * en memoria y se envía aquí: las facturas y reportes llevan datos financieros y no deben
 * quedar en cachés del navegador ni de intermediarios.
 */
final class PdfResponse
{
    /**
     * @return list<string> líneas de cabecera, cada una sin saltos de línea
     */
    public static function headersFor(string $filename, int $length): array
    {
        $clean    = str_replace(["\r", "\n"], '', $filename);
        $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $clean);

        return [
            'Content-Type: application/pdf',
            'Content-Disposition: inline; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($clean),
            'Content-Length: ' . $length,
            'Cache-Control: private, no-store',
        ];
    }

    /**
     * Genera los bytes antes de emitir nada: si TCPDF falla no queda una respuesta PDF a medias y el
     * manejador global responde la página 500.
     */
    public static function send(\TCPDF $pdf, string $filename): void
    {
        $bytes = $pdf->Output($filename, 'S');

        // La sesión de PHP envía Pragma: no-cache; el PDF solo declara Cache-Control.
        header_remove('Pragma');
        header_remove('Expires');
        foreach (self::headersFor($filename, strlen($bytes)) as $line) {
            header($line, true);
        }

        echo $bytes;
    }
}
