<?php

namespace App\Helpers;

class ReportFilters
{
    private const TOP_OPTIONS = [5, 10, 20, 50];

    /**
     * Parsea y normaliza el rango de fechas del GET.
     * Default: primer y último día del mes actual.
     *
     * @return array{fecha_desde: string, fecha_hasta: string, desde_display: string, hasta_display: string}
     */
    public static function parseDateRange(array $get): array
    {
        $desde = is_string($get['fecha_desde'] ?? null) ? trim($get['fecha_desde']) : '';
        $hasta = is_string($get['fecha_hasta'] ?? null) ? trim($get['fecha_hasta']) : '';

        $desdeDate = self::parseDate($desde);
        $hastaDate = self::parseDate($hasta);

        if ($desdeDate !== null && $hastaDate !== null && $desdeDate > $hastaDate) {
            $desdeDate = null;
            $hastaDate = null;
        }

        // Una fecha sola no invalida el rango: se completa con el extremo del
        // mismo mes, para no descartar silenciosamente la fecha elegida.
        if ($desdeDate !== null && $hastaDate === null) {
            $hastaDate = new \DateTime($desdeDate->format('Y-m-t'));
        } elseif ($hastaDate !== null && $desdeDate === null) {
            $desdeDate = new \DateTime($hastaDate->format('Y-m-01'));
        }

        if ($desdeDate === null || $hastaDate === null) {
            $desdeDate = new \DateTime('first day of this month');
            $hastaDate = new \DateTime('last day of this month');
        }

        return [
            'fecha_desde'    => $desdeDate->format('Y-m-d') . ' 00:00:00',
            'fecha_hasta'    => $hastaDate->format('Y-m-d') . ' 23:59:59',
            'desde_display'  => $desdeDate->format('Y-m-d'),
            'hasta_display'  => $hastaDate->format('Y-m-d'),
        ];
    }

    /** Cantidad de filas del ranking de productos: solo valores de la lista, 10 en cualquier otro caso. */
    public static function parseTop(array $get): int
    {
        $top = is_scalar($get['top'] ?? null) ? (int)$get['top'] : 10;

        return in_array($top, self::TOP_OPTIONS, true) ? $top : 10;
    }

    private static function parseDate(string $value): ?\DateTime
    {
        if ($value === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $d : null;
    }
}
