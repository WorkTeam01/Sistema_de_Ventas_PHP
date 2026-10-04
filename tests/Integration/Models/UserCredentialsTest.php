<?php

namespace Tests\Integration\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class UserCredentialsTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private User $user;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->user = new User();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
    }

    #[DataProvider('emailProvider')]
    public function test_normalizeEmail_trims_and_lowercases(string $input, string $expected): void
    {
        $this->assertSame($expected, User::normalizeEmail($input));
    }

    public static function emailProvider(): array
    {
        return [
            'mayúsculas y espacios' => ['  ADMIN@Sistema.com ', 'admin@sistema.com'],
            'ya normalizado' => ['a@b.com', 'a@b.com'],
            'tabulación y salto' => ["\tUser@X.COM\n", 'user@x.com'],
        ];
    }

    public function test_createUser_stores_normalized_email_and_findByEmail_normalizes_lookup(): void
    {
        $this->user->createUser('Admin', '  ADMIN@Sistema.com ', 1, 'secret123');

        $row = $this->user->find(1);
        $this->assertSame('admin@sistema.com', $row['email']);
        $this->assertIsArray($this->user->findByEmail('  ADMIN@SISTEMA.com'));
    }

    public function test_emailExists_normalizes_input(): void
    {
        $this->user->createUser('A', 'a@b.com', 1, 'secret123');

        $this->assertTrue($this->user->emailExists(' A@B.COM '));
        $this->assertFalse($this->user->emailExists(' A@B.COM ', 1));
    }

    public function test_updateUser_and_updateProfileInfo_store_normalized_email(): void
    {
        $this->user->createUser('A', 'a@b.com', 1, 'secret123');

        $this->user->updateUser(1, 'A', ' NEW@B.com ', 1);
        $this->assertSame('new@b.com', $this->user->find(1)['email']);

        $this->user->updateProfileInfo(1, 'A', "  Otro@B.COM\n");
        $this->assertSame('otro@b.com', $this->user->find(1)['email']);
    }

    #[DataProvider('passwordProvider')]
    public function test_validatePassword_enforces_minimum_length(string $password, bool $valid): void
    {
        $this->assertSame($valid, User::validatePassword($password));
    }

    public static function passwordProvider(): array
    {
        return [
            '7 caracteres' => ['1234567', false],
            '8 caracteres' => ['12345678', true],
            'vacía' => ['', false],
            '8 multibyte' => ['contraña', true],
            '7 multibyte' => ['contrañ', false],
        ];
    }

    public function test_min_password_length_is_8(): void
    {
        $this->assertSame(8, User::MIN_PASSWORD_LENGTH);
    }

    public function test_verifyCurrentPassword_accepts_correct_and_rejects_wrong(): void
    {
        $this->user->createUser('A', 'a@b.com', 1, 'secret123');

        $this->assertTrue($this->user->verifyCurrentPassword(1, 'secret123'));
        $this->assertFalse($this->user->verifyCurrentPassword(1, 'otra-clave'));
        $this->assertFalse($this->user->verifyCurrentPassword(999, 'secret123'));
    }
}
