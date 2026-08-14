<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Eliminar producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products"><i class="fas fa-warehouse"></i>
                                Almacén</a></li>
                        <li class="breadcrumb-item active">Eliminar producto</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <form id="formEliminar" action="<?= BASE_URL; ?>/products/delete" method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id_producto" value="<?= $id_producto; ?>">

                        <div class="card card-danger card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Confirmación de
                                    eliminación</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección"><i
                                            class="fas fa-minus"></i></button>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <h5><i class="icon fas fa-ban"></i> Acción irreversible</h5>
                                    Esta operación eliminará permanentemente el producto seleccionado.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="delete_codigo">Código</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                                </div>
                                                <input type="text" id="delete_codigo"
                                                    value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="delete_categoria">Categoría</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                </div>
                                                <input type="text" id="delete_categoria"
                                                    value="<?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="delete_nombre">Nombre del producto</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-box"></i></span>
                                                </div>
                                                <input type="text" id="delete_nombre"
                                                    value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="delete_id">ID</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                </div>
                                                <input type="text" id="delete_id" value="<?= $id_producto; ?>" class="form-control"
                                                    disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL; ?>/products" class="btn btn-default w-100"><i
                                                class="fas fa-times"></i> Cancelar</a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="button" class="btn btn-danger w-100 btn-confirm-delete-product"
                                            data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-trash"></i> Eliminar producto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shield-alt"></i> Verificación</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="img-thumbnail img-fluid" width="80%"
                                    alt="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22><rect width=%22120%22 height=%22120%22 fill=%22%23e9ecef%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22sans-serif%22 font-size=%2212%22 fill=%22%236c757d%22>Sin imagen</text></svg>'; this.alt='Imagen no disponible';">
                            </div>
                            <p class="text-sm text-muted mb-2">Confirma que estás eliminando el producto correcto.</p>
                            <p class="text-sm text-muted mb-2">Si el producto tiene movimientos históricos, no podrá
                                eliminarse.</p>
                            <p class="text-sm text-muted mb-0">Esta acción no se puede deshacer.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->