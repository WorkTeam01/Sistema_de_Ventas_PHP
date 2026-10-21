(function ($) {
    'use strict';

    var table = $('#documentItems');
    var template = document.getElementById('documentItemTemplate');
    var symbol = table.data('currency-symbol') || '$';

    function formatAmount(amount) {
        return symbol + ' ' + new Intl.NumberFormat('es-CL', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(amount);
    }

    function updateTotals() {
        var total = 0;
        table.find('tbody .document-item').each(function () {
            var row = $(this);
            var quantity = Number(row.find('.item-quantity').val()) || 0;
            var price = Number(row.find('.item-price').val()) || 0;
            var subtotal = quantity * price;
            total += subtotal;
            row.find('.item-subtotal').text(quantity > 0 && price >= 0 ? formatAmount(subtotal) : '—');
        });
        $('#documentTotal').text(formatAmount(total));
    }

    table.on('change', '.product-select', function () {
        var option = $(this).find(':selected');
        $(this).closest('tr').find('.item-price').val(option.data('price') || '');
        updateTotals();
    });

    table.on('input change', '.item-quantity, .item-price', updateTotals);

    table.on('click', '[data-action="remove-item"]', function () {
        var rows = table.find('tbody .document-item');
        if (rows.length === 1) {
            rows.find('select').val('').trigger('change');
            rows.find('.item-quantity').val('1');
            rows.find('.item-price').val('');
        } else {
            $(this).closest('tr').remove();
        }
        updateTotals();
    });

    $('[data-action="add-item"]').on('click', function () {
        table.find('tbody').append(template.content.firstElementChild.cloneNode(true));
    });

    updateTotals();
})(jQuery);
