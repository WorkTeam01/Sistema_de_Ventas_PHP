<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private ?PDO $connection = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->connect();
        }

        return self::$instance;
    }

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

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    // Compatibilidad hacia atrás para código existente que espera PDO directo.
    public static function pdo(): PDO
    {
        return self::getInstance()->getConnection();
    }
}
