<!-- Fila 1: Producto -->
<div class="form-group">
    <label for="selectProductoAjuste">Producto <span class="text-danger">*</span></label>
    <select name="id_producto" id="selectProductoAjuste"
        class="form-control select2">
        <option value="">Seleccione un producto...</option>
        <?php foreach ($todosProductos as $p): ?>
            <option value="<?= (int)$p['id_producto'] ?>"
                data-stock="<?= (int)$p['stock'] ?>">
                <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>
                (Stock: <?= (int)$p['stock'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Fila 2: Tipo y Cantidad -->
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="selectTipoAjuste">Tipo de Ajuste <span class="text-danger">*</span></label>
            <select name="tipo" id="selectTipoAjuste" class="form-control select2">
                <option value="">Seleccione un tipo...</option>
                <option value="entrada">Entrada (+) — Incrementar stock</option>
                <option value="salida">Salida (-) — Reducir stock</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="inputCantidad">Cantidad <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                </div>
                <input type="number" name="cantidad" id="inputCantidad"
                    class="form-control" min="1" step="1" placeholder="0">
                <div class="input-group-append">
                    <span class="input-group-text">unidades</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Motivo -->
<div class="form-group mb-0">
    <label for="textareaMotivo">Motivo del Ajuste <span class="text-danger">*</span></label>
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
        </div>
        <textarea name="motivo" id="textareaMotivo" class="form-control"
            rows="3" maxlength="255"
            placeholder="Ej: Inventario físico anual, producto dañado, error en registro previo..."></textarea>
    </div>
    <small class="text-muted">Máximo 255 caracteres</small>
</div>

<!-- Preview de stock proyectado -->
<div class="alert alert-secondary mt-3 mb-0 d-none" id="stock-preview">
    <strong>Stock proyectado:</strong>
    <span id="stock-anterior" class="font-weight-bold">0</span>
    <i class="fas fa-arrow-right mx-2 text-muted"></i>
    <span id="stock-nuevo" class="font-weight-bold text-primary">0</span>
    unidades
</div>