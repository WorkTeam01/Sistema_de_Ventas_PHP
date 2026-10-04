<?php

namespace Tests\Integration\Models;

use App\Core\Config;
use App\Models\RememberToken;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * La expiración se calcula con el reloj de la BD; los vencimientos se siembran con SQL, no con date().
 */
final class RememberTokenMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private RememberToken $tokens;
    private ?string $savedDays = null;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->tokens = new RememberToken();

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, id_rol, password_user) VALUES
             ('Uno', 'uno@x.com', 1, 'x'), ('Dos', 'dos@x.com', 1, 'x')"
        );

        Config::load();
        $this->savedDays = $_ENV['REMEMBER_LIFETIME'] ?? null;
        unset($_ENV['REMEMBER_LIFETIME']);
    }

    protected function tearDown(): void
    {
        if ($this->savedDays === null) {
            unset($_ENV['REMEMBER_LIFETIME']);
        } else {
            $_ENV['REMEMBER_LIFETIME'] = $this->savedDays;
        }

        parent::tearDown();
    }

    /** @return array{0: string, 1: string} selector y validador */
    private static function split(string $cookie): array
    {
        return explode(':', $cookie, 2);
    }

    private function row(string $selector): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tb_sesiones_recordadas WHERE selector = ?');
        $stmt->execute([$selector]);

        return $stmt->fetch();
    }

    public function test_issue_stores_only_the_validator_hash(): void
    {
        $issued = $this->tokens->issue(1, '1.1.1.1', 'UA');
        [$selector, $validator] = self::split($issued['cookie']);

        $row = $this->row($selector);
        $this->assertSame(24, strlen($selector));
        $this->assertSame(hash('sha256', $validator), $row['token_hash']);
        $this->assertNotSame($validator, $row['token_hash']);
    }

    public function test_issue_uses_remember_days_from_env(): void
    {
        $_ENV['REMEMBER_LIFETIME'] = '3';
        [$selector] = self::split($this->tokens->issue(1, '1.1.1.1', null)['cookie']);

        $days = (int)$this->pdo->query(
            "SELECT DATEDIFF(fyh_expira, NOW()) FROM tb_sesiones_recordadas WHERE selector = '$selector'"
        )->fetchColumn();
        $this->assertSame(3, $days);
    }

    public function test_issue_purges_expired_rows_of_that_user_only(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_sesiones_recordadas (id_usuario, selector, token_hash, fyh_expira, ip) VALUES
             (1, 'vencido-uno', 'h', NOW() - INTERVAL 1 HOUR, '1.1.1.1'),
             (2, 'vencido-dos', 'h', NOW() - INTERVAL 1 HOUR, '1.1.1.1')"
        );

        $this->tokens->issue(1, '1.1.1.1', null);

        $this->assertFalse($this->row('vencido-uno'));
        $this->assertIsArray($this->row('vencido-dos'));
    }

    public function test_consume_rotates_only_that_row_and_old_validator_stops_working(): void
    {
        $a = $this->tokens->issue(1, '1.1.1.1', 'UA');
        $b = $this->tokens->issue(1, '1.1.1.1', 'UA');
        [$selA, $valA] = self::split($a['cookie']);
        [$selB, $valB] = self::split($b['cookie']);

        $result = $this->tokens->consume($selA, $valA, '1.1.1.1', 'UA');

        $this->assertSame(1, $result['id_usuario']);
        $this->assertFalse($result['ip_changed']);
        [, $newValA] = self::split($result['cookie']);
        $this->assertNotSame($valA, $newValA);
        $this->assertNotNull($this->row($selA)['fyh_ultimo_uso']);

        $this->assertNull($this->tokens->consume($selA, $valA, '1.1.1.1', 'UA'));
        $this->assertNotNull($this->tokens->consume($selB, $valB, '1.1.1.1', 'UA'));
    }

    public function test_consume_reports_ip_change_and_stores_new_ip_without_rejecting(): void
    {
        [$selector, $validator] = self::split($this->tokens->issue(1, '1.1.1.1', 'UA')['cookie']);

        $result = $this->tokens->consume($selector, $validator, '2.2.2.2', 'Otro UA');

        $this->assertTrue($result['ip_changed']);
        $row = $this->row($selector);
        $this->assertSame('2.2.2.2', $row['ip']);
        $this->assertSame('Otro UA', $row['user_agent']);
    }

    public function test_invalid_tokens_return_null_and_leave_rows_untouched(): void
    {
        [$selector, $validator] = self::split($this->tokens->issue(1, '1.1.1.1', 'UA')['cookie']);
        $before = $this->row($selector);

        $this->assertNull($this->tokens->consume($selector, 'validador-incorrecto', '9.9.9.9', 'X'));
        $this->assertNull($this->tokens->consume('selector-inexistente-123', $validator, '1.1.1.1', 'UA'));

        $this->assertSame($before, $this->row($selector));
    }

    public function test_expired_token_is_rejected_and_row_is_kept(): void
    {
        [$selector, $validator] = self::split($this->tokens->issue(1, '1.1.1.1', null)['cookie']);
        $this->pdo->exec("UPDATE tb_sesiones_recordadas SET fyh_expira = NOW() - INTERVAL 1 SECOND");

        $this->assertNull($this->tokens->consume($selector, $validator, '1.1.1.1', null));
        $this->assertIsArray($this->row($selector));
    }

    public function test_revoke_deletes_only_that_device(): void
    {
        [$selA] = self::split($this->tokens->issue(1, '1.1.1.1', null)['cookie']);
        [$selB] = self::split($this->tokens->issue(1, '1.1.1.1', null)['cookie']);

        $this->tokens->revoke($selA);

        $this->assertFalse($this->row($selA));
        $this->assertIsArray($this->row($selB));
    }

    public function test_revoke_all_for_deletes_every_device_of_the_user_only(): void
    {
        $this->tokens->issue(1, '1.1.1.1', null);
        $this->tokens->issue(1, '1.1.1.1', null);
        [$other] = self::split($this->tokens->issue(2, '1.1.1.1', null)['cookie']);

        $this->tokens->revokeAllFor(1);

        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM tb_sesiones_recordadas WHERE id_usuario = 1')->fetchColumn();
        $this->assertSame(0, $count);
        $this->assertIsArray($this->row($other));
    }
}
