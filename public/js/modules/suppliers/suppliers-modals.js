/**
 * ============================================================================
 * GESTIÓN DE PROVEEDORES - Operaciones CRUD con Modales
 * ============================================================================
 * Maneja las operaciones de crear, editar y eliminar proveedores mediante
 * modales y AJAX usando ToastUtils para feedback visual.
 */

// Variable global para prevenir envíos múltiples
let isSubmitting = false;

$(document).ready(function () {

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO CREAR
    // ========================================================================
    $('#formCreate').validate({
        rules: {
            nombre_proveedor: {
                required: true,
                maxlength: 255
            },
            empresa: {
                required: true,
                maxlength: 255,
                remote: {
                    url: BASE_URL + '/suppliers/check-nombre',
                    type: 'POST',
                    data: {
                        empresa: function () {
                            return $('#create_empresa').val();
                        },
                        id: function () {
                            return null;
                        }
                    }
                }
            },
            celular: {
                required: true,
                maxlength: 50
            },
            telefono: {
                maxlength: 50
            },
            email: {
                email: true,
                maxlength: 254
            },
            direccion: {
                required: true,
                maxlength: 255
            }
        },
        messages: {
            nombre_proveedor: {
                required: 'El nombre del contacto es obligatorio.',
                maxlength: 'El nombre no puede exceder 255 caracteres.'
            },
            empresa: {
                required: 'La empresa es obligatoria.',
                maxlength: 'La empresa no puede exceder 255 caracteres.'
            },
            celular: {
                required: 'El celular es obligatorio.',
                maxlength: 'El celular no puede exceder 50 caracteres.'
            },
            telefono: {
                maxlength: 'El teléfono no puede exceder 50 caracteres.'
            },
            email: {
                email: 'Ingrese un email válido.',
                maxlength: 'El email no puede exceder 254 caracteres.'
            },
            direccion: {
                required: 'La dirección es obligatoria.',
                maxlength: 'La dirección no puede exceder 255 caracteres.'
            }
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function () {
            crearProveedor();
        }
    });

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO EDITAR
    // ========================================================================
    $('#formEdit').validate({
        rules: {
            nombre_proveedor: {
                required: true,
                maxlength: 255
            },
            empresa: {
                required: true,
                maxlength: 255,
                remote: {
                    url: BASE_URL + '/suppliers/check-nombre',
                    type: 'POST',
                    data: {
                        empresa: function () {
                            return $('#edit_empresa').val();
                        },
                        id: function () {
                            return $('#edit_id').val() || null;
                        }
                    }
                }
            },
            celular: {
                required: true,
                maxlength: 50
            },
            telefono: {
                maxlength: 50
            },
            email: {
                email: true,
                maxlength: 254
            },
            direccion: {
                required: true,
                maxlength: 255
            }
        },
        messages: {
            nombre_proveedor: {
                required: 'El nombre del contacto es obligatorio.',
                maxlength: 'El nombre no puede exceder 255 caracteres.'
            },
            empresa: {
                required: 'La empresa es obligatoria.',
                maxlength: 'La empresa no puede exceder 255 caracteres.'
            },
            celular: {
                required: 'El celular es obligatorio.',
                maxlength: 'El celular no puede exceder 50 caracteres.'
            },
            telefono: {
                maxlength: 'El teléfono no puede exceder 50 caracteres.'
            },
            email: {
                email: 'Ingrese un email válido.',
                maxlength: 'El email no puede exceder 254 caracteres.'
            },
            direccion: {
                required: 'La dirección es obligatoria.',
                maxlength: 'La dirección no puede exceder 255 caracteres.'
            }
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function () {
            actualizarProveedor();
        }
    });

    // ========================================================================
    // CREAR PROVEEDOR
    // ========================================================================
    function crearProveedor() {
        if (isSubmitting) return false;

        const formData = $('#formCreate').serialize();
        const submitBtn = $('#btnCreate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Guardando proveedor...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/suppliers/store',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();

                    if (response.success) {
                        $('#modalCreate').modal('hide');
                        $('#formCreate')[0].reset();
                        $('#formCreate').validate().resetForm();

                        ToastUtils.success(response.message);

                        setTimeout(function () {
                            window.location.reload();
                        }, 3000);
                    } else {
                        isSubmitting = false;
                        submitBtn.prop('disabled', false).html(originalText);
                        ToastUtils.error(response.message);
                    }
                },
                error: function () {
                    loadingToast.close();
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalText);
                    ToastUtils.error('Error en la comunicación con el servidor, por favor intente nuevamente.');
                }
            });
        }, 1500);
    }

    // ========================================================================
    // VER DETALLE DEL PROVEEDOR
    // ========================================================================
    $(document).on('click', '.btn-show', function () {
        if ($(this).data('processing')) return false;

        const $button = $(this);
        const id = $button.data('id');

        $button.data('processing', true).prop('disabled', true);

        ToastUtils.loadingWithMinTime('Cargando datos del proveedor...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/suppliers/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);

                    if (response.success) {
                        const data = response.data;

                        $('#show_nombre_proveedor').text(data.nombre_proveedor || '—');
                        $('#show_empresa').text(data.empresa || '—');
                        $('#show_celular').text(data.celular || '—');
                        $('#show_telefono').text(data.telefono || '—');
                        $('#show_email').text(data.email || '—');
                        $('#show_direccion').text(data.direccion || '—');

                        $('#modalShow').modal('show');
                    } else {
                        ToastUtils.error(response.message);
                    }
                },
                error: function () {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);
                    ToastUtils.error('Error en la comunicación con el servidor, por favor intente nuevamente.');
                }
            });
        }, 1500);
    });

    // ========================================================================
    // CARGAR DATOS PARA EDITAR
    // ========================================================================
    $(document).on('click', '.btn-edit', function () {
        if ($(this).data('processing')) return false;

        const $button = $(this);
        const id = $button.data('id');

        $button.data('processing', true).prop('disabled', true);

        ToastUtils.loadingWithMinTime('Cargando datos del proveedor...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/suppliers/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);

                    if (response.success) {
                        const data = response.data;

                        $('#edit_id').val(data.id_proveedor);
                        $('#edit_nombre_proveedor').val(data.nombre_proveedor);
                        $('#edit_empresa').val(data.empresa);
                        $('#edit_celular').val(data.celular);
                        $('#edit_telefono').val(data.telefono || '');
                        $('#edit_email').val(data.email || '');
                        $('#edit_direccion').val(data.direccion);

                        $('#formEdit').validate().resetForm();
                        $('#formEdit').find('.is-invalid').removeClass('is-invalid');

                        $('#modalEdit').modal('show');
                    } else {
                        ToastUtils.error(response.message);
                    }
                },
                error: function () {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);
                    ToastUtils.error('Error en la comunicación con el servidor, por favor intente nuevamente.');
                }
            });
        }, 1500);
    });

    // ========================================================================
    // ACTUALIZAR PROVEEDOR
    // ========================================================================
    function actualizarProveedor() {
        if (isSubmitting) return false;

        const id = $('#edit_id').val();
        const formData = $('#formEdit').serialize();
        const submitBtn = $('#btnUpdate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Actualizando proveedor...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/suppliers/update/' + id,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();

                    if (response.success) {
                        $('#modalEdit').modal('hide');

                        ToastUtils.success(response.message);

                        setTimeout(function () {
                            window.location.reload();
                        }, 3000);
                    } else {
                        isSubmitting = false;
                        submitBtn.prop('disabled', false).html(originalText);
                        ToastUtils.error(response.message);
                    }
                },
                error: function () {
                    loadingToast.close();
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalText);
                    ToastUtils.error('Error en la comunicación con el servidor.');
                }
            });
        }, 1500);
    }

    // ========================================================================
    // ELIMINAR PROVEEDOR
    // ========================================================================
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        AlertUtils.confirm(
            '¿Está seguro?',
            'Se eliminará al proveedor "' + nombre + '". Esta acción no se puede deshacer.',
            function () {
                ToastUtils.loadingWithMinTime('Eliminando proveedor...', function (loadingToast) {
                    $.ajax({
                        url: BASE_URL + '/suppliers/delete',
                        type: 'POST',
                        data: {id_proveedor: id},
                        dataType: 'json',
                        success: function (response) {
                            loadingToast.close();

                            if (response.success) {
                                ToastUtils.success(response.message);

                                setTimeout(function () {
                                    window.location.reload();
                                }, 3000);
                            } else {
                                ToastUtils.error(response.message);
                            }
                        },
                        error: function () {
                            loadingToast.close();
                            ToastUtils.error('Error en la comunicación con el servidor.');
                        }
                    });
                }, 1500);
            },
            {confirmText: 'Sí, eliminar', cancelColor: '#6c757d'}
        );
    });

    // ========================================================================
    // RESTABLECER ESTADO AL CERRAR MODALES
    // ========================================================================
    $('.modal').on('hidden.bs.modal', function () {
        isSubmitting = false;

        const $form = $(this).find('form');
        if ($form.length) {
            $form[0].reset();
            $form.validate().resetForm();
            $(this).find('.is-invalid').removeClass('is-invalid');
        }

        $(this).find('button[type="submit"]').prop('disabled', false).each(function () {
            const $btn = $(this);
            const $icon = $btn.find('i').first();
            if ($icon.length) {
                const iconClass = $icon.attr('class').replace('fa-spinner fa-spin', 'fa-check');
                const text = $btn.text().trim().replace('Procesando...', '');
                $btn.html('<i class="' + iconClass + '"></i> ' + (text || 'Guardar'));
            }
        });
    });

    $('.modal').on('hide.bs.modal', function () {
        document.activeElement.blur();
        document.body.focus();
    });
});
