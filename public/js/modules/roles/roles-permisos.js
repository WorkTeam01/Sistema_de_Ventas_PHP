/**
 * ============================================================================
 * ASIGNACIÓN DE PERMISOS POR ROL
 * ============================================================================
 * Envía el conjunto de permisos marcados para el rol vía AJAX
 * usando ToastUtils para feedback visual.
 */

let isSubmitting = false;

$(document).ready(function () {

    // ========================================================================
    // CONTADOR DE PERMISOS SELECCIONADOS
    // ========================================================================
    function actualizarContador() {
        const total = $('.permiso-checkbox').length;
        const marcados = $('.permiso-checkbox:checked').length;
        $('#contadorPermisos').text(marcados + ' / ' + total + ' seleccionados');
    }

    // ========================================================================
    // ESTADO DEL CHECKBOX "SELECCIONAR MÓDULO" SEGÚN SUS PERMISOS
    // ========================================================================
    function actualizarToggleModulo($modulo) {
        const $checkboxes = $modulo.find('.permiso-checkbox');
        const total = $checkboxes.length;
        const marcados = $checkboxes.filter(':checked').length;
        const $toggle = $modulo.find('.modulo-toggle');

        $toggle.prop('checked', total > 0 && marcados === total);
        $toggle.prop('indeterminate', marcados > 0 && marcados < total);
    }

    function inicializarTogglesModulo() {
        $('.modulo-permisos').each(function () {
            actualizarToggleModulo($(this));
        });
    }

    inicializarTogglesModulo();
    actualizarContador();

    // ========================================================================
    // SELECCIONAR / DESELECCIONAR TODOS LOS PERMISOS
    // ========================================================================
    $('#btnSeleccionarTodos').on('click', function () {
        $('.permiso-checkbox').prop('checked', true);
        inicializarTogglesModulo();
        actualizarContador();
    });

    $('#btnDeseleccionarTodos').on('click', function () {
        $('.permiso-checkbox').prop('checked', false);
        inicializarTogglesModulo();
        actualizarContador();
    });

    // ========================================================================
    // SELECCIONAR / DESELECCIONAR TODOS LOS PERMISOS DE UN MÓDULO
    // ========================================================================
    $(document).on('change', '.modulo-toggle', function () {
        const checked = $(this).prop('checked');
        $(this).closest('.modulo-permisos').find('.permiso-checkbox').prop('checked', checked);
        actualizarContador();
    });

    // ========================================================================
    // ACTUALIZAR ESTADO AL MARCAR/DESMARCAR UN PERMISO INDIVIDUAL
    // ========================================================================
    $(document).on('change', '.permiso-checkbox', function () {
        actualizarToggleModulo($(this).closest('.modulo-permisos'));
        actualizarContador();
    });

    $('#formPermisos').on('submit', function (e) {
        e.preventDefault();

        if (isSubmitting) return false;

        const id = $('#btnGuardarPermisos').data('id');
        const formData = $('#formPermisos').serialize();
        const submitBtn = $('#btnGuardarPermisos');
        const originalText = submitBtn.html();

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        ToastUtils.loadingWithMinTime('Guardando permisos...', function (loadingToast) {
            $.ajax({
                url: BASE_URL + '/roles/permisos/' + id,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    loadingToast.close();
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalText);

                    if (response.success) {
                        ToastUtils.success(response.message);
                    } else {
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
    });
});
