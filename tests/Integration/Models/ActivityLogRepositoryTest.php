<?php

namespace Tests\Integration\Models;

use App\Models\ActivityLog;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ActivityLogRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private ActivityLog $log;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->log = new ActivityLog();
        $this->seedUser();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed
    // -------------------------------------------------------------------------

    private function seedUser(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Admin Test', 'admin@test.com', 'hash', 1)");
    }

    private function insertLog(array $overrides = []): int
    {
        $data = array_merge([
            'id_usuario'     => 1,
            'usuario_nombre' => 'Admin Test',
            'accion'         => 'delete',
            'entidad'        => 'sale',
            'entidad_id'     => 10,
            'descripcion'    => 'Venta Nro 10 eliminada',
            'datos_anteriores' => json_encode(['nro_venta' => 10, 'total_pagado' => '150.00']),
            'datos_nuevos'   => null,
            'ip_address'     => '127.0.0.1',
        ], $overrides);

        return (int) $this->log->create($data);
    }

    // -------------------------------------------------------------------------
    // create (store)
    // -------------------------------------------------------------------------

    public function test_create_inserts_log_with_all_fields(): void
    {
        $id = $this->insertLog();

        $this->assertGreaterThan(0, $id);

        $row = $this->log->find($id);
        $this->assertNotFalse($row);
        $this->assertSame('delete',          $row['accion']);
        $this->assertSame('sale',            $row['entidad']);
        $this->assertSame(10,                (int)$row['entidad_id']);
        $this->assertSame('Admin Test',      $row['usuario_nombre']);
        $this->assertSame(1,                 (int)$row['id_usuario']);
        $this->assertSame('127.0.0.1',       $row['ip_address']);
        $this->assertNotNull($row['datos_anteriores']);
        $this->assertNull($row['datos_nuevos']);
        $this->assertNotNull($row['fyh_creacion']);
    }

    public function test_create_allows_null_entity_id(): void
    {
        $id = $this->insertLog(['entidad_id' => null]);
        $row = $this->log->find($id);
        $this->assertNull($row['entidad_id']);
    }

    public function test_create_allows_null_usuario(): void
    {
        $id = $this->insertLog(['id_usuario' => null]);
        $row = $this->log->find($id);
        $this->assertNull($row['id_usuario']);
        $this->assertSame('Admin Test', $row['usuario_nombre']);
    }

    public function test_datos_anteriores_stores_valid_json(): void
    {
        $before = ['nro_venta' => 5, 'total_pagado' => '200.00', 'nombre_cliente' => 'Juan'];
        $id = $this->insertLog(['datos_anteriores' => json_encode($before, JSON_UNESCAPED_UNICODE)]);
        $row = $this->log->find($id);

        $decoded = json_decode($row['datos_anteriores'], true);
        $this->assertSame(5,       $decoded['nro_venta']);
        $this->assertSame('Juan',  $decoded['nombre_cliente']);
    }

    // -------------------------------------------------------------------------
    // record() — método estático
    // -------------------------------------------------------------------------

    public function test_record_does_not_throw_when_user_session_is_absent(): void
    {
        // Sin sesión activa, record() debe silenciar el error y no insertar nada
        // (Auth::user() retorna null/empty, pero el try/catch lo absorbe).
        // Lo que verificamos es que no lanza excepción.
        $this->expectNotToPerformAssertions();
        ActivityLog::record('delete', 'sale', 1, 'Test sin sesión');
    }

    // -------------------------------------------------------------------------
    // usuario_nombre como campo desnormalizado
    // -------------------------------------------------------------------------

    /**
     * SQLite sin PRAGMA foreign_keys = ON no aplica ON DELETE SET NULL.
     * Este test verifica el diseño: usuario_nombre se desnormaliza al insertar
     * para que sea recuperable incluso si id_usuario queda NULL en MySQL.
     */
    public function test_usuario_nombre_is_persisted_independently_of_id_usuario(): void
    {
        // Log con usuario válido
        $id = $this->insertLog(['id_usuario' => 1, 'usuario_nombre' => 'Admin Test']);
        $row = $this->log->find($id);
        $this->assertSame('Admin Test', $row['usuario_nombre']);
        $this->assertSame(1, (int)$row['id_usuario']);

        // Log sin usuario (como quedaría en MySQL tras ON DELETE SET NULL)
        $idOrfan = $this->insertLog(['id_usuario' => null, 'usuario_nombre' => 'Admin Test']);
        $rowOrfan = $this->log->find($idOrfan);
        $this->assertNull($rowOrfan['id_usuario'],              'id_usuario puede ser NULL');
        $this->assertSame('Admin Test', $rowOrfan['usuario_nombre'], 'usuario_nombre se conserva');
    }

    // -------------------------------------------------------------------------
    // Múltiples registros — orden de inserción
    // -------------------------------------------------------------------------

    public function test_logs_are_retrieved_in_insertion_order(): void
    {
        $this->insertLog(['accion' => 'delete',       'entidad' => 'sale',     'descripcion' => 'primero']);
        $this->insertLog(['accion' => 'price_change', 'entidad' => 'product',  'descripcion' => 'segundo']);
        $this->insertLog(['accion' => 'delete',       'entidad' => 'purchase', 'descripcion' => 'tercero']);

        $all = $this->log->all();
        $this->assertCount(3, $all);
        $this->assertSame('primero', $all[0]['descripcion']);
        $this->assertSame('tercero', $all[2]['descripcion']);
    }
}
