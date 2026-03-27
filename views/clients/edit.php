<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Actualizar cliente</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/clients"><i class="fas fa-user-friends"></i> Clientes</a></li>
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
                            <h3 class="card-title">Datos para modificar <span class="badge badge-secondary ml-1">ID #<?= $id_cliente ?></span></h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <form action="<?= BASE_URL ?>/clients/update" method="post" autocomplete="off">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="id_cliente" value="<?= $id_cliente; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre_cliente">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" name="nombre_cliente" id="nombre_cliente" class="form-control"
                                                value="<?= htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nit_ci_cliente">NIT/CI <span class="text-danger">*</span></label>
                                            <input type="text" name="nit_ci_cliente" id="nit_ci_cliente" class="form-control"
                                                value="<?= htmlspecialchars($nit_ci_cliente, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="celular_cliente">Celular <span class="text-danger">*</span></label>
                                            <input type="text" name="celular_cliente" id="celular_cliente" class="form-control"
                                                value="<?= htmlspecialchars($celular_cliente, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email_cliente">Correo electrónico <span class="text-danger">*</span></label>
                                            <input type="email" name="email_cliente" id="email_cliente" class="form-control"
                                                value="<?= htmlspecialchars($email_cliente, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/clients" class="btn btn-default w-100">
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
                            <p>Modifica los datos de contacto del cliente.</p>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><i class="fas fa-asterisk text-danger mr-1" style="font-size:.7rem;"></i> Todos los campos son obligatorios.</li>
                                <li class="mb-1"><i class="fas fa-id-card text-info mr-1"></i> El NIT/CI identifica al cliente en las facturas.</li>
                            </ul>
                            <p class="text-muted mb-0"><small>Los cambios se aplicarán a todas las ventas asociadas a este cliente.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content-wrapper -->
