/**
 * ============================================================================
 * INVENTARIO — Control de Stock y Ajustes
 * ============================================================================
 */

$(document).ready(function () {

    // ── DataTable: Historial de Ajustes ──────────────────────────────────────
    if ($('#adjustmentsTable').length) {
        $('#adjustmentsTable').DataTable({
            responsive: true,
            autoWidth: false,
            buttons: [{
                extend: 'collection',
                text: 'Reportes',
                orientation: 'landscape',
                buttons: [{
                    text: 'Copiar',
                    extend: 'copy',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                }, {
                    extend: 'pdf',
                    title: 'Ajustes de Stock - Sistema de Ventas',
                    filename: 'ajustes_stock_' + new Date().toISOString().slice(0, 10),
                    pageSize: 'LETTER',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] },
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 11;
                        doc.styles.tableHeader.fillColor = '#4b545c';
                        doc.styles.tableHeader.color = '#ffffff';

                        doc.content.splice(0, 1, {
                            text: 'AJUSTES DE STOCK - SISTEMA DE VENTAS',
                            style: { fontSize: 16, alignment: 'center', bold: true, margin: [0, 10, 0, 10] }
                        });

                        doc.content.splice(1, 0, {
                            text: 'Historial de ajustes manuales de inventario',
                            style: { fontSize: 11, alignment: 'center', italic: true, margin: [0, 0, 0, 10] }
                        });

                        doc.content.splice(2, 0, {
                            text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                            style: { fontSize: 9, alignment: 'right', margin: [0, 0, 0, 10] }
                        });

                        doc.footer = function (currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: 'Sistema de Ventas', alignment: 'left', fontSize: 8 },
                                    { text: 'Página ' + currentPage + ' de ' + pageCount, alignment: 'center', fontSize: 8 },
                                    { text: 'Confidencial', alignment: 'right', fontSize: 8 }
                                ],
                                margin: [40, 0]
                            };
                        };
                    }
                }, {
                    extend: 'excel',
                    title: 'Ajustes de Stock - Sistema de Ventas',
                    messageTop: 'Historial de ajustes manuales de inventario',
                    messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                }, {
                    extend: 'csv',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                }, {
                    extend: 'print',
                    text: 'Imprimir',
                    title: 'Ajustes de Stock - Sistema de Ventas',
                    messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5] },
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
            pageLength: 25,
            order: [[0, 'desc']],
            columnDefs: [
                { className: 'text-center', targets: [2, 3] },
            ],
            language: dtLanguage(),
            initComplete: function () {
                $(this.api().table().node()).css('visibility', 'visible');
            },
        }).buttons().container().appendTo('#adjustmentsTable_wrapper .col-md-6:eq(0)');
    }

    // ── Select2 dentro del modal ──────────────────────────────────────────────
    $('#modalAjusteStock').on('shown.bs.modal', function () {
        const $sel = $('#selectProductoAjuste');

        $sel.on('change.preview', updatePreview);
        $('#selectTipoAjuste').on('change.preview', updatePreview);
        $('#inputCantidad').on('input.preview', updatePreview);

    }).on('hidden.bs.modal', function () {
        const $sel = $('#selectProductoAjuste');

        $('#formAjusteStock')[0].reset();
        $('#selectTipoAjuste').val('');
        $('#stock-preview').addClass('d-none');

        $sel.off('change.preview');
        $('#selectTipoAjuste').off('change.preview');
        $('#inputCantidad').off('input.preview');

        if ($sel.data('select2')) {
            $sel.val(null).trigger('change');
        }
    });

    // Redirección rápida a compras desde alertas de stock bajo
    $(document).on('click', '.btn-compra-rapida', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        AlertUtils.confirm(
            '¿Registrar compra para abastecer stock?',
            `El producto "${nombre}" tiene stock bajo. ¿Deseas ir a Compras para registrar una nueva compra?`,
            () => {
                window.location.href = BASE_URL + '/purchases/create?id_producto=' + id;
            },
            {
                icon: 'question',
                confirmColor: '#fd7e14',
                cancelColor: '#6c757d',
                confirmText: 'Sí, ir a Compras',
                cancelText: 'Cancelar',
            }
        );
    });

    // ── Preview de stock proyectado ───────────────────────────────────────────
    function updatePreview() {
        const $sel = $('#selectProductoAjuste');
        const stock = parseInt($sel.find('option:selected').data('stock') || 0, 10);
        const tipo = $('#selectTipoAjuste').val();
        const cantidad = parseInt($('#inputCantidad').val(), 10);

        if (!$sel.val() || !tipo || !cantidad || cantidad <= 0) {
            $('#stock-preview').addClass('d-none');
            return;
        }

        const nuevo = tipo === 'entrada' ? stock + cantidad : stock - cantidad;

        $('#stock-anterior').text(stock);
        $('#stock-nuevo')
            .text(nuevo)
            .removeClass('text-primary text-danger text-warning')
            .addClass(nuevo < 0 ? 'text-danger' : nuevo === 0 ? 'text-warning' : 'text-primary');

        $('#stock-preview').removeClass('d-none');
    }

    // ── Validación y confirmación del formulario ──────────────────────────────
    $('#formAjusteStock').on('submit', function (e) {
        e.preventDefault();

        const idProducto = $('#selectProductoAjuste').val();
        const cantidad = parseInt($('#inputCantidad').val(), 10);
        const motivo = $('#textareaMotivo').val().trim();
        const tipo = $('#selectTipoAjuste').val();

        if (!idProducto) {
            ToastUtils.error('Debe seleccionar un producto.');
            return;
        }
        if (!tipo) {
            ToastUtils.error('Debe seleccionar el tipo de ajuste.');
            return;
        }
        if (!cantidad || cantidad <= 0) {
            ToastUtils.error('La cantidad debe ser mayor a cero.');
            return;
        }
        if (!motivo) {
            ToastUtils.error('El motivo es obligatorio.');
            return;
        }

        AlertUtils.confirm(
            '¿Confirmar ajuste de stock?',
            'Esta acción actualizará el stock del producto.',
            () => {
                $('#btnGuardarAjuste').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'
                );
                ToastUtils.loadingWithMinTime('Registrando ajuste...', function () {
                    $('#formAjusteStock').off('submit').submit();
                }, 1000);
            },
            {
                icon: 'question',
                confirmColor: '#28a745',
                confirmText: 'Sí, guardar',
                cancelText: 'Cancelar',
            }
        );
    });

    // ── Helpers ───────────────────────────────────────────────────────────────
    function dtLanguage() {
        return {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
            sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior',
            },
        };
    }
});
