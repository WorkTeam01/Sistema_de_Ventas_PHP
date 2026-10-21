<form method="get" action="<?= BASE_URL ?>/inventory" class="form-row align-items-end mb-4">
    <input type="hidden" name="tab" value="movimientos">
    <div class="form-group col-md-3">
        <label for="desde">Desde</label>
        <input type="date" id="desde" name="desde" class="form-control"
            value="<?= htmlspecialchars($filtros['desde'], ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div class="form-group col-md-3">
        <label for="hasta">Hasta</label>
        <input type="date" id="hasta" name="hasta" class="form-control"
            value="<?= htmlspecialchars($filtros['hasta'], ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div class="form-group col-md-4">
        <label for="id_producto">Producto</label>
        <select id="id_producto" name="id_producto" class="form-control select2">
            <option value="">Todos los productos</option>
            <?php foreach ($todosProductos as $product): ?>
                <option value="<?= (int)$product['id_producto'] ?>"
                    <?= (int)$filtros['id_producto'] === (int)$product['id_producto'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($product['codigo'] . ' — ' . $product['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group col-md-2">
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-filter mr-1"></i> Filtrar
        </button>
    </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <p class="text-muted mb-2">
        Período <?= htmlspecialchars($filtros['desde'], ENT_QUOTES, 'UTF-8') ?>
        al <?= htmlspecialchars($filtros['hasta'], ENT_QUOTES, 'UTF-8') ?>.
        El saldo actual refleja el stock registrado en el sistema.
    </p>
    <div>
    <a class="btn btn-outline-primary mb-2"
        href="<?= BASE_URL ?>/inventory/export-summary?desde=<?= rawurlencode($filtros['desde']) ?>&amp;hasta=<?= rawurlencode($filtros['hasta']) ?>&amp;id_producto=<?= (int)$filtros['id_producto'] ?>">
        <i class="fas fa-file-csv mr-1"></i> Descargar resumen CSV
    </a>
    <a class="btn btn-outline-success mb-2"
        href="<?= BASE_URL ?>/inventory/export-movements?desde=<?= rawurlencode($filtros['desde']) ?>&amp;hasta=<?= rawurlencode($filtros['hasta']) ?>&amp;id_producto=<?= (int)$filtros['id_producto'] ?>">
        <i class="fas fa-file-csv mr-1"></i> Descargar movimientos CSV
    </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-right">Saldo inicial</th>
                <th class="text-right">Ingresos</th>
                <th class="text-right">Salidas</th>
                <th class="text-right">Saldo actual</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resumenMovimientos as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right"><?= (int)$row['stock_inicial'] ?> <?= htmlspecialchars($row['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right text-success"><?= (int)$row['ingresos'] ?> <?= htmlspecialchars($row['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right text-danger"><?= (int)$row['salidas'] ?> <?= htmlspecialchars($row['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right font-weight-bold"><?= (int)$row['stock_actual'] ?> <?= htmlspecialchars($row['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($resumenMovimientos)): ?>
                <tr><td colspan="6" class="text-center text-muted">No hay productos para los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h2 class="h5 mt-4">Detalle de movimientos</h2>
<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Código / Producto</th>
                <th>Movimiento</th>
                <th>Referencia</th>
                <th>Usuario</th>
                <th class="text-right">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $movement): ?>
                <tr>
                    <td><?= htmlspecialchars($movement['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($movement['codigo'] . ' — ' . $movement['producto'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($movement['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($movement['referencia'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($movement['usuario'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right <?= (float)$movement['cantidad'] < 0 ? 'text-danger' : 'text-success' ?>">
                        <?= (float)$movement['cantidad'] > 0 ? '+' : '' ?><?= (int)$movement['cantidad'] ?> <?= htmlspecialchars($movement['unidad_medida'] ?? 'unidad', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($movimientos)): ?>
                <tr><td colspan="6" class="text-center text-muted">No hay movimientos para el período seleccionado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
