<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
        System Ventas
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; <?= $Año; ?> <a href="https://adminlte.io">AdminLTE.io</a></strong> | Todos los derechos reservados
</footer>

<!-- Control sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
    <div class="p-3 control-sidebar-content">
        <h5><i class="fas fa-cogs mr-2"></i>Personalizar Sistema</h5>
        <hr class="mb-2">

        <!-- Configuración del Sistema -->
        <div class="mb-3">
            <h6><i class="fas fa-palette mr-1"></i>Tema General</h6>
            <div class="mb-2"><input type="checkbox" id="toggle-dark-mode" value="1" class="mr-1"><span style="cursor: pointer;">Modo Oscuro</span></div>
            <div class="mb-2"><input type="checkbox" id="layout-boxed" value="1" class="mr-1"><span style="cursor: pointer;">Diseño Encajonado</span></div>
        </div>
        <div class="mb-3">
            <h6><i class="fas fa-window-maximize mr-1"></i>Opciones de Cabecera</h6>
            <div class="mb-1"><input type="checkbox" id="header-fixed" value="1" class="mr-1"><span style="cursor: pointer;">Fijo</span></div>
            <div class="mb-1"><input type="checkbox" id="dropdown-legacy-offset" value="1" class="mr-1"><span style="cursor: pointer;">Offset Legacy</span></div>
            <div class="mb-1"><input type="checkbox" id="no-border" value="1" class="mr-1"><span style="cursor: pointer;">Sin Bordes</span></div>
        </div>
        <div class="mb-3">
            <h6><i class="fas fa-bars mr-1"></i>Opciones de Menú Lateral</h6>
            <div class="mb-1"><input type="checkbox" id="sidebar-collapsed" value="1" class="mr-1"><span style="cursor: pointer;">Contraído</span></div>
            <div class="mb-1"><input type="checkbox" id="sidebar-fixed" value="1" class="mr-1"><span style="cursor: pointer;">Fijo</span></div>
            <div class="mb-1"><input type="checkbox" id="sidebar-mini" value="1" checked="checked" class="mr-1"><span style="cursor: pointer;">Mini</span></div>
            <div class="mb-1"><input type="checkbox" id="sidebar-mini-md" value="1" class="mr-1"><span style="cursor: pointer;">Mini MD</span></div>
            <div class="mb-1"><input type="checkbox" id="sidebar-mini-xs" value="1" class="mr-1"><span style="cursor: pointer;">Mini XS</span></div>
            <div class="mb-1"><input type="checkbox" id="nav-flat-style" value="1" class="mr-1"><span style="cursor: pointer;">Estilo Plano</span></div>
            <div class="mb-1"><input type="checkbox" id="nav-legacy-style" value="1" class="mr-1"><span style="cursor: pointer;">Estilo Legacy</span></div>
            <div class="mb-1"><input type="checkbox" id="nav-compact" value="1" class="mr-1"><span style="cursor: pointer;">Compacto</span></div>
            <div class="mb-1"><input type="checkbox" id="nav-child-indent" value="1" class="mr-1"><span style="cursor: pointer;">Sangría Hijos</span></div>
            <div class="mb-1"><input type="checkbox" id="nav-child-hide-on-collapse" value="1" class="mr-1"><span style="cursor: pointer;">Ocultar al Contraer</span></div>
            <div class="mb-1"><input type="checkbox" id="disable-hover-focus-expand" value="1" class="mr-1"><span style="cursor: pointer;">Desactivar Auto-Expandir</span></div>
        </div>
        <div class="mb-3">
            <h6><i class="fas fa-shoe-prints mr-1"></i>Opciones de Pie de Página</h6>
            <div class="mb-1"><input type="checkbox" id="footer-fixed" value="1" class="mr-1"><span style="cursor: pointer;">Fijo</span></div>
        </div>

        <div class="mb-3">
            <h6><i class="fas fa-text-height mr-1"></i>Opciones de Texto Pequeño</h6>
            <div class="mb-1"><input type="checkbox" id="text-sm-body" value="1" class="mr-1"><span style="cursor: pointer;">Cuerpo</span></div>
            <div class="mb-1"><input type="checkbox" id="text-sm-navbar" value="1" class="mr-1"><span style="cursor: pointer;">Barra de Navegación</span></div>
            <div class="mb-1"><input type="checkbox" id="text-sm-brand" value="1" class="mr-1"><span style="cursor: pointer;">Marca</span></div>
            <div class="mb-1"><input type="checkbox" id="text-sm-sidebar" value="1" class="mr-1"><span style="cursor: pointer;">Menú Lateral</span></div>
            <div class="mb-1"><input type="checkbox" id="text-sm-footer" value="1" class="mr-1"><span style="cursor: pointer;">Pie de Página</span></div>
        </div>
        <div class="mb-3">
            <h6><i class="fas fa-brush mr-1"></i>Variantes de Barra de Navegación</h6>
            <select id="navbar-variant-select" class="custom-select mb-3 text-light border-0 bg-white">
                <option value="">Ninguna seleccionada</option>
                <option value="primary" class="bg-primary">Primary</option>
                <option value="secondary" class="bg-secondary">Secondary</option>
                <option value="info" class="bg-info">Info</option>
                <option value="success" class="bg-success">Success</option>
                <option value="danger" class="bg-danger">Danger</option>
                <option value="indigo" class="bg-indigo">Indigo</option>
                <option value="purple" class="bg-purple">Purple</option>
                <option value="pink" class="bg-pink">Pink</option>
                <option value="navy" class="bg-navy">Navy</option>
                <option value="lightblue" class="bg-lightblue">Lightblue</option>
                <option value="teal" class="bg-teal">Teal</option>
                <option value="cyan" class="bg-cyan">Cyan</option>
                <option value="dark" class="bg-dark">Dark</option>
                <option value="gray-dark" class="bg-gray-dark">Gray dark</option>
                <option value="gray" class="bg-gray">Gray</option>
                <option value="light" class="bg-light">Light</option>
                <option value="warning" class="bg-warning">Warning</option>
                <option value="white" class="bg-white">White</option>
                <option value="orange" class="bg-orange">Orange</option>
            </select>
        </div>
        <div class="mb-3">
            <h6><i class="fas fa-paint-brush mr-1"></i>Variantes de Color de Acento</h6>
            <select id="accent-variant-select" class="custom-select mb-3 border-0">
                <option value="">Ninguna seleccionada</option>
                <option value="primary" class="bg-primary">Primary</option>
                <option value="warning" class="bg-warning">Warning</option>
                <option value="info" class="bg-info">Info</option>
                <option value="danger" class="bg-danger">Danger</option>
                <option value="success" class="bg-success">Success</option>
                <option value="indigo" class="bg-indigo">Indigo</option>
                <option value="lightblue" class="bg-lightblue">Lightblue</option>
                <option value="navy" class="bg-navy">Navy</option>
                <option value="purple" class="bg-purple">Purple</option>
                <option value="fuchsia" class="bg-fuchsia">Fuchsia</option>
                <option value="pink" class="bg-pink">Pink</option>
                <option value="maroon" class="bg-maroon">Maroon</option>
                <option value="orange" class="bg-orange">Orange</option>
                <option value="lime" class="bg-lime">Lime</option>
                <option value="teal" class="bg-teal">Teal</option>
                <option value="olive" class="bg-olive">Olive</option>
            </select>
        </div>

        <div class="mb-3">
            <h6><i class="fas fa-moon mr-1"></i>Variantes Sidebar Oscuro</h6>
            <select id="dark-sidebar-variant-select" class="custom-select mb-3 text-light border-0 bg-primary">
                <option value="">Ninguna seleccionada</option>
                <option value="primary" class="bg-primary">Primary</option>
                <option value="warning" class="bg-warning">Warning</option>
                <option value="info" class="bg-info">Info</option>
                <option value="danger" class="bg-danger">Danger</option>
                <option value="success" class="bg-success">Success</option>
                <option value="indigo" class="bg-indigo">Indigo</option>
                <option value="lightblue" class="bg-lightblue">Lightblue</option>
                <option value="navy" class="bg-navy">Navy</option>
                <option value="purple" class="bg-purple">Purple</option>
                <option value="fuchsia" class="bg-fuchsia">Fuchsia</option>
                <option value="pink" class="bg-pink">Pink</option>
                <option value="maroon" class="bg-maroon">Maroon</option>
                <option value="orange" class="bg-orange">Orange</option>
                <option value="lime" class="bg-lime">Lime</option>
                <option value="teal" class="bg-teal">Teal</option>
                <option value="olive" class="bg-olive">Olive</option>
            </select>
        </div>

        <div class="mb-3">
            <h6><i class="fas fa-lightbulb mr-1"></i>Variantes Sidebar Claro</h6>
            <select id="light-sidebar-variant-select" class="custom-select mb-3 border-0">
                <option value="">Ninguna seleccionada</option>
                <option value="primary" class="bg-primary">Primary</option>
                <option value="warning" class="bg-warning">Warning</option>
                <option value="info" class="bg-info">Info</option>
                <option value="danger" class="bg-danger">Danger</option>
                <option value="success" class="bg-success">Success</option>
                <option value="indigo" class="bg-indigo">Indigo</option>
                <option value="lightblue" class="bg-lightblue">Lightblue</option>
                <option value="navy" class="bg-navy">Navy</option>
                <option value="purple" class="bg-purple">Purple</option>
                <option value="fuchsia" class="bg-fuchsia">Fuchsia</option>
                <option value="pink" class="bg-pink">Pink</option>
                <option value="maroon" class="bg-maroon">Maroon</option>
                <option value="orange" class="bg-orange">Orange</option>
                <option value="lime" class="bg-lime">Lime</option>
                <option value="teal" class="bg-teal">Teal</option>
                <option value="olive" class="bg-olive">Olive</option>
            </select>
        </div>

        <div class="mb-3">
            <h6><i class="fas fa-tags mr-1"></i>Variantes del Logo</h6>
            <select id="logo-variant-select" class="custom-select mb-3 border-0">
                <option value="">Ninguna seleccionada</option>
                <option value="primary" class="bg-primary">Primary</option>
                <option value="secondary" class="bg-secondary">Secondary</option>
                <option value="info" class="bg-info">Info</option>
                <option value="success" class="bg-success">Success</option>
                <option value="danger" class="bg-danger">Danger</option>
                <option value="indigo" class="bg-indigo">Indigo</option>
                <option value="purple" class="bg-purple">Purple</option>
                <option value="pink" class="bg-pink">Pink</option>
                <option value="navy" class="bg-navy">Navy</option>
                <option value="lightblue" class="bg-lightblue">Lightblue</option>
                <option value="teal" class="bg-teal">Teal</option>
                <option value="cyan" class="bg-cyan">Cyan</option>
                <option value="dark" class="bg-dark">Dark</option>
                <option value="gray-dark" class="bg-gray-dark">Gray dark</option>
                <option value="gray" class="bg-gray">Gray</option>
                <option value="light" class="bg-light">Light</option>
                <option value="warning" class="bg-warning">Warning</option>
                <option value="white" class="bg-white">White</option>
                <option value="orange" class="bg-orange">Orange</option>
            </select>
        </div>

        <!-- Botones de Control -->
        <div class="mb-3 text-center">
            <button type="button" class="btn btn-outline-warning btn-sm mr-2" id="reset-settings">
                <i class="fas fa-undo mr-1"></i>Restablecer
            </button>
            <button type="button" class="btn btn-outline-info btn-sm" id="save-settings">
                <i class="fas fa-save mr-1"></i>Guardar
            </button>
        </div>

        <div class="text-center">
            <small class="text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Los cambios se aplican automáticamente
            </small>
        </div>
    </div>
</aside>
<div id="sidebar-overlay"></div>
</div>
<!-- ./wrapper -->

<!-- Estilos CSS adicionales para el control-sidebar personalizado -->
<style>
    /* Configuración del control-sidebar para scroll correcto */
    .control-sidebar.control-sidebar-dark {
        position: fixed;
        top: 0;
        bottom: 0;
        right: -250px;
        width: 250px;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        transition: right 0.3s ease-in-out;
        z-index: 1031;
    }

    /* Cuando está abierto */
    .control-sidebar-slide-open .control-sidebar.control-sidebar-dark {
        right: 0;
    }

    /* Mejoras específicas para el scrollbar del control-sidebar */
    .control-sidebar.control-sidebar-dark::-webkit-scrollbar {
        width: 6px;
    }

    .control-sidebar.control-sidebar-dark::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .control-sidebar.control-sidebar-dark::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .control-sidebar.control-sidebar-dark::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Contenido del control-sidebar con padding adecuado */
    .control-sidebar .control-sidebar-content {
        min-height: calc(100vh - 20px);
        padding: 15px;
        box-sizing: border-box;
    }

    /* Estilos específicos para headers dentro del control-sidebar */
    .control-sidebar .control-sidebar-content h5 {
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 15px;
        font-size: 1.1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        padding-bottom: 10px;
    }

    .control-sidebar .control-sidebar-content h6 {
        color: #ffc107;
        font-weight: 600;
        margin-bottom: 8px;
        margin-top: 5px;
        font-size: 0.875rem;
    }

    /* Estilos específicos para botones dentro del control-sidebar */
    .control-sidebar .control-sidebar-content .btn {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        border-width: 1px;
        font-size: 0.8rem;
    }

    /* Espaciado para las secciones */
    .control-sidebar .control-sidebar-content .mb-3 {
        margin-bottom: 1.5rem;
    }

    /* Estilo para los select dentro del control-sidebar */
    .control-sidebar .control-sidebar-content .custom-select {
        font-size: 0.85rem;
        padding: 0.375rem 0.75rem;
    }

    /* Estilo para checkboxes */
    .control-sidebar .control-sidebar-content input[type="checkbox"] {
        margin-right: 8px;
    }

    .control-sidebar .control-sidebar-content span {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.9);
    }
</style>

<!-- REQUIRED SCRIPTS -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function handleCheckboxChange(checkboxId, className) {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                const label = checkbox.closest('label') || checkbox.parentElement;
                if (label) {
                    label.classList.add('clickable-label');
                    label.addEventListener('click', function(event) {
                        if (event.target !== checkbox) {
                            event.preventDefault();
                            checkbox.checked = !checkbox.checked;
                            toggleMultipleClasses(className, checkbox.checked);
                            saveSettings();
                        }
                    });
                }
                checkbox.addEventListener('change', function() {
                    toggleMultipleClasses(className, this.checked);
                    saveSettings();
                });
            }
        }

        // Función para manejar múltiples clases
        function toggleMultipleClasses(classNames, isChecked) {
            const classes = classNames.split(' ');
            classes.forEach(className => {
                if (className.trim()) {
                    if (isChecked) {
                        document.body.classList.add(className.trim());
                    } else {
                        document.body.classList.remove(className.trim());
                    }
                }
            });
        }

        // Función para manejar cambios en los selects
        function handleSelectChange(selectId, targetElement, classPrefix) {
            const select = document.getElementById(selectId);
            if (select) {
                select.addEventListener('change', function() {
                    const target = document.querySelector(targetElement);
                    if (target) {
                        // Remover todas las clases que comienzan con el prefijo
                        target.className = target.className.split(' ')
                            .filter(c => !c.startsWith(classPrefix))
                            .join(' ');

                        if (this.value) {
                            // Agregar la nueva clase de variante
                            target.classList.add(`${classPrefix}${this.value}`);

                            // Manejar clases específicas para diferentes elementos
                            handleSpecificClasses(classPrefix, this.value, target);
                        } else {
                            // Si se selecciona "None selected", volver al estilo por defecto
                            handleDefaultStyles(classPrefix, target);
                        }

                        // Asegurarse de que las clases importantes permanezcan
                        ensureImportantClasses(classPrefix, target);
                    }
                    saveSettings();
                });
            }
        }

        // Funciones auxiliares para manejar clases específicas y estilos por defecto
        function handleSpecificClasses(classPrefix, value, target) {
            if (classPrefix === 'navbar-') {
                handleNavbarClasses(value, target);
            } else if (classPrefix === 'sidebar-dark-' || classPrefix === 'sidebar-light-') {
                handleSidebarClasses(value, target, classPrefix);
            } else if (classPrefix === 'bg-') {
                handleBrandLogoClasses(value, target);
            }
        }

        function handleDefaultStyles(classPrefix, target) {
            if (classPrefix === 'navbar-') {
                target.classList.add('navbar-white', 'navbar-light');
                target.classList.remove('navbar-dark');
            } else if (classPrefix === 'sidebar-dark-' || classPrefix === 'sidebar-light-') {
                target.classList.add('sidebar-dark-primary');
                target.classList.remove('sidebar-light-primary');
            } else if (classPrefix === 'bg-') {
                target.classList.remove('navbar-light', 'navbar-dark');
                target.className = 'brand-link';
            }
        }

        function ensureImportantClasses(classPrefix, target) {
            if (classPrefix === 'navbar-') {
                target.classList.add('main-header', 'navbar', 'navbar-expand');
            } else if (classPrefix === 'sidebar-dark-' || classPrefix === 'sidebar-light-') {
                target.classList.add('main-sidebar', 'elevation-4');
            } else if (classPrefix === 'bg-') {
                target.classList.add('brand-link');
            }
        }

        // Funciones específicas para manejar clases de navbar, sidebar y brand logo
        function handleNavbarClasses(value, target) {
            if (['white', 'light'].includes(value)) {
                target.classList.add('navbar-light');
                target.classList.remove('navbar-dark');
            } else {
                target.classList.add('navbar-dark');
                target.classList.remove('navbar-light');
            }
        }

        function handleSidebarClasses(value, target, classPrefix) {
            target.classList.remove('sidebar-dark-primary', 'sidebar-light-primary');
            if (value === 'light') {
                target.classList.remove('sidebar-dark-primary');
                target.classList.add('sidebar-light-primary');
            } else {
                target.classList.remove('sidebar-light-primary');
                target.classList.add(`sidebar-dark-${value}`);
            }
        }

        function handleBrandLogoClasses(value, target) {
            target.classList.remove('navbar-light', 'navbar-dark');
            if (['light', 'white', 'warning'].includes(value)) {
                target.classList.add('navbar-light');
            } else {
                target.classList.add('navbar-dark');
            }
        }

        // Función para manejar cambios en el accent
        function handleAccentVariantChange(selectId) {
            const select = document.getElementById(selectId);
            if (select) {
                select.addEventListener('change', function() {
                    const body = document.body;
                    body.className = body.className.replace(/accent-\S+/g, '');
                    if (this.value) {
                        body.classList.add(`accent-${this.value}`);
                    }
                    saveSettings();
                });
            }
        }

        // Función para guardar configuraciones
        function saveSettings() {
            const settings = {};
            document.querySelectorAll('.control-sidebar input[type="checkbox"]').forEach(checkbox => {
                settings[checkbox.id] = checkbox.checked;
            });
            document.querySelectorAll('.control-sidebar select').forEach(select => {
                settings[select.id] = select.value;
            });
            localStorage.setItem('controlSidebarSettings', JSON.stringify(settings));
        }

        // Función para cargar configuraciones
        function loadSettings() {
            const settings = JSON.parse(localStorage.getItem('controlSidebarSettings'));
            if (settings) {
                Object.keys(settings).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = settings[key];
                            element.dispatchEvent(new Event('change'));
                        } else if (element.tagName === 'SELECT') {
                            element.value = settings[key];
                            element.dispatchEvent(new Event('change'));
                        }
                    }
                });
            }
        }

        // Aplicar los manejadores de eventos
        handleCheckboxChange('toggle-dark-mode', 'dark-mode');
        handleCheckboxChange('layout-boxed', 'layout-boxed');
        handleCheckboxChange('header-fixed', 'layout-navbar-fixed');
        handleCheckboxChange('dropdown-legacy-offset', 'layout-navbar-not-fixed');
        handleCheckboxChange('no-border', 'layout-navbar-border');
        handleCheckboxChange('sidebar-collapsed', 'sidebar-collapse');
        handleCheckboxChange('sidebar-fixed', 'layout-fixed');
        handleCheckboxChange('sidebar-mini', 'sidebar-mini');
        handleCheckboxChange('sidebar-mini-md', 'sidebar-mini-md sidebar-mini');
        handleCheckboxChange('sidebar-mini-xs', 'sidebar-mini-xs sidebar-mini');
        handleCheckboxChange('nav-flat-style', 'nav-flat');
        handleCheckboxChange('nav-legacy-style', 'nav-legacy');
        handleCheckboxChange('nav-compact', 'nav-compact');
        handleCheckboxChange('nav-child-indent', 'nav-child-indent');
        handleCheckboxChange('nav-child-hide-on-collapse', 'nav-child-indent-legacy');
        handleCheckboxChange('disable-hover-focus-expand', 'sidebar-no-expand');
        handleCheckboxChange('footer-fixed', 'layout-footer-fixed');
        handleCheckboxChange('text-sm-body', 'text-sm');
        handleCheckboxChange('text-sm-navbar', 'text-sm');
        handleCheckboxChange('text-sm-brand', 'text-sm');
        handleCheckboxChange('text-sm-sidebar', 'text-sm');
        handleCheckboxChange('text-sm-footer', 'text-sm');

        handleSelectChange('navbar-variant-select', '.main-header', 'navbar-');
        handleAccentVariantChange('accent-variant-select');
        handleSelectChange('dark-sidebar-variant-select', '.main-sidebar', 'sidebar-dark-');
        handleSelectChange('light-sidebar-variant-select', '.main-sidebar', 'sidebar-light-');
        handleSelectChange('logo-variant-select', '.brand-link', 'bg-');

        // Clear button functionality
        const clearButton = document.getElementById('clear-selection');
        if (clearButton) {
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                const selects = document.querySelectorAll('.control-sidebar select');
                selects.forEach(select => {
                    select.value = '';
                    select.dispatchEvent(new Event('change'));
                });

                // Restaurar clases por defecto
                document.querySelector('.main-header').className = 'main-header navbar navbar-expand navbar-white navbar-light';
                document.querySelector('.main-sidebar').className = 'main-sidebar sidebar-dark-primary elevation-4';
                document.querySelector('.brand-link').className = 'brand-link';
                document.body.className = document.body.className.replace(/accent-\S+/g, '');

                // Desmarcar todos los checkboxes
                document.querySelectorAll('.control-sidebar input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change'));
                });

                saveSettings();
            });
        }

        // Función para manejar la apertura y cierre del control-sidebar
        function setupControlSidebar() {
            const body = document.body;
            const controlSidebar = document.querySelector('.control-sidebar');
            const toggleButton = document.querySelector('[data-widget="control-sidebar"]');

            // Función para abrir el control-sidebar
            function openControlSidebar() {
                body.classList.add('control-sidebar-slide-open');
                controlSidebar.style.display = 'block';
            }

            // Función para cerrar el control-sidebar
            function closeControlSidebar() {
                body.classList.remove('control-sidebar-slide-open');
                setTimeout(() => {
                    controlSidebar.style.display = 'none';
                }, 300); // Ajusta este tiempo según la duración de tu animación CSS
            }

            // Event listener para el botón de toggle
            if (toggleButton) {
                toggleButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (body.classList.contains('control-sidebar-slide-open')) {
                        closeControlSidebar();
                    } else {
                        openControlSidebar();
                    }
                });
            }

            // Event listener para cerrar al hacer clic fuera
            document.addEventListener('click', function(event) {
                if (controlSidebar &&
                    !controlSidebar.contains(event.target) &&
                    event.target !== toggleButton &&
                    body.classList.contains('control-sidebar-slide-open')) {
                    closeControlSidebar();
                }
            });

            // Prevenir que los clics dentro del control-sidebar lo cierren
            if (controlSidebar) {
                controlSidebar.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            }
        }

        // Llamar a la función de configuración cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', setupControlSidebar);

        // Funcionalidad para botones de control
        function setupControlButtons() {
            const resetButton = document.getElementById('reset-settings');
            const saveButton = document.getElementById('save-settings');

            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    if (confirm('¿Estás seguro de que deseas restablecer todas las configuraciones?')) {
                        // Limpiar localStorage
                        localStorage.clear();

                        // Restablecer checkboxes
                        document.querySelectorAll('.control-sidebar input[type="checkbox"]').forEach(checkbox => {
                            checkbox.checked = checkbox.id === 'sidebar-mini' ? true : false;
                        });

                        // Restablecer selects
                        document.querySelectorAll('.control-sidebar select').forEach(select => {
                            select.selectedIndex = 0;
                        });

                        // Recargar configuraciones por defecto
                        location.reload();
                    }
                });
            }

            if (saveButton) {
                saveButton.addEventListener('click', function() {
                    // Mostrar mensaje de confirmación
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check mr-1"></i>Guardado';
                    this.classList.remove('btn-outline-info');
                    this.classList.add('btn-success');

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-info');
                    }, 2000);
                });
            }
        }

        // Configurar botones de control
        setupControlButtons();

        // Cargar configuraciones guardadas
        loadSettings();
    });
</script>

<!-- Bootstrap 4 -->
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/jszip/jszip.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/pdfmake/pdfmake.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/pdfmake/vfs_fonts.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?= $URL; ?>/public/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

</body>

</html>