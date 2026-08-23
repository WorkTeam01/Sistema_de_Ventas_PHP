<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Crear usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/users"><i class="fas fa-users"></i>
                                Usuarios</a></li>
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
                    <form id="userCreateForm" action="<?= BASE_URL ?>/users" method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Card 1: Datos del usuario -->
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-user mr-1"></i> Datos del usuario</h2>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombres">Nombres <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" id="nombres" name="nombres" class="form-control"
                                            placeholder="Nombre completo del usuario" autocomplete="off"
                                            aria-required="true">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Correo electrónico <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-envelope"></i></span>
                                                </div>
                                                <input type="email" id="email" name="email" class="form-control"
                                                    placeholder="correo@ejemplo.com" autocomplete="off"
                                                    aria-required="true">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="rol">Rol <span class="text-danger">*</span></label>
                                            <select name="rol" id="rol" class="form-control select2" aria-required="true">
                                                <option value="">— Seleccione un rol —</option>
                                                <?php foreach ($roles_datos as $roles_dato) : ?>
                                                    <option value="<?= (int)$roles_dato['id_rol']; ?>">
                                                        <?= htmlspecialchars($roles_dato['rol'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Credenciales de acceso -->
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-key mr-1"></i> Credenciales de acceso</h2>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_user">Contraseña <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                </div>
                                                <input type="password" id="password_user" name="password_user"
                                                    class="form-control"
                                                    placeholder="Mínimo 6 caracteres" aria-required="true">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-default toggle-password"
                                                        aria-label="Mostrar contraseña">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_repeat">Repita la contraseña <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                </div>
                                                <input type="password" id="password_repeat" name="password_repeat"
                                                    class="form-control"
                                                    placeholder="Repita la contraseña" aria-required="true">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-default toggle-password"
                                                        aria-label="Mostrar contraseña">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/users" class="btn btn-default w-100">
                                            <i class="fas fa-times mr-1"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" id="btnCreateUser" class="btn btn-primary w-100">
                                            <i class="fas fa-save mr-1"></i> Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <!-- Información adicional -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-info-circle"></i> Información adicional</h2>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Registra las credenciales del usuario para que pueda acceder al sistema.</p>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><i class="fas fa-asterisk text-danger mr-1"
                                        style="font-size:.7rem;"></i> Todos los campos son obligatorios.
                                </li>
                                <li class="mb-1"><i class="fas fa-user-tag text-info mr-1"></i> El rol determina los
                                    permisos y accesos del usuario.
                                </li>
                                <li class="mb-1"><i class="fas fa-lock text-warning mr-1"></i> La contraseña debe tener
                                    al menos 6 caracteres.
                                </li>
                            </ul>
                            <p class="text-muted mb-0"><small>El correo electrónico debe ser único en el
                                    sistema.</small></p>
                        </div>
                    </div>

                    <!-- Vista previa -->
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-eye"></i> Vista previa</h2>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <i class="fas fa-user-circle fa-5x text-secondary mb-2"></i>
                            </div>
                            <h3 class="profile-username text-center" id="preview-nombre">Nombre del Usuario</h3>
                            <p class="text-muted text-center" id="preview-email">email@ejemplo.com</p>
                            <ul class="list-group list-group-unbordered mb-0">
                                <li class="list-group-item">
                                    <b><i class="fas fa-user-tag text-info"></i> Rol</b>
                                    <span class="float-right" id="preview-rol">— Sin rol —</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content-wrapper -->