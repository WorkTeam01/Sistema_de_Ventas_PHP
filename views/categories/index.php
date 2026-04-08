<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de categorías</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Categorías</li>
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
                                <h3 class="card-title">Categorías registradas</h3>
                                <div class="card-tools d-flex">
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-toggle="modal"
                                            data-target="#modalCreate">
                                        <i class="fas fa-plus"></i> Nueva categoría
                                    </button>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="categoryTable" class="table table-bordered table-hover table-striped table-sm"
                                   style="visibility: hidden;">
                                <thead>
                                <tr>
                                    <th class="text-center">Nro</th>
                                    <th class="text-center">Nombre de categoría</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $contador = 0; ?>
                                <?php foreach ($categories_datos as $category): ?>
                                    <tr>
                                        <td class="text-center"><?= ++$contador ?></td>
                                        <td><?= htmlspecialchars($category['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-success btn-sm btn-edit"
                                                    data-id="<?= $category['id_categoria'] ?>"
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
                            <p>Las categorías permiten organizar los productos del inventario en grupos lógicos.</p>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><i class="fas fa-tags text-primary mr-1"></i> Cada producto pertenece a
                                    una categoría.
                                </li>
                                <li class="mb-1"><i class="fas fa-search text-info mr-1"></i> Facilitan la búsqueda y
                                    filtrado en el almacén.
                                </li>
                                <li class="mb-1"><i class="fas fa-chart-bar text-success mr-1"></i> Útiles para generar
                                    reportes por grupo.
                                </li>
                            </ul>
                            <p class="text-muted mb-0"><small>Solo se puede modificar el nombre; no se puede eliminar
                                    una categoría con productos asociados.</small></p>
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
                <h5 class="modal-title" id="modalCreateLabel">Crear categoría</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreate">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create_nombre_categoria">Nombre de categoría <span
                                    class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_nombre_categoria" name="nombre_categoria"
                               required maxlength="100" placeholder="Ej: Electrónica, Ropa">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear categoría
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
                <h5 class="modal-title" id="modalEditLabel">Editar categoría</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEdit">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nombre_categoria">Nombre de categoría <span
                                    class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_nombre_categoria" name="nombre_categoria"
                               required maxlength="100">
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
