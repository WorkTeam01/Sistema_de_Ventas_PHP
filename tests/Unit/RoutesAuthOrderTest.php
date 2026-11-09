<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * PermissionMiddleware no es la única barrera de sesión: confía en que 'auth' ya corrió antes. Una ruta
 * con 'can:' y sin 'auth' delante llegaría a comprobar permisos sin haber revalidado la cuenta.
 * Se lee el archivo de rutas como texto; no se instancia el Router.
 */
final class RoutesAuthOrderTest extends TestCase
{
    /**
     * @return array{checked: int, offenders: list<string>} arreglos de middleware con 'can:' revisados y los que
     *         no tienen 'auth' antes del primer 'can:'
     */
    public static function scan(string $routesSource): array
    {
        $checked   = 0;
        $offenders = [];

        preg_match_all("/\\[([^\\[\\]]*'can:[^\\[\\]]*)\\]/", $routesSource, $matches);

        foreach ($matches[1] as $list) {
            $checked++;
            $items = array_map(static fn(string $item): string => trim($item, " \t\r\n'\""), explode(',', $list));
            $auth  = array_search('auth', $items, true);
            $can   = null;
            foreach ($items as $index => $item) {
                if (str_starts_with($item, 'can:')) {
                    $can = $index;
                    break;
                }
            }

            if ($auth === false || $can === null || $auth > $can) {
                $offenders[] = trim(preg_replace('/\s+/', ' ', $list));
            }
        }

        return ['checked' => $checked, 'offenders' => $offenders];
    }

    public function test_every_can_middleware_is_preceded_by_auth(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/routes/web.php');
        $result = self::scan($source);

        $this->assertSame([], $result['offenders'], "Rutas con 'can:' sin 'auth' antes");
        $this->assertGreaterThan(0, $result['checked']);
        // Si el patrón dejara de reconocer arreglos, el recuento no coincidiría con los 'can:' del archivo.
        $this->assertSame(substr_count($source, "'can:"), $result['checked']);
    }

    public function test_the_scan_flags_can_without_auth(): void
    {
        $result = self::scan("\$router->get('/x', [XController::class, 'a'], ['can:manage_x']);");

        $this->assertSame(1, $result['checked']);
        $this->assertSame(["'can:manage_x'"], $result['offenders']);
    }

    public function test_the_scan_flags_auth_placed_after_can(): void
    {
        $result = self::scan("\$router->get('/x', [XController::class, 'a'], ['can:manage_x', 'auth']);");

        $this->assertCount(1, $result['offenders']);
    }

    public function test_the_scan_accepts_auth_before_can_even_across_lines(): void
    {
        $result = self::scan("\$router->get('/x', [XController::class, 'a'], [\n    'auth',\n    'can:manage_x',\n]);");

        $this->assertSame(1, $result['checked']);
        $this->assertSame([], $result['offenders']);
    }
}
