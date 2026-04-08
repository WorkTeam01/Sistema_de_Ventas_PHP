<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de productos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Almacén</li>
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
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title">Productos registrados</h3>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/products/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nuevo producto
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="productTable" class="table table-bordered table-hover table-striped table-sm"
                                   style="visibility: hidden;">
                                <thead>
                                <tr>
                                    <th class="text-center">Nro</th>
                                    <th class="text-center">Imagen</th>
                                    <th class="text-center">Código</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Categoría</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Precio venta</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $contador = 0;
                                foreach ($products_datos as $product) :
                                    $id_producto = $product['id_producto'];
                                    $stock_actual = (int)$product['stock'];
                                    $stock_minimo = (int)$product['stock_minimo'];
                                    $stock_maximo = (int)$product['stock_maximo'];
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $contador += 1; ?></td>
                                        <td class="text-center">
                                            <img class="rounded mx-auto d-block"
                                                 src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($product['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                                 width="30"
                                                 alt="<?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </td>
                                        <td><?= htmlspecialchars($product['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($product['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php if ($stock_actual < $stock_minimo) : ?>
                                            <td class="bg-danger text-center"><?= $stock_actual; ?></td>
                                        <?php elseif ($stock_maximo > 0 && $stock_actual > $stock_maximo) : ?>
                                            <td class="bg-success text-center"><?= $stock_actual; ?></td>
                                        <?php else : ?>
                                            <td class="text-center"><?= $stock_actual; ?></td>
                                        <?php endif; ?>
                                        <td class="text-right"><?= htmlspecialchars($product['precio_venta'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>/products/show/<?= $id_producto ?>"
                                                   class="btn btn-info btn-sm" data-toggle="tooltip"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/products/edit/<?= $id_producto ?>"
                                                   class="btn btn-success btn-sm" data-toggle="tooltip"
                                                   title="Editar producto">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                        data-toggle="tooltip" title="Eliminar producto"
                                                        onclick="confirmarEliminar(<?= $id_producto ?>, '<?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
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