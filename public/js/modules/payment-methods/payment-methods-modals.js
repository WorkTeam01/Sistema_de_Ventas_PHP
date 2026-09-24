/**
 * ============================================================================
 * MÉTODOS DE PAGO - Operaciones CRUD con Modales
 * ============================================================================
 * Maneja las operaciones de crear, editar y eliminar métodos de pago mediante
 * modales y AJAX usando ToastUtils para feedback visual.
 * Activar/desactivar se resuelve dentro de update (campo `activo`), sin verbos
 * toggle/activate.
 */

// Variable global para prevenir envíos múltiples
let isSubmitting = false;

$(document).ready(function () {

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO CREAR
    // ========================================================================
    $('#formCreate').validate({
        rules: {
            nombre: {
                required: true,
                maxlength: 60,
                remote: {
                    url: BASE_URL + '/payment-methods/check-nombre',
                    type: 'POST',
                    data: {
                        nombre: function () {
                            return $('#create_nombre').val();
                        },
                        id: function () {
                            return null;
                        }
                    }
                }
            },
            tipo: {
                required: true
            }
        },
        messages: {
            nombre: {
                required: 'El nombre del método de pago es obligatorio.',
                maxlength: 'El nombre no puede exceder 60 caracteres.'
            },
            tipo: {
                required: 'Seleccione un tipo de método de pago.'
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
            crearMetodoPago();
        }
    });

    // ========================================================================
    // VALIDACIÓN CON JQUERY VALIDATE - FORMULARIO EDITAR
    // ========================================================================
    $('#formEdit').validate({
        rules: {
            nombre: {
                required: true,
                maxlength: 60,
                remote: {
                    url: BASE_URL + '/payment-methods/check-nombre',
                    type: 'POST',
                    data: {
                        nombre: function () {
                            return $('#edit_nombre').val();
                        },
                        id: function () {
                            return $('#edit_id').val() || null;
                        }
                    }
                }
            },
            tipo: {
                required: true
            }
        },
        messages: {
            nombre: {
                required: 'El nombre del método de pago es obligatorio.',
                maxlength: 'El nombre no puede exceder 60 caracteres.'
            },
            tipo: {
                required: 'Seleccione un tipo de método de pago.'
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
            actualizarMetodoPago();
        }
    });

    // ========================================================================
    // CREAR MÉTODO DE PAGO
    // ========================================================================
    function crearMetodoPago() {
        if (isSubmitting) return false;

        const formData = $('#formCreate').serialize();
        const submitBtn = $('#btnCreate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Guardando método de pago...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/payment-methods/store',
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

        ToastUtils.loadingWithMinTime('Cargando datos del método de pago...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/payment-methods/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    $button.data('processing', false).prop('disabled', false);

                    if (response.success) {
                        const data = response.data;

                        $('#edit_id').val(data.id_metodo_pago);
                        $('#edit_nombre').val(data.nombre);
                        $('#edit_tipo').val(data.tipo);
                        $('#edit_activo').prop('checked', parseInt(data.activo, 10) === 1);

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
    // ACTUALIZAR MÉTODO DE PAGO (incluye activar/desactivar vía `activo`)
    // ========================================================================
    function actualizarMetodoPago() {
        if (isSubmitting) return false;

        const id = $('#edit_id').val();
        const formData = $('#formEdit').serializeArray()
            .filter(function (item) {
                return item.name !== 'activo';
            });
        formData.push({
            name: 'activo',
            value: $('#edit_activo').is(':checked') ? '1' : '0'
        });

        const submitBtn = $('#btnUpdate');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Actualizando método de pago...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/payment-methods/update/' + id,
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
    // ELIMINAR MÉTODO DE PAGO
    // ========================================================================
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const name = $(this).data('nombre');

        AlertUtils.confirmDeleteItem('método de pago', name, function () {
            ToastUtils.loadingWithMinTime('Eliminando método de pago...', function (loadingToast) {
                $.ajax({
                    url: BASE_URL + '/payment-methods/delete',
                    type: 'POST',
                    data: {
                        id_metodo_pago: id,
                        csrf_token: $('input[name="csrf_token"]').first().val()
                    },
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
        });
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
