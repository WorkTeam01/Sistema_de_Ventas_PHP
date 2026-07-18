$(document).ready(function () {
    $('.btn-confirm-delete-product').on('click', function () {
        const name = $(this).data('nombre');

        AlertUtils.confirmDeleteItem('producto', name, function () {
            document.getElementById('formEliminar').submit();
        });
    });
});
