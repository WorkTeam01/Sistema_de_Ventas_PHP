<?php

namespace App\Core;

use PDO;

/**
 * Clase abstracta base para todos los modelos MVC.
 *
 * Provee operaciones CRUD genéricas sobre la tabla configurada en $table.
 * Las subclases deben declarar $table y opcionalmente $primaryKey.
 */
abstract class Model
{
    /** Conexión PDO activa (alias principal). */
    protected PDO $db;
    /** Alias de $db para compatibilidad con código legacy. */
    protected PDO $pdo;
    /** Nombre de la tabla en la base de datos. */
    protected string $table;
    /** Nombre de la columna de clave primaria. */
    protected string $primaryKey = 'id';

    /**
     * Inicializa $db y $pdo con la conexión Singleton de Database.
     */
    public function __construct()
    {
        $connection = Database::getInstance()->getConnection();
        $this->db = $connection;
        $this->pdo = $connection; // alias para compatibilidad
    }

    /**
     * Devuelve todos los registros de la tabla.
     *
     * @return array Lista de registros como arrays asociativos.
     */
    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca un registro por su clave primaria.
     *
     * @param int|string $id Valor de la PK.
     * @return array|false Registro encontrado o false si no existe.
     */
    public function find(int|string $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Inserta un nuevo registro en la tabla.
     *
     * @param array $data Pares columna => valor a insertar.
     * @return int|false ID generado del nuevo registro, o false si falló.
     */
    public function create(array $data): int|false
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);

        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})");

        if ($stmt->execute($data)) {
            return (int) $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Actualiza los campos de un registro identificado por su PK.
     *
     * @param int|string $id   Valor de la PK.
     * @param array      $data Pares columna => valor a actualizar.
     * @return bool True si la operación fue exitosa.
     */
    public function update(int|string $id, array $data): bool
    {
        $fields = '';
        foreach (array_keys($data) as $key) {
            $fields .= "{$key} = :{$key}, ";
        }
        $fields = rtrim($fields, ', ');

        $sql = "UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Elimina un registro por su clave primaria.
     *
     * @param int|string $id Valor de la PK.
     * @return bool True si se eliminó al menos un registro.
     */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Cuenta el total de registros en la tabla.
     *
     * @return int Total de filas.
     */
    public function count(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table}");
        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Indica si el registro tiene dependencias en otras tablas.
     * Devuelve false por defecto; las subclases deben sobreescribir si aplica.
     *
     * @param int|string $id Valor de la PK.
     * @return bool True si el registro está referenciado y no se puede eliminar.
     */
    public function isReferenced(int|string $id): bool
    {
        return false;
    }

    /**
     * Ejecuta una consulta SQL arbitraria con parámetros y devuelve todos los resultados.
     *
     * @param string $sql    Sentencia SQL con placeholders.
     * @param array  $params Valores para los placeholders.
     * @return array Lista de registros.
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Compatibilidad hacia atrás
    /** @deprecated Usar all() */
    public function findAll(): array
    {
        return $this->all();
    }

    /** @deprecated Usar create() */
    public function insert(array $data): bool
    {
        return $this->create($data) !== false;
    }
}
