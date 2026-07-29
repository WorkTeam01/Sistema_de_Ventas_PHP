/**
 * Utilidades compartidas para los formularios de autenticación
 * (login, forgot-password, reset-password): configuración común de
 * jQuery Validate y manejo de envío con estado de carga.
 */
const AuthFormUtils = {

    /**
     * Callbacks comunes de jQuery Validate (placement, highlight, etc.)
     * Se combinan con `rules` y `messages` propios de cada formulario.
     */
    commonValidateCallbacks: {
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            const errorId = element.attr('id') + '-error';
            error.attr('id', errorId);
            error.addClass('invalid-feedback');
            element.attr('aria-describedby', errorId);
            element.closest('.input-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
            $(element).removeAttr('aria-describedby');
        },
        invalidHandler: function (event, validator) {
            const errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        }
    },

    /**
     * Maneja el envío de un formulario de auth: deshabilita el botón,
     * muestra estado de carga y hace submit tras un tiempo mínimo.
     * Si la página empieza a navegar (submit exitoso), cancela el aviso
     * de "tardando más de lo esperado" para no mostrarlo de forma contradictoria.
     *
     * @param {HTMLFormElement} form
     * @param {Object} opts
     * @param {string} opts.btnId - id del botón de submit
     * @param {string} opts.statusId - id del contenedor aria-live de estado
     * @param {string} opts.loadingLabel - texto del botón mientras carga (ej. "Ingresando...")
     * @param {string} opts.loadingToast - texto del toast de carga
     * @param {string} opts.statusMessage - texto del aria-live mientras se procesa
     * @param {number} [opts.minTime=1500] - tiempo mínimo de loading antes de enviar
     * @param {number} [opts.slowTimeout=5000] - tiempo tras el cual se avisa que tarda
     */
    handleSubmit: function (form, opts) {
        const $btn = $('#' + opts.btnId);
        const $status = $('#' + opts.statusId);
        const originalText = $btn.html();
        const minTime = opts.minTime ?? 1500;
        const slowTimeout = opts.slowTimeout ?? 5000;
        let navigating = false;

        $(window).one('pagehide beforeunload', function () {
            navigating = true;
        });

        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin mr-2"></i>' + opts.loadingLabel);
        $status.text(opts.statusMessage);

        if (typeof ToastUtils !== 'undefined') {
            ToastUtils.loadingWithMinTime(opts.loadingToast, () => {
                navigating = true;
                form.submit();
            }, minTime);
        } else {
            setTimeout(() => {
                navigating = true;
                form.submit();
            }, minTime);
        }

        setTimeout(() => {
            if (navigating) return;
            $btn.prop('disabled', false).html(originalText);
            $status.text('Esto está tardando más de lo esperado. Revisa tu conexión y presiona el botón para reintentar.');
        }, slowTimeout);
    }
};
