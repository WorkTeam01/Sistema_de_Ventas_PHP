<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle del producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products"><i class="fas fa-warehouse"></i> Almacén</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Imagen -->
                <div class="col-md-3">
                    <div class="card card-outline card-info">
                        <div class="card-body text-center">
                            <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>"
                                class="img-fluid rounded" alt="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                            <h5 class="mt-3 mb-1"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="badge badge-primary badge-pill p-2"><?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="card-footer">
                            <a href="<?= BASE_URL ?>/products/edit/<?= $id_producto ?>" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-pencil-alt"></i> Editar
                            </a>
                            <a href="<?= BASE_URL ?>/products" class="btn btn-default w-100">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Datos del producto -->
                <div class="col-md-9">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">Información del producto</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th class="text-muted" style="width:40%">Código</th>
                                            <td><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Categoría</th>
                                            <td><?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Nombre</th>
                                            <td><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Descripción</th>
                                            <td><?= htmlspecialchars($descripcion ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Fecha de ingreso</th>
                                            <td><?= htmlspecialchars($fecha_ingreso, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th class="text-muted" style="width:45%">Precio de compra</th>
                                            <td><?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Precio de venta</th>
                                            <td><?= htmlspecialchars($precio_venta, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Stock actual</th>
                                            <td>
                                                <?php
                                                $s  = (int) $stock;
                                                $sn = (int) $stock_minimo;
                                                $sx = (int) $stock_maximo;
                                                if ($s < $sn) {
                                                    echo '<span class="badge badge-danger">' . $s . ' (bajo mínimo)</span>';
                                                } elseif ($sx > 0 && $s > $sx) {
                                                    echo '<span class="badge badge-success">' . $s . ' (sobre máximo)</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">' . $s . '</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Stock mínimo</th>
                                            <td><?= htmlspecialchars($stock_minimo ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Stock máximo</th>
                                            <td><?= htmlspecialchars($stock_maximo ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auditoría -->
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title text-muted"><i class="fas fa-history mr-1"></i> Auditoría</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted" style="width:25%">Fecha de creación</th>
                                    <td><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Última actualización</th>
                                    <td><?= htmlspecialchars($fyh_actualizacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->