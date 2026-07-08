<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-labelledby="modalCreateLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalCreateLabel">Registrar permiso</h5>
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
                                <label for="create_clave">Clave <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_clave" name="clave"
                                       maxlength="60" placeholder="ej: manage_products">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_modulo">Módulo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_modulo" name="modulo"
                                       maxlength="40" placeholder="ej: products">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="create_descripcion">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="create_descripcion" name="descripcion"
                                          maxlength="150" rows="2" placeholder="Descripción del permiso"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-check"></i> Crear permiso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="modalEditLabel">Editar permiso</h5>
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
                                <label for="edit_clave">Clave <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_clave" name="clave"
                                       maxlength="60">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_modulo">Módulo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_modulo" name="modulo"
                                       maxlength="40">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_descripcion">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_descripcion" name="descripcion"
                                          maxlength="150" rows="2"></textarea>
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
