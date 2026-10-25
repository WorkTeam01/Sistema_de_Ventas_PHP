/**
 * Neutraliza fórmulas en la exportación CSV de DataTables: una celda que empieza
 * por = + - @ (o tab/CR) la evalúa la hoja de cálculo al abrir el archivo.
 * Mismo criterio que ReportController::csvCell(). Excel no lo necesita: Buttons
 * escribe el texto como inlineStr, no como fórmula.
 */
(function ($) {
    const FORMULA_START = /^[=+\-@\t\r]/;
    const STRIP_OPTIONS = {stripHtml: true, stripNewlines: true, decodeEntities: true, trim: true};

    // Este `body` reemplaza al stripData de Buttons (quita HTML, entidades y
    // saltos de línea), así que se aplica primero para no exportar marcado.
    function neutralizeFormula(data) {
        const text = $.fn.dataTable.Buttons.stripData(data, STRIP_OPTIONS);
        if (typeof text !== 'string') return text;
        if (text !== '' && FORMULA_START.test(text) && !$.isNumeric(text)) {
            return "'" + text;
        }
        return text;
    }

    // Buttons copia la config del botón con un extend superficial: el
    // exportOptions de cada módulo pisaría uno definido en el botón base, así
    // que el formateo se inyecta al momento de exportar.
    const csvButton = $.fn.dataTable.ext.buttons.csvHtml5;
    const baseAction = csvButton.action;

    csvButton.action = function (e, dt, button, config) {
        config.exportOptions = $.extend(true, {}, config.exportOptions, {
            format: { body: neutralizeFormula }
        });
        return baseAction.call(this, e, dt, button, config);
    };
})(jQuery);
