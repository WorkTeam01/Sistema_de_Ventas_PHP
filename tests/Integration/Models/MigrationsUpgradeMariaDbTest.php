<?php

namespace Tests\Integration\Models;

use App\Models\User;
use PDOException;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Actualización de una instalación en el estado de 1.18.3 (esquema en tests/fixtures/schema_1.18.3.sql,
 * copia de `git show 1.18.3:database/schema.sql`) con datos representativos: las migraciones 011-014
 * no deben perder ni reescribir filas de negocio.
 */
final class MigrationsUpgradeMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const DIR = __DIR__ . '/../../../database/migrations/';
    private const LEGACY_SCHEMA = __DIR__ . '/../../fixtures/schema_1.18.3.sql';
    private const MIGRATIONS = [
        '011_pos_reservas.sql',
        '012_integridad_unicos.sql',
        '013_tokens_y_auth.sql',
        '014_portabilidad_mysql84.sql',
    ];

    /** Columnas de negocio por tabla (sin fyh_actualizacion, que el motor actualiza solo). */
    private const BUSINESS_COLUMNS = [
        'tb_ventas'           => ['id_venta', 'nro_venta', 'id_cliente', 'id_usuario', 'total_pagado', 'fyh_creacion'],
        'tb_carrito'          => ['id_carrito', 'nro_venta', 'id_producto', 'cantidad', 'precio_unitario'],
        'tb_pagos'            => ['id_pago', 'id_venta', 'id_metodo_pago', 'monto'],
        'tb_metodos_pago'     => ['id_metodo_pago', 'nombre', 'tipo', 'activo'],
        'tb_devoluciones'     => ['id_devolucion', 'nro_devolucion', 'id_venta', 'id_usuario', 'motivo', 'monto'],
        'tb_devolucion_items' => ['id_detalle', 'id_devolucion', 'id_producto', 'cantidad', 'precio_unitario'],
        'tb_compras'          => ['id_compra', 'id_producto', 'nro_compra', 'fecha_compra', 'id_proveedor', 'comprobante', 'id_usuario', 'precio_compra', 'cantidad'],
        'tb_almacen'          => ['id_producto', 'codigo', 'nombre', 'stock', 'precio_compra', 'precio_venta', 'imagen', 'id_usuario', 'id_categoria'],
        'tb_clientes'         => ['id_cliente', 'nombre_cliente', 'nit_ci_cliente', 'email_cliente'],
        'tb_usuarios'         => ['id_usuario', 'nombres', 'email', 'password_user', 'id_rol'],
    ];

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->installLegacySchema();
    }

    private function installLegacySchema(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec("DROP TABLE `{$table}`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec(file_get_contents(self::LEGACY_SCHEMA));
    }

    /** Datos como los de una instalación en uso: ventas con y sin cliente, pagos, devolución, compras, carrito abierto. */
    private function seedRepresentativeData(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador'), ('Vendedor')");
        // Contraseña de 6 caracteres (válida en 1.18.3) y correo con mayúsculas, con tokens en claro.
        $hash = password_hash('abc123', PASSWORD_DEFAULT);
        $this->pdo->prepare(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol, reset_token, reset_token_expiracion,
                                      remember_token, remember_token_expiry, login_intentos)
             VALUES ('Ana', 'Ana.Vendedora@Test.COM', ?, 2, 'resetplain', NOW() + INTERVAL 1 HOUR,
                     'rememberplain', NOW() + INTERVAL 5 DAY, 3),
                    ('Beto', 'beto@test.com', ?, 1, NULL, NULL, NULL, NULL, 0)"
        )->execute([$hash, $hash]);
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion) VALUES ('Prov', '1', 'E', 'D')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente) VALUES ('Cli', '123', '7', 'cli@test.com')");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria)
             VALUES ('P1', 'Prod 1', 'd', 40, 5.50, 10.25, CURDATE(), 'uploads/p1.png', 1, 1),
                    ('P2', 'Prod 2', 'd', 7, 1.10, 2.20, CURDATE(), 'producto_default.png', 1, 1)"
        );
        $this->pdo->exec("INSERT INTO tb_metodos_pago (nombre, tipo) VALUES ('Efectivo', 'efectivo'), ('Tarjeta', 'no_efectivo')");

        // Venta 1: con cliente, pago mixto. Venta 2: sin cliente, efectivo. Venta 3: se devuelve parcialmente.
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario) VALUES (1, 1, 2, 10.25), (1, 2, 1, 2.20), (2, 2, 3, 2.20), (3, 1, 4, 10.25)");
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES
                (1, 1, 2, 22.70), (2, NULL, 2, 6.60), (3, NULL, 1, 41.00)"
        );
        $this->pdo->exec("INSERT INTO tb_pagos (id_venta, id_metodo_pago, monto) VALUES (1, 1, 10.00), (1, 2, 12.70), (2, 1, 6.60), (3, 2, 41.00)");
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto) VALUES (1, 3, 1, 'Defecto', 20.50)");
        $this->pdo->exec("INSERT INTO tb_devolucion_items (id_devolucion, id_producto, cantidad, precio_unitario) VALUES (1, 1, 2, 10.25)");
        $this->pdo->exec(
            "INSERT INTO tb_compras (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
             VALUES (1, 1, CURDATE(), 1, 'c1', 1, 5.50, 20), (2, 2, CURDATE(), 1, 'c2', 1, 1.10, 10)"
        );
        // Carrito abierto de una venta en curso (sin fila en tb_ventas).
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (4, 1, 1)");
    }

    /** @return array<string, array{count: int, hash: string}> */
    private function snapshot(): array
    {
        $this->pdo->exec('SET SESSION group_concat_max_len = 1048576');
        $result = [];
        foreach (self::BUSINESS_COLUMNS as $table => $columns) {
            $concat = implode(", '|', ", array_map(fn($c) => "IFNULL(`{$c}`, '~')", $columns));
            $row = $this->pdo->query(
                "SELECT COUNT(*) AS n, MD5(IFNULL(GROUP_CONCAT(CONCAT({$concat}) ORDER BY `{$columns[0]}` SEPARATOR ';'), '')) AS h
                 FROM `{$table}`"
            )->fetch();
            $result[$table] = ['count' => (int)$row['n'], 'hash' => $row['h']];
        }

        return $result;
    }

    /** @return array<string, string> sumas de negocio que el usuario vería en pantalla */
    private function totals(): array
    {
        return $this->pdo->query(
            "SELECT
                (SELECT SUM(total_pagado) FROM tb_ventas) AS ventas,
                (SELECT SUM(monto) FROM tb_pagos) AS pagos,
                (SELECT SUM(monto) FROM tb_devoluciones) AS devoluciones,
                (SELECT SUM(precio_compra * cantidad) FROM tb_compras) AS compras,
                (SELECT SUM(stock) FROM tb_almacen) AS stock"
        )->fetch();
    }

    private function runMigration(string $file): void
    {
        $this->pdo->exec(file_get_contents(self::DIR . $file));
    }

    private function runAll(): void
    {
        foreach (self::MIGRATIONS as $file) {
            $this->runMigration($file);
        }
    }

    private function scalar(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    private function structureSignature(): string
    {
        $cols = $this->scalar(
            "SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', TABLE_NAME, COLUMN_NAME, COLUMN_TYPE) ORDER BY TABLE_NAME, COLUMN_NAME))
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
        );
        $idx = $this->scalar(
            "SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', TABLE_NAME, INDEX_NAME, COLUMN_NAME, NON_UNIQUE) ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX))
             FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
        );
        $fks = $this->scalar(
            "SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME) ORDER BY TABLE_NAME, CONSTRAINT_NAME))
             FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL"
        );

        return "{$cols}/{$idx}/{$fks}";
    }

    public function testMigrationsKeepEveryBusinessRowAndTotal(): void
    {
        $this->seedRepresentativeData();
        $before = $this->snapshot();
        $totalsBefore = $this->totals();

        $this->runAll();

        $this->assertSame($before, $this->snapshot(), 'Una migración borró o reescribió filas de negocio');
        $this->assertSame($totalsBefore, $this->totals());
        $this->assertSame(1, (int)$this->scalar('SELECT COUNT(*) FROM tb_ventas WHERE id_cliente IS NULL AND nro_venta = 2'));
        $this->assertSame(1, (int)$this->scalar('SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = 4'), 'El carrito abierto debe seguir ahí');
    }

    public function testMigrationsApplyTheExpectedStructuralChanges(): void
    {
        $this->seedRepresentativeData();

        $this->runAll();

        $this->assertSame(0, (int)$this->scalar(
            "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_ventas' AND REFERENCED_TABLE_NAME = 'tb_carrito'"
        ));
        $this->assertSame(0, (int)$this->scalar(
            "SELECT NON_UNIQUE FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_ventas' AND INDEX_NAME = 'uq_venta_nro'"
        ));
        $this->assertSame('varchar', $this->scalar(
            "SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_almacen' AND COLUMN_NAME = 'imagen'"
        ));
        // La secuencia del POS arranca por encima del mayor nro_venta ya usado (el carrito abierto, 4).
        $this->assertSame(4, (int)$this->scalar('SELECT MAX(id_reserva) FROM tb_pos_reservas'));
    }

    public function testExistingLoginsKeepWorkingAndOnlyOldTokensAreCleared(): void
    {
        $this->seedRepresentativeData();

        $this->runAll();

        $user = (new User())->findByEmail('ana.vendedora@test.com');
        $this->assertIsArray($user, 'Un correo guardado con mayúsculas debe seguir encontrándose');
        $this->assertTrue(User::passwordMatches($user, 'abc123'), 'La contraseña de 6 caracteres sigue siendo válida al iniciar sesión');
        $this->assertNull($user['reset_token']);
        $this->assertNull($user['remember_token']);
        $this->assertNull($user['remember_token_expiry']);
        $this->assertSame(2, (int)$this->scalar('SELECT COUNT(*) FROM tb_usuarios'));
    }

    public function testRunningTheMigrationsTwiceChangesNothingTheSecondTime(): void
    {
        $this->seedRepresentativeData();
        $this->runAll();
        $snapshot = $this->snapshot();
        $structure = $this->structureSignature();
        $reservations = (int)$this->scalar('SELECT COUNT(*) FROM tb_pos_reservas');

        $this->runAll();

        $this->assertSame($snapshot, $this->snapshot());
        $this->assertSame($structure, $this->structureSignature());
        $this->assertSame($reservations, (int)$this->scalar('SELECT COUNT(*) FROM tb_pos_reservas'));
    }

    public function testMigrationsThatMustAbortLeaveEveryRowAndTheStructureUntouched(): void
    {
        $this->seedRepresentativeData();
        // Datos que 1.18.3 admitía: nro_venta repetido, venta sin líneas, nro_compra repetido, imagen larga.
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES (1, 2, 5.00), (50, 2, 5.00)');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec(
            "INSERT INTO tb_compras (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
             VALUES (1, 1, CURDATE(), 1, 'dup', 1, 1.00, 1)"
        );
        $this->pdo->exec("UPDATE tb_almacen SET imagen = '" . str_repeat('x', 300) . "' WHERE id_producto = 2");
        $snapshot = $this->snapshot();
        $structure = $this->structureSignature();

        $messages = [];
        foreach (['012_integridad_unicos.sql', '014_portabilidad_mysql84.sql'] as $file) {
            try {
                $this->runMigration($file);
                $this->fail("{$file} debía abortar");
            } catch (PDOException $e) {
                $messages[$file] = $e->getMessage();
            }
        }

        $this->assertStringContainsString('nro_venta repetido', $messages['012_integridad_unicos.sql']);
        $this->assertStringContainsString('venta sin líneas', $messages['012_integridad_unicos.sql']);
        $this->assertStringContainsString('nro_compra repetido', $messages['012_integridad_unicos.sql']);
        $this->assertStringContainsString('imagen de mas de 255 caracteres', $messages['014_portabilidad_mysql84.sql']);
        $this->assertSame($snapshot, $this->snapshot(), 'Una migración abortada modificó filas');
        $this->assertSame($structure, $this->structureSignature(), 'Una migración abortada modificó la estructura');
    }
}
