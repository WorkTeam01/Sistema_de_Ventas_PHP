$(document).ready(function () {
    // Validación del rango de fechas antes de enviar el formulario
    $('#filterForm').on('submit', function (e) {
        const desde = $('#desde').val();
        const hasta = $('#hasta').val();

        if (!desde || !hasta) {
            e.preventDefault();
            ToastUtils.error('Las fechas de inicio y fin son obligatorias.');
            return;
        }

        if (desde > hasta) {
            e.preventDefault();
            ToastUtils.error('La fecha de inicio no puede ser mayor a la fecha de fin.');
        }
    });

    // DataTable
    $('#activityLogTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] },
            { className: 'text-center', targets: [0, 2, 3, 4, 6] },
        ],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
            sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
            sInfoPostFix: '',
            sSearch: 'Buscar:',
            sUrl: '',
            sInfoThousands: ',',
            sLoadingRecords: 'Cargando...',
            oPaginate: {
                sFirst: 'Primero',
                sLast: 'Último',
                sNext: 'Siguiente',
                sPrevious: 'Anterior',
            },
            oAria: {
                sSortAscending: ': Activar para ordenar la columna de manera ascendente',
                sSortDescending: ': Activar para ordenar la columna de manera descendente',
            },
        },
        initComplete: function () {
            $(this.api().table().node()).css('visibility', 'visible');
        },
    });
});
