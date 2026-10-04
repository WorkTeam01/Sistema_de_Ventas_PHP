<?php

namespace Tests\Unit\Services;

use App\Core\Config;
use App\Services\EmailService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class EmailServiceTest extends TestCase
{
    private ?string $savedMinutes = null;

    protected function setUp(): void
    {
        Config::load();
        $this->savedMinutes = $_ENV['RESET_TOKEN_MINUTES'] ?? null;
        unset($_ENV['RESET_TOKEN_MINUTES']);
    }

    protected function tearDown(): void
    {
        if ($this->savedMinutes === null) {
            unset($_ENV['RESET_TOKEN_MINUTES']);
        } else {
            $_ENV['RESET_TOKEN_MINUTES'] = $this->savedMinutes;
        }
    }

    private function body(string $nombres, string $url): string
    {
        $method = new ReflectionMethod(EmailService::class, 'buildHtmlBody');

        return $method->invoke(new EmailService(), $nombres, $url);
    }

    public function test_html_body_escapes_user_name_and_url(): void
    {
        $html = $this->body('<script>alert(1)</script>"', 'http://x/?a="><img src=x onerror=1>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_html_body_shows_configured_token_minutes(): void
    {
        $_ENV['RESET_TOKEN_MINUTES'] = '30';
        $this->assertStringContainsString('<strong>30 minutos</strong>', $this->body('Ana', 'http://x/r/abc'));

        unset($_ENV['RESET_TOKEN_MINUTES']);
        $this->assertStringContainsString('<strong>60 minutos</strong>', $this->body('Ana', 'http://x/r/abc'));
    }
}
