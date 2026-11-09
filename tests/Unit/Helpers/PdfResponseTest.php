<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PdfResponse;
use Tests\TestCase;

final class PdfResponseTest extends TestCase
{
    /** @param list<string> $headers */
    private function lines(array $headers, string $name): array
    {
        return array_values(array_filter(
            $headers,
            static fn(string $line): bool => stripos($line, $name . ':') === 0
        ));
    }

    public function test_cache_control_is_a_single_private_no_store_line(): void
    {
        $headers = PdfResponse::headersFor('factura_venta_12.pdf', 100);

        $this->assertSame(['Cache-Control: private, no-store'], $this->lines($headers, 'Cache-Control'));
    }

    public function test_it_never_sends_pragma_or_expires(): void
    {
        $headers = PdfResponse::headersFor('factura_venta_12.pdf', 100);

        $this->assertSame([], $this->lines($headers, 'Pragma'));
        $this->assertSame([], $this->lines($headers, 'Expires'));
    }

    public function test_type_disposition_and_length_are_preserved(): void
    {
        $headers = PdfResponse::headersFor('comprobante_compra_7.pdf', 2048);

        $this->assertSame(['Content-Type: application/pdf'], $this->lines($headers, 'Content-Type'));
        $this->assertSame(['Content-Length: 2048'], $this->lines($headers, 'Content-Length'));
        $this->assertSame(
            ['Content-Disposition: inline; filename="comprobante_compra_7.pdf"; filename*=UTF-8\'\'comprobante_compra_7.pdf'],
            $this->lines($headers, 'Content-Disposition')
        );
    }

    public function test_filename_with_accents_and_quotes_is_sanitized_and_encoded(): void
    {
        $headers = PdfResponse::headersFor("reporte \"ñandú\"\r\nX-Evil: 1.pdf", 1);
        $disposition = $this->lines($headers, 'Content-Disposition')[0];

        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
        $this->assertSame([], $this->lines($headers, 'X-Evil'));
        $this->assertMatchesRegularExpression('/filename="[A-Za-z0-9._-]+"/', $disposition);
        $this->assertStringContainsString("filename*=UTF-8''reporte%20%22%C3%B1and%C3%BA%22X-Evil%3A%201.pdf", $disposition);
    }

    public function test_every_line_is_a_single_header(): void
    {
        foreach (PdfResponse::headersFor("a\r\nb.pdf", 5) as $line) {
            $this->assertDoesNotMatchRegularExpression('/[\r\n]/', $line);
        }
    }
}
