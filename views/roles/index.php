<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de roles</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Roles</li>
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
                <div class="col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title">Roles registrados</h3>
                                <div class="card-tools d-flex">
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-toggle="modal"
                                            data-target="#modalCreate">
                                        <i class="fas fa-plus"></i> Nuevo rol
                                    </button>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="roleTable" class="table table-bordered table-hover table-striped table-sm"
                                   style="visibility: hidden;">
                                <thead>
                                <tr>
                                    <th class="text-center">Nro</th>
                                    <th class="text-center">Nombre rol</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $contador = 0; ?>
                                <?php foreach ($roles_datos as $roles_dato): ?>
                                    <tr>
                                        <td class="text-center"><?= ++$contador ?></td>
                                        <td><?= htmlspecialchars($roles_dato['rol'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-success btn-sm btn-edit"
                                                    data-id="<?= $roles_dato['id_rol'] ?>"
                                                    data-toggle="tooltip"
                                                    title="Editar">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Los roles controlan los permisos de acceso de cada usuario al sistema:</p>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><span class="badge badge-danger mr-1">Administrador</span> Acceso total
                                    al sistema.
                                </li>
                                <li class="mb-1"><span class="badge badge-success mr-1">Vendedor</span> Gestión de
                                    ventas, clientes e inventario.
                                </li>
                                <li class="mb-1"><span class="badge badge-warning mr-1">Comprador</span> Gestión de
                                    compras, proveedores e inventario.
                                </li>
                            </ul>
                            <p class="text-muted mb-0"><small>Modifica únicamente el nombre del rol, no sus permisos de
                                    acceso.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->

<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-labelledby="modalCreateLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalCreateLabel">Crear rol</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreate">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create_rol">Nombre del rol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_rol" name="rol" required maxlength="100"
                               placeholder="Ej: Supervisor, Cajero">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear rol
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="modalEditLabel">Editar rol</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEdit">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_rol">Nombre del rol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_rol" name="rol" required maxlength="100">
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