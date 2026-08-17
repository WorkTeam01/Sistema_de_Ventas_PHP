<section class="content-wrapper">
    <section class=" content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Módulo de Inventario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active">Inventario</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline card-outline-tabs">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= $activeTab === 'stock' ? 'active' : '' ?>"
                                        href="<?= BASE_URL ?>/inventory?tab=stock">
                                        <i class="fas fa-boxes mr-1"></i> Control de Stock
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $activeTab === 'ajustes' ? 'active' : '' ?>"
                                        href="<?= BASE_URL ?>/inventory?tab=ajustes">
                                        <i class="fas fa-balance-scale mr-1"></i> Ajustes de Stock
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <?php if ($activeTab === 'stock'): ?>
                                <?php include __DIR__ . '/partials/stock-control.php'; ?>
                            <?php else: ?>
                                <?php include __DIR__ . '/partials/adjustments.php'; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</section>

<!-- Modal de Ajuste de Stock -->
<div class="modal fade" id="modalAjusteStock" tabindex="-1" role="dialog"
    aria-labelledby="modalAjusteStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalAjusteStockLabel">
                    <i class="fas fa-balance-scale mr-2"></i> Realizar Ajuste de Stock
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAjusteStock" method="POST" action="<?= BASE_URL ?>/inventory/adjustments">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <?php include __DIR__ . '/partials/adjustment-form.php'; ?>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnGuardarAjuste">
                        <i class="fas fa-save mr-1"></i> Guardar Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>