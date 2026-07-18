$(document).ready(function () {
    $('.btn-confirm-delete-user').on('click', function () {
        const name = $(this).data('nombre');

        AlertUtils.confirmDeleteItem('usuario', name, function () {
            document.getElementById('formEliminar').submit();
        });
    });
});
