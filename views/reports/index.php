<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Reportes</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Reportes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <?php if ($rol_sesion === 'Administrador' || $rol_sesion === 'Vendedor'): ?>
                    <div class="col-md-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/sales" class="text-decoration-none">
                            <div class="info-box">
                                <span class="info-box-icon bg-success elevation-1">
                                    <i class="fas fa-chart-line"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Ventas por Período</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/top-products" class="text-decoration-none">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning elevation-1">
                                    <i class="fas fa-trophy"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Top Productos</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($rol_sesion === 'Administrador'): ?>
                    <div class="col-md-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/purchases" class="text-decoration-none">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1">
                                    <i class="fas fa-shopping-cart"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Compras por Período</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/clients" class="text-decoration-none">
                            <div class="info-box">
                                <span class="info-box-icon bg-info elevation-1">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Clientes</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>
</section>