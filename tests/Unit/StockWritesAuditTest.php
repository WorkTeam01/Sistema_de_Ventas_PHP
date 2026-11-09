<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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

    /** Tabla de productos, con o sin comillas invertidas. */
    private const TABLE = '`?tb_almacen`?';

    /** La columna `stock` exacta (no `stock_minimo`), con o sin comillas invertidas o prefijo de alias. */
    private const STOCK_COLUMN = '(?<![\w])`?stock`?(?![\w`])';

    /**
     * UPDATE tb_almacen [alias] SET … stock =, INSERT [IGNORE] INTO tb_almacen (… stock …) y
     * REPLACE INTO tb_almacen (que borra y reinserta la fila, así que reescribe el stock).
     */
    private static function patterns(): array
    {
        $table = self::TABLE;
        $stock = self::STOCK_COLUMN;

        return [
            'update'  => "/UPDATE\\s+{$table}(?:\\s+(?:AS\\s+)?\\w+)?\\s+SET\\b[^;\"']*?{$stock}\\s*=/i",
            'insert'  => "/INSERT\\s+(?:IGNORE\\s+)?INTO\\s+{$table}\\s*\\([^)]*?{$stock}[^)]*\\)/i",
            'replace' => "/REPLACE\\s+INTO\\s+{$table}/i",
        ];
    }

    /**
     * Clases de un directorio de modelos que declaran `$table = 'tb_almacen'`, como ruta relativa. Heredar
     * create()/update() de Model sobre esa tabla escribiría stock sin pasar por los escritores controlados.
     *
     * @return list<string>
     */
    public static function modelsDeclaringProductTable(string $modelsDir): array
    {
        $found = [];

        foreach (glob($modelsDir . '/*.php') ?: [] as $file) {
            if (preg_match('/\$table\s*=\s*[\'"]`?tb_almacen`?[\'"]/', (string)file_get_contents($file)) === 1) {
                $found[] = basename($file);
            }
        }

        sort($found);

        return $found;
    }

    /** Indica si el código fuente contiene SQL que escribe `tb_almacen.stock`. */
    public static function sourceWritesStock(string $source): bool
    {
        foreach (self::patterns() as $pattern) {
            if (preg_match($pattern, $source) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> rutas relativas a app/ con un UPDATE de stock */
    public static function filesWritingStock(string $appDir): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (self::sourceWritesStock((string)file_get_contents($file->getPathname()))) {
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

    /** @return array<string, array{string}> */
    public static function writingStatements(): array
    {
        return [
            'update simple'        => ['UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?'],
            'update con alias'     => ['UPDATE tb_almacen al SET al.stock = 0'],
            'update con AS'        => ['UPDATE tb_almacen AS al SET al.nombre = ?, al.stock = ?'],
            'update con backticks' => ['UPDATE `tb_almacen` SET `stock` = ?'],
            'update en líneas'     => ["UPDATE tb_almacen\n   SET nombre = ?,\n       stock = ?\n WHERE id_producto = ?"],
            'insert'               => ['INSERT INTO tb_almacen (codigo, stock) VALUES (?, ?)'],
            'insert con backticks' => ['INSERT INTO `tb_almacen` (`codigo`, `stock`) VALUES (?, ?)'],
            'insert ignore'        => ['INSERT IGNORE INTO tb_almacen (stock) VALUES (1)'],
            'insert en líneas'     => ["INSERT INTO tb_almacen\n  (codigo,\n   stock,\n   nombre)\nVALUES (?, ?, ?)"],
            'replace'              => ['REPLACE INTO tb_almacen (codigo, nombre) VALUES (?, ?)'],
            'replace con backticks' => ['REPLACE INTO `tb_almacen` SET nombre = ?'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function harmlessStatements(): array
    {
        return [
            'update de stock_minimo'  => ['UPDATE tb_almacen SET stock_minimo = ? WHERE id_producto = ?'],
            'update de stock_maximo'  => ['UPDATE `tb_almacen` al SET al.stock_maximo = ?'],
            'update de otra tabla'    => ['UPDATE tb_categorias SET stock = 1'],
            'insert sin stock'        => ['INSERT INTO tb_almacen (codigo, nombre, stock_minimo) VALUES (?, ?, ?)'],
            'insert en otra tabla'    => ['INSERT INTO tb_ajustes_stock (id_producto, stock_anterior, stock_posterior) VALUES (?, ?, ?)'],
            'select'                  => ['SELECT stock FROM tb_almacen WHERE stock = 0'],
            'replace de otra tabla'   => ['REPLACE INTO tb_categorias (nombre_categoria) VALUES (?)'],
        ];
    }

    #[DataProvider('writingStatements')]
    public function test_the_scan_detects_statements_that_write_stock(string $sql): void
    {
        $this->assertTrue(self::sourceWritesStock("<?php\n\$db->prepare(\"{$sql}\");\n"));
    }

    #[DataProvider('harmlessStatements')]
    public function test_the_scan_ignores_statements_that_do_not_write_stock(string $sql): void
    {
        $this->assertFalse(self::sourceWritesStock("<?php\n\$db->prepare(\"{$sql}\");\n"));
    }

    public function test_only_product_declares_the_stock_table(): void
    {
        $this->assertSame(['Product.php'], self::modelsDeclaringProductTable(dirname(__DIR__, 2) . '/app/Models'));
    }

    public function test_the_model_scan_flags_another_class_declaring_the_stock_table(): void
    {
        $dir = sys_get_temp_dir() . '/stock_models_' . getmypid();
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/Product.php', "<?php\nclass Product { protected string \$table = 'tb_almacen'; }\n");
        file_put_contents($dir . '/Shadow.php', "<?php\nclass Shadow { protected string \$table = \"tb_almacen\"; }\n");
        file_put_contents($dir . '/Category.php', "<?php\nclass Category { protected string \$table = 'tb_categorias'; }\n");

        try {
            $this->assertSame(['Product.php', 'Shadow.php'], self::modelsDeclaringProductTable($dir));
        } finally {
            array_map('unlink', glob($dir . '/*.php'));
            rmdir($dir);
        }
    }
}
