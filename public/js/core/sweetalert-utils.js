/**
 * SweetAlert2 Utilities
 * Funciones helper para notificaciones Toast y Alerts usando SweetAlert2
 */

/**
 * Escapa caracteres HTML especiales en un string.
 * Usar siempre que se interpole texto dinámico (datasets, respuestas de API)
 * dentro de una opción `html` de SweetAlert2 u otro innerHTML.
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char];
    });
}

// ============================================
// ToastUtils - Utilidades para Toast (notificaciones pequeñas)
// ============================================

const ToastUtils = {
    // Configuración base del toast
    baseConfig: {
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    },

    /**
     * Toast de éxito
     * @param {string} title - Título del toast
     * @param {string} text - Texto adicional (opcional)
     * @param {number} timer - Tiempo en ms (default: 3000)
     * @returns {Promise}
     */
    success: function (title, text = '', timer = 3000) {
        const Toast = Swal.mixin(this.baseConfig);
        return Toast.fire({
            icon: 'success',
            title: title,
            text: text,
            timer: timer
        });
    },

    /**
     * Toast de error
     * @param {string} title - Título del toast
     * @param {string} text - Texto adicional (opcional)
     * @param {number} timer - Tiempo en ms (default: 4000)
     * @returns {Promise}
     */
    error: function (title, text = '', timer = 4000) {
        const Toast = Swal.mixin(this.baseConfig);
        return Toast.fire({
            icon: 'error',
            title: title,
            text: text,
            timer: timer
        });
    },

    /**
     * Toast de advertencia
     * @param {string} title - Título del toast
     * @param {string} text - Texto adicional (opcional)
     * @param {number} timer - Tiempo en ms (default: 4000)
     * @returns {Promise}
     */
    warning: function (title, text = '', timer = 4000) {
        const Toast = Swal.mixin(this.baseConfig);
        return Toast.fire({
            icon: 'warning',
            title: title,
            text: text,
            timer: timer
        });
    },

    /**
     * Toast de información
     * @param {string} title - Título del toast
     * @param {string} text - Texto adicional (opcional)
     * @param {number} timer - Tiempo en ms (default: 3000)
     * @returns {Promise}
     */
    info: function (title, text = '', timer = 3000) {
        const Toast = Swal.mixin(this.baseConfig);
        return Toast.fire({
            icon: 'info',
            title: title,
            text: text,
            timer: timer
        });
    },

    /**
     * Toast de carga (loading)
     * @param {string} title - Título del loading
     * @returns {Promise}
     */
    loading: function (title = 'Cargando...') {
        return Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        }).fire({
            title: title
        });
    },

    /**
     * Toast de carga con tiempo mínimo garantizado
     * Útil para evitar flashes de loading muy rápidos
     * @param {string} mensaje - Mensaje del loading
     * @param {function} accion - Función a ejecutar después del tiempo mínimo
     * @param {number} tiempoMinimo - Tiempo mínimo en ms (default: 1000ms)
     * @returns {Promise}
     */
    loadingWithMinTime: function (mensaje = 'Cargando...', accion, tiempoMinimo = 1000) {
        const loadingToast = this.loading(mensaje);
        const tiempoInicio = Date.now();

        const ejecutarAccion = () => {
            const tiempoTranscurrido = Date.now() - tiempoInicio;

            if (tiempoTranscurrido < tiempoMinimo) {
                setTimeout(() => {
                    accion(loadingToast);
                }, tiempoMinimo - tiempoTranscurrido);
            } else {
                accion(loadingToast);
            }
        };

        setTimeout(ejecutarAccion, 0);
        return loadingToast;
    }
};

// ============================================
// AlertUtils - Utilidades para Alerts (modales completos)
// ============================================

const AlertUtils = {
    // Configuración base para alerts (solo para modales)
    baseConfig: {
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#3085d6'
    },

    /**
     * Alert de éxito (Delegado a ToastUtils)
     * @param {string} title
     * @param {string} text
     * @param {number} timer
     */
    success: function (title, text = '', timer = 3000) {
        return ToastUtils.success(title, text, timer);
    },

    /**
     * Alert de error (Delegado a ToastUtils)
     * @param {string} title
     * @param {string} text
     */
    error: function (title, text = '') {
        return ToastUtils.error(title, text);
    },

    /**
     * Alert de advertencia (Delegado a ToastUtils)
     * @param {string} title
     * @param {string} text
     */
    warning: function (title, text = '') {
        return ToastUtils.warning(title, text);
    },

    /**
     * Alert de información (Delegado a ToastUtils)
     * @param {string} title
     * @param {string} text
     */
    info: function (title, text = '') {
        return ToastUtils.info(title, text);
    },

    /**
     * Alert de confirmación
     * @param {string} title - Título del alert
     * @param {string} text - Texto del alert
     * @param {function} onConfirm - Callback si confirma
     * @param {object} options - Opciones adicionales
     * @returns {Promise}
     */
    confirm: function (title, text, onConfirm, options = {}) {
        return Swal.fire({
            title: title,
            text: text,
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#d33',
            cancelButtonColor: options.cancelColor || '#3085d6',
            confirmButtonText: options.confirmText || 'Sí, continuar',
            cancelButtonText: options.cancelText || 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            }
            return result;
        });
    },

    /**
     * Alert de confirmación para eliminar un ítem, con su nombre destacado en negrita.
     * Reemplaza los `Swal.fire(...)` en línea que se repetían en cada vista de eliminación.
     * @param {string} itemLabel - Tipo de ítem en minúscula, ej. "usuario", "producto"
     * @param {string} itemName - Nombre del ítem a mostrar (se escapa automáticamente)
     * @param {function} onConfirm - Callback si confirma
     * @param {object} options - title, article ("a"/"al"), warningText, extraHtml, confirmText, cancelText, confirmColor, cancelColor
     * @returns {Promise}
     */
    confirmDeleteItem: function (itemLabel, itemName, onConfirm, options = {}) {
        const article = options.article || 'a';
        const warningText = options.warningText || 'Esta acción no se puede deshacer.';
        const html = options.html
            || ('Se eliminará permanentemente ' + article + ' <strong>' + escapeHtml(itemName) + '</strong>.<br>' + warningText + (options.extraHtml || ''));

        return Swal.fire({
            title: options.title || ('¿Eliminar ' + itemLabel + '?'),
            html: html,
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#d33',
            cancelButtonColor: options.cancelColor || '#6c757d',
            confirmButtonText: options.confirmText || 'Sí, eliminar',
            cancelButtonText: options.cancelText || 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            }
            return result;
        });
    },

    /**
     * Alert informativo cuando un ítem no puede eliminarse por tener registros asociados.
     * @param {string} itemLabel - Tipo de ítem en minúscula, ej. "usuario", "producto"
     * @param {string} itemName - Nombre del ítem (se escapa automáticamente)
     * @param {string} reasonsHtml - Lista `<li>` con los motivos (ya construida por el caller)
     * @param {object} options - title, article, closingText, confirmText, confirmColor
     * @returns {Promise}
     */
    blockedDelete: function (itemLabel, itemName, reasonsHtml, options = {}) {
        const article = options.article || 'al';
        const closingText = options.closingText || 'Elimina primero esos registros antes de continuar.';

        return Swal.fire({
            title: options.title || 'No se puede eliminar',
            html: 'Se detectaron registros asociados ' + article + ' ' + itemLabel + ' <strong>' + escapeHtml(itemName) + '</strong>:'
                + '<ul class="text-left mt-2">' + reasonsHtml + '</ul>' + closingText,
            icon: 'error',
            confirmButtonText: options.confirmText || 'Entendido',
            confirmButtonColor: options.confirmColor || '#3085d6'
        });
    },

    /**
     * Alert de bienvenida personalizado
     * @param {string} userName - Nombre del usuario
     */
    welcome: function (userName) {
        const greeting = this._getGreeting();

        Swal.fire({
            title: `${greeting}, ${userName}!`,
            html: `<p>Es un placer verte de nuevo</p>
                   <p class="text-muted mb-0"><small>${new Date().toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })}</small></p>`,
            icon: 'success',
            timer: 5000,
            timerProgressBar: true,
            showConfirmButton: false,
            position: 'center',
            backdrop: true,
            customClass: {
                popup: 'animated fadeInDown'
            }
        });
    },

    /**
     * Obtiene el saludo según la hora del día
     * @private
     * @returns {string}
     */
    _getGreeting: function () {
        const hour = new Date().getHours();

        if (hour >= 5 && hour < 12) {
            return 'Buenos días';
        } else if (hour >= 12 && hour < 19) {
            return 'Buenas tardes';
        } else {
            return 'Buenas noches';
        }
    }
};
