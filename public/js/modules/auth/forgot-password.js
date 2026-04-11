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
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.input-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function (form) {
            const $btn = $('#btnSend');
            const originalText = $btn.html();

            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Procesando solicitud...', () => {
                    form.submit();
                }, 1500);
            } else {
                setTimeout(() => { form.submit(); }, 1500);
            }

            setTimeout(() => {
                $btn.prop('disabled', false).html(originalText);
            }, 5000);
        }
    });
});