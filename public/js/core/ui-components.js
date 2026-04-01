/**
 * UI Components Utilities
 * 
 * Funciones helper para inicializar y configurar componentes de UI
 * como Select2, Tooltips, y otros elementos reutilizables.
 */

// ============================================
// ComponentUtils - Utilidades para componentes UI
// ============================================

const ComponentUtils = {

    /**
     * Configuración base para Select2
     */
    select2BaseConfig: {
        theme: 'bootstrap4',
        width: '100%',
        allowClear: false,
        minimumResultsForSearch: 7,
        closeOnSelect: true,
        dropdownAutoWidth: true,
        language: {
            noResults: function () {
                return "No se encontraron resultados";
            },
            searching: function () {
                return "Buscando...";
            },
            loadingMore: function () {
                return "Cargando más resultados...";
            },
            inputTooShort: function () {
                return "Por favor ingrese 1 o más caracteres";
            },
            inputTooLong: function () {
                return "Por favor elimine algunos caracteres";
            },
            maximumSelected: function () {
                return "Solo puede seleccionar un elemento";
            }
        }
    },

    /**
     * Configuración base para Tooltips
     */
    tooltipBaseConfig: {
        placement: 'top',
        trigger: 'hover focus',
        delay: {
            show: 200,
            hide: 0
        },
        container: 'body',
        boundary: 'window',
        animation: true
    },

    /**
     * Inicializar Select2 en todos los elementos con clase .select2
     * 
     * @param {Object} customConfig - Configuración personalizada (opcional)
     * @param {string} selector - Selector CSS (default: '.select2')
     * @returns {boolean} - True si se inicializó correctamente
     */
    initSelect2: function (customConfig = {}, selector = '.select2') {
        try {
            // Verificar disponibilidad
            const hasJQuery = typeof $ !== 'undefined';
            const hasSelect2 = hasJQuery && typeof $.fn.select2 !== 'undefined';
            const elements = hasJQuery ? $(selector) : [];

            // Si no hay elementos, no hacer nada
            if (elements.length === 0) return false;

            // Si hay elementos pero falta el plugin, advertir
            if (!hasSelect2) {
                console.warn('Se encontraron elementos .select2 pero el plugin Select2 no está cargado.');
                return false;
            }

            // Destruir instancias existentes para evitar duplicados
            elements.each(function () {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });

            // Combinar configuración base con personalizada
            const config = { ...this.select2BaseConfig, ...customConfig };

            // Limpiar espacios en blanco de las opciones
            this.trimSelectOptions(selector);

            // Inicializar Select2
            elements.select2(config);

            // Manejar eventos de apertura/cierre para animaciones
            this._setupSelect2Events();

            return true;

        } catch (error) {
            console.error('Error al inicializar Select2:', error);
            return false;
        }
    },

    /**
     * Inicializar Select2 en un elemento específico
     * 
     * @param {string|jQuery} element - Elemento o selector
     * @param {Object} customConfig - Configuración personalizada
     * @returns {boolean}
     */
    initSelect2Single: function (element, customConfig = {}) {
        try {
            if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                return false;
            }

            const $element = $(element);

            // Destruir si ya existe
            if ($element.data('select2')) {
                $element.select2('destroy');
            }

            const config = { ...this.select2BaseConfig, ...customConfig };
            $element.select2(config);

            return true;

        } catch (error) {
            console.error('Error al inicializar Select2 single:', error);
            return false;
        }
    },

    /**
     * Configurar eventos para Select2 (flechas de dropdown)
     * @private
     */
    _setupSelect2Events: function () {
        // Remover eventos previos para evitar duplicados
        $(document).off('select2:open.componentUtils select2:close.componentUtils');

        // Agregar clase cuando se abre el dropdown
        $(document).on('select2:open.componentUtils', function (e) {
            const targetId = $(e.target).attr('id');
            if (targetId) {
                $('#select2-' + targetId + '-container')
                    .closest('.select2-container')
                    .addClass('select2-container--arrow-up');
            } else {
                $('.select2-container--open').addClass('select2-container--arrow-up');
            }
        });

        // Remover clase cuando se cierra
        $(document).on('select2:close.componentUtils', function () {
            $('.select2-container').removeClass('select2-container--arrow-up');
        });
    },

    /**
     * Limpiar espacios en blanco de las opciones de un select
     * 
     * @param {string} selector - Selector del select
     */
    trimSelectOptions: function (selector = 'select') {
        try {
            const selects = typeof $ !== 'undefined' ? $(selector) : [];
            selects.find('option').each(function () {
                const $option = $(this);
                const text = $option.text();
                if (text && text.trim() !== text) {
                    $option.text(text.trim());
                }

                // También limpiar los tooltips si tienen
                const title = $option.attr('title');
                if (title && title.trim() !== title) {
                    $option.attr('title', title.trim());
                }
            });
        } catch (error) {
            console.error('Error al limpiar opciones de select:', error);
        }
    },

    /**
     * Destruir todas las instancias de Select2
     * 
     * @param {string} selector - Selector CSS (default: '.select2')
     */
    destroySelect2: function (selector = '.select2') {
        try {
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                $(selector).each(function () {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });
            }
        } catch (error) {
            console.error('Error al destruir Select2:', error);
        }
    },

    /**
     * Inicializar Tooltips en todos los elementos con data-toggle="tooltip"
     * 
     * @param {Object} customConfig - Configuración personalizada (opcional)
     * @param {string} selector - Selector CSS (default: '[data-toggle="tooltip"]')
     * @returns {boolean}
     */
    initTooltips: function (customConfig = {}, selector = '[data-toggle="tooltip"]') {
        try {
            // Verificar disponibilidad
            const hasJQuery = typeof $ !== 'undefined';
            const hasTooltip = hasJQuery && typeof $.fn.tooltip !== 'undefined';
            const elements = hasJQuery ? $(selector) : [];

            // Si no hay elementos, no hacer nada
            if (elements.length === 0) return false;

            // Si hay elementos pero falta el plugin, advertir
            if (!hasTooltip) {
                console.warn('Se encontraron elementos con data-toggle="tooltip" pero Bootstrap Tooltip no está cargado.');
                return false;
            }

            // Destruir tooltips existentes
            this.destroyTooltips(selector);

            // Combinar configuración
            const config = { ...this.tooltipBaseConfig, ...customConfig };

            // Inicializar tooltips generales
            $(selector + ':not(.progress-bar)').tooltip(config);

            // Inicializar tooltips especiales para progress-bar
            $('.progress-bar[data-toggle="tooltip"]').tooltip({
                ...config,
                template: '<div class="tooltip" role="tooltip">' +
                    '<div class="arrow"></div>' +
                    '<div class="tooltip-inner bg-primary"></div>' +
                    '</div>'
            });

            // Manejar interacción táctil
            this._setupTooltipTouchEvents(selector);

            // Aplicar estilos personalizados
            this.applyTooltipStyles();

            return true;

        } catch (error) {
            console.error('Error al inicializar Tooltips:', error);
            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.error('Error al inicializar tooltips');
            }
            return false;
        }
    },

    /**
     * Configurar eventos touch para tooltips
     * @private
     */
    _setupTooltipTouchEvents: function (selector) {
        // Remover eventos previos (usando namespace para seguridad)
        $(document).off('touchstart.componentUtils touchend.componentUtils touchcancel.componentUtils');

        // Mostrar tooltip en touch
        $(document).on('touchstart.componentUtils', selector, function () {
            $(this).tooltip('show');
        });

        // Ocultar tooltip al finalizar touch
        $(document).on('touchend.componentUtils touchcancel.componentUtils', selector, function () {
            $(this).tooltip('hide');
        });
    },

    /**
     * Aplicar estilos a los tooltips
     */
    applyTooltipStyles: function () {
        try {
            // Estilo por defecto (Negro/Gris oscuro con texto blanco)
            const style = {
                background: '#212529',
                border: '#343a40',
                text: '#ffffff'
            };

            // Crear o actualizar elemento de estilos dinámicos
            let styleElement = document.getElementById('tooltip-custom-styles');
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = 'tooltip-custom-styles';
                document.head.appendChild(styleElement);
            }

            // Inyectar CSS
            styleElement.textContent = `
                .tooltip .tooltip-inner {
                    background-color: ${style.background} !important;
                    color: ${style.text} !important;
                    border: 1px solid ${style.border};
                    padding: 0.5rem 0.75rem;
                    font-size: 0.85rem;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
                    border-radius: 0.3rem;
                    font-weight: 400;
                }
                .tooltip .arrow::before {
                    border-top-color: ${style.background} !important;
                }
                .bs-tooltip-top .arrow::before, .bs-tooltip-auto[x-placement^=top] .arrow::before {
                    border-top-color: ${style.background} !important;
                }
                .bs-tooltip-bottom .arrow::before, .bs-tooltip-auto[x-placement^=bottom] .arrow::before {
                    border-bottom-color: ${style.background} !important;
                }
                .bs-tooltip-left .arrow::before, .bs-tooltip-auto[x-placement^=left] .arrow::before {
                    border-left-color: ${style.background} !important;
                }
                .bs-tooltip-right .arrow::before, .bs-tooltip-auto[x-placement^=right] .arrow::before {
                    border-right-color: ${style.background} !important;
                }
                .tooltip {
                    opacity: 1 !important;
                    z-index: 1060;
                }
            `;
        } catch (error) {
            console.error('Error al aplicar estilos de tooltips:', error);
        }
    },

    /**
     * Destruir todos los tooltips
     * 
     * @param {string} selector - Selector CSS (default: '[data-toggle="tooltip"]')
     */
    destroyTooltips: function (selector = '[data-toggle="tooltip"]') {
        try {
            if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
                $('.tooltip').remove();
                $(selector).tooltip('dispose');
            }
        } catch (error) {
            console.error('Error al destruir Tooltips:', error);
        }
    },

    /**
     * Reinicializar todos los componentes UI
     * Útil después de cargar contenido dinámico vía AJAX
     * 
     * @param {Object} options - Opciones de configuración
     * @returns {Object} - Estado de inicialización
     */
    initAll: function (options = {}) {
        const results = {
            select2: false,
            tooltips: false
        };

        try {
            // Limpiar todos los selects estándar primero
            this.trimSelectOptions();

            // Inicializar Select2
            if (options.select2 !== false) {
                results.select2 = this.initSelect2(
                    options.select2Config || {},
                    options.select2Selector || '.select2'
                );
            }

            // Inicializar Tooltips
            if (options.tooltips !== false) {
                results.tooltips = this.initTooltips(
                    options.tooltipsConfig || {},
                    options.tooltipsSelector || '[data-toggle="tooltip"]'
                );
            }

            return results;

        } catch (error) {
            console.error('Error al inicializar componentes:', error);
            return results;
        }
    },

    /**
     * Destruir todos los componentes UI
     */
    destroyAll: function () {
        this.destroySelect2();
        this.destroyTooltips();
    }
};

// ============================================
// Inicialización automática al cargar el DOM
// ============================================
$(document).ready(function () {
    // Inicializar automáticamente todos los componentes
    ComponentUtils.initAll();
});

// También reinicializar después de que AdminLTE termine de cargar
$(window).on('load', function () {
    // Pequeño delay para asegurar que AdminLTE haya terminado
    setTimeout(function () {
        ComponentUtils.initAll();
    }, 100);
});
