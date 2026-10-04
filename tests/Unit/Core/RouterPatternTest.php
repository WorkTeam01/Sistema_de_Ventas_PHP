<?php

namespace Tests\Unit\Core;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

class RouterPatternTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('idRoutes')]
    public function test_id_parameter_only_matches_digits(string $uri, bool $expected): void
    {
        $pattern = Router::compilePattern('/users/edit/{id}');

        $this->assertSame($expected, (bool) preg_match($pattern, $uri));
    }

    public static function idRoutes(): array
    {
        return [
            'numérico'          => ['/users/edit/15', true],
            'letras'            => ['/users/edit/abc', false],
            'alfanumérico'      => ['/users/edit/12ab', false],
            'con guion'         => ['/users/edit/1-2', false],
            'con guion bajo'    => ['/users/edit/1_2', false],
            'vacío'             => ['/users/edit/', false],
            'subruta'           => ['/users/edit/15/x', false],
        ];
    }

    public function test_token_parameter_stays_alphanumeric(): void
    {
        $pattern = Router::compilePattern('/auth/reset/{token}');

        $this->assertSame(1, preg_match($pattern, '/auth/reset/ab12CD-_9'));
        $this->assertSame(0, preg_match($pattern, '/auth/reset/ab/cd'));
    }

    public function test_id_and_token_in_same_route_capture_in_order(): void
    {
        $pattern = Router::compilePattern('/x/{id}/{token}');

        $this->assertSame(1, preg_match($pattern, '/x/7/abc', $m));
        $this->assertSame(['/x/7/abc', '7', 'abc'], $m);
        $this->assertSame(0, preg_match($pattern, '/x/seven/abc'));
    }

    public function test_static_and_root_routes_match_exactly(): void
    {
        $this->assertSame(1, preg_match(Router::compilePattern('/'), '/'));
        $this->assertSame(0, preg_match(Router::compilePattern('/'), '/users'));
        $this->assertSame(1, preg_match(Router::compilePattern('/sales/'), '/sales'));
    }
}
