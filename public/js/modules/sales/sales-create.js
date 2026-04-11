/**
 * ============================================================================
 * GESTIÓN DE VENTAS - POS Wizard (3 pasos: Cliente → Carrito → Pago)
 * ============================================================================
 */

// ---- Estado del wizard ----

let currentStep = 0;
const steps = ['#pane-cliente', '#pane-carrito', '#pane-pago'];
const tabLinks = ['#tab-cliente-link', '#tab-carrito-link', '#tab-pago-link'];

function goToStep(n) {
    currentStep = Math.max(0, Math.min(n, steps.length - 1));
    $(tabLinks[currentStep]).tab('show');
    updateProgress();
}

function updateProgress() {
    const pct = Math.round(((currentStep + 1) / steps.length) * 100);
    const text = 'Paso ' + (currentStep + 1) + ' de ' + steps.length;
    $('#tab-progress').css('width', pct + '%').text(text)
        .attr('aria-valuenow', pct);
}

// Sincronizar progreso cuando el usuario hace click directamente en un tab
$('#saleTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    const idx = tabLinks.indexOf('#' + $(e.target).attr('id'));
    if (idx !== -1) {
        currentStep = idx;
        updateProgress();
    }
});

// ---- Restaurar tab y cliente tras recarga por carrito (sessionStorage) ----

$(function () {
    // Restaurar cliente seleccionado
    const savedClient = sessionStorage.getItem('pos_client');
    if (savedClient) {
        try {
            const c = JSON.parse(savedClient);
            seleccionarCliente(c.id, c.nombre, c.nit, c.celular, c.email);
        } catch (e) {
            sessionStorage.removeItem('pos_client');
        }
    }

    // Restaurar paso del wizard
    const savedStep = sessionStorage.getItem('pos_step');
    if (savedStep !== null) {
        sessionStorage.removeItem('pos_step');
        goToStep(parseInt(savedStep, 10));
    }
});

// Guardar paso actual antes de agregar al carrito (vuelve a Tab 2)
$('#btn_agregar_carrito').on('click', function () {
    if (!selectedProductId) {
        AlertUtils.warning('Atención', 'Seleccione un producto primero.');
        return;
    }
    const cantidad = parseInt($('#prod_cantidad').val());
    if (!cantidad || cantidad < 1) {
        $('#lbl_cantidad').removeClass('d-none');
        $('#prod_cantidad').focus();
        return;
    }
    $('#lbl_cantidad').addClass('d-none');
    sessionStorage.setItem('pos_step', '1'); // Tab 2: Carrito
    $('#cart_id_producto').val(selectedProductId);
    $('#cart_cantidad').val(cantidad);
    $('#formCarrito').submit();
});

// Guardar paso actual antes de eliminar del carrito (vuelve a Tab 2)
$(document).on('submit', '.form-cart-remove', function () {
    sessionStorage.setItem('pos_step', '1'); // Tab 2: Carrito
});

// ---- Botones de navegación ----

$('#btn-sig-cliente').on('click', function () {
    if (!$('#id_cliente_hidden').val()) {
        AlertUtils.warning('Atención', 'Debe seleccionar un cliente antes de continuar.');
        return;
    }
    goToStep(1);
});

$('#btn-ant-carrito').on('click', function () {
    goToStep(0);
});

$('#btn-sig-carrito').on('click', function () {
    const cartCount = parseInt($('#badge-cart-count').text(), 10) || 0;
    if (cartCount === 0) {
        AlertUtils.warning('Atención', 'Debe agregar al menos un producto al carrito antes de continuar.');
        return;
    }
    goToStep(2);
});

$('#btn-ant-pago').on('click', function () {
    goToStep(1);
});

// ---- DataTables en modales (lazy init) ----

$('#modal-buscar_producto').on('shown.bs.modal', function () {
    if ($.fn.DataTable.isDataTable('#productTable')) {
        $('#productTable').DataTable().columns.adjust().responsive.recalc();
        return;
    }
    $('#productTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], [5, 10, 25]],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando _START_ al _END_ de _TOTAL_ productos',
            sInfoEmpty: 'Mostrando 0 al 0 de 0 productos',
            sInfoFiltered: '(de _MAX_ productos)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: {sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior'}
        }
    });
});

$('#modal-buscar_cliente').on('shown.bs.modal', function () {
    if ($.fn.DataTable.isDataTable('#clientTable')) {
        $('#clientTable').DataTable().columns.adjust().responsive.recalc();
        return;
    }
    $('#clientTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], [5, 10, 25]],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando _START_ al _END_ de _TOTAL_ clientes',
            sInfoEmpty: 'Mostrando 0 al 0 de 0 clientes',
            sInfoFiltered: '(de _MAX_ clientes)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: {sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior'}
        }
    });
});

// ---- Seleccionar producto del modal ----

let selectedProductId = '';

$(document).on('click', '.btn-seleccionar', function () {
    selectedProductId = $(this).data('id');
    $('#prod_nombre').val($(this).data('nombre'));
    $('#prod_descripcion').val($(this).data('descripcion'));
    $('#prod_precio').val($(this).data('precio'));
    $('#prod_cantidad').val(1).focus();
});

// ---- Seleccionar cliente del modal ----

function seleccionarCliente(id, nombre, nit, celular, email) {
    $('#id_cliente_hidden').val(id);
    $('#cliente_nombre').val(nombre);
    $('#cliente_nit').val(nit);
    $('#cliente_celular').val(celular);
    $('#cliente_email').val(email);

    // Mostrar campos, ocultar alert
    $('#alert-sin-cliente').addClass('d-none');
    $('#cliente-fields').removeClass('d-none');

    // Actualizar resumen lateral
    $('#resumen-cliente').removeClass('text-muted font-italic').text(nombre);

    // Persistir en sessionStorage para sobrevivir recargas del carrito
    sessionStorage.setItem('pos_client', JSON.stringify({id, nombre, nit, celular, email}));
}

$(document).on('click', '.btn-seleccionar-cliente', function () {
    seleccionarCliente(
        $(this).data('id'),
        $(this).data('nombre'),
        $(this).data('nit'),
        $(this).data('celular'),
        $(this).data('email')
    );
    $('#modal-buscar_cliente').modal('hide');
});

// ---- Crear nuevo cliente desde ventas ----

$('#formNuevoCliente').validate({
    rules: {
        nombre_cliente: {required: true, minlength: 3, maxlength: 255},
        nit_ci_cliente: {
            required: true, minlength: 3, maxlength: 50,
            remote: {
                url: BASE_URL + '/clients/check-nit-ci',
                type: 'POST',
                data: {
                    nit_ci_cliente: function () {
                        return $('#nc_nit_ci').val();
                    },
                    id: function () {
                        return null;
                    }
                }
            }
        },
        celular_cliente: {required: true, minlength: 7, maxlength: 50},
        email_cliente: {
            required: true, email: true, maxlength: 254,
            remote: {
                url: BASE_URL + '/clients/check-email',
                type: 'POST',
                data: {
                    email_cliente: function () {
                        return $('#nc_email').val();
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
            required: 'El nombre es obligatorio.',
            minlength: 'Mínimo 3 caracteres.'
        },
        nit_ci_cliente: {
            required: 'El NIT/CI es obligatorio.',
            minlength: 'Mínimo 3 caracteres.'
        },
        celular_cliente: {
            required: 'El celular es obligatorio.',
            minlength: 'Mínimo 7 caracteres.'
        },
        email_cliente: {
            required: 'El correo es obligatorio.',
            email: 'Ingrese un correo válido.'
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
        crearNuevoCliente();
    }
});

let isCreatingClient = false;

function crearNuevoCliente() {
    if (isCreatingClient) return;

    const formData = $('#formNuevoCliente').serialize();
    const $btn = $('#btnNuevoCliente');
    const originalHtml = $btn.html();

    isCreatingClient = true;
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

    ToastUtils.loadingWithMinTime('Guardando cliente...', function (loadingToast) {
        $.ajax({
            url: BASE_URL + '/clients/store',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                loadingToast.close();
                isCreatingClient = false;
                $btn.prop('disabled', false).html(originalHtml);

                if (response.success) {
                    const d = response.data;
                    seleccionarCliente(d.id_cliente, d.nombre_cliente, d.nit_ci_cliente, d.celular_cliente, d.email_cliente);

                    $('#modal-nuevo_cliente').modal('hide');
                    $('#formNuevoCliente')[0].reset();
                    $('#formNuevoCliente').validate().resetForm();
                    $('#formNuevoCliente').find('.is-invalid').removeClass('is-invalid');

                    ToastUtils.success(response.message || 'Cliente creado y seleccionado.');
                } else {
                    ToastUtils.error(response.message);
                }
            },
            error: function () {
                loadingToast.close();
                isCreatingClient = false;
                $btn.prop('disabled', false).html(originalHtml);
                ToastUtils.error('Error en la comunicación con el servidor.');
            }
        });
    }, 1500);
}

$('#modal-nuevo_cliente').on('hidden.bs.modal', function () {
    isCreatingClient = false;
    $('#formNuevoCliente')[0].reset();
    $('#formNuevoCliente').validate().resetForm();
    $('#formNuevoCliente').find('.is-invalid').removeClass('is-invalid');
});

// ---- Calcular cambio ----

$(document).on('input keyup', '#total_pagado', function () {
    const cancelar = parseFloat($('#total_a_cancelar_hidden').val()) || 0;
    const pagado = parseFloat($(this).val()) || 0;
    const cambio = pagado - cancelar;
    $('#cambio').val(cambio.toFixed(2));
});

// ---- Validar antes de guardar la venta ----

$('#formVenta').on('submit', function (e) {
    if (!$('#id_cliente_hidden').val()) {
        e.preventDefault();
        AlertUtils.warning('Atención', 'Debe seleccionar un cliente antes de guardar la venta.');
        goToStep(0);
        return;
    }
    // Limpiar sesión al confirmar la venta
    sessionStorage.removeItem('pos_client');
    sessionStorage.removeItem('pos_step');
});