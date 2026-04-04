/**
 * ============================================================================
 * GESTIÓN DE COMPRAS - Inicialización DataTable
 * ============================================================================
 */

$(document).ready(function () {
    $('#purchaseTable').DataTable({
        responsive: true,
        autoWidth: false,
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]}
            }, {
                extend: 'pdf',
                title: 'Compras - Sistema de Ventas',
                filename: 'compras_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]},
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: 'COMPRAS - SISTEMA DE VENTAS',
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    doc.content.splice(1, 0, {
                        text: 'Listado de compras registradas en el sistema',
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
                title: 'Compras - Sistema de Ventas',
                messageTop: 'Registro de compras del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]}
            }, {
                extend: 'csv',
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]}
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Compras - Sistema de Ventas',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]},
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
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ compras',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 compras',
            sInfoFiltered: '(filtrado de un total de _MAX_ compras)',
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
    }).buttons().container().appendTo('#purchaseTable_wrapper .col-md-6:eq(0)');
});

function confirmarEliminar(id, idProducto, cantidad, nombre) {
    AlertUtils.confirm(
        '¿Está seguro?',
        'Se eliminará la compra del producto "' + nombre + '" y se revertirá el stock. Esta acción no se puede deshacer.',
        function () {
            document.getElementById('eliminarId').value = id;
            document.getElementById('eliminarProductoId').value = idProducto;
            document.getElementById('eliminarCantidad').value = cantidad;

            ToastUtils.loadingWithMinTime('Eliminando compra...', function () {
                document.getElementById('formEliminar').submit();
            }, 800);
        },
        {
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar'
        }
    );
}