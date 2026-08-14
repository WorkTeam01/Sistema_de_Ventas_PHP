<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Registrar producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products"><i class="fas fa-warehouse"></i>
                                Almacén</a></li>
                        <li class="breadcrumb-item active">Registrar producto</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form
                    id="productCreateForm"
                    action="<?= BASE_URL ?>/products"
                    method="post"
                    enctype="multipart/form-data"
            >
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="row">
                    <!-- ============================================ -->
                    <!-- COLUMNA IZQUIERDA: Campos del formulario     -->
                    <!-- ============================================ -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header card-outline card-primary">
                                <h3 class="card-title"><i class="fas fa-info-circle"></i> Información general</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="codigo">Código</label>
                                            <input
                                                    type="text"
                                                    id="codigo"
                                                    value="<?= htmlspecialchars($next_code, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control"
                                                    disabled
                                            >
                                            <small class="text-muted">Autogenerado</small>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="id_categoria">Categoría <span class="text-danger">*</span></label>
                                            <div class="d-flex">
                                                <select id="id_categoria" name="id_categoria"
                                                        class="form-control select2 mr-2" required>
                                                    <option value="">Seleccionar...</option>
                                                    <?php foreach ($categories as $category) : ?>
                                                        <option value="<?= $category['id_categoria']; ?>">
                                                            <?= htmlspecialchars($category['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <a href="<?= BASE_URL ?>/categories/create" class="btn btn-primary"
                                                   title="Nueva categoría">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="nombre">Nombre del producto <span class="text-danger">*</span></label>
                                            <input type="text" id="nombre" name="nombre" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="usuario">Usuario</label>
                                            <input
                                                    type="text"
                                                    id="usuario"
                                                    value="<?= htmlspecialchars($email_sesion, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control"
                                                    disabled
                                            >
                                            <small class="text-muted">Sesión actual</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descripcion">Descripción</label>
                                            <textarea id="descripcion" name="descripcion" rows="2" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /.card-body -->
                        </div><!-- /.card -->

                        <div class="card">
                            <div class="card-header card-outline card-primary">
                                <h3 class="card-title"><i class="fas fa-boxes"></i> Inventario</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="stock">Stock <span class="text-danger">*</span></label>
                                            <input type="number" id="stock" name="stock" class="form-control" min="0"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="stock_minimo">Stock mínimo</label>
                                            <input type="number" id="stock_minimo" name="stock_minimo"
                                                   class="form-control" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="stock_maximo">Stock máximo</label>
                                            <input type="number" id="stock_maximo" name="stock_maximo"
                                                   class="form-control" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fecha_ingreso">Fecha ingreso <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button type="button" class="input-group-text"
                                                            data-toggle="date-picker"
                                                            aria-label="Abrir selector de fecha"
                                                            data-target="fecha_ingreso">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </button>
                                                </div>
                                                <input type="date" id="fecha_ingreso" name="fecha_ingreso"
                                                       class="form-control" required
                                                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header card-outline card-primary">
                                <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Precios</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_compra">Precio compra <span class="text-danger">*</span></label>
                                            <input
                                                    type="number"
                                                    id="precio_compra"
                                                    name="precio_compra"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_venta">Precio venta <span class="text-danger">*</span></label>
                                            <input
                                                    type="number"
                                                    id="precio_venta"
                                                    name="precio_venta"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/products" class="btn btn-default w-100">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" id="btnCreateProduct" class="btn btn-primary w-100">
                                            <i class="fas fa-save"></i> Guardar producto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.col-md-8 -->

                    <!-- ============================================ -->
                    <!-- COLUMNA DERECHA: Sidebar sticky              -->
                    <!-- ============================================ -->
                    <div class="col-md-4">
                        <div class="product-sidebar-sticky">

                            <!-- Panel: Imagen -->
                            <div class="card">
                                <div class="card-header card-outline card-info">
                                    <h3 class="card-title"><i class="fas fa-image mr-1"></i> Imagen del producto</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-0">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input
                                                        type="file"
                                                        name="image"
                                                        class="form-control-file"
                                                        id="fileInput"
                                                        accept=".jpg,.jpeg,.png,.webp"
                                                >
                                                <label for="fileInput" class="custom-file-label">Seleccionar
                                                    imagen</label>
                                            </div>
                                        </div>
                                        <small class="text-muted">JPG, PNG o WEBP — máx. 2MB. Opcional.</small>
                                        <output id="imagePreview"></output>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel: Resumen de precios -->
                            <?php include __DIR__ . '/partial/_resumen_precios.php'; ?>

                        </div><!-- /.product-sidebar-sticky -->
                    </div><!-- /.col-md-4 -->
                </div><!-- /.row -->

            </form>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->
