<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Actualizar usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/dashboard"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= $URL ?>/users"><i class="fas fa-users"></i> Usuarios</a></li>
                        <li class="breadcrumb-item active">Actualizar usuario</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-widget widget-user-2">
                        <div class="widget-user-header bg-success">
                            <div class="widget-user-image">
                                <img class="img-circle elevation-2" src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" alt="User Avatar">
                            </div>
                            <h3 class="widget-user-username">Modificar usuario: <?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <h5 class="widget-user-desc"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></h5>
                        </div>
                    </div>

                    <form action="<?= $URL; ?>/users/update" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id_usuario" value="<?= $id_usuario; ?>">

                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user"></i> Información de la cuenta</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Actualiza la información principal del usuario.</p>

                                <div class="form-group">
                                    <label for="nombres">Nombres <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" id="nombres" name="nombres" class="form-control" value="<?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="rol">Rol <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <select id="rol" name="rol" class="form-control" required>
                                            <?php foreach ($roles_datos as $roles_dato) {
                                                $idRol = (int) $roles_dato['id_rol']; ?>
                                                <option value="<?= $idRol; ?>" <?= $idRol === $idRolActual ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($roles_dato['rol'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-lock"></i> Seguridad</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Cambio de contraseña (opcional).</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_user">Nueva contraseña</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                </div>
                                                <input type="password" id="password_user" name="password_user" class="form-control" placeholder="Dejar en blanco para mantener actual">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_repeat">Confirmar nueva contraseña</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                </div>
                                                <input type="password" id="password_repeat" name="password_repeat" class="form-control" placeholder="Repita la contraseña">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> Deje ambos campos vacíos si no desea cambiar la contraseña.
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= $URL; ?>/users" class="btn btn-default w-100"><i class="fas fa-times"></i> Cancelar</a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i> Guardar cambios</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-badge"></i> Resumen</h3>
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
                                    <b><i class="fas fa-hashtag text-primary"></i> ID</b> <span class="float-right badge badge-primary"><?= $id_usuario; ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user-tag text-info"></i> Rol actual</b> <span class="float-right"><?= htmlspecialchars($rolActual, ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-lightbulb"></i> Recomendaciones</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-sm text-muted mb-2">Valida que el correo sea único y vigente.</p>
                            <p class="text-sm text-muted mb-2">Cambia contraseña solo cuando sea necesario.</p>
                            <p class="text-sm text-muted mb-0">Confirma que el rol asignado coincide con los permisos esperados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<!-- /.content-wrapper -->