'use strict';

$(function () {
    // Validación: fecha_desde <= fecha_hasta antes de submit
    $('#form-filters').on('submit', function (e) {
        const desde = $('#fecha_desde').val();
        const hasta = $('#fecha_hasta').val();

        if (desde && hasta && desde > hasta) {
            e.preventDefault();
            ToastUtils.warning('La fecha "Desde" no puede ser mayor que "Hasta".');
            return false;
        }
    });

    // ── DataTable: Reporte de Ventas ──────────────────────────────────────────
    if ($('#salesTable').length) {
        $('#salesTable').DataTable({
            responsive: true,
            autoWidth: false,
            order: [[1, 'desc']],
            pageLength: 25,
            buttons: [{
                extend: 'collection',
                text: '<i class="fas fa-download mr-1"></i> Exportar',
                buttons: [{
                    text: 'Copiar',
                    extend: 'copy',
                    exportOptions: { columns: [0, 1, 2, 3] }
                }, {
                    extend: 'excel',
                    title: 'Reporte de Ventas - Sistema de Ventas',
                    messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: { columns: [0, 1, 2, 3] }
                }, {
                    extend: 'csv',
                    exportOptions: { columns: [0, 1, 2, 3] }
                }, {
                    extend: 'print',
                    text: 'Imprimir',
                    title: 'Reporte de Ventas - Sistema de Ventas',
                    messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: { columns: [0, 1, 2, 3] },
                    customize: function (win) {
                        $(win.document.body).find('table')
                            .addClass('table-striped')
                            .css('font-size', '12px');
                    }
                }]
            }, {
                extend: 'colvis',
                text: '<i class="fas fa-columns mr-1"></i> Columnas'
            }],
            columnDefs: [
                { className: 'text-right', targets: [3] }
            ],
            language: dtLanguage(),
            initComplete: function () {
                $(this.api().table().node()).css('visibility', 'visible');
            }
        }).buttons().container().appendTo('#salesTable_wrapper .col-md-6:eq(0)');
    }

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
