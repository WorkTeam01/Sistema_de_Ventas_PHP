<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ReportFilters;
use Tests\TestCase;

final class ReportFiltersTest extends TestCase
{
    public function test_default_returns_current_month(): void
    {
        $result = ReportFilters::parseDateRange([]);

        $expectedDesde = date('Y-m-01') . ' 00:00:00';
        $expectedHasta = date('Y-m-t')  . ' 23:59:59';

        $this->assertSame($expectedDesde, $result['fecha_desde']);
        $this->assertSame($expectedHasta, $result['fecha_hasta']);
    }

    public function test_valid_range_is_parsed_correctly(): void
    {
        $result = ReportFilters::parseDateRange([
            'fecha_desde' => '2025-01-15',
            'fecha_hasta' => '2025-01-31',
        ]);

        $this->assertSame('2025-01-15 00:00:00', $result['fecha_desde']);
        $this->assertSame('2025-01-31 23:59:59', $result['fecha_hasta']);
        $this->assertSame('2025-01-15', $result['desde_display']);
        $this->assertSame('2025-01-31', $result['hasta_display']);
    }

    public function test_inverted_range_falls_back_to_current_month(): void
    {
        $result = ReportFilters::parseDateRange([
            'fecha_desde' => '2025-03-31',
            'fecha_hasta' => '2025-01-01',
        ]);

        $this->assertSame(date('Y-m-01') . ' 00:00:00', $result['fecha_desde']);
        $this->assertSame(date('Y-m-t')  . ' 23:59:59', $result['fecha_hasta']);
    }

    public function test_garbage_input_falls_back_to_current_month(): void
    {
        $result = ReportFilters::parseDateRange([
            'fecha_desde' => 'not-a-date',
            'fecha_hasta' => '99/99/9999',
        ]);

        $this->assertSame(date('Y-m-01') . ' 00:00:00', $result['fecha_desde']);
        $this->assertSame(date('Y-m-t')  . ' 23:59:59', $result['fecha_hasta']);
    }

    public function test_missing_hasta_falls_back_to_current_month(): void
    {
        $result = ReportFilters::parseDateRange([
            'fecha_desde' => '2025-06-01',
        ]);

        $this->assertSame(date('Y-m-01') . ' 00:00:00', $result['fecha_desde']);
    }

    public function test_same_day_range_is_valid(): void
    {
        $result = ReportFilters::parseDateRange([
            'fecha_desde' => '2025-06-15',
            'fecha_hasta' => '2025-06-15',
        ]);

        $this->assertSame('2025-06-15 00:00:00', $result['fecha_desde']);
        $this->assertSame('2025-06-15 23:59:59', $result['fecha_hasta']);
    }
}
