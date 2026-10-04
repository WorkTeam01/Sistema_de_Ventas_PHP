<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UploadsHtaccessTest extends TestCase
{
    private const RELATIVE_PATH = 'public/uploads/products/.htaccess';

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_htaccess_exists_and_blocks_php_execution(): void
    {
        $file = $this->root() . '/' . self::RELATIVE_PATH;

        $this->assertFileExists($file);

        $content = (string) file_get_contents($file);
        $this->assertStringContainsString('FilesMatch', $content);
        $this->assertMatchesRegularExpression('/ph(p|tml|ar)/i', $content);
        $this->assertMatchesRegularExpression('/Require all denied|Deny from all/', $content);
    }

    public function test_htaccess_is_not_ignored_by_git(): void
    {
        exec(
            'git -C ' . escapeshellarg($this->root()) . ' check-ignore -q ' . escapeshellarg(self::RELATIVE_PATH),
            $output,
            $exitCode
        );

        // 1 = no ignorado; 0 = ignorado; 128 = no es un repo git (p. ej. artefacto de deploy).
        $this->assertNotSame(0, $exitCode, 'El .htaccess de subidas no puede estar en .gitignore.');
    }
}
