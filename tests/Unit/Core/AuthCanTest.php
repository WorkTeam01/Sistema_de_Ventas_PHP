<?php

namespace Tests\Unit\Core;

use App\Core\Auth;
use Tests\TestCase;

final class AuthCanTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function test_can_returns_true_when_permiso_in_session(): void
    {
        $_SESSION['permisos'] = ['view_dashboard', 'manage_users'];

        $this->assertTrue(Auth::can('view_dashboard'));
        $this->assertTrue(Auth::can('manage_users'));
    }

    public function test_can_returns_false_when_permiso_not_in_session(): void
    {
        $_SESSION['permisos'] = ['view_dashboard'];

        $this->assertFalse(Auth::can('manage_users'));
    }

    public function test_can_returns_false_when_session_has_no_permisos(): void
    {
        $this->assertFalse(Auth::can('view_dashboard'));
    }

    public function test_is_admin_returns_true_when_is_superadmin_in_session(): void
    {
        $_SESSION['permisos'] = ['is_superadmin', 'view_dashboard'];

        $this->assertTrue(Auth::isAdmin());
    }

    public function test_is_admin_returns_false_without_is_superadmin(): void
    {
        $_SESSION['permisos'] = ['view_dashboard', 'manage_users'];

        $this->assertFalse(Auth::isAdmin());
    }

    public function test_can_uses_strict_comparison(): void
    {
        $_SESSION['permisos'] = [0, ''];

        $this->assertFalse(Auth::can('view_dashboard'));
    }
}
