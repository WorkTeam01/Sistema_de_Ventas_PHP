<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Ejecuta un test *MariaDbTest en un proceso aparte con credenciales inválidas para comprobar
 * que con CI definida falla y que sin CI se salta.
 */
final class MariaDbRequiredInCiTest extends TestCase
{
    private const TARGET = 'tests/Integration/Models/ClientRepositoryMariaDbTest.php';

    /** @return array{code:int, output:string} */
    private function runTargetWithBadCredentials(?string $ci): array
    {
        $env = getenv();
        unset($env['CI']);
        if ($ci !== null) {
            $env['CI'] = $ci;
        }
        $env['DB_TEST_HOST'] = '127.0.0.1';
        $env['DB_TEST_PORT'] = '1';
        $env['DB_TEST_NAME'] = 'inexistente';
        $env['DB_TEST_USER'] = 'nadie';
        $env['DB_TEST_PASS'] = 'mala';

        $root = dirname(__DIR__, 2);
        $proc = proc_open(
            [PHP_BINARY, 'vendor/bin/phpunit', '--no-configuration', '--bootstrap', 'tests/bootstrap.php', self::TARGET],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
            $env
        );
        $this->assertIsResource($proc);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['code' => proc_close($proc), 'output' => $output];
    }

    public function testFailsWhenCiIsDefinedAndMariaDbIsUnreachable(): void
    {
        $result = $this->runTargetWithBadCredentials('true');

        $this->assertNotSame(0, $result['code'], $result['output']);
        $this->assertStringContainsString('CI', $result['output']);
        $this->assertStringNotContainsString('Skipped: 1', $result['output']);
    }

    public function testSkipsWhenCiIsNotDefinedAndMariaDbIsUnreachable(): void
    {
        $result = $this->runTargetWithBadCredentials(null);

        $this->assertSame(0, $result['code'], $result['output']);
        $this->assertStringContainsString('Skipped', $result['output']);
    }

    public function testCiSetToFalseCountsAsNotCi(): void
    {
        $result = $this->runTargetWithBadCredentials('false');

        $this->assertSame(0, $result['code'], $result['output']);
    }
}
