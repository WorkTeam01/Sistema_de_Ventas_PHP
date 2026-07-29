$(document).ready(function () {
    // Indicador de fortaleza
    $('#password').on('keyup', function () {
        const val = $(this).val();
        const strength = calcStrength(val);
        updateStrengthBar(strength);
    });

    function calcStrength(val) {
        if (!val) return 0;
        let score = 0;
        score += Math.min(val.length * 4, 40);
        if (/[a-z]/.test(val)) score += 10;
        if (/[A-Z]/.test(val)) score += 10;
        if (/[0-9]/.test(val)) score += 10;
        if (/[^a-zA-Z0-9]/.test(val)) score += 15;
        return Math.min(score, 100);
    }

    function updateStrengthBar(score) {
        const $fill = $('#strengthFill');
        const $text = $('#strengthText');
        $fill.css('width', score + '%');

        $fill.removeClass('strength-weak strength-fair strength-good strength-strong');
        $text.removeClass('strength-weak strength-fair strength-good strength-strong');

        if (score === 0) {
            $fill.css('width', '0');
            $text.text('');
        } else if (score <= 30) {
            $fill.addClass('strength-weak');
            $text.addClass('strength-weak').text('Contraseña muy débil');
        } else if (score <= 60) {
            $fill.addClass('strength-fair');
            $text.addClass('strength-fair').text('Contraseña débil');
        } else if (score <= 80) {
            $fill.addClass('strength-good');
            $text.addClass('strength-good').text('Contraseña buena');
        } else {
            $fill.addClass('strength-strong');
            $text.addClass('strength-strong').text('Contraseña fuerte');
        }
    }

    // Validación jQuery Validate
    $('#resetForm').validate({
        rules: {
            password: {
                required: true,
                minlength: 8
            },
            password_confirm: {
                required: true,
                equalTo: '#password'
            }
        },
        messages: {
            password: {
                required: 'La contraseña es requerida',
                minlength: 'La contraseña debe tener al menos 8 caracteres'
            },
            password_confirm: {
                required: 'Confirma tu contraseña',
                equalTo: 'Las contraseñas no coinciden'
            }
        },
        ...AuthFormUtils.commonValidateCallbacks,
        submitHandler: function (form) {
            AuthFormUtils.handleSubmit(form, {
                btnId: 'btnReset',
                statusId: 'formStatus',
                loadingLabel: 'Guardando...',
                loadingToast: 'Actualizando contraseña...',
                statusMessage: 'Actualizando contraseña, por favor espera...'
            });
        }
    });
});