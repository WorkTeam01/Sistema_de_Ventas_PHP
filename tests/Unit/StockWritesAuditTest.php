<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * El stock solo puede cambiar desde los modelos que lo hacen con control de piso,
 * registro y transacción; un UPDATE suelto en un controlador o helper lo saltaría.
 */
final class StockWritesAuditTest extends TestCase
{
    private const ALLOWED = [
        'Models/Sale.php',
        'Models/SaleReturn.php',
        'Models/Purchase.php',
        'Models/StockAdjustment.php',
    ];

    private const STOCK_UPDATE = '/UPDATE\s+tb_almacen\s+SET\b[^;"\']*?\bstock\s*=/i';

    /** @return list<string> rutas relativas a app/ con un UPDATE de stock */
    public static function filesWritingStock(string $appDir): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (preg_match(self::STOCK_UPDATE, (string)file_get_contents($file->getPathname()))) {
                $found[] = str_replace('\\', '/', substr($file->getPathname(), strlen($appDir) + 1));
            }
        }

        sort($found);

        return $found;
    }

    public function test_only_allowed_models_update_stock(): void
    {
        $unexpected = array_values(array_diff(self::filesWritingStock(dirname(__DIR__, 2) . '/app'), self::ALLOWED));

        $this->assertSame([], $unexpected, 'UPDATE tb_almacen SET stock fuera de los modelos permitidos');
    }

    public function test_the_scan_detects_a_stock_update_in_a_controller(): void
    {
        $dir = sys_get_temp_dir() . '/stock_audit_' . getmypid();
        mkdir($dir . '/Controllers', 0777, true);
        file_put_contents(
            $dir . '/Controllers/FooController.php',
            "<?php\n\$db->prepare(\"UPDATE tb_almacen\n   SET nombre = ?, stock = ? WHERE id_producto = ?\");\n"
        );
        file_put_contents($dir . '/Controllers/BarController.php', "<?php\n\$db->prepare(\"UPDATE tb_almacen SET stock_minimo = ? WHERE id_producto = ?\");\n");

        try {
            $this->assertSame(['Controllers/FooController.php'], self::filesWritingStock($dir));
        } finally {
            array_map('unlink', glob($dir . '/Controllers/*.php'));
            rmdir($dir . '/Controllers');
            rmdir($dir);
        }
    }
}
