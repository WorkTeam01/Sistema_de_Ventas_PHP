/**
 * ============================================================================
 * GESTIÓN DE CLIENTES - Operaciones CRUD con Modales
 * ============================================================================
 * Maneja las operaciones de crear, editar y eliminar clientes mediante
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
            nombre_cliente: {
                required: true,
                minlength: 3,
                maxlength: 255
            },
            nit_ci_cliente: {
                required: true,
                minlength: 3,
                maxlength: 50,
                remote: {
                    url: BASE_URL + '/clients/check-nit-ci',
                    type: 'POST',
                    data: {
                        nit_ci_cliente: function () {
                            return $('#create_nit_ci_cliente').val();
                        },
                        id: function () {
                            return null;
                        }
                    }
                }
            },
            celular_cliente: {
                required: true,
                minlength: 7,
                maxlength: 50
            },
            email_cliente: {
                required: true,
                email: true,
                maxlength: 254,
                remote: {
                    url: BASE_URL + '/clients/check-email',
                    type: 'POST',
                    data: {
                        email_cliente: function () {
                            return $('#create_email_cliente').val();
                        },
                        id: function () {
                            return null;
                        }
                    }
                }
            }
        },
        messages: {
            nombre_cliente: {
                required: 'El nombre del cliente es obligatorio.',
                minlength: 'El nombre debe tener al menos 3 caracteres.',
                maxlength: 'El nombre no puede exceder 255 caracteres.'
            },
            nit_ci_cliente: {
                required: 'El NIT/CI es obligatorio.',
                minlength: 'El NIT/CI debe tener al menos 3 caracteres.',
                maxlength: 'El NIT/CI no puede exceder 50 caracteres.'
            },
            celular_cliente: {
                required: 'El celular es obligatorio.',
                minlength: 'El celular debe tener al menos 7 caracteres.',
                maxlength: 'El celular no puede exceder 50 caracteres.'
            },
            email_cliente: {
                required: 'El correo electrónico es obligatorio.',
                email: 'Ingrese un correo electrónico válido.',
                maxlength: 'El correo no puede exceder 254 caracteres.'
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
            crearCliente();
        }
    });

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO EDITAR
    // ========================================================================
    $('#formEdit').validate({
        rules: {
            nombre_cliente: {
                required: true,
                minlength: 3,
                maxlength: 255
            },
            nit_ci_cliente: {
                required: true,
                minlength: 3,
                maxlength: 50,
                remote: {
                    url: BASE_URL + '/clients/check-nit-ci',
                    type: 'POST',
                    data: {
                        nit_ci_cliente: function () {
                            return $('#edit_nit_ci_cliente').val();
                        },
                        id: function () {
                            return $('#edit_id').val() || null;
                        }
                    }
                }
            },
            celular_cliente: {
                required: true,
                minlength: 7,
                maxlength: 50
            },
            email_cliente: {
                required: true,
                email: true,
                maxlength: 254,
                remote: {
                    url: BASE_URL + '/clients/check-email',
                    type: 'POST',
                    data: {
                        email_cliente: function () {
                            return $('#edit_email_cliente').val();
                        },
                        id: function () {
                            return $('#edit_id').val() || null;
                        }
                    }
                }
            }
        },
        messages: {
            nombre_cliente: {
                required: 'El nombre del cliente es obligatorio.',
                minlength: 'El nombre debe tener al menos 3 caracteres.',
                maxlength: 'El nombre no puede exceder 255 caracteres.'
            },
            nit_ci_cliente: {
                required: 'El NIT/CI es obligatorio.',
                minlength: 'El NIT/CI debe tener al menos 3 caracteres.',
                maxlength: 'El NIT/CI no puede exceder 50 caracteres.'
            },
            celular_cliente: {
                required: 'El celular es obligatorio.',
                minlength: 'El celular debe tener al menos 7 caracteres.',
                maxlength: 'El celular no puede exceder 50 caracteres.'
            },
            email_cliente: {
                required: 'El correo electrónico es obligatorio.',
                email: 'Ingrese un correo electrónico válido.',
                maxlength: 'El correo no puede exceder 254 caracteres.'
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
            actualizarCliente();
        }
    });

    // ========================================================================
    // CREAR CLIENTE
    // ========================================================================
    function crearCliente() {
        if (isSubmitting) return false;

        const formData = $('#formCreate').serialize();
        const submitBtn = $('#btnCreate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Guardando cliente...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/clients/store',
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

        ToastUtils.loadingWithMinTime('Cargando datos del cliente...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/clients/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);

                    if (response.success) {
                        const data = response.data;

                        $('#edit_id').val(data.id_cliente);
                        $('#edit_nombre_cliente').val(data.nombre_cliente);
                        $('#edit_nit_ci_cliente').val(data.nit_ci_cliente);
                        $('#edit_celular_cliente').val(data.celular_cliente);
                        $('#edit_email_cliente').val(data.email_cliente);

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
    // ACTUALIZAR CLIENTE
    // ========================================================================
    function actualizarCliente() {
        if (isSubmitting) return false;

        const id = $('#edit_id').val();
        const formData = $('#formEdit').serialize();
        const submitBtn = $('#btnUpdate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Actualizando cliente...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/clients/update/' + id,
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
    // ELIMINAR CLIENTE
    // ========================================================================
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        AlertUtils.confirm(
            '¿Está seguro?',
            'Se eliminará al cliente "' + nombre + '". Esta acción no se puede deshacer.',
            function () {
                ToastUtils.loadingWithMinTime('Eliminando cliente...', function (loadingToast) {
                    $.ajax({
                        url: BASE_URL + '/clients/delete',
                        type: 'POST',
                        data: {id_cliente: id, csrf_token: $('input[name="csrf_token"]').first().val()},
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
                        error: function (jqXHR) {
                            loadingToast.close();
                            const msg = jqXHR.responseJSON?.message || 'Error en la comunicación con el servidor.';
                            ToastUtils.error(msg);
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
