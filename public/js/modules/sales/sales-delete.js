$(document).ready(function () {
    $('.btn-confirm-delete-sale').on('click', function () {
        const saleNumber = $(this).data('nro-venta');

        AlertUtils.confirm(
            '¿Está seguro?',
            'Se eliminará la Venta N° ' + saleNumber + '. Esta acción no se puede deshacer.',
            function () {
                document.getElementById('formEliminar').submit();
            },
            {
                confirmText: 'Sí, eliminar',
                cancelText: 'Cancelar',
                cancelColor: '#6c757d'
            }
        );
    });
});
