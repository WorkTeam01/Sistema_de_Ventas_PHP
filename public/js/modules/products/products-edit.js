$(document).ready(function () {
    // Preview de imagen con reemplazo de la imagen actual
    $('#fileInput').on('change', function (evt) {
        const files = evt.target.files;
        for (let i = 0, f; f = files[i]; i++) {
            if (!f.type.match('image.*')) continue;
            const reader = new FileReader();
            reader.onload = (function (theFile) {
                return function (e) {
                    document.getElementById('currentImage').style.display = 'none';
                    document.getElementById('imagePreview').innerHTML =
                        '<img class="img-thumbnail img-fluid d-block mb-3" src="' + e.target.result + '" width="100%" title="' + encodeURIComponent(theFile.name) + '"/>';
                };
            })(f);
            reader.readAsDataURL(f);
        }
    });

    // Validación del formulario
    $('#productEditForm').validate({
        rules: ProductFormShared.validationRules,
        messages: ProductFormShared.validationMessages,
        invalidHandler: ProductFormShared.invalidHandler,
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function (form) {
            const $btn = $('#btnEditProduct');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Actualizando producto...', () => {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });

    // Cálculo de margen en tiempo real (sidebar)
    ProductFormShared.initMargenCalculator();
});
