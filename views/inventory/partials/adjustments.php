<!-- Filtros -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary collapsed-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros de Búsqueda</h2>
                <div class="card-tools m-0">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= BASE_URL ?>/inventory" id="filterAjustes">
                    <input type="hidden" name="tab" value="ajustes">
                    <!-- Fila 1: Filtros -->
                    <div class="row">
                        <div class="col-sm-6 col-lg-3">
                            <div class="form-group">
                                <label>Desde:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button type="button" class="input-group-text"
                                                data-toggle="date-picker"
                                                aria-label="Abrir selector de fecha"
                                                data-target="ajuste_desde">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                    </div>
                                    <input type="date" id="ajuste_desde" name="desde" class="form-control"
                                        value="<?= htmlspecialchars($filtros['desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="form-group">
                                <label>Hasta:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button type="button" class="input-group-text"
                                                data-toggle="date-picker"
                                                aria-label="Abrir selector de fecha"
                                                data-target="ajuste_hasta">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                    </div>
                                    <input type="date" id="ajuste_hasta" name="hasta" class="form-control"
                                        value="<?= htmlspecialchars($filtros['hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <div class="form-group">
                                <label>Tipo:</label>
                                <select name="tipo" class="form-control select2">
                                    <option value="">Todos</option>
                                    <option value="entrada" <?= ($filtros['tipo'] ?? '') === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                                    <option value="salida" <?= ($filtros['tipo'] ?? '') === 'salida'  ? 'selected' : '' ?>>Salida</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="form-group">
                                <label>Producto:</label>
                                <select name="id_producto" class="form-control select2" style="width: 100%;">
                                    <option value="">Todos los productos</option>
                                    <?php foreach ($todosProductos as $p): ?>
                                        <option value="<?= (int)$p['id_producto'] ?>"
                                            <?= (int)($filtros['id_producto'] ?? 0) === (int)$p['id_producto'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Fila 2: Botones -->
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <div class="btn-group">
                                <a href="<?= BASE_URL ?>/inventory?tab=ajustes" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i> Limpiar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabla historial de ajustes -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-balance-scale mr-1"></i> Historial de Ajustes de Stock
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm mr-2"
                            data-toggle="modal" data-target="#modalAjusteStock">
                            <i class="fas fa-plus mr-1"></i> Ajustar Stock
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="adjustmentsTable"
                    class="table table-bordered table-striped table-hover table-sm w-100"
                    style="visibility: hidden;">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Cantidad</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ajustes as $aj): ?>
                            <?php
                            $tipoBadge = $aj['tipo'] === 'entrada'
                                ? '<span class="badge badge-success"><i class="fas fa-arrow-up mr-1"></i> Entrada</span>'
                                : '<span class="badge badge-danger"><i class="fas fa-arrow-down mr-1"></i> Salida</span>';
                            ?>
                            <tr>
                                <td data-order="<?= htmlspecialchars($aj['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= date('d/m/Y H:i', strtotime($aj['fyh_creacion'])) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($aj['producto_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($aj['producto_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-center"><?= $tipoBadge ?></td>
                                <td class="text-center font-weight-bold">
                                    <?= (int)$aj['cantidad'] ?>
                                </td>
                                <td><?= htmlspecialchars($aj['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($aj['usuario_nombre'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>