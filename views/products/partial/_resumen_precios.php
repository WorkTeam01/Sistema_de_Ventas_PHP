<?php
/**
 * Panel de resumen de precios (compra, venta, ganancia, margen).
 * Compartido entre create.php y edit.php; actualizado en tiempo real
 * por ProductFormShared.initMargenCalculator() (products-form-shared.js).
 */
?>
<div class="card" id="resumenCard" data-currency="<?= htmlspecialchars(APP_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calculator mr-1"></i> Resumen</h2>
    </div>
    <div class="card-body p-0" aria-live="polite">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
            <tr>
                <td class="text-muted">Precio compra</td>
                <td class="text-right font-weight-bold" id="resumenPrecioCompra"><?= APP_CURRENCY_SYMBOL ?> 0.00</td>
            </tr>
            <tr>
                <td class="text-muted">Precio venta</td>
                <td class="text-right font-weight-bold" id="resumenPrecioVenta"><?= APP_CURRENCY_SYMBOL ?> 0.00</td>
            </tr>
            <tr class="border-top">
                <td class="text-muted">Ganancia</td>
                <td class="text-right font-weight-bold" id="resumenGanancia"><?= APP_CURRENCY_SYMBOL ?> 0.00</td>
            </tr>
            <tr>
                <td class="text-muted">Margen</td>
                <td class="text-right font-weight-bold">
                    <span class="badge badge-secondary" id="badgeMargen">0.00%</span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
