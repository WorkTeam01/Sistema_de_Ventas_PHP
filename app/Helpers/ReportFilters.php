<?php

namespace App\Helpers;

class ReportFilters
{
    /**
     * Parsea y normaliza el rango de fechas del GET.
     * Default: primer y último día del mes actual.
     *
     * @return array{fecha_desde: string, fecha_hasta: string, desde_display: string, hasta_display: string}
     */
    public static function parseDateRange(array $get): array
    {
        $desde = trim($get['fecha_desde'] ?? '');
        $hasta = trim($get['fecha_hasta'] ?? '');

        $desdeDate = self::parseDate($desde);
        $hastaDate = self::parseDate($hasta);

        if ($desdeDate === null || $hastaDate === null || $desdeDate > $hastaDate) {
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

    private static function parseDate(string $value): ?\DateTime
    {
        if ($value === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $d : null;
    }
}
