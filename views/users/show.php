<?php
include_once __DIR__ . '/../../layout/parte1.php';

$id_usuario = (int) ($usuario_data['id_usuario'] ?? 0);
$nombres = $usuario_data['nombres'] ?? '';
$email = $usuario_data['email'] ?? '';
$rol = $usuario_data['rol'] ?? '';
?>

<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalles del usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/dashboard"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/users"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li class="breadcrumb-item active">Detalles</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-address-card"></i> Información del usuario</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombres">Nombres</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" id="nombres" value="<?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" id="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="rol">Rol</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <input type="text" id="rol" value="<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="id_usuario">ID</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                        </div>
                                        <input type="text" id="id_usuario" value="<?= $id_usuario; ?>" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="<?= $URL; ?>/users" class="btn btn-default mr-1"><i class="fas fa-arrow-left"></i> Volver</a>
                        <a href="<?= $URL; ?>/users/edit/<?= $id_usuario; ?>" class="btn btn-info mr-1"><i class="fas fa-pencil-alt"></i> Editar</a>
                        <a href="<?= $URL; ?>/users/delete/<?= $id_usuario; ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</a>
                    </div>

                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-circle"></i> Perfil</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <img class="profile-user-img img-fluid img-circle" src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center"><?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="text-muted text-center"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="list-group list-group-unbordered mb-0">
                            <li class="list-group-item">
                                <b><i class="fas fa-user-tag text-info"></i> Rol</b>
                                <span class="float-right badge badge-info"><?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-muted mb-2">Esta vista es solo de consulta.</p>
                        <p class="text-sm text-muted mb-2">Para modificar datos usa el botón <strong>Editar</strong>.</p>
                        <p class="text-sm text-muted mb-0">Para eliminar usuario, usa el botón <strong>Eliminar</strong>.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
</div><!-- /.container-fluid -->
</div>
<!-- /.content -->
<!-- /.content-wrapper -->

<?php include_once __DIR__ . '/../../layout/mensajes.php'; ?>
<?php include_once __DIR__ . '/../../layout/parte2.php'; ?>