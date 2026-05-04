<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use Tests\TestCase;

final class ClientValidationTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validEmailProvider')]
    public function test_isValidEmail_accepts_valid_emails(string $email): void
    {
        $this->assertTrue($this->client->isValidEmail($email));
    }

    public static function validEmailProvider(): array
    {
        return [
            'simple'          => ['user@example.com'],
            'subdominio'      => ['user@mail.example.com'],
            'con_punto'       => ['first.last@example.org'],
            'con_plus'        => ['user+tag@example.com'],
            'numeros'         => ['user123@example123.com'],
            'dominio_largo'   => ['a@b.co'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidEmailProvider')]
    public function test_isValidEmail_rejects_invalid_emails(string $email): void
    {
        $this->assertFalse($this->client->isValidEmail($email));
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'sin_arroba'     => ['userexample.com'],
            'sin_dominio'    => ['user@'],
            'sin_usuario'    => ['@example.com'],
            'doble_arroba'   => ['user@@example.com'],
            'solo_espacios'  => ['   '],
            'vacio'          => [''],
        ];
    }
}
