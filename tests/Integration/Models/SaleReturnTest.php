<?php

namespace Tests\Integration\Models;

use App\Models\Sale;
use App\Models\SaleReturn;
use PDO;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class SaleReturnTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private SaleReturn $returns;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->returns = new SaleReturn();

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
             VALUES ('Vendedor', 'vendedor@test.com', 'hash', 1)"
        );
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec(
            "INSERT INTO tb_clientes
                (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
             VALUES ('Cliente', '123', '70000000', 'cliente@test.com')"
        );
        $this->pdo->exec(
            "INSERT INTO tb_almacen
                (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo,
                 stock_maximo, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P001', 'Producto', 5.00, 12.50, 7, 1, 20,
                     '2026-01-01', 1, 1)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_carrito
                (nro_venta, id_producto, cantidad, precio_unitario)
             VALUES (10, 1, 5, 12.50)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado)
             VALUES (10, 1, 1, 62.50)"
        );
    }

    public function test_register_reingresa_stock_calcula_monto_y_acumula_pendiente(): void
    {
        $result = $this->returns->register(1, 'Cliente devolvió unidades', [1 => 2]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['id']);
        $this->assertSame(1, $result['nro']);
        $this->assertEqualsWithDelta(25.00, $result['monto'], 0.001);
        $this->assertSame(9, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
        $this->assertSame(
            2,
            (int)$this->pdo->query(
                "SELECT cantidad FROM tb_movimientos_inventario WHERE tipo = 'Devolución'"
            )->fetchColumn()
        );

        $pending = $this->returns->pendingByVenta(1);
        $this->assertSame(3, (int)$pending[0]['pendiente']);

        $second = $this->returns->register(1, 'Devolución restante', [1 => 3]);
        $this->assertTrue($second['ok']);
        $this->assertSame(0, (int)$this->returns->pendingByVenta(1)[0]['pendiente']);
        $third = $this->returns->register(1, 'Excede el pendiente', [1 => 1]);
        $this->assertFalse($third['ok']);
        $this->assertSame('conflict', $third['error']);
        $this->assertSame(2, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_activity_log
             WHERE entidad = "sale_return" AND accion = "create"'
        )->fetchColumn());
        $this->assertSame(12, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
    }

    public function test_register_rejects_conflict_without_changing_stock_or_rows(): void
    {
        $result = $this->returns->register(1, 'Cantidad inválida', [1 => 6]);

        $this->assertFalse($result['ok']);
        $this->assertSame('conflict', $result['error']);
        $this->assertSame(7, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_rejects_empty_and_invalid_requests_without_side_effects(): void
    {
        $empty = $this->returns->register(1, 'Sin cantidades', [1 => 0]);
        $this->assertFalse($empty['ok']);
        $this->assertSame('empty', $empty['error']);

        $invalid = $this->returns->register(1, 'Cantidad inválida', [1 => -1]);
        $this->assertFalse($invalid['ok']);
        $this->assertSame('validation', $invalid['error']);

        $this->assertSame(7, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_rejects_missing_reason_and_sale(): void
    {
        $missingReason = $this->returns->register(1, '   ', [1 => 1]);
        $this->assertFalse($missingReason['ok']);
        $this->assertSame('validation', $missingReason['error']);

        $missingSale = $this->returns->register(999, 'Venta inexistente', [1 => 1]);
        $this->assertFalse($missingSale['ok']);
        $this->assertSame('not_found', $missingSale['error']);
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_does_not_modify_original_sale_or_items(): void
    {
        $sale = new Sale();
        $before = $sale->findWithDetails(1);

        $result = $this->returns->register(1, 'Producto no requerido', [1 => 1]);
        $this->assertTrue($result['ok']);

        $after = $sale->findWithDetails(1);
        $this->assertSame($before['total_pagado'], $after['total_pagado']);
        $this->assertSame($before['nro_venta'], $after['nro_venta']);
        $this->assertCount(1, $after['items']);
        $this->assertSame($before['items'][0]['cantidad'], $after['items'][0]['cantidad']);
        $this->assertSame(
            $before['items'][0]['precio_unitario'],
            $after['items'][0]['precio_unitario']
        );
        $this->assertEqualsWithDelta(12.50, (float)$after['items'][0]['precio_venta'], 0.001);
    }

    public function test_register_records_return_activity_with_amount_and_details(): void
    {
        $result = $this->returns->register(1, 'Producto defectuoso', [1 => 2]);
        $this->assertTrue($result['ok']);

        $log = $this->pdo->query(
            "SELECT entidad, entidad_id, datos_nuevos
             FROM tb_activity_log
             WHERE entidad = 'sale_return'"
        )->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('sale_return', $log['entidad']);
        $this->assertSame($result['id'], (int)$log['entidad_id']);

        $data = json_decode($log['datos_nuevos'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(10, $data['nro_venta']);
        $this->assertSame('Producto defectuoso', $data['motivo']);
        $this->assertEqualsWithDelta(25.00, $data['monto_devuelto'], 0.001);
        $this->assertSame(1, $data['detalle'][0]['id_producto']);
        $this->assertSame(2, $data['detalle'][0]['cantidad']);
        $this->assertSame(12.50, $data['detalle'][0]['precio_unitario']);
    }

    public function test_sale_is_referenced_after_registering_return(): void
    {
        $sale = new Sale();

        $this->assertFalse($sale->isReferenced(1));

        $this->returns->register(1, 'Producto no requerido', [1 => 1]);

        $this->assertTrue($sale->isReferenced(1));
    }

    private function replaceSaleLines(array $lines, string $totalPaid): void
    {
        $this->pdo->exec('DELETE FROM tb_carrito WHERE nro_venta = 10');
        $insert = $this->pdo->prepare(
            'INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario)
             VALUES (10, 1, ?, ?)'
        );
        foreach ($lines as [$quantity, $price]) {
            $insert->execute([$quantity, $price]);
        }
        $this->pdo->prepare('UPDATE tb_ventas SET total_pagado = ? WHERE id_venta = 1')
            ->execute([$totalPaid]);
    }

    private function stock(): int
    {
        return (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn();
    }

    public function test_pending_sums_repeated_lines_of_the_same_product(): void
    {
        $this->replaceSaleLines([[3, '12.50'], [2, '12.50']], '62.50');

        $pending = $this->returns->pendingByVenta(1);
        $this->assertCount(1, $pending);
        $this->assertSame(5, (int)$pending[0]['vendida']);
        $this->assertSame(5, (int)$pending[0]['pendiente']);

        $this->assertTrue($this->returns->register(1, 'Parcial', [1 => 4])['ok']);
        $this->assertSame(1, (int)$this->returns->pendingByVenta(1)[0]['pendiente']);

        $excess = $this->returns->register(1, 'Excede', [1 => 2]);
        $this->assertSame('conflict', $excess['error']);
    }

    public function test_register_caps_amount_to_remaining_and_restores_full_stock(): void
    {
        $this->replaceSaleLines([[5, '12.50']], '40.00');

        $result = $this->returns->register(1, 'Venta con descuento', [1 => 5]);

        $this->assertTrue($result['ok']);
        $this->assertEqualsWithDelta(40.00, $result['monto'], 0.001);
        $this->assertSame(12, $this->stock());
    }

    public function test_register_records_zero_amount_when_remaining_is_exhausted(): void
    {
        $this->replaceSaleLines([[5, '12.50']], '25.00');

        $first = $this->returns->register(1, 'Primera', [1 => 2]);
        $this->assertEqualsWithDelta(25.00, $first['monto'], 0.001);

        $second = $this->returns->register(1, 'Segunda', [1 => 3]);
        $this->assertTrue($second['ok']);
        $this->assertEqualsWithDelta(0.00, $second['monto'], 0.001);
        $this->assertSame(12, $this->stock());
        $this->assertSame(
            '25.00',
            number_format((float)$this->pdo->query(
                'SELECT SUM(monto) FROM tb_devoluciones WHERE id_venta = 1'
            )->fetchColumn(), 2, '.', '')
        );
    }

    public function test_register_compares_amounts_in_cents_without_float_drift(): void
    {
        $this->replaceSaleLines([[3, '11.10']], '33.30');

        foreach ([1, 2, 3] as $n) {
            $result = $this->returns->register(1, "Devolución $n", [1 => 1]);
            $this->assertTrue($result['ok']);
            $this->assertEqualsWithDelta(11.10, $result['monto'], 0.0001);
        }
    }

    public function test_issue_token_returns_32_hex_characters_and_is_random(): void
    {
        $token = SaleReturn::issueToken();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token);
        $this->assertNotSame($token, SaleReturn::issueToken());
    }

    public function test_register_with_same_token_replays_original_without_touching_stock(): void
    {
        $token = SaleReturn::issueToken();

        $first = $this->returns->register(1, 'Primera', [1 => 2], $token);
        $this->assertTrue($first['ok']);
        $this->assertFalse($first['replayed']);

        $second = $this->returns->register(1, 'Primera', [1 => 2], $token);

        $this->assertTrue($second['ok']);
        $this->assertTrue($second['replayed']);
        $this->assertSame($first['id'], $second['id']);
        $this->assertEqualsWithDelta($first['monto'], $second['monto'], 0.001);
        $this->assertSame(9, $this->stock());
        $this->assertSame(1, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query(
            "SELECT COUNT(*) FROM tb_activity_log WHERE entidad = 'sale_return'"
        )->fetchColumn());
    }

    public function test_register_with_distinct_tokens_allows_two_partial_returns(): void
    {
        $first = $this->returns->register(1, 'Primera', [1 => 2], SaleReturn::issueToken());
        $second = $this->returns->register(1, 'Segunda', [1 => 3], SaleReturn::issueToken());

        $this->assertTrue($first['ok']);
        $this->assertTrue($second['ok']);
        $this->assertFalse($second['replayed']);
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertSame(12, $this->stock());
    }

    public function test_register_stores_token_and_works_without_it(): void
    {
        $token = SaleReturn::issueToken();
        $this->returns->register(1, 'Con token', [1 => 1], $token);
        $this->returns->register(1, 'Sin token', [1 => 1]);

        $this->assertSame($token, $this->pdo->query(
            'SELECT token_formulario FROM tb_devoluciones WHERE id_devolucion = 1'
        )->fetchColumn());
        $this->assertNull($this->pdo->query(
            'SELECT token_formulario FROM tb_devoluciones WHERE id_devolucion = 2'
        )->fetchColumn() ?: null);
    }

    public function test_register_rejects_malformed_token(): void
    {
        $result = $this->returns->register(1, 'Token roto', [1 => 1], 'no-es-un-token');

        $this->assertFalse($result['ok']);
        $this->assertSame('validation', $result['error']);
        $this->assertSame(7, $this->stock());
    }

    public function test_register_resolves_token_collision_by_returning_the_original(): void
    {
        $token = SaleReturn::issueToken();
        $this->pdo->prepare(
            "INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto, token_formulario)
             VALUES (50, 1, 1, 'Ganadora', 25.00, ?)"
        )->execute([$token]);

        // Simula la carrera: la comprobación previa no ve la fila, el INSERT choca con el UNIQUE
        $racing = new class extends SaleReturn {
            private bool $first = true;

            protected function findByToken(int $idVenta, string $token): ?array
            {
                if ($this->first) {
                    $this->first = false;
                    return null;
                }
                return parent::findByToken($idVenta, $token);
            }
        };

        $result = $racing->register(1, 'Perdedora', [1 => 2], $token);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['replayed']);
        $this->assertSame(1, $result['id']);
        $this->assertSame(7, $this->stock());
        $this->assertSame(1, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_reports_conflict_when_temporary_number_collides(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto)
             VALUES (-1, 1, 1, 'Concurrente', 0)"
        );

        $result = $this->returns->register(1, 'Choque', [1 => 1], SaleReturn::issueToken());

        $this->assertFalse($result['ok']);
        $this->assertSame('conflict', $result['error']);
        $this->assertSame(7, $this->stock());
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devolucion_items'
        )->fetchColumn());
    }

    public function test_register_replays_when_the_winner_already_consumed_all_pending(): void
    {
        $token = SaleReturn::issueToken();
        $winner = $this->returns->register(1, 'Ganadora', [1 => 5], $token);
        $this->assertTrue($winner['ok']);

        // El perdedor pasó la comprobación previa antes del commit del ganador; al obtener
        // el bloqueo de la venta ya no queda pendiente, pero debe recibir la original y no un conflicto
        $racing = new class extends SaleReturn {
            private bool $first = true;

            protected function findByToken(int $idVenta, string $token): ?array
            {
                if ($this->first) {
                    $this->first = false;
                    return null;
                }
                return parent::findByToken($idVenta, $token);
            }
        };

        $result = $racing->register(1, 'Perdedora', [1 => 5], $token);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['replayed']);
        $this->assertSame($winner['id'], $result['id']);
        $this->assertSame(12, $this->stock());
        $this->assertSame(1, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }
}
