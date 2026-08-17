<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-labelledby="modalCreateLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalCreateLabel">Registrar proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreate" autocomplete="off">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_nombre_proveedor">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_nombre_proveedor"
                                       name="nombre_proveedor"
                                       maxlength="255" placeholder="Nombre del contacto" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_empresa">Empresa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_empresa" name="empresa"
                                       maxlength="255" placeholder="Nombre de la empresa" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_celular">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_celular" name="celular"
                                       maxlength="50" placeholder="Número de celular" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="create_telefono" name="telefono"
                                       maxlength="50" placeholder="Teléfono fijo (opcional)">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_email">Email</label>
                                <input type="email" class="form-control" id="create_email" name="email"
                                       maxlength="254" placeholder="correo@empresa.com (opcional)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_direccion">Dirección <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="create_direccion" name="direccion"
                                          maxlength="255" rows="2" placeholder="Dirección de la empresa" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle -->
<div class="modal fade" id="modalShow" tabindex="-1" role="dialog" aria-labelledby="modalShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="modalShowLabel">Detalle del proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre del contacto</label>
                            <p class="form-control-plaintext border-bottom" id="show_nombre_proveedor">—</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Empresa</label>
                            <p class="form-control-plaintext border-bottom" id="show_empresa">—</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Celular</label>
                            <p class="form-control-plaintext border-bottom" id="show_celular">—</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Teléfono</label>
                            <p class="form-control-plaintext border-bottom" id="show_telefono">—</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Email</label>
                            <p class="form-control-plaintext border-bottom" id="show_email">—</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Dirección</label>
                            <p class="form-control-plaintext border-bottom" id="show_direccion">—</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="modalEditLabel">Editar proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEdit" autocomplete="off">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nombre_proveedor">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_nombre_proveedor"
                                       name="nombre_proveedor"
                                       maxlength="255" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_empresa">Empresa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_empresa" name="empresa"
                                       maxlength="255" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_celular">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_celular" name="celular"
                                       maxlength="50" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="edit_telefono" name="telefono"
                                       maxlength="50">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email"
                                       maxlength="254">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_direccion">Dirección <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_direccion" name="direccion"
                                          maxlength="255" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnUpdate">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>