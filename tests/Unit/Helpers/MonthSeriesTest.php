<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MonthSeries;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MonthSeriesTest extends TestCase
{
    public function testReturnsMonthsOldestFirstEndingInCurrentMonth(): void
    {
        $this->assertSame(
            ['2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'],
            MonthSeries::lastMonths(6, new \DateTimeImmutable('2026-10-03'))
        );
    }

    public function testDay31DoesNotSkipOrRepeatMonths(): void
    {
        // strtotime("-1 months") desde el 31 de marzo cae en el 3 de marzo: febrero se perdía
        $this->assertSame(
            ['2025-10', '2025-11', '2025-12', '2026-01', '2026-02', '2026-03'],
            MonthSeries::lastMonths(6, new \DateTimeImmutable('2026-03-31'))
        );
    }

    public function testCrossesYearBoundary(): void
    {
        $this->assertSame(
            ['2025-08', '2025-09', '2025-10', '2025-11', '2025-12', '2026-01'],
            MonthSeries::lastMonths(6, new \DateTimeImmutable('2026-01-31'))
        );
    }

    public static function everyDayOfEveryMonth(): array
    {
        $cases = [];
        foreach ([2025, 2026, 2028] as $year) { // 2028 es bisiesto
            for ($month = 1; $month <= 12; $month++) {
                $last = (int)(new \DateTimeImmutable("$year-$month-01"))->format('t');
                foreach ([1, 28, 29, 30, 31] as $day) {
                    if ($day <= $last) {
                        $cases["$year-$month-$day"] = [sprintf('%04d-%02d-%02d', $year, $month, $day)];
                    }
                }
            }
        }

        return $cases;
    }

    #[DataProvider('everyDayOfEveryMonth')]
    public function testAlwaysSixDistinctConsecutiveMonths(string $base): void
    {
        $series = MonthSeries::lastMonths(6, new \DateTimeImmutable($base));

        $this->assertCount(6, $series);
        $this->assertCount(6, array_unique($series));
        $this->assertSame(substr($base, 0, 7), end($series));

        for ($i = 1; $i < 6; $i++) {
            $expected = (new \DateTimeImmutable($series[$i - 1] . '-01'))->modify('+1 month')->format('Y-m');
            $this->assertSame($expected, $series[$i]);
        }
    }

    public function testOtherLengths(): void
    {
        $base = new \DateTimeImmutable('2026-03-31');

        $this->assertSame(['2026-03'], MonthSeries::lastMonths(1, $base));
        $this->assertCount(12, MonthSeries::lastMonths(12, $base));
        $this->assertSame([], MonthSeries::lastMonths(0, $base));
    }

    public function testDefaultsToToday(): void
    {
        $this->assertSame(date('Y-m'), MonthSeries::lastMonths(3)[2]);
    }
}
