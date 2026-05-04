<?php

namespace Tests\Concerns;

use App\Core\Database;
use PDO;

/**
 * Crea una BD SQLite in-memory fresca antes de cada test de integración
 * y la registra como singleton para que los modelos la usen sin tocar MySQL.
 */
trait RefreshDatabase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        Database::set($this->pdo);

        $schema = file_get_contents(__DIR__ . '/../fixtures/schema.sqlite.sql');
        $this->pdo->exec($schema);
    }

    protected function tearDown(): void
    {
        // Reinyectar un PDO vacío en lugar de resetear a null,
        // para que los tests Unit que se ejecuten después puedan
        // instanciar modelos sin intentar conectar a MySQL.
        Database::set(new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]));

        parent::tearDown();
    }
}
