<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SecurityHeaders;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    /** @return array<string, array{string, bool}> */
    public static function appUrls(): array
    {
        return [
            'https'                 => ['https://tienda.example.com', true],
            'https en mayúsculas'   => ['HTTPS://tienda.example.com', true],
            'https con ruta'        => ['https://host/Sistema/public', true],
            'http'                  => ['http://localhost/Sistema/public', false],
            'vacío'                 => ['', false],
            'sin esquema'           => ['tienda.example.com', false],
            'esquema parecido'      => ['httpsx://tienda.example.com', false],
            'https solo en la ruta' => ['http://host/https://x', false],
        ];
    }

    #[DataProvider('appUrls')]
    public function test_https_is_decided_by_the_app_url_scheme(string $appUrl, bool $expected): void
    {
        $this->assertSame($expected, SecurityHeaders::isHttpsUrl($appUrl));
    }

    public function test_hsts_is_not_sent_over_http(): void
    {
        $this->assertNull(SecurityHeaders::hstsValue('http://localhost', 31536000));
        $this->assertNull(SecurityHeaders::hstsValue('', 86400));
    }

    /** @return array<string, array{mixed, string}> */
    public static function maxAges(): array
    {
        return [
            'entero'            => [3600, 'max-age=3600'],
            'texto numérico'    => ['3600', 'max-age=3600'],
            'cero'              => [0, 'max-age=0'],
            'cero como texto'   => ['0', 'max-age=0'],
            'un año'            => ['31536000', 'max-age=31536000'],
            'null'              => [null, 'max-age=86400'],
            'vacío'             => ['', 'max-age=86400'],
            'texto'             => ['abc', 'max-age=86400'],
            'negativo'          => [-1, 'max-age=86400'],
            'negativo en texto' => ['-5', 'max-age=86400'],
            'decimal'           => ['1.5', 'max-age=86400'],
            'con espacios'      => [' 3600 ', 'max-age=86400'],
            'booleano'          => [true, 'max-age=86400'],
        ];
    }

    #[DataProvider('maxAges')]
    public function test_hsts_max_age_falls_back_to_the_default_when_invalid(mixed $raw, string $expected): void
    {
        $this->assertSame($expected, SecurityHeaders::hstsValue('https://tienda.example.com', $raw));
    }

    public function test_hsts_never_includes_subdomains_or_preload(): void
    {
        foreach ([0, 86400, 31536000, 'abc', null] as $raw) {
            $value = (string) SecurityHeaders::hstsValue('https://tienda.example.com', $raw);

            $this->assertStringNotContainsStringIgnoringCase('includeSubDomains', $value);
            $this->assertStringNotContainsStringIgnoringCase('preload', $value);
        }
    }
}
