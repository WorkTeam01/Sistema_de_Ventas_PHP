/**
 * ============================================================================
 * GESTIÓN DE ROLES - Operaciones CRUD con Modales
 * ============================================================================
 * Maneja las operaciones de crear y editar roles mediante modales y AJAX
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
            rol: {
                required: true,
                maxlength: 100,
                remote: {
                    url: BASE_URL + '/roles/check-nombre',
                    type: 'POST',
                    data: {
                        rol: function () {
                            return $('#create_rol').val();
                        },
                        id: function () {
                            return null;
                        }
                    }
                }
            }
        },
        messages: {
            rol: {
                required: 'El nombre del rol es obligatorio.',
                maxlength: 'El nombre no puede exceder 100 caracteres.'
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
            crearRol();
        }
    });

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO EDITAR
    // ========================================================================
    $('#formEdit').validate({
        rules: {
            rol: {
                required: true,
                maxlength: 100,
                remote: {
                    url: BASE_URL + '/roles/check-nombre',
                    type: 'POST',
                    data: {
                        rol: function () {
                            return $('#edit_rol').val();
                        },
                        id: function () {
                            return $('#edit_id').val() || null;
                        }
                    }
                }
            }
        },
        messages: {
            rol: {
                required: 'El nombre del rol es obligatorio.',
                maxlength: 'El nombre no puede exceder 100 caracteres.'
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
            actualizarRol();
        }
    });

    // ========================================================================
    // CREAR ROL
    // ========================================================================
    function crearRol() {
        if (isSubmitting) return false;

        const formData = $('#formCreate').serialize();
        const submitBtn = $('#btnCreate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Guardando rol...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/roles/store',
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

        ToastUtils.loadingWithMinTime('Cargando datos del rol...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/roles/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);

                    if (response.success) {
                        const data = response.data;

                        $('#edit_id').val(data.id_rol);
                        $('#edit_rol').val(data.rol);

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
    // ACTUALIZAR ROL
    // ========================================================================
    function actualizarRol() {
        if (isSubmitting) return false;

        const id = $('#edit_id').val();
        const formData = $('#formEdit').serialize();
        const submitBtn = $('#btnUpdate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Actualizando rol...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/roles/update/' + id,
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
                    const msg = jqXHR.responseJSON?.message || 'Error en la comunicación con el servidor, por favor intente nuevamente.';
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

        $(this).find('form')[0].reset();
        $(this).find('form').validate().resetForm();
        $(this).find('.is-invalid').removeClass('is-invalid');

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
