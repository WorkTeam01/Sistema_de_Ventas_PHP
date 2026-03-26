<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'tb_usuarios';
    protected string $primaryKey = 'id_usuario';

    public function countAll(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function verifyCredentials(string $email, string $plainPassword): array|false
    {
        $user = $this->findByEmail($email);

        if (!$user || !isset($user['password_user'])) {
            return false;
        }

        if (!password_verify($plainPassword, $user['password_user'])) {
            return false;
        }

        return $user;
    }

    public function findAllWithRole(): array
    {
        $stmt = $this->db->query(
            "SELECT us.id_usuario, us.nombres, us.email, us.id_rol, rol.rol
             FROM tb_usuarios us
             INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol
             ORDER BY us.id_usuario DESC"
        );

        return $stmt->fetchAll();
    }

    public function findWithRoleById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT us.id_usuario, us.nombres, us.email, us.id_rol, rol.rol
             FROM tb_usuarios us
             INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol
             WHERE us.id_usuario = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function findAllRoles(): array
    {
        $stmt = $this->db->query("SELECT id_rol, rol FROM tb_roles ORDER BY id_rol ASC");
        return $stmt->fetchAll();
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId !== null) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = ? AND id_usuario <> ?");
            $stmt->execute([$email, $excludeUserId]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createUser(string $nombres, string $email, int $idRol, string $passwordHash): bool
    {
        return $this->insert([
            'nombres' => $nombres,
            'email' => $email,
            'id_rol' => $idRol,
            'password_user' => $passwordHash,
        ]);
    }

    public function updateUser(int $id, string $nombres, string $email, int $idRol, ?string $passwordHash = null): bool
    {
        if ($passwordHash === null) {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET nombres = ?, email = ?, id_rol = ?
                 WHERE {$this->primaryKey} = ?"
            );

            return $stmt->execute([$nombres, $email, $idRol, $id]);
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET nombres = ?, email = ?, id_rol = ?, password_user = ?
             WHERE {$this->primaryKey} = ?"
        );

        return $stmt->execute([$nombres, $email, $idRol, $passwordHash, $id]);
    }
}
