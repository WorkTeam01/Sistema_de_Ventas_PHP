<?php
$type = (string)$document['tipo'];
$canRegisterReceipt = $type === 'orden_compra'
    && $canManage
    && $document['estado'] !== 'cancelada';
$prefix = $type === 'cotizacion' ? 'COT' : 'OC';
$typePlural = $type === 'cotizacion' ? 'Cotizaciones' : 'Órdenes de compra';
$number = $prefix . '-' . str_pad((string)$document['id_documento'], 6, '0', STR_PAD_LEFT);
$contactName = $type === 'cotizacion'
    ? ($document['nombre_cliente'] ?? '—')
    : ($document['nombre_proveedor'] ?? '—');
$beforeAfter = static function (?string $json): string {
    $decoded = $json === null ? null : json_decode($json, true);
    return $decoded === null
        ? 'Sin versión anterior'
        : json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
};
?>
<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($number, ENT_QUOTES, 'UTF-8') ?></h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/documents?tipo=<?= rawurlencode($type) ?>"><?= htmlspecialchars($typePlural, ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($number, ENT_QUOTES, 'UTF-8') ?></li>
                    </ol>
                </div>
                <div class="no-print">
                    <button type="button" class="btn btn-outline-secondary" data-action="print-document">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </button>
                    <?php if ($canManage): ?>
                        <a class="btn btn-primary" href="<?= BASE_URL ?>/documents/edit/<?= (int)$document['id_documento'] ?>">
                            <i class="fas fa-pencil-alt mr-1"></i> Corregir
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h2 class="card-title"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($number, ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="card-tools">
                        <span class="badge badge-info"><?= htmlspecialchars(ucfirst($document['estado']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        Registro comercial interno. No es una boleta, factura, guía de despacho ni DTE emitido ante el SII.
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <strong><?= $type === 'cotizacion' ? 'Cliente' : 'Proveedor' ?>:</strong>
                            <?= htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') ?><br>
                            <?php if ($type === 'cotizacion' && !empty($document['nit_ci_cliente'])): ?>
                                <strong>RUT:</strong> <?= htmlspecialchars($document['nit_ci_cliente'], ENT_QUOTES, 'UTF-8') ?><br>
                            <?php elseif ($type === 'orden_compra' && !empty($document['empresa'])): ?>
                                <strong>Empresa:</strong> <?= htmlspecialchars($document['empresa'], ENT_QUOTES, 'UTF-8') ?><br>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6 text-sm-right">
                            <strong>Fecha:</strong> <?= htmlspecialchars($document['fecha_documento'], ENT_QUOTES, 'UTF-8') ?><br>
                            <strong>Vencimiento:</strong> <?= htmlspecialchars($document['fecha_vencimiento'] ?? 'No indicado', ENT_QUOTES, 'UTF-8') ?><br>
                            <strong>Registrado por:</strong> <?= htmlspecialchars($document['nombre_usuario'] ?? 'Usuario eliminado', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Unidad</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Precio unitario</th>
                                    <th class="text-right">Subtotal</th>
                                    <?php if ($canRegisterReceipt): ?>
                                        <th class="no-print">Acción</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($document['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['codigo_producto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['nombre_producto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-right"><?= (int)$item['cantidad'] ?></td>
                                        <td class="text-right"><?= htmlspecialchars(APP_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$item['precio_unitario'], 2, ',', '.') ?></td>
                                        <td class="text-right"><?= htmlspecialchars(APP_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$item['subtotal'], 2, ',', '.') ?></td>
                                        <?php if ($canRegisterReceipt): ?>
                                            <td class="no-print">
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="<?= BASE_URL ?>/purchases/create?<?= htmlspecialchars(http_build_query([
                                                       'id_producto' => (int)$item['id_producto'],
                                                       'id_proveedor' => (int)$document['id_proveedor'],
                                                       'cantidad' => (int)$item['cantidad'],
                                                       'precio_compra' => number_format((float)$item['precio_unitario'], 2, '.', ''),
                                                       'origen_oc' => (int)$document['id_documento'],
                                                   ]), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-box-open mr-1"></i> Registrar ingreso
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr><th colspan="<?= $canRegisterReceipt ? 6 : 5 ?>" class="text-right">Total</th>
                                    <th class="text-right"><?= htmlspecialchars(APP_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$document['total'], 2, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php if (!empty($document['observaciones'])): ?>
                        <h3 class="h6">Observaciones</h3>
                        <p><?= nl2br(htmlspecialchars($document['observaciones'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-outline card-secondary history-section">
                <div class="card-header"><h2 class="card-title">Historial de cambios</h2></div>
                <div class="card-body">
                    <p class="text-muted">Cada corrección conserva la versión anterior, la nueva versión, el usuario y la fecha.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th><th>Versiones</th></tr></thead>
                            <tbody>
                                <?php foreach ($history as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(ucfirst($entry['accion']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($entry['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <details>
                                                <summary>Ver datos</summary>
                                                <strong>Antes</strong>
                                                <pre><?= htmlspecialchars($beforeAfter($entry['datos_anteriores']), ENT_QUOTES, 'UTF-8') ?></pre>
                                                <strong>Después</strong>
                                                <pre><?= htmlspecialchars($beforeAfter($entry['datos_nuevos']), ENT_QUOTES, 'UTF-8') ?></pre>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</section>
