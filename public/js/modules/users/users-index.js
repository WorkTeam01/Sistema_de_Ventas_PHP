$(document).ready(function () {
    $('#userTable').DataTable({
        responsive: true,
        autoWidth: false,
        buttons: [
            {
                extend: 'collection',
                text: 'Reportes',
                orientation: 'landscape',
                buttons: [
                    { text: 'Copiar', extend: 'copy' },
                    { extend: 'pdf' },
                    { extend: 'csv' },
                    { extend: 'excel' },
                    { text: 'Imprimir', extend: 'print' }
                ]
            },
            { extend: 'colvis', text: 'Visualización de columnas' }
        ],
        pageLength: 5,
        lengthMenu: [[3, 5, 10, 25, 50], [3, 5, 10, 25, 50]],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Usuarios',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 Usuarios',
            sInfoFiltered: '(filtrado de un total de _MAX_ Usuarios)',
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
    }).buttons().container().appendTo('#userTable_wrapper .col-md-6:eq(0)');
});

function confirmarEliminar(url) {
    AlertUtils.confirmDelete(url, '¿Está seguro?', 'No podrá recuperar este registro.');
}
