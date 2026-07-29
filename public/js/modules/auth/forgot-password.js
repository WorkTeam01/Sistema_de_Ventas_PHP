$(document).ready(function () {
    $('#forgotForm').validate({
        rules: {
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            email: {
                required: 'El correo electrónico es requerido',
                email: 'Por favor, ingresa un correo electrónico válido'
            }
        },
        ...AuthFormUtils.commonValidateCallbacks,
        submitHandler: function (form) {
            AuthFormUtils.handleSubmit(form, {
                btnId: 'btnSend',
                statusId: 'formStatus',
                loadingLabel: 'Enviando...',
                loadingToast: 'Procesando solicitud...',
                statusMessage: 'Enviando enlace de recuperación, por favor espera...'
            });
        }
    });
});