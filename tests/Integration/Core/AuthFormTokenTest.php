<?php

namespace Tests\Integration\Core;

use App\Core\Auth;
use Tests\TestCase;

/** Los tokens de formulario de un solo uso evitan que un doble envío repita una escritura. */
final class AuthFormTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_a_token_can_be_consumed_only_once(): void
    {
        $token = Auth::issueFormToken('stock_adjustment');

        $this->assertTrue(Auth::consumeFormToken('stock_adjustment', $token));
        $this->assertFalse(Auth::consumeFormToken('stock_adjustment', $token));
    }

    public function test_tokens_from_several_open_tabs_are_all_valid(): void
    {
        $first = Auth::issueFormToken('stock_adjustment');
        $second = Auth::issueFormToken('stock_adjustment');

        $this->assertTrue(Auth::consumeFormToken('stock_adjustment', $second));
        $this->assertTrue(Auth::consumeFormToken('stock_adjustment', $first));
    }

    public function test_a_token_is_not_valid_for_another_scope(): void
    {
        $token = Auth::issueFormToken('stock_adjustment');

        $this->assertFalse(Auth::consumeFormToken('otro_formulario', $token));
        $this->assertTrue(Auth::consumeFormToken('stock_adjustment', $token));
    }

    public function test_unknown_and_empty_tokens_are_rejected(): void
    {
        Auth::issueFormToken('stock_adjustment');

        $this->assertFalse(Auth::consumeFormToken('stock_adjustment', 'inventado'));
        $this->assertFalse(Auth::consumeFormToken('stock_adjustment', ''));
    }

    public function test_only_the_most_recent_tokens_are_kept(): void
    {
        $oldest = Auth::issueFormToken('stock_adjustment');
        for ($i = 0; $i < 20; $i++) {
            Auth::issueFormToken('stock_adjustment');
        }

        $this->assertFalse(Auth::consumeFormToken('stock_adjustment', $oldest));
    }
}
