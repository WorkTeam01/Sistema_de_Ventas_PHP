/**
 * ============================================================================
 * GESTIÓN DE VENTAS - POS (Crear nueva venta)
 * ============================================================================
 */

$(document).ready(function () {
    $('#productTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 5,
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible',
            sInfo: 'Mostrando _START_ al _END_ de _TOTAL_ productos',
            sInfoEmpty: 'Mostrando 0 de 0 productos',
            sInfoFiltered: '(filtrado de _MAX_ total)',
            sSearch: 'Buscar:',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            }
        }
    });

    $('#clientTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 5,
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible',
            sInfo: 'Mostrando _START_ al _END_ de _TOTAL_ clientes',
            sInfoEmpty: 'Mostrando 0 de 0 clientes',
            sInfoFiltered: '(filtrado de _MAX_ total)',
            sSearch: 'Buscar:',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            }
        }
    });
});

// Seleccionar producto del modal
var selectedProductId = '';

$(document).on('click', '.btn-seleccionar', function () {
    selectedProductId = $(this).data('id');
    $('#prod_nombre').val($(this).data('nombre'));
    $('#prod_descripcion').val($(this).data('descripcion'));
    $('#prod_precio').val($(this).data('precio'));
    $('#prod_cantidad').val(1).focus();
});

// Agregar al carrito
$('#btn_agregar_carrito').click(function () {
    if (!selectedProductId) {
        AlertUtils.warning('Atención', 'Seleccione un producto primero.');
        return;
    }
    var cantidad = parseInt($('#prod_cantidad').val());
    if (!cantidad || cantidad < 1) {
        $('#lbl_cantidad').removeClass('d-none');
        $('#prod_cantidad').focus();
        return;
    }
    $('#lbl_cantidad').addClass('d-none');
    $('#cart_id_producto').val(selectedProductId);
    $('#cart_cantidad').val(cantidad);
    $('#formCarrito').submit();
});

// Seleccionar cliente del modal
$(document).on('click', '.btn-seleccionar-cliente', function () {
    $('#id_cliente_hidden').val($(this).data('id'));
    $('#cliente_nombre').val($(this).data('nombre'));
    $('#cliente_nit').val($(this).data('nit'));
    $('#cliente_celular').val($(this).data('celular'));
    $('#cliente_email').val($(this).data('email'));
    $('#modal-buscar_cliente').modal('hide');
});

// Calcular cambio
$('#total_pagado').keyup(function () {
    var cancelar = parseFloat($('#total_a_cancelar_hidden').val()) || 0;
    var pagado = parseFloat($(this).val()) || 0;
    var cambio = pagado - cancelar;
    $('#cambio').val(isNaN(cambio) ? '' : cambio.toFixed(2));
});

// Validar antes de enviar la venta
$('#formVenta').on('submit', function (e) {
    var id_cliente = $('#id_cliente_hidden').val();
    if (!id_cliente) {
        e.preventDefault();
        AlertUtils.warning('Atención', 'Debe seleccionar un cliente antes de guardar la venta.');
    }
});