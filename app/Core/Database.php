<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Singleton que gestiona la conexión PDO a la base de datos MySQL.
 *
 * Usar Database::getInstance()->getConnection() para obtener la conexión.
 */
class Database
{
    private static ?self $instance = null;
    private ?PDO $connection = null;

    private function __construct() {}

    /**
     * Devuelve la instancia única, creando la conexión si aún no existe.
     *
     * @return self Instancia Singleton.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->connect();
        }

        return self::$instance;
    }

    /**
     * Crea la conexión PDO con charset utf8mb4, modo de error por excepción
     * y fetch mode asociativo. Termina la ejecución si la conexión falla.
     */
    private function connect(): void
    {
        $host = Config::get('DB_HOST', 'localhost');
        $db   = Config::get('DB_NAME', Config::get('DB_DATABASE', 'test'));
        $user = Config::get('DB_USER', Config::get('DB_USERNAME', 'root'));
        $pass = Config::get('DB_PASS', Config::get('DB_PASSWORD', ''));
        $charset = 'utf8mb4';

        try {
            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    /**
     * Devuelve la conexión PDO activa, reconectando si es null.
     *
     * @return PDO Conexión activa.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    // Compatibilidad hacia atrás para código existente que espera PDO directo.
    /**
     * Alias estático de getConnection() para compatibilidad con código legacy.
     *
     * @return PDO Conexión activa.
     */
    public static function pdo(): PDO
    {
        return self::getInstance()->getConnection();
    }
}
