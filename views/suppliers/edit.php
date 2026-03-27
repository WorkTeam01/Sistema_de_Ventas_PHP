<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Actualizar proveedor</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/suppliers"><i class="fas fa-truck"></i> Proveedores</a></li>
                        <li class="breadcrumb-item active">Editar</li>
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
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Datos para modificar <span class="badge badge-secondary ml-1">ID #<?= $id_proveedor ?></span></h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <form action="<?= BASE_URL ?>/suppliers/update" method="post" autocomplete="off">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="id_proveedor" value="<?= $id_proveedor; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre_proveedor">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" name="nombre_proveedor" id="nombre_proveedor" class="form-control"
                                                value="<?= htmlspecialchars($nombre_proveedor, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="empresa">Empresa <span class="text-danger">*</span></label>
                                            <input type="text" name="empresa" id="empresa" class="form-control"
                                                value="<?= htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="celular">Celular <span class="text-danger">*</span></label>
                                            <input type="text" name="celular" id="celular" class="form-control"
                                                value="<?= htmlspecialchars($celular, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="telefono">Teléfono</label>
                                            <input type="text" name="telefono" id="telefono" class="form-control"
                                                value="<?= htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" name="email" id="email" class="form-control"
                                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="direccion">Dirección <span class="text-danger">*</span></label>
                                            <textarea name="direccion" id="direccion" class="form-control" placeholder="Dirección de la empresa" rows="2" autocomplete="off" required><?= htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/suppliers" class="btn btn-default w-100">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-save"></i> Actualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información adicional</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Modifica los datos de contacto del proveedor.</p>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><i class="fas fa-asterisk text-danger mr-1" style="font-size:.7rem;"></i> Campos obligatorios: Nombre, Empresa, Celular y Dirección.</li>
                                <li class="mb-1"><i class="fas fa-phone text-info mr-1"></i> Teléfono y Email son opcionales.</li>
                            </ul>
                            <p class="text-muted mb-0"><small>Los cambios se aplicarán a todas las compras asociadas a este proveedor.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content-wrapper -->