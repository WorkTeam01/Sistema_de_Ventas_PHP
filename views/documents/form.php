<?php
$editing = $document !== null;
$documentId = (int)($document['id_documento'] ?? 0);
$contactId = (int)($document['id_cliente'] ?? $document['id_proveedor'] ?? 0);
$items = $document['items'] ?? [];
if ($items === []) {
    $items = [['id_producto' => '', 'cantidad' => 1, 'precio_unitario' => '']];
}
$prefix = $type === 'cotizacion' ? 'COT' : 'OC';
$typePlural = $type === 'cotizacion' ? 'Cotizaciones' : 'Órdenes de compra';
?>
<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $editing ? 'Editar' : 'Crear' ?> <?= htmlspecialchars(mb_strtolower($typeLabel), ENT_QUOTES, 'UTF-8') ?></h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/documents?tipo=<?= rawurlencode($type) ?>"><?= htmlspecialchars($typePlural, ENT_QUOTES, 'UTF-8') ?></a></li>
                <li class="breadcrumb-item active"><?= $editing ? $prefix . '-' . $documentId : 'Nuevo' ?></li>
            </ol>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <form method="post" action="<?= BASE_URL ?>/documents<?= $editing ? '/update' : '' ?>" id="commercialDocumentForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id_documento" value="<?= $documentId ?>">
                <?php endif; ?>

                <div class="card card-outline card-primary">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="id_contacto"><?= $type === 'cotizacion' ? 'Cliente' : 'Proveedor' ?> <span class="text-danger">*</span></label>
                                <select id="id_contacto" name="id_contacto" class="form-control select2" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($contacts as $contact): ?>
                                        <?php
                                        $id = (int)($contact['id_cliente'] ?? $contact['id_proveedor']);
                                        $label = $type === 'cotizacion'
                                            ? $contact['nombre_cliente']
                                            : $contact['nombre_proveedor'] . ' — ' . ($contact['empresa'] ?? '');
                                        ?>
                                        <option value="<?= $id ?>" <?= $contactId === $id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="fecha_documento">Fecha <span class="text-danger">*</span></label>
                                <input type="date" id="fecha_documento" name="fecha_documento" class="form-control"
                                    value="<?= htmlspecialchars($document['fecha_documento'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="fecha_vencimiento">Vencimiento (opcional)</label>
                                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control"
                                    value="<?= htmlspecialchars($document['fecha_vencimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" class="form-control" required>
                                    <?php foreach ($statuses as $status => $label): ?>
                                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= ($document['estado'] ?? 'borrador') === $status ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="h5 mb-0">Productos</h2>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-action="add-item">
                                <i class="fas fa-plus mr-1"></i> Agregar producto
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="documentItems"
                                data-currency-symbol="<?= htmlspecialchars(APP_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?>">
                                <thead>
                                    <tr>
                                        <th style="min-width: 250px;">Producto</th>
                                        <th style="width: 120px;">Cantidad</th>
                                        <th style="width: 180px;">Precio unitario</th>
                                        <th style="width: 150px;" class="text-right">Subtotal</th>
                                        <th style="width: 55px;"><span class="sr-only">Quitar</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr class="document-item">
                                            <td>
                                                <select name="items[id_producto][]" class="form-control product-select" required>
                                                    <option value="">Seleccionar producto...</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <?php
                                                        $productId = (int)$product['id_producto'];
                                                        $defaultPrice = $type === 'cotizacion' ? $product['precio_venta'] : $product['precio_compra'];
                                                        ?>
                                                        <option value="<?= $productId ?>"
                                                            data-price="<?= htmlspecialchars((string)$defaultPrice, ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= (int)($item['id_producto'] ?? 0) === $productId ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($product['codigo'] . ' — ' . $product['nombre'] . ' (' . $product['unidad_medida'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" name="items[cantidad][]" class="form-control item-quantity"
                                                min="1" max="1000000" step="1" value="<?= (int)($item['cantidad'] ?? 1) ?>" required></td>
                                            <td><input type="number" name="items[precio_unitario][]" class="form-control item-price"
                                                min="0" max="9999999999.99" step="0.01"
                                                value="<?= htmlspecialchars((string)($item['precio_unitario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></td>
                                            <td class="text-right align-middle item-subtotal">—</td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-action="remove-item" aria-label="Quitar producto">
                                                    <i class="fas fa-times" aria-hidden="true"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Total</th>
                                        <th class="text-right" id="documentTotal">—</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" maxlength="1000"><?= htmlspecialchars($document['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="alert alert-info mb-0">
                            Este documento es una cotización u orden comercial interna. Guardarlo no registra una compra, venta ni movimiento de stock y no genera un DTE del SII.
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap">
                        <a href="<?= BASE_URL ?>/documents?tipo=<?= rawurlencode($type) ?>" class="btn btn-default mr-2 mb-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-save mr-1"></i> Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</section>

<template id="documentItemTemplate">
    <tr class="document-item">
        <td>
            <select name="items[id_producto][]" class="form-control product-select" required>
                <option value="">Seleccionar producto...</option>
                <?php foreach ($products as $product): ?>
                    <?php $defaultPrice = $type === 'cotizacion' ? $product['precio_venta'] : $product['precio_compra']; ?>
                    <option value="<?= (int)$product['id_producto'] ?>" data-price="<?= htmlspecialchars((string)$defaultPrice, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($product['codigo'] . ' — ' . $product['nombre'] . ' (' . $product['unidad_medida'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="items[cantidad][]" class="form-control item-quantity" min="1" max="1000000" step="1" value="1" required></td>
        <td><input type="number" name="items[precio_unitario][]" class="form-control item-price" min="0" max="9999999999.99" step="0.01" value="" required></td>
        <td class="text-right align-middle item-subtotal">—</td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-outline-danger btn-sm" data-action="remove-item" aria-label="Quitar producto">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
</template>
