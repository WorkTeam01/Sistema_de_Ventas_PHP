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
            $instance = new self();
            $instance->connect();
            self::$instance = $instance;
        }

        return self::$instance;
    }

    /**
     * Desplazamiento UTC de la zona horaria de PHP (APP_TIMEZONE) en formato '+HH:MM', ya entrecomillado.
     * Se fija en la sesión de MySQL para que NOW(), CURDATE() y los DEFAULT CURRENT_TIMESTAMP usen el
     * mismo reloj que date() de PHP en vez del de la zona del servidor de BD. Se usa el desplazamiento
     * y no el nombre porque este último exige que el servidor tenga cargadas las tablas de zonas.
     */
    private function quotedUtcOffset(): string
    {
        return "'" . (new \DateTimeImmutable('now'))->format('P') . "'";
    }

    /**
     * Crea la conexión PDO con charset utf8mb4, modo de error por excepción
     * y fetch mode asociativo. Si la conexión falla lanza una RuntimeException genérica.
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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = " . $this->quotedUtcOffset(),
            ]);
        } catch (PDOException $e) {
            // El mensaje de PDO incluye host y usuario: solo va al log, nunca a la excepción.
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            throw new \RuntimeException('No se pudo conectar a la base de datos.');
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

    /**
     * Inyecta una conexión PDO externa (útil para tests con SQLite in-memory).
     * No afecta al flujo normal de producción si nunca se llama.
     */
    public static function set(PDO $pdo): void
    {
        self::$instance = new self();
        self::$instance->connection = $pdo;
    }

    /**
     * Limpia el singleton (útil en tearDown de tests de integración).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
