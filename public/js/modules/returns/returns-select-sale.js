$(function () {
    $('#tbl-sales').DataTable({
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ ventas',
            infoEmpty: 'No hay ventas',
            infoFiltered: '(filtrado de _MAX_ total)',
            zeroRecords: 'No se encontraron ventas',
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
        },
        order: [[0, 'desc']],
    });
});
