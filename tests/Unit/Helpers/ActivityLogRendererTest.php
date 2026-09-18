<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ActivityLogRenderer;
use Tests\TestCase;

final class ActivityLogRendererTest extends TestCase
{
    public function test_sale_return_fields_are_rendered_with_spanish_labels(): void
    {
        $rows = ActivityLogRenderer::prepare(json_encode([
            'id_venta' => 12,
            'motivo' => 'Producto defectuoso',
            'monto_devuelto' => 30.50,
            'detalle' => [
                [
                    'id_producto' => 7,
                    'cantidad' => 1,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(
            ['ID Venta', 'Motivo', 'Monto devuelto', 'Detalle'],
            array_column($rows, 'label')
        );
        $this->assertSame(['scalar', 'scalar', 'scalar', 'list'], array_column($rows, 'type'));
    }

    public function test_create_action_uses_default_badge_class(): void
    {
        $this->assertSame('badge-primary', ActivityLogRenderer::badgeClass('create'));
    }
}
