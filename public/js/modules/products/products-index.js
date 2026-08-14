/**
 * ============================================================================
 * GESTIÓN DE PRODUCTOS - Inicialización DataTable
 * ============================================================================
 */

$(document).ready(function () {
    $('#productTable').DataTable({
        responsive: true,
        autoWidth: false,
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {columns: [0, 2, 3, 4, 5, 6]}
            }, {
                extend: 'pdf',
                title: 'Productos - Sistema de Ventas',
                filename: 'productos_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {columns: [0, 2, 3, 4, 5, 6]},
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: 'PRODUCTOS - SISTEMA DE VENTAS',
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    doc.content.splice(1, 0, {
                        text: 'Listado de productos registrados en el sistema',
                        style: {
                            fontSize: 11,
                            alignment: 'center',
                            italic: true,
                            margin: [0, 0, 0, 10]
                        }
                    });

                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: {
                            fontSize: 9,
                            alignment: 'right',
                            margin: [0, 0, 0, 10]
                        }
                    });

                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [
                                {text: 'Sistema de Ventas', alignment: 'left', fontSize: 8},
                                {text: 'Página ' + currentPage + ' de ' + pageCount, alignment: 'center', fontSize: 8},
                                {text: 'Confidencial', alignment: 'right', fontSize: 8}
                            ],
                            margin: [40, 0]
                        };
                    };
                }
            }, {
                extend: 'excel',
                title: 'Productos - Sistema de Ventas',
                messageTop: 'Registro de productos del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {columns: [0, 2, 3, 4, 5, 6]}
            }, {
                extend: 'csv',
                exportOptions: {columns: [0, 2, 3, 4, 5, 6]}
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Productos - Sistema de Ventas',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {columns: [0, 2, 3, 4, 5, 6]},
                customize: function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');
                }
            }]
        }, {
            extend: 'colvis',
            text: 'Columnas'
        }],
        pageLength: 5,
        lengthMenu: [[3, 5, 10, 25, 50], [3, 5, 10, 25, 50]],
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
            $('#productTableLoading').remove();
            $(this.api().table().node()).css('visibility', 'visible');
        }
    }).buttons().container().appendTo('#productTable_wrapper .col-md-6:eq(0)');

    $('#productTable tbody').on('click', '.btn-delete-product', function () {
        confirmDelete($(this).data('id'), $(this).data('nombre'));
    });
});

function confirmDelete(id, name) {
    const $buttons = $('.btn-delete-product');
    $buttons.prop('disabled', true);

    ToastUtils.loadingWithMinTime('Verificando producto...', function (toast) {
        fetch(BASE_URL + '/products/check/' + id)
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                toast.close();
                $buttons.prop('disabled', false);

                if (data.referenced) {
                    let reasons = '';
                    if (data.carrito > 0) reasons += '<li>' + data.carrito + ' ítem(s) en carrito de ventas</li>';
                    if (data.compras > 0) reasons += '<li>' + data.compras + ' compra(s) registrada(s)</li>';

                    AlertUtils.blockedDelete('producto', name, reasons);
                    return;
                }

                ToastUtils.loadingWithMinTime('Redirigiendo...', function () {
                    window.location.href = BASE_URL + '/products/delete/' + id;
                }, 1200);
            })
            .catch(function () {
                if (toast) toast.close();
                $buttons.prop('disabled', false);
                ToastUtils.error('Error de conexión', 'No se pudo verificar el producto.');
            });
    }, 1500);
}
