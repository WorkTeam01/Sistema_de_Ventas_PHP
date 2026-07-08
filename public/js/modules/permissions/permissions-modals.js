/**
 * ============================================================================
 * GESTIÓN DE PERMISOS - Operaciones CRUD con Modales
 * ============================================================================
 * Maneja las operaciones de crear y editar permisos mediante modales y AJAX
 * usando ToastUtils para feedback visual.
 */

// Variable global para prevenir envíos múltiples
let isSubmitting = false;

$(document).ready(function () {

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO CREAR
    // ========================================================================
    $('#formCreate').validate({
        rules: {
            clave: {
                required: true,
                maxlength: 60,
                remote: {
                    url: BASE_URL + '/permissions/check-clave',
                    type: 'POST',
                    data: {
                        clave: function () {
                            return $('#create_clave').val();
                        },
                        id: function () {
                            return null;
                        }
                    }
                }
            },
            modulo: {
                required: true,
                maxlength: 40
            },
            descripcion: {
                required: true,
                maxlength: 150
            }
        },
        messages: {
            clave: {
                required: 'La clave es obligatoria.',
                maxlength: 'La clave no puede exceder 60 caracteres.'
            },
            modulo: {
                required: 'El módulo es obligatorio.',
                maxlength: 'El módulo no puede exceder 40 caracteres.'
            },
            descripcion: {
                required: 'La descripción es obligatoria.',
                maxlength: 'La descripción no puede exceder 150 caracteres.'
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
            crearPermiso();
        }
    });

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO EDITAR
    // ========================================================================
    $('#formEdit').validate({
        rules: {
            clave: {
                required: true,
                maxlength: 60,
                remote: {
                    url: BASE_URL + '/permissions/check-clave',
                    type: 'POST',
                    data: {
                        clave: function () {
                            return $('#edit_clave').val();
                        },
                        id: function () {
                            return $('#edit_id').val() || null;
                        }
                    }
                }
            },
            modulo: {
                required: true,
                maxlength: 40
            },
            descripcion: {
                required: true,
                maxlength: 150
            }
        },
        messages: {
            clave: {
                required: 'La clave es obligatoria.',
                maxlength: 'La clave no puede exceder 60 caracteres.'
            },
            modulo: {
                required: 'El módulo es obligatorio.',
                maxlength: 'El módulo no puede exceder 40 caracteres.'
            },
            descripcion: {
                required: 'La descripción es obligatoria.',
                maxlength: 'La descripción no puede exceder 150 caracteres.'
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
            actualizarPermiso();
        }
    });

    // ========================================================================
    // CREAR PERMISO
    // ========================================================================
    function crearPermiso() {
        if (isSubmitting) return false;

        const formData = $('#formCreate').serialize();
        const submitBtn = $('#btnCreate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Guardando permiso...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/permissions/store',
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
                error: function (jqXHR) {
                    loadingToast.close();
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalText);
                    const msg = jqXHR.responseJSON?.message || 'Error en la comunicación con el servidor, por favor intente nuevamente.';
                    ToastUtils.error(msg);
                }
            });
        }, 1500);
    }

    // ========================================================================
    // CARGAR DATOS PARA EDITAR
    // ========================================================================
    $(document).on('click', '.btn-edit', function () {
        if ($(this).data('processing')) return false;

        const $button = $(this);
        const id = $button.data('id');

        $button.data('processing', true).prop('disabled', true);

        ToastUtils.loadingWithMinTime('Cargando datos del permiso...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/permissions/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);

                    if (response.success) {
                        const data = response.data;

                        $('#edit_id').val(data.id_permiso);
                        $('#edit_clave').val(data.clave);
                        $('#edit_modulo').val(data.modulo);
                        $('#edit_descripcion').val(data.descripcion);

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
    // ACTUALIZAR PERMISO
    // ========================================================================
    function actualizarPermiso() {
        if (isSubmitting) return false;

        const id = $('#edit_id').val();
        const formData = $('#formEdit').serialize();
        const submitBtn = $('#btnUpdate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Actualizando permiso...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/permissions/update/' + id,
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
                error: function (jqXHR) {
                    loadingToast.close();
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalText);
                    const msg = jqXHR.responseJSON?.message || 'Error en la comunicación con el servidor.';
                    ToastUtils.error(msg);
                }
            });
        }, 1500);
    }

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
