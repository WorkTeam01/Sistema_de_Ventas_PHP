<?php

namespace Tests\Concerns;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Igual que RefreshDatabase pero contra una MariaDB real (no SQLite in-memory).
 * Se usa solo en tests que dependen de funciones de fecha específicas de MySQL
 * (CURDATE(), NOW(), YEAR(), DATE_FORMAT(), ...) que SQLite no soporta.
 *
 * Si la BD de test no está disponible (credenciales en .env.testing ausentes,
 * o el servidor no responde), el test se salta en vez de fallar — así la suite
 * sigue corriendo en local sin exigir MariaDB instalada.
 */
trait RefreshMariaDatabase
{
    protected PDO $pdo;

    private string $mariaDbTestDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = $this->connectOrSkip();

        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
        $this->pdo->exec($schema);
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            $this->dropAllTables($this->pdo);
        }

        parent::tearDown();
    }

    private function connectOrSkip(): PDO
    {
        $host = $_ENV['DB_TEST_HOST'] ?? getenv('DB_TEST_HOST');
        $port = $_ENV['DB_TEST_PORT'] ?? getenv('DB_TEST_PORT') ?: '3306';
        $name = $_ENV['DB_TEST_NAME'] ?? getenv('DB_TEST_NAME');
        $user = $_ENV['DB_TEST_USER'] ?? getenv('DB_TEST_USER');
        $pass = $_ENV['DB_TEST_PASS'] ?? getenv('DB_TEST_PASS') ?: '';

        if (!$host || !$name || !$user) {
            $this->markTestSkipped(
                'BD de test MariaDB no configurada. Copia .env.testing.example a .env.testing '
                    . 'para correr este test.'
            );
        }

        // Nombre único por proceso: paratest corre varios procesos PHP en paralelo, y si
        // todos comparten el mismo nombre de BD, el DROP/CREATE de un proceso invalida la
        // sesión de otro a mitad de test ("Unknown database"). Sufijar con el PID aísla
        // cada proceso en su propia BD física, sin tocar la config del usuario.
        $this->mariaDbTestDatabase = $name . '_' . getmypid();

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port}",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $pdo->exec("DROP DATABASE IF EXISTS `{$this->mariaDbTestDatabase}`");
            $pdo->exec("CREATE DATABASE `{$this->mariaDbTestDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `{$this->mariaDbTestDatabase}`");
        } catch (PDOException $e) {
            $this->markTestSkipped('No se pudo conectar a la BD de test MariaDB: ' . $e->getMessage());
        }

        Database::set($pdo);

        return $pdo;
    }

    private function dropAllTables(PDO $pdo): void
    {
        if (!isset($this->mariaDbTestDatabase)) {
            return;
        }

        $pdo->exec("DROP DATABASE IF EXISTS `{$this->mariaDbTestDatabase}`");
    }
}
