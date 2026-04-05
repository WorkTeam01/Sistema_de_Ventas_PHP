<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products"><i class="fas fa-warehouse"></i>
                                Almacén</a></li>
                        <li class="breadcrumb-item active">Editar producto</li>
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
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Modificar datos del producto</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <form id="productEditForm" action="<?= BASE_URL ?>/products/update" method="post"
                              enctype="multipart/form-data">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="id_producto" value="<?= $id_producto; ?>">
                                <input type="hidden" name="image_text"
                                       value="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="row">
                                    <!-- Columna de campos -->
                                    <div class="col-md-9">
                                        <!-- Fila 1: Código, Categoría, Nombre -->
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Código</label>
                                                    <input type="text"
                                                           value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>"
                                                           class="form-control" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Categoría <span class="text-danger">*</span></label>
                                                    <div class="d-flex">
                                                        <select id="id_categoria" name="id_categoria"
                                                                class="form-control select2 mr-2" required>
                                                            <?php foreach ($categories as $category) : ?>
                                                                <option value="<?= $category['id_categoria']; ?>"
                                                                        <?= $category['id_categoria'] == $id_categoria ? 'selected' : ''; ?>>
                                                                    <?= htmlspecialchars($category['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <a href="<?= BASE_URL ?>/categories/create"
                                                           class="btn btn-primary" title="Nueva categoría">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label>Nombre del producto <span
                                                                class="text-danger">*</span></label>
                                                    <input type="text" id="nombre" name="nombre" class="form-control"
                                                           value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Fila 2: Usuario, Descripción -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Usuario</label>
                                                    <input type="text"
                                                           value="<?= htmlspecialchars($email_sesion, ENT_QUOTES, 'UTF-8'); ?>"
                                                           class="form-control" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Descripción</label>
                                                    <textarea name="descripcion" rows="2"
                                                              class="form-control"><?= htmlspecialchars($descripcion ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Fila 3: Stock y fecha -->
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Stock <span class="text-danger">*</span></label>
                                                    <input type="number" id="stock" name="stock" class="form-control"
                                                           value="<?= htmlspecialchars($stock, ENT_QUOTES, 'UTF-8'); ?>"
                                                           min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Stock mínimo</label>
                                                    <input type="number" id="stock_minimo" name="stock_minimo"
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($stock_minimo ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Stock máximo</label>
                                                    <input type="number" id="stock_maximo" name="stock_maximo"
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($stock_maximo ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Fecha ingreso <span class="text-danger">*</span></label>
                                                    <input type="date" id="fecha_ingreso" name="fecha_ingreso"
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($fecha_ingreso, ENT_QUOTES, 'UTF-8'); ?>"
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Fila 4: Precios -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Precio compra <span class="text-danger">*</span></label>
                                                    <input type="number" id="precio_compra" name="precio_compra"
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?>"
                                                           step="0.01" min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Precio venta <span class="text-danger">*</span></label>
                                                    <input type="number" id="precio_venta" name="precio_venta"
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($precio_venta, ENT_QUOTES, 'UTF-8'); ?>"
                                                           step="0.01" min="0" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Columna de imagen -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Imagen actual</label>
                                            <img id="currentImage"
                                                 src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>"
                                                 class="img-thumbnail img-fluid d-block mb-2" width="100%"
                                                 alt="Imagen actual">
                                        </div>
                                        <div class="form-group">
                                            <label>Cambiar imagen</label>
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" name="image" class="form-control-file"
                                                           id="fileInput" accept=".jpg,.jpeg,.png,.webp">
                                                    <label for="fileInput" class="custom-file-label">Seleccionar
                                                        imagen</label>
                                                </div>
                                            </div>
                                            <small class="text-muted">JPG, PNG o WEBP — máx. 2MB. Dejar vacío para
                                                conservar la imagen actual.</small>
                                            <output id="imagePreview"></output>
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
                                        <button type="submit" id="btnEditProduct" class="btn btn-success w-100">
                                            <i class="fas fa-save"></i> Actualizar producto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->