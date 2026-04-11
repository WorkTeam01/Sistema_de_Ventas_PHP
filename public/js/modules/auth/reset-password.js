$(document).ready(function () {
    // 1. Visibilidad de contraseñas
    function setupToggle(btnId, inputId) {
        const $btn   = $('#' + btnId);
        const $input = $('#' + inputId);

        $btn.on('click', function () {
            const isPassword = $input.attr('type') === 'password';
            $input.attr('type', isPassword ? 'text' : 'password');
            $btn.find('i').toggleClass('fa-eye fa-eye-slash');
        });
    }

    setupToggle('togglePassword', 'password');
    setupToggle('toggleConfirm',  'password_confirm');

    // 2. Indicador de fortaleza
    $('#password').on('keyup', function () {
        const val      = $(this).val();
        const strength = calcStrength(val);
        updateStrengthBar(strength);
    });

    function calcStrength(val) {
        if (!val) return 0;
        let score = 0;
        score += Math.min(val.length * 4, 40);
        if (/[a-z]/.test(val))  score += 10;
        if (/[A-Z]/.test(val))  score += 10;
        if (/[0-9]/.test(val))  score += 10;
        if (/[^a-zA-Z0-9]/.test(val)) score += 15;
        return Math.min(score, 100);
    }

    function updateStrengthBar(score) {
        const $fill = $('#strengthFill');
        const $text = $('#strengthText');
        $fill.css('width', score + '%');

        $fill.removeClass('strength-weak strength-fair strength-good strength-strong');

        if (score === 0) {
            $fill.css('width', '0');
            $text.text('');
        } else if (score <= 30) {
            $fill.addClass('strength-weak');
            $text.text('Contraseña muy débil').css('color', '#dc3545');
        } else if (score <= 60) {
            $fill.addClass('strength-fair');
            $text.text('Contraseña débil').css('color', '#ffc107');
        } else if (score <= 80) {
            $fill.addClass('strength-good');
            $text.text('Contraseña buena').css('color', '#17a2b8');
        } else {
            $fill.addClass('strength-strong');
            $text.text('Contraseña fuerte').css('color', '#28a745');
        }
    }

    // 3. Validación jQuery Validate
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
            const $btn = $('#btnReset');
            const originalText = $btn.html();

            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Actualizando contraseña...', () => {
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