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
 * sigue corriendo en local sin exigir MariaDB instalada. Con la variable de entorno
 * `CI` definida, en cambio, falla.
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
            $this->mariaDbUnavailable(
                'BD de test MariaDB no configurada. Copia .env.testing.example a .env.testing '
                    . 'para correr este test.'
            );
        }

        // Cada setUp hace DROP DATABASE: el nombre debe ser inequívocamente de test para que una
        // configuración equivocada nunca borre la BD de desarrollo.
        if (stripos($name, 'test') === false) {
            $this->fail("DB_TEST_NAME='{$name}' no parece una BD de test (debe contener \"test\"): el trait la borra y la recrea.");
        }

        // Nombre fijo: una sola BD de test, recreada en cada test. Por eso la suite no debe
        // ejecutarse en paralelo (dos procesos se pisarían el DROP/CREATE); una corrida
        // interrumpida deja esa misma BD, que la siguiente recrea.
        $this->mariaDbTestDatabase = $name;

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
            $this->mariaDbUnavailable('No se pudo conectar a la BD de test MariaDB: ' . $e->getMessage());
        }

        Database::set($pdo);

        return $pdo;
    }

    /**
     * En CI la ausencia de MariaDB es un fallo (un salto silencioso dejaría sin cubrir lo que
     * solo se puede probar contra el motor real); en local se salta para no exigir el servidor.
     */
    private function mariaDbUnavailable(string $reason): never
    {
        $ci = $_ENV['CI'] ?? getenv('CI');
        if ($ci !== false && $ci !== '' && !in_array(strtolower((string)$ci), ['0', 'false'], true)) {
            $this->fail("CI definida y sin MariaDB de test: {$reason}");
        }

        $this->markTestSkipped($reason);
    }

    private function dropAllTables(PDO $pdo): void
    {
        if (!isset($this->mariaDbTestDatabase)) {
            return;
        }

        $pdo->exec("DROP DATABASE IF EXISTS `{$this->mariaDbTestDatabase}`");
    }
}
