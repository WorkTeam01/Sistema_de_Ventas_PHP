$(document).ready(function () {
    $("#userTable").DataTable({
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Usuarios",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Usuarios",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Usuarios)",
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
    }).buttons().container().appendTo('#userTable_wrapper .col-md-6:eq(0)');
});

function confirmarEliminar(id, nombre) {
    // 1. Verificar si el usuario tiene registros asociados
    fetch(`${BASE_URL}/users/check/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.referenced) {
                // Construir detalle de referencias
                let detalles = '';
                if (data.productos > 0) {
                    detalles += `<li>${data.productos} producto(s) registrado(s) en almacén</li>`;
                }
                if (data.compras > 0) {
                    detalles += `<li>${data.compras} compra(s) registrada(s)</li>`;
                }

                Swal.fire({
                    title: 'No se puede eliminar',
                    html: `El usuario <strong>${nombre}</strong> tiene registros asociados:<ul class="text-left mt-2">${detalles}</ul>Elimina primero esos registros antes de continuar.`,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // 2. Sin referencias — redirigir a la página de confirmación
            window.location.href = `${BASE_URL}/users/delete/${id}`;
        })
        .catch(() => {
            ToastUtils.error('Error de conexión', 'No se pudo verificar el usuario.');
        });
}