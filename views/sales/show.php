<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle de Venta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/sales"><i class="fas fa-shopping-cart"></i>
                                Ventas</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Información general de la venta -->
                <div class="col-md-12">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-receipt"></i> Venta
                                N° <?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>/sales/invoice/<?= (int)$id_venta ?>" target="_blank"
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-print"></i> Imprimir factura
                                </a>
                                <a href="<?= BASE_URL ?>/sales" class="btn btn-default btn-sm ml-1">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                        <tr>
                                            <th class="bg-light" style="width: 40%">N° Venta</th>
                                            <td><?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Total pagado</th>
                                            <td>
                                                <span class="badge badge-warning">Bs. <?= htmlspecialchars(number_format((float)$total_pagado, 2), ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Fecha de creación</th>
                                            <td><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Última actualización</th>
                                            <td><?= htmlspecialchars($fyh_actualizacion ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                        <tr>
                                            <th class="bg-light" style="width: 40%">Cliente</th>
                                            <td><?= htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">NIT/CI</th>
                                            <td><?= htmlspecialchars($nit_ci_cliente, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Celular</th>
                                            <td><?= htmlspecialchars($celular_cliente ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Email</th>
                                            <td><?= htmlspecialchars($email_cliente ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de ítems del carrito -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-bag"></i> Productos de la venta</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-hover table-striped">
                                    <thead class="bg-secondary text-white">
                                    <tr class="text-center">
                                        <th>Nro</th>
                                        <th>Producto</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $nro_item = 0;
                                    $total_cantidad = 0;
                                    $precio_total = 0.0;
                                    foreach ($items as $item) :
                                        $nro_item++;
                                        $subtotal = (float)$item['cantidad'] * (float)$item['precio_venta'];
                                        $total_cantidad += (int)$item['cantidad'];
                                        $precio_total += $subtotal;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $nro_item ?></td>
                                            <td><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center"><?= (int)$item['cantidad'] ?></td>
                                            <td class="text-center">
                                                Bs. <?= htmlspecialchars(number_format((float)$item['precio_venta'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center">Bs. <?= number_format($subtotal, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($items)) : ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No hay productos registrados
                                                en esta venta.
                                            </td>
                                        </tr>
                                    <?php else : ?>
                                        <tr class="bg-light">
                                            <th colspan="3" class="text-right">Total</th>
                                            <th class="text-center"><?= $total_cantidad ?></th>
                                            <th></th>
                                            <th class="text-center text-warning">
                                                Bs. <?= number_format($precio_total, 2) ?></th>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->