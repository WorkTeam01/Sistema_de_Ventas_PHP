/**
 * ============================================================================
 * GESTIÓN DE VENTAS - POS (Crear nueva venta)
 * ============================================================================
 */

// DataTables en modales: inicializar en shown.bs.modal para que el plugin
// pueda medir dimensiones reales y activar el responsive (botón +).
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
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ productos',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 productos',
            sInfoFiltered: '(filtrado de un total de _MAX_ productos)',
            sInfoPostFix: '',
            sSearch: 'Buscar:',
            sUrl: '',
            sInfoThousands: ',',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            },
            oAria: {
                sSortAscending: ': Activar para ordenar la columna de manera ascendente',
                sSortDescending: ': Activar para ordenar la columna de manera descendente'
            }
        },
        initComplete: function () {
            $(this.api().table().node()).css('visibility', 'visible');
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
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ clientes',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 clientes',
            sInfoFiltered: '(filtrado de un total de _MAX_ clientes)',
            sInfoPostFix: '',
            sSearch: 'Buscar:',
            sUrl: '',
            sInfoThousands: ',',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior'
            },
            oAria: {
                sSortAscending: ': Activar para ordenar la columna de manera ascendente',
                sSortDescending: ': Activar para ordenar la columna de manera descendente'
            }
        },
        initComplete: function () {
            $(this.api().table().node()).css('visibility', 'visible');
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