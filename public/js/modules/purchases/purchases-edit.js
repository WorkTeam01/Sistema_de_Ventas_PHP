/**
 * ============================================================================
 * GESTIÓN DE COMPRAS - Formulario de edición
 * ============================================================================
 */

$(document).ready(function () {

    // -------------------------------------------------------------------------
    // Resumen en tiempo real
    // -------------------------------------------------------------------------

    function formatMoney(value) {
        return '$ ' + parseFloat(value || 0).toFixed(2);
    }

    function updateResumen() {
        var $productoOption = $('#id_producto option:selected');
        var $proveedorOption = $('#id_proveedor option:selected');
        var precio = parseFloat($('#precio_compra').val()) || 0;
        var cantidad = parseInt($('#cantidad').val()) || 0;
        var total = precio * cantidad;

        var productoText = $productoOption.val() ? $productoOption.text().trim() : '—';
        var proveedorText = $proveedorOption.val() ? $proveedorOption.text().trim() : '—';

        $('#resumenProducto').text(productoText).attr('title', productoText);
        $('#resumenProveedor').text(proveedorText).attr('title', proveedorText);
        $('#resumenPrecio').text(formatMoney(precio));
        $('#resumenCantidad').text(cantidad > 0 ? cantidad : 0);
        $('#badgeTotal').text(formatMoney(total));
    }

    $('#id_producto, #id_proveedor').on('change', updateResumen);
    $('#precio_compra, #cantidad').on('input change', updateResumen);

    // Poblar resumen con los valores actuales al cargar
    updateResumen();

    // -------------------------------------------------------------------------
    // Validación del formulario
    // -------------------------------------------------------------------------

    $('#purchaseEditForm').validate({
        rules: {
            fecha_compra: {
                required: true
            },
            comprobante: {
                required: true,
                minlength: 3
            },
            id_producto: {
                required: true
            },
            id_proveedor: {
                required: true
            },
            precio_compra: {
                required: true,
                number: true,
                min: 0.01
            },
            cantidad: {
                required: true,
                digits: true,
                min: 1
            }
        },
        messages: {
            fecha_compra: {
                required: 'La fecha de compra es requerida'
            },
            comprobante: {
                required: 'El comprobante es requerido',
                minlength: 'El comprobante debe tener al menos 3 caracteres'
            },
            id_producto: {
                required: 'Debe seleccionar un producto'
            },
            id_proveedor: {
                required: 'Debe seleccionar un proveedor'
            },
            precio_compra: {
                required: 'El precio de compra es requerido',
                number: 'Ingrese un precio válido',
                min: 'El precio debe ser mayor a 0'
            },
            cantidad: {
                required: 'La cantidad es requerida',
                digits: 'La cantidad debe ser un número entero',
                min: 'La cantidad debe ser al menos 1'
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            if (element.closest('.input-group').length) {
                element.closest('.input-group').after(error);
            } else {
                element.closest('.form-group').append(error);
            }
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function (form) {
            var $btn = $(form).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Actualizando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Actualizando compra...', function () {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });
});
