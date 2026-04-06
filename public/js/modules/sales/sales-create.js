/**
 * ============================================================================
 * GESTIÓN DE VENTAS - POS Wizard (3 pasos: Cliente → Carrito → Pago)
 * ============================================================================
 */

// ---- Estado del wizard ----

let currentStep = 0;
const steps    = ['#pane-cliente', '#pane-carrito', '#pane-pago'];
const tabLinks = ['#tab-cliente-link', '#tab-carrito-link', '#tab-pago-link'];

function goToStep(n) {
    currentStep = Math.max(0, Math.min(n, steps.length - 1));
    $(tabLinks[currentStep]).tab('show');
    updateProgress();
}

function updateProgress() {
    const pct  = Math.round(((currentStep + 1) / steps.length) * 100);
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

// ---- Restaurar tab tras recarga por carrito (sessionStorage) ----

$(function () {
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

$('#btn-ant-carrito').on('click', function () { goToStep(0); });

$('#btn-sig-carrito').on('click', function () {
    const cartCount = parseInt($('#badge-cart-count').text(), 10) || 0;
    if (cartCount === 0) {
        AlertUtils.warning('Atención', 'Debe agregar al menos un producto al carrito antes de continuar.');
        return;
    }
    goToStep(2);
});

$('#btn-ant-pago').on('click', function () { goToStep(1); });

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
            sProcessing:     'Procesando...',
            sLengthMenu:     'Mostrar _MENU_ registros',
            sZeroRecords:    'No se encontraron resultados',
            sEmptyTable:     'Ningún dato disponible en esta tabla',
            sInfo:           'Mostrando _START_ al _END_ de _TOTAL_ productos',
            sInfoEmpty:      'Mostrando 0 al 0 de 0 productos',
            sInfoFiltered:   '(de _MAX_ productos)',
            sSearch:         'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
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
            sProcessing:     'Procesando...',
            sLengthMenu:     'Mostrar _MENU_ registros',
            sZeroRecords:    'No se encontraron resultados',
            sEmptyTable:     'Ningún dato disponible en esta tabla',
            sInfo:           'Mostrando _START_ al _END_ de _TOTAL_ clientes',
            sInfoEmpty:      'Mostrando 0 al 0 de 0 clientes',
            sInfoFiltered:   '(de _MAX_ clientes)',
            sSearch:         'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
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

$(document).on('click', '.btn-seleccionar-cliente', function () {
    const nombre = $(this).data('nombre');

    $('#id_cliente_hidden').val($(this).data('id'));
    $('#cliente_nombre').val(nombre);
    $('#cliente_nit').val($(this).data('nit'));
    $('#cliente_celular').val($(this).data('celular'));
    $('#cliente_email').val($(this).data('email'));

    // Actualizar estado visual en el tab Cliente
    $('#alert-sin-cliente').addClass('d-none');
    $('#alert-cliente-ok').removeClass('d-none');

    // Actualizar resumen lateral
    $('#resumen-cliente').removeClass('text-muted font-italic').text(nombre);

    $('#modal-buscar_cliente').modal('hide');
});

// ---- Calcular cambio ----

$('#total_pagado').on('input', function () {
    const cancelar = parseFloat($('#total_a_cancelar_hidden').val()) || 0;
    const pagado   = parseFloat($(this).val()) || 0;
    const cambio   = pagado - cancelar;
    $('#cambio').val(isNaN(cambio) ? '' : cambio.toFixed(2));
});

// ---- Validar antes de guardar la venta ----

$('#formVenta').on('submit', function (e) {
    if (!$('#id_cliente_hidden').val()) {
        e.preventDefault();
        AlertUtils.warning('Atención', 'Debe seleccionar un cliente antes de guardar la venta.');
        goToStep(0); // Llevar al Tab 1: Cliente
    }
});