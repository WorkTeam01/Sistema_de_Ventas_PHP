<?php

namespace App\Helpers;

class MonthSeries
{
    /**
     * Etiquetas `Y-m` de los últimos $n meses, del más antiguo al mes de $base.
     * Se parte del primer día del mes: restar meses desde el día 31 desborda
     * (31 de marzo − 1 mes = 3 de marzo) y repetiría o saltaría meses.
     *
     * @return string[]
     */
    public static function lastMonths(int $n, ?\DateTimeImmutable $base = null): array
    {
        $first = ($base ?? new \DateTimeImmutable())->modify('first day of this month');

        $months = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $months[] = $first->modify("-$i months")->format('Y-m');
        }

        return $months;
    }
}
