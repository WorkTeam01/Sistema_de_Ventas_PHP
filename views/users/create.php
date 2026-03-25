<?php
include_once __DIR__ . '/../../layout/parte1.php';
?>

<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Crear usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/dashboard"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/users"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li class="breadcrumb-item active">Crear usuario</li>
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
                    <form action="<?= $URL; ?>/users" method="post">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-plus"></i> Ingrese los datos del usuario</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="form-group">
                                            <label for="nombres">Nombres <span class="text-danger">*</span></label>
                                            <input type="text" id="nombres" name="nombres" class="form-control" placeholder="Ingrese los nombres de usuario" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <input type="email" id="email" name="email" class="form-control" placeholder="Ingrese el email del usuario" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="rol">Rol <span class="text-danger">*</span></label>
                                            <select name="rol" id="rol" class="form-control" required>
                                                <?php foreach ($roles_datos as $roles_dato) : ?>
                                                    <option value="<?= (int) $roles_dato['id_rol']; ?>"><?= htmlspecialchars($roles_dato['rol'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_user">Contraseña <span class="text-danger">*</span></label>
                                            <input type="password" id="password_user" name="password_user" class="form-control" placeholder="Ingrese la contraseña del usuario" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_repeat">Repita la contraseña <span class="text-danger">*</span></label>
                                            <input type="password" id="password_repeat" name="password_repeat" class="form-control" placeholder="Vuelva a ingresar la contraseña" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= $URL; ?>/users" class="btn btn-default w-100">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save"></i> Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información adicional</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="callout callout-info">
                                <h5><i class="fas fa-info"></i> Información:</h5>
                                <p><strong>Nota:</strong> Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                                <p>Asegúrese de ingresar una contraseña segura para proteger la cuenta del usuario.</p>
                                <p>El rol asignado determinará los permisos y accesos que tendrá el usuario dentro del sistema.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
<!-- /.content-wrapper -->

<?php include_once __DIR__ . '/../../layout/mensajes.php'; ?>
<?php include_once __DIR__ . '/../../layout/parte2.php'; ?>