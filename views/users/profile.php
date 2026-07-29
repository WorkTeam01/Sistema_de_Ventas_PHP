<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Mi Perfil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Mi Perfil</li>
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
                <!-- Columna izquierda: tarjeta de perfil -->
                <div class="col-md-4">
                    <div class="card card-outline <?= $cardClass ?>">
                        <div class="card-body box-profile">

                            <!-- Avatar con iniciales -->
                            <div class="profile-avatar-initials <?= $cardClass ?>">
                                <?= $initSafe ?>
                            </div>

                            <h3 class="profile-username text-center"><?= $nombresSafe ?></h3>
                            <p class="text-muted text-center">
                                <span class="badge <?= $badgeClass ?>"><?= $rolSafe ?></span>
                            </p>

                            <ul class="list-group list-group-unbordered mb-0">
                                <li class="list-group-item">
                                    <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                    <span class="float-right text-muted small"><?= $emailSafe ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-calendar-alt mr-1"></i> Miembro desde</b>
                                    <span class="float-right"><?= $fechaSafe ?></span>
                                </li>
                            </ul>

                        </div>
                    </div>
                </div>
                <!-- /.col-md-4 -->

                <!-- ── Columna derecha: tabs edición ──────────────────────── -->
                <div class="col-md-8">
                    <div class="card card-outline card-outline-tabs <?= $cardClass ?>">

                        <!-- Tabs nav -->
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="profile-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= $navPerfilClass ?>"
                                        id="tab-perfil-link"
                                        data-toggle="tab"
                                        href="#tab-perfil"
                                        role="tab">
                                        <i class="fas fa-user-edit"></i> Editar perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $navPasswordClass ?>"
                                        id="tab-password-link"
                                        data-toggle="tab"
                                        href="#tab-password"
                                        role="tab">
                                        <i class="fas fa-lock"></i> Cambiar contraseña
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Tabs content -->
                        <div class="card-body">
                            <div class="tab-content" id="profile-tabs-content">

                                <!-- ── Tab 1: Editar perfil ──────────────────── -->
                                <div class="tab-pane fade <?= $panePerfilClass ?>"
                                    id="tab-perfil"
                                    role="tabpanel">

                                    <form id="form-info"
                                        action="<?= BASE_URL ?>/profile/update"
                                        method="POST"
                                        data-check-email-url="<?= $checkUrl ?>"
                                        data-user-id="<?= $id_usuario ?>">

                                        <input type="hidden" name="csrf_token" value="<?= $csrfSafe ?>">

                                        <div class="form-group">
                                            <label for="nombres">Nombre completo</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                </div>
                                                <input type="text"
                                                    id="nombres"
                                                    name="nombres"
                                                    class="form-control"
                                                    value="<?= $nombresSafe ?>"
                                                    autocomplete="off">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="email">Correo electrónico</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-envelope"></i></span>
                                                </div>
                                                <input type="email"
                                                    id="email"
                                                    name="email"
                                                    class="form-control"
                                                    value="<?= $emailSafe ?>"
                                                    autocomplete="off">
                                            </div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label>Rol asignado</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-user-tag"></i></span>
                                                </div>
                                                <input type="text"
                                                    class="form-control"
                                                    value="<?= $rolSafe ?>"
                                                    disabled>
                                            </div>
                                            <small class="form-text text-muted">El rol solo puede ser modificado por un
                                                administrador.</small>
                                        </div>

                                        <hr>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar información
                                        </button>

                                    </form>
                                </div>
                                <!-- /.tab-pane#tab-perfil -->

                                <!-- ── Tab 2: Cambiar contraseña ─────────────── -->
                                <div class="tab-pane fade <?= $panePasswordClass ?>"
                                    id="tab-password"
                                    role="tabpanel">

                                    <form id="form-password"
                                        action="<?= BASE_URL ?>/profile/password"
                                        method="POST">

                                        <input type="hidden" name="csrf_token" value="<?= $csrfSafe ?>">

                                        <div class="callout callout-info">
                                            <p class="mb-0"><i class="fas fa-info-circle"></i> La contraseña debe tener
                                                al menos <strong>6 caracteres</strong>.</p>
                                        </div>

                                        <div class="form-group">
                                            <label for="password-user-input">Nueva contraseña</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                </div>
                                                <input type="password"
                                                    id="password-user-input"
                                                    name="password_user"
                                                    class="form-control"
                                                    autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <button type="button"
                                                        class="btn btn-default toggle-password"
                                                        aria-label="Mostrar contraseña">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="password-repeat-input">Confirmar nueva contraseña</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                </div>
                                                <input type="password"
                                                    id="password-repeat-input"
                                                    name="password_repeat"
                                                    class="form-control"
                                                    autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <button type="button"
                                                        class="btn btn-default toggle-password"
                                                        aria-label="Mostrar contraseña">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-key"></i> Cambiar contraseña
                                        </button>

                                    </form>
                                </div>
                                <!-- /.tab-pane#tab-password -->

                            </div>
                            <!-- /.tab-content -->
                        </div>
                        <!-- /.card-body -->

                    </div>
                </div>
                <!-- /.col-md-8 -->

            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->