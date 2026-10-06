<?php

namespace Tests\Unit\Core;

use App\Core\Config;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionFailureTest extends TestCase
{
    private array $envBackup = [];
    private string $logFile = '';
    private string|false $previousLog = '';

    protected function setUp(): void
    {
        Config::load();
        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
            $this->envBackup[$key] = $_ENV[$key] ?? null;
            unset($_ENV[$key]);
        }

        $_ENV['DB_HOST'] = 'host-inexistente.invalid';
        $_ENV['DB_NAME'] = 'bd_secreta';
        $_ENV['DB_USER'] = 'usuario_secreto';
        $_ENV['DB_PASS'] = 'clave_secreta';

        $this->logFile = tempnam(sys_get_temp_dir(), 'dbfail');
        $this->previousLog = ini_set('error_log', $this->logFile);
        Database::reset();
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
        ini_set('error_log', $this->previousLog === false ? '' : $this->previousLog);
        @unlink($this->logFile);
        // Reinyectar SQLite: dejar el singleton vacío haría que los tests Unit posteriores
        // (orden aleatorio) intenten conectar a un MySQL que en CI no existe.
        Database::set(new \PDO('sqlite::memory:', null, null, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]));
    }

    public function test_failed_connection_throws_generic_exception_without_credentials(): void
    {
        try {
            Database::getInstance();
            $this->fail('Se esperaba RuntimeException.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringNotContainsString('host-inexistente', $message);
            $this->assertStringNotContainsString('usuario_secreto', $message);
            $this->assertStringNotContainsString('clave_secreta', $message);
            $this->assertStringNotContainsString('bd_secreta', $message);
            $this->assertNull($e->getPrevious(), 'La excepción PDO no debe viajar adjunta.');
        }
    }

    public function test_failed_connection_logs_the_detail(): void
    {
        try {
            Database::getInstance();
        } catch (\RuntimeException) {
        }

        $log = (string) file_get_contents($this->logFile);
        $this->assertStringContainsString('Error de conexión a la base de datos', $log);
        $this->assertStringNotContainsString('clave_secreta', $log);
    }

    public function test_failed_connection_does_not_leave_a_half_built_singleton(): void
    {
        try {
            Database::getInstance();
        } catch (\RuntimeException) {
        }

        $this->expectException(\RuntimeException::class);
        Database::getInstance();
    }
}
