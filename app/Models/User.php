<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'tb_usuarios';
    protected string $primaryKey = 'id_usuario';

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
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
}