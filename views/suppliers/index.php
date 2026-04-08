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
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
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
                                                <button type="button" class="btn btn-success btn-sm btn-edit"
                                                        data-id="<?= $supplier['id_proveedor'] ?>"
                                                        data-toggle="tooltip"
                                                        title="Editar">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                        data-id="<?= $supplier['id_proveedor'] ?>"
                                                        data-nombre="<?= htmlspecialchars($supplier['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-toggle="tooltip"
                                                        title="Eliminar">
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

<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-labelledby="modalCreateLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalCreateLabel">Registrar proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreate" autocomplete="off">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_nombre_proveedor">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_nombre_proveedor"
                                       name="nombre_proveedor"
                                       maxlength="255" placeholder="Nombre del contacto">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_empresa">Empresa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_empresa" name="empresa"
                                       maxlength="255" placeholder="Nombre de la empresa">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_celular">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_celular" name="celular"
                                       maxlength="50" placeholder="Número de celular">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="create_telefono" name="telefono"
                                       maxlength="50" placeholder="Teléfono fijo (opcional)">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_email">Email</label>
                                <input type="email" class="form-control" id="create_email" name="email"
                                       maxlength="254" placeholder="correo@empresa.com (opcional)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_direccion">Dirección <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="create_direccion" name="direccion"
                                          maxlength="255" rows="2" placeholder="Dirección de la empresa"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear proveedor
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
                <h5 class="modal-title" id="modalEditLabel">Editar proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEdit" autocomplete="off">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nombre_proveedor">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_nombre_proveedor"
                                       name="nombre_proveedor"
                                       maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_empresa">Empresa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_empresa" name="empresa"
                                       maxlength="255">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_celular">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_celular" name="celular"
                                       maxlength="50">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="edit_telefono" name="telefono"
                                       maxlength="50">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email"
                                       maxlength="254">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_direccion">Dirección <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_direccion" name="direccion"
                                          maxlength="255" rows="2"></textarea>
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
