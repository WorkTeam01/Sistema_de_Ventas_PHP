<?php

namespace Tests\Integration\Models;

use App\Models\Client;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Cubre métodos de Client con funciones de fecha específicas de MySQL
 * (CURDATE(), YEAR(), MONTH()) que SQLite in-memory no soporta.
 * Ver ClientRepositoryTest para el resto de la cobertura (SQLite).
 */
final class ClientRepositoryMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private Client $client;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->client = new Client();
    }

    /**
     * $sqlDate es una expresión SQL (NOW(), NOW() - INTERVAL 1 MONTH, ...) evaluada
     * por el propio MariaDB, no una fecha calculada con date()/strtotime() de PHP
     * — el reloj/timezone de PHP y el del servidor MariaDB pueden no coincidir.
     */
    private function createClientWithDate(string $sqlDate, string $email): void
    {
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente, fyh_creacion)
            VALUES ('Cliente Test', '" . random_int(10000000, 99999999) . "', '70000000', '$email', $sqlDate)");
    }

    public function test_countNewThisMonth_counts_only_current_month(): void
    {
        $this->createClientWithDate('NOW()', 'este-mes@example.com');
        $this->createClientWithDate('NOW() - INTERVAL 1 MONTH', 'mes-pasado@example.com');

        $this->assertSame(1, $this->client->countNewThisMonth());
    }

    public function test_countNewThisMonth_returns_zero_when_no_clients_this_month(): void
    {
        $this->createClientWithDate('NOW() - INTERVAL 1 MONTH', 'mes-pasado@example.com');

        $this->assertSame(0, $this->client->countNewThisMonth());
    }
}
