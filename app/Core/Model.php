<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $connection = Database::getInstance()->getConnection();
        $this->db = $connection;
        $this->pdo = $connection; // alias para compatibilidad
    }

    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int|string $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

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

    public function delete(int|string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table}");
        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function isReferenced(int|string $id): bool
    {
        return false;
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Compatibilidad hacia atrás
    public function findAll(): array
    {
        return $this->all();
    }

    public function insert(array $data): bool
    {
        return $this->create($data) !== false;
    }
}
