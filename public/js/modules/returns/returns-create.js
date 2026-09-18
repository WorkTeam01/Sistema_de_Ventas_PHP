$(function () {
    var $inputs = $('.qty-input');
    var $btnRegistrar = $('#btnRegistrar');
    var $montoPreview = $('#montoPreview');
    var $motivo = $('#motivo');
    var $motivoCount = $('#motivoCount');
    var currencySymbol = window.APP_CURRENCY_SYMBOL || 'Bs.';
    var MOTIVO_MIN = 10;

    function updatePreview() {
        var total = 0;
        var hasAny = false;

        $inputs.each(function () {
            var $input = $(this);
            var qty = parseInt($input.val(), 10) || 0;
            var $badge = $('.pendiente[data-product-id="' + $input.data('product-id') + '"]');
            var max = parseInt($badge.text(), 10) || 0;
            var precio = parseFloat($badge.data('precio')) || 0;

            if (qty > max) {
                $input.val(max);
                qty = max;
            }
            if (qty < 0) {
                $input.val(0);
                qty = 0;
            }

            if (qty > 0) {
                hasAny = true;
                total += qty * precio;
            }
        });

        $montoPreview.text(currencySymbol + ' ' + total.toFixed(2));

        var motivoLen = $motivo.val().trim().length;
        $motivoCount.text(motivoLen);

        var motivoValid = motivoLen >= MOTIVO_MIN;
        $btnRegistrar.prop('disabled', !hasAny || !motivoValid);
    }

    $inputs.on('input change', updatePreview);
    $motivo.on('input', updatePreview);
    updatePreview();
});
