<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de clientes</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Clientes</li>
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
                                <h2 class="card-title">Clientes registrados</h2>
                                <div class="card-tools d-flex">
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-toggle="modal"
                                            data-target="#modalCreate">
                                        <i class="fas fa-plus"></i> Nuevo cliente
                                    </button>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="clientTable" class="table table-bordered table-hover table-striped table-sm"
                                   style="visibility: hidden;">
                                <thead>
                                <tr>
                                    <th class="text-center">Nro</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">NIT/CI</th>
                                    <th class="text-center">Celular</th>
                                    <th class="text-center">Correo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $contador = 0; ?>
                                <?php foreach ($clients_datos as $client): ?>
                                    <tr>
                                        <td class="text-center"><?= ++$contador ?></td>
                                        <td><?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($client['nit_ci_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($client['celular_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($client['email_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-success btn-sm btn-edit"
                                                        data-id="<?= $client['id_cliente'] ?>"
                                                        data-toggle="tooltip"
                                                        title="Editar"
                                                        aria-label="Editar cliente <?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                    <span class="sr-only">Editar</span>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                        data-id="<?= $client['id_cliente'] ?>"
                                                        data-nombre="<?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-toggle="tooltip"
                                                        title="Eliminar"
                                                        aria-label="Eliminar cliente <?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?>">
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

<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-labelledby="modalCreateLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalCreateLabel">Registrar cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreate" autocomplete="off">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_nombre_cliente">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_nombre_cliente" name="nombre_cliente"
                                       maxlength="255" placeholder="Nombre completo del cliente" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_nit_ci_cliente">NIT/CI <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_nit_ci_cliente" name="nit_ci_cliente"
                                       maxlength="50" placeholder="Número de NIT o CI" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_celular_cliente">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_celular_cliente"
                                       name="celular_cliente"
                                       maxlength="50" placeholder="Número de celular" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_email_cliente">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="create_email_cliente" name="email_cliente"
                                       maxlength="254" placeholder="correo@ejemplo.com" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="modalEditLabel">Editar cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEdit" autocomplete="off">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nombre_cliente">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_nombre_cliente" name="nombre_cliente"
                                       maxlength="255" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nit_ci_cliente">NIT/CI <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_nit_ci_cliente" name="nit_ci_cliente"
                                       maxlength="50" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_celular_cliente">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_celular_cliente" name="celular_cliente"
                                       maxlength="50" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email_cliente">Correo electrónico <span
                                            class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_email_cliente" name="email_cliente"
                                       maxlength="254" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnUpdate">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
