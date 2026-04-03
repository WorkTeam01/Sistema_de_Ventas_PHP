$(document).ready(function () {
    $("#productTable").DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy'
            }, {
                extend: 'pdf',
            }, {
                extend: 'csv',
            }, {
                extend: 'excel',
            }, {
                text: 'Imprimir',
                extend: 'print'
            }]
        },
        {
            extend: 'colvis',
            text: 'Visualización de columnas'
        }
        ],
        "pageLength": 5,
        lengthMenu: [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ productos",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 productos",
            "sInfoFiltered": "(filtrado de un total de _MAX_ productos)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        initComplete: function () {
            $(this.api().table().node()).css('visibility', 'visible');
        }
    }).buttons().container().appendTo('#productTable_wrapper .col-md-6:eq(0)');
});

function confirmarEliminar(id, nombre) {
    const $btns = $('button[onclick*="confirmarEliminar"]');
    $btns.prop('disabled', true);

    ToastUtils.loadingWithMinTime('Verificando producto...', function (toast) {
        fetch(BASE_URL + '/products/check/' + id)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                toast.close();
                $btns.prop('disabled', false);

                if (data.referenced) {
                    let detalles = '';
                    if (data.carrito > 0) detalles += '<li>' + data.carrito + ' ítem(s) en carrito de ventas</li>';
                    if (data.compras > 0) detalles += '<li>' + data.compras + ' compra(s) registrada(s)</li>';

                    Swal.fire({
                        title: 'No se puede eliminar',
                        html: 'El producto <strong>' + nombre + '</strong> tiene registros asociados:<ul class="text-left mt-2">' + detalles + '</ul>Elimina primero esos registros antes de continuar.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                ToastUtils.loadingWithMinTime('Redirigiendo...', function () {
                    window.location.href = BASE_URL + '/products/delete/' + id;
                }, 1200);
            })
            .catch(function () {
                if (toast) toast.close();
                $btns.prop('disabled', false);
                ToastUtils.error('Error de conexión', 'No se pudo verificar el producto.');
            });
    }, 1500);
}
