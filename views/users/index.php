<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de usuarios</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Usuarios</li>
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
                                <h2 class="card-title">Usuarios registrados</h2>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/users/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nuevo usuario
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="userTable" class="table table-bordered table-hover table-striped table-sm"
                                style="visibility: hidden;">
                                <thead>
                                    <tr>
                                        <th class="text-center">Nro</th>
                                        <th class="text-center">Nombres</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">Rol</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 0;
                                    foreach ($usuarios_datos as $usuarios_dato) :
                                        $id_usuario = $usuarios_dato['id_usuario']; ?>
                                        <tr>
                                            <td class="text-center"><?= $contador += 1; ?></td>
                                            <td><?= htmlspecialchars($usuarios_dato['nombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($usuarios_dato['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($usuarios_dato['rol'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= BASE_URL ?>/users/edit/<?= $id_usuario ?>"
                                                        class="btn btn-success btn-sm" data-toggle="tooltip"
                                                        title="Editar usuario" aria-label="Editar usuario">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm btn-delete-user"
                                                        data-id="<?= $id_usuario ?>"
                                                        data-nombre="<?= htmlspecialchars($usuarios_dato['nombres'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-toggle="tooltip" title="Eliminar usuario"
                                                        aria-label="Eliminar usuario">
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