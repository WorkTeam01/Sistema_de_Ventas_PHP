<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Eliminar Venta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/sales"><i class="fas fa-shopping-cart"></i>
                                Ventas</a></li>
                        <li class="breadcrumb-item active">Eliminar</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <!-- Advertencia -->
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-exclamation-triangle"></i> Atención</h5>
                        Está a punto de eliminar la <strong>Venta
                            N° <?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></strong>.
                        Esta acción revertirá el stock de todos los productos del carrito.
                        <strong>Esta operación no se puede deshacer.</strong>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Detalle de la venta -->
                <div class="col-md-8">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-receipt"></i> Venta
                                N° <?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                        <tr>
                                            <th class="bg-light" style="width: 45%">N° Venta</th>
                                            <td><?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Total pagado</th>
                                            <td>
                                                <span class="badge badge-warning">Bs. <?= htmlspecialchars(number_format((float)$total_pagado, 2), ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Fecha</th>
                                            <td><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                        <tr>
                                            <th class="bg-light" style="width: 45%">Cliente</th>
                                            <td><?= htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">NIT/CI</th>
                                            <td><?= htmlspecialchars($nit_ci_cliente, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Ítems del carrito -->
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
                                    <?php if (!empty($items)) : ?>
                                        <tr class="bg-light">
                                            <th colspan="3" class="text-right">Total</th>
                                            <th class="text-center"><?= $total_cantidad ?></th>
                                            <th></th>
                                            <th class="text-center">Bs. <?= number_format($precio_total, 2) ?></th>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de confirmación -->
                <div class="col-md-4">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-exclamation-circle"></i> Confirmar eliminación</h3>
                        </div>
                        <div class="card-body">
                            <p>Al confirmar, se realizarán las siguientes operaciones de forma irreversible:</p>
                            <ul>
                                <li>Se eliminará el registro de la venta.</li>
                                <li>Se eliminarán los ítems del carrito asociados.</li>
                                <li>El stock de cada producto será restaurado.</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <form id="formEliminar" action="<?= BASE_URL ?>/sales/delete" method="post">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_venta" value="<?= (int)$id_venta ?>">
                                <div class="d-flex flex-column">
                                    <a href="<?= BASE_URL ?>/sales" class="btn btn-default mb-2 w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="button" class="btn btn-danger w-100" onclick="confirmarEliminar()">
                                        <i class="fas fa-trash"></i> Confirmar eliminación
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->
<script>
    function confirmarEliminar() {
        var nroVenta = <?= json_encode((string)$nro_venta) ?>;
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se eliminará la Venta N° ' + nroVenta + '. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formEliminar').submit();
            }
        });
    }
</script>