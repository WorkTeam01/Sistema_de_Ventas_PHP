<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de proveedores</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Proveedores</li>
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
                                <h3 class="card-title">Proveedores registrados</h3>
                                <div class="card-tools d-flex">
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-toggle="modal"
                                            data-target="#modalCreate">
                                        <i class="fas fa-plus"></i> Nuevo proveedor
                                    </button>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="supplierTable" class="table table-bordered table-hover table-striped table-sm"
                                   style="visibility: hidden;">
                                <thead>
                                <tr>
                                    <th class="text-center">Nro</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Empresa</th>
                                    <th class="text-center">Celular</th>
                                    <th class="text-center">Dirección</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $contador = 0; ?>
                                <?php foreach ($suppliers_datos as $supplier): ?>
                                    <tr>
                                        <td class="text-center"><?= ++$contador ?></td>
                                        <td><?= htmlspecialchars($supplier['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($supplier['empresa'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($supplier['celular'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($supplier['direccion'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-info btn-sm btn-show"
                                                        data-id="<?= $supplier['id_proveedor'] ?>"
                                                        data-toggle="tooltip"
                                                        title="Ver detalle"
                                                        aria-label="Ver detalle de <?= htmlspecialchars($supplier['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-eye"></i>
                                                    <span class="sr-only">Ver detalle</span>
                                                </button>
                                                <button type="button" class="btn btn-success btn-sm btn-edit"
                                                        data-id="<?= $supplier['id_proveedor'] ?>"
                                                        data-toggle="tooltip"
                                                        title="Editar"
                                                        aria-label="Editar proveedor <?= htmlspecialchars($supplier['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                    <span class="sr-only">Editar</span>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                        data-id="<?= $supplier['id_proveedor'] ?>"
                                                        data-nombre="<?= htmlspecialchars($supplier['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-toggle="tooltip"
                                                        title="Eliminar"
                                                        aria-label="Eliminar proveedor <?= htmlspecialchars($supplier['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="sr-only">Eliminar</span>
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

<?php require __DIR__ . '/partial/_modals.php'; ?>