<?php

namespace Tests\Integration\Models;

use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

final class PreflightMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const PREFLIGHT = __DIR__ . '/../../../database/preflight_004.sql';

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Ana', 'ana@test.com', 'x', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion) VALUES ('Prov', '1', 'E', 'D')");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P1', 'Prod', 'd', 10, 1, 2, CURDATE(), 1, 1)"
        );
    }

    /** @return array<string, string> comprobacion => valores */
    private function runPreflight(): array
    {
        $rows = $this->pdo->query(file_get_contents(self::PREFLIGHT))->fetchAll();

        return array_column($rows, 'valores', 'comprobacion');
    }

    private function sale(int $nro, bool $withCart = true): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES ({$nro}, 1, 2)");
        if ($withCart) {
            $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ({$nro}, 1, 1)");
        }
    }

    private function purchase(int $nro): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_compras (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
             VALUES (1, {$nro}, CURDATE(), 1, 'c', 1, 1, 1)"
        );
    }

    public function test_clean_database_returns_no_rows(): void
    {
        $this->sale(1);
        $this->purchase(1);

        $this->assertSame([], $this->runPreflight());
    }

    public function test_reports_each_seeded_problem(): void
    {
        $this->pdo->exec('ALTER TABLE tb_ventas DROP INDEX uq_venta_nro');
        $this->pdo->exec('ALTER TABLE tb_compras DROP INDEX uq_compra_nro');
        $this->pdo->exec('ALTER TABLE tb_almacen MODIFY imagen text NOT NULL');

        $this->sale(10);
        $this->sale(10, withCart: false);
        $this->sale(11, withCart: false);
        $this->purchase(20);
        $this->purchase(20);
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (99, 1, 1)");
        $this->pdo->exec("UPDATE tb_almacen SET imagen = '" . str_repeat('x', 300) . "' WHERE id_producto = 1");

        $result = $this->runPreflight();

        $this->assertSame('10', $result['nro_venta repetido']);
        $this->assertSame('11', $result['venta sin lineas en tb_carrito']);
        $this->assertSame('20', $result['nro_compra repetido']);
        $this->assertSame('1', $result['imagen de mas de 255 caracteres (id_producto)']);
        $this->assertSame('99', $result['carrito abierto sin venta (nro_venta)']);
        $this->assertCount(5, $result);
    }

    public function test_preflight_contains_only_a_select(): void
    {
        $sql = file_get_contents(self::PREFLIGHT);
        $code = preg_replace('/--[^\n]*/', '', $sql);

        $this->assertDoesNotMatchRegularExpression('/\b(insert|update|delete|alter|drop|create|truncate|replace)\b/i', $code);
        $this->assertSame(1, preg_match_all('/;/', $code));
    }
}
