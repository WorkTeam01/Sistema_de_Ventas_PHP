<?php

namespace Tests\Integration\Models;

use App\Core\Config;
use App\Models\AuthAttempt;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Las ventanas de tiempo se calculan con el reloj de la BD, así que se prueban contra MariaDB real;
 * las fechas pasadas se siembran con NOW() - INTERVAL en SQL, nunca con date() de PHP.
 */
final class AuthAttemptMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const ENV_VARS = ['LOGIN_MAX_PER_EMAIL', 'LOGIN_MAX_PER_IP', 'LOGIN_WINDOW_MINUTES', 'RESET_COOLDOWN_MINUTES', 'RESET_MAX_PER_IP_HOUR'];

    private AuthAttempt $attempts;
    private array $savedEnv = [];

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->attempts = new AuthAttempt();

        // Los tests no dependen del .env local: se carga primero y se limpian los umbrales.
        Config::load();
        foreach (self::ENV_VARS as $var) {
            $this->savedEnv[$var] = $_ENV[$var] ?? null;
            unset($_ENV[$var]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $var => $value) {
            if ($value === null) {
                unset($_ENV[$var]);
            } else {
                $_ENV[$var] = $value;
            }
        }

        parent::tearDown();
    }

    /** Registra N intentos y devuelve el id del último. */
    private function registerMany(int $n, string $ip, string $correo, ?int $userId = null, string $tipo = 'login'): int
    {
        $id = 0;
        for ($i = 0; $i < $n; $i++) {
            $id = $this->attempts->register($tipo, $ip, $correo, $userId);
        }

        return $id;
    }

    public function test_sixth_attempt_for_same_email_is_blocked_and_fifth_is_not(): void
    {
        $fifth = 0;
        for ($i = 1; $i <= 5; $i++) {
            $fifth = $this->attempts->register('login', '10.0.0.' . $i, 'a@b.com', null);
        }
        $this->assertFalse($this->attempts->isBlocked('login', $fifth, '10.0.0.5', 'a@b.com'));

        $sixth = $this->attempts->register('login', '10.0.0.6', 'a@b.com', null);
        $this->assertTrue($this->attempts->isBlocked('login', $sixth, '10.0.0.6', 'a@b.com'));
    }

    public function test_twenty_first_attempt_from_same_ip_is_blocked_across_emails(): void
    {
        $id = 0;
        for ($i = 1; $i <= 20; $i++) {
            $id = $this->attempts->register('login', '9.9.9.9', "u$i@b.com", null);
        }
        $this->assertFalse($this->attempts->isBlocked('login', $id, '9.9.9.9', 'u20@b.com'));

        $id = $this->attempts->register('login', '9.9.9.9', 'u21@b.com', null);
        $this->assertTrue($this->attempts->isBlocked('login', $id, '9.9.9.9', 'u21@b.com'));
    }

    public function test_nonexistent_email_counts_like_any_other(): void
    {
        $id = $this->registerMany(6, '1.1.1.1', 'nadie@nada.com', null);

        $this->assertTrue($this->attempts->isBlocked('login', $id, '1.1.1.1', 'nadie@nada.com'));
    }

    public function test_email_is_normalized_when_registering_and_counting(): void
    {
        $this->attempts->register('login', '1.1.1.1', '  A@B.com ', null);
        $this->registerMany(4, '1.1.1.2', 'a@b.com');
        $id = $this->attempts->register('login', '1.1.1.3', 'A@B.COM', null);

        $this->assertTrue($this->attempts->isBlocked('login', $id, '1.1.1.3', ' a@b.com'));
    }

    public function test_email_longer_than_the_column_is_registered_and_counted(): void
    {
        $long = str_repeat('a', 300) . '@test.com';

        $this->registerMany(5, '1.1.1.1', $long);
        $id = $this->attempts->register('login', '1.1.1.2', $long, null);

        $this->assertTrue($this->attempts->isBlocked('login', $id, '1.1.1.2', $long));
    }

    public function test_burst_of_ten_lets_only_the_first_five_by_id_through(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->attempts->register('login', '2.2.2.2', 'victima@b.com', 1);
        }

        $allowed = array_filter(
            $ids,
            fn(int $id) => !$this->attempts->isBlocked('login', $id, '2.2.2.2', 'victima@b.com')
        );

        $this->assertSame(array_slice($ids, 0, 5), array_values($allowed));
    }

    public function test_attempts_outside_the_window_do_not_count(): void
    {
        $this->registerMany(5, '3.3.3.3', 'a@b.com');
        $this->pdo->exec("UPDATE tb_intentos_auth SET fyh_creacion = NOW() - INTERVAL 20 MINUTE");

        $id = $this->attempts->register('login', '3.3.3.3', 'a@b.com', null);

        $this->assertFalse($this->attempts->isBlocked('login', $id, '3.3.3.3', 'a@b.com'));
    }

    public function test_clear_resets_the_email_counter_only(): void
    {
        $this->registerMany(5, '4.4.4.4', 'a@b.com');
        $this->registerMany(2, '4.4.4.5', 'otro@b.com');

        $this->attempts->clear('login', 'A@b.com');

        $id = $this->attempts->register('login', '4.4.4.4', 'a@b.com', null);
        $this->assertFalse($this->attempts->isBlocked('login', $id, '4.4.4.4', 'a@b.com'));
        $this->assertSame(2, $this->attempts->countSince('login', '4.4.4.5', 60));
    }

    public function test_types_are_counted_separately(): void
    {
        $this->registerMany(5, '5.5.5.5', 'a@b.com', null, 'reset');
        $id = $this->attempts->register('login', '5.5.5.5', 'a@b.com', null);

        $this->assertFalse($this->attempts->isBlocked('login', $id, '5.5.5.5', 'a@b.com'));
        $this->assertSame(5, $this->attempts->countSince('reset', '5.5.5.5', 3600));
    }

    public function test_countSince_respects_the_window(): void
    {
        $this->registerMany(3, '6.6.6.6', 'a@b.com', null, 'reset');
        $this->pdo->exec("UPDATE tb_intentos_auth SET fyh_creacion = NOW() - INTERVAL 2 HOUR LIMIT 2");

        $this->assertSame(1, $this->attempts->countSince('reset', '6.6.6.6', 3600));
        $this->assertSame(3, $this->attempts->countSince('reset', '6.6.6.6', 3 * 3600));
    }

    public function test_recentForUser_counts_previous_attempts_of_that_user_only(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('R')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('A', 'a@b.com', 'x', 1), ('B', 'b@b.com', 'x', 1)");

        $this->attempts->register('reset', '7.7.7.7', 'a@b.com', 1);
        $this->attempts->register('reset', '7.7.7.7', 'b@b.com', 2);
        $current = $this->attempts->register('reset', '7.7.7.7', 'a@b.com', 1);

        $this->assertSame(1, $this->attempts->recentForUser('reset', 1, 300, $current));
        $this->assertSame(0, $this->attempts->recentForUser('reset', 3, 300, $current));
    }

    public function test_register_purges_attempts_older_than_a_day(): void
    {
        $this->registerMany(2, '8.8.8.8', 'a@b.com');
        $this->pdo->exec("UPDATE tb_intentos_auth SET fyh_creacion = NOW() - INTERVAL 2 DAY");

        $this->attempts->register('login', '8.8.8.8', 'a@b.com', null);

        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_intentos_auth')->fetchColumn());
    }

    public function test_max_per_email_is_read_from_env(): void
    {
        $_ENV['LOGIN_MAX_PER_EMAIL'] = '2';

        $this->registerMany(2, '1.2.3.4', 'a@b.com');
        $third = $this->attempts->register('login', '1.2.3.4', 'a@b.com', null);

        $this->assertTrue($this->attempts->isBlocked('login', $third, '1.2.3.4', 'a@b.com'));
    }

    public function test_max_per_ip_is_read_from_env(): void
    {
        $_ENV['LOGIN_MAX_PER_IP'] = '3';

        $id = 0;
        for ($i = 1; $i <= 4; $i++) {
            $id = $this->attempts->register('login', '1.2.3.4', "u$i@b.com", null);
        }

        $this->assertTrue($this->attempts->isBlocked('login', $id, '1.2.3.4', 'u4@b.com'));
    }

    public function test_window_is_read_from_env(): void
    {
        $_ENV['LOGIN_WINDOW_MINUTES'] = '1';
        $this->registerMany(5, '1.2.3.4', 'a@b.com');
        $this->pdo->exec("UPDATE tb_intentos_auth SET fyh_creacion = NOW() - INTERVAL 2 MINUTE");

        $id = $this->attempts->register('login', '1.2.3.4', 'a@b.com', null);

        $this->assertFalse($this->attempts->isBlocked('login', $id, '1.2.3.4', 'a@b.com'));
    }

    public function test_purge_keeps_attempts_inside_a_window_longer_than_a_day(): void
    {
        $_ENV['LOGIN_WINDOW_MINUTES'] = '1440';
        $this->registerMany(2, '1.2.3.4', 'a@b.com');
        $this->pdo->exec("UPDATE tb_intentos_auth SET fyh_creacion = NOW() - INTERVAL 23 HOUR");

        $this->attempts->register('login', '1.2.3.4', 'a@b.com', null);

        $this->assertSame(3, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_intentos_auth')->fetchColumn());
    }

    public function test_reset_is_throttled_for_second_request_of_same_user_within_cooldown(): void
    {
        $first = $this->attempts->register('reset', '3.3.3.1', 'a@b.com', 7);
        $this->assertFalse($this->attempts->resetThrottled($first, '3.3.3.1', 7));

        $second = $this->attempts->register('reset', '3.3.3.2', 'a@b.com', 7);
        $this->assertTrue($this->attempts->resetThrottled($second, '3.3.3.2', 7));
    }

    public function test_reset_cooldown_expires_with_db_clock(): void
    {
        $this->attempts->register('reset', '3.3.3.1', 'a@b.com', 7);
        $this->pdo->exec('UPDATE tb_intentos_auth SET fyh_creacion = NOW() - INTERVAL 6 MINUTE');

        $next = $this->attempts->register('reset', '3.3.3.1', 'a@b.com', 7);
        $this->assertFalse($this->attempts->resetThrottled($next, '3.3.3.1', 7));
    }

    public function test_reset_sixth_request_from_same_ip_in_an_hour_is_throttled_even_for_unknown_emails(): void
    {
        $id = 0;
        for ($i = 1; $i <= 5; $i++) {
            $id = $this->attempts->register('reset', '4.4.4.4', "n$i@b.com", null);
            $this->assertFalse($this->attempts->resetThrottled($id, '4.4.4.4', null), "solicitud $i");
        }

        $id = $this->attempts->register('reset', '4.4.4.4', 'n6@b.com', null);
        $this->assertTrue($this->attempts->resetThrottled($id, '4.4.4.4', null));
    }

    public function test_login_attempts_do_not_count_toward_reset_limits(): void
    {
        $this->registerMany(10, '5.5.5.5', 'a@b.com', 7, 'login');

        $id = $this->attempts->register('reset', '5.5.5.5', 'a@b.com', 7);
        $this->assertFalse($this->attempts->resetThrottled($id, '5.5.5.5', 7));
    }
}
