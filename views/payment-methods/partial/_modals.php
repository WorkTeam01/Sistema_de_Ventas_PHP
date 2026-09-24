<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-labelledby="modalCreateLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalCreateLabel">Registrar método de pago</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreate" autocomplete="off">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create_nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_nombre" name="nombre"
                            maxlength="60" placeholder="Ej: Tarjeta, QR, Transferencia" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="create_tipo">Tipo <span class="text-danger">*</span></label>
                        <select class="form-control" id="create_tipo" name="tipo" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="no_efectivo">No efectivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear método
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="modalEditLabel">Editar método de pago</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEdit" autocomplete="off">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre"
                            maxlength="60" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tipo">Tipo <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_tipo" name="tipo" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="no_efectivo">No efectivo</option>
                        </select>
                    </div>
                    <div class="custom-control custom-switch mb-0">
                        <input type="checkbox" class="custom-control-input" id="edit_activo"
                            name="activo" value="1" checked>
                        <label class="custom-control-label" for="edit_activo">Activo</label>
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