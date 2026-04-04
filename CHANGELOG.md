# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/)
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

---

## [1.2.5] - 2026-04-04

### Refactorizado

- `views/sales/index.php` — eliminado bloque `<script>` inline completo (DataTable init básico sin exportOptions)
- `views/sales/create.php` — eliminado bloque `<script>` inline completo (~100 líneas: DataTables de modales, lógica de
  carrito, selección producto/cliente, cálculo de cambio, validación pre-submit)
- `app/Controllers/SaleController.php` — `index()` agrega `pageScripts` con `sales-index.js`; `create()` agrega
  `pageScripts` con `sales-create.js`

### Agregado

- `public/js/modules/sales/sales-index.js` — DataTable estandarizado: `exportOptions: { columns: [0,1,2,3,4] }` en
  todos los botones para excluir columna Acciones (índice 5); PDF con título/subtítulo/fecha/footer paginado;
  Excel con `messageTop`/`messageBottom`; `pageLength: 10`; `lengthMenu: [[5,10,25,50]]`; botón ColVis = `'Columnas'`
- `public/js/modules/sales/sales-create.js` — JS del POS extraído: DataTables de modales `#productTable` /
  `#clientTable` (sin botones de exportación); selección de producto y cliente; agregar al carrito; cálculo de cambio;
  validación pre-submit; `Swal.fire()` directo reemplazado por `AlertUtils.warning()` en 2 lugares

### Notas de Versión

- **Módulo sales alineado al estándar de modularización JS:** replica el patrón del módulo `users` (v1.2.3) y
  `purchases` (v1.2.4) — todo el JS en archivos separados bajo `public/js/modules/sales/`, sin lógica inline en vistas.
- **Sin `confirmarEliminar` en sales-index.js:** las ventas usan página de confirmación dedicada (
  `views/sales/delete.php`);
  el modelo `Sale` no implementa `isReferenced()`, por lo que no aplica el patrón AJAX check previo.

---

## [1.2.4] - 2026-04-04

### Refactorizado

- `views/purchases/index.php` — eliminado bloque `<script>` inline completo (DataTable init + `confirmarEliminar` con
  `Swal.fire()` directo); el form oculto `#formEliminar` se mantiene intacto
- `views/purchases/create.php` — agregado `id="purchaseCreateForm"` al `<form>` para que jQuery Validate lo tome como
  selector
- `views/purchases/edit.php` — agregado `id="purchaseEditForm"` al `<form>`
- `app/Controllers/PurchaseController.php` — `index()` agrega `pageScripts` con `purchases-index.js`; `create()` y
  `edit()` agregan `pageScripts` con sus respectivos JS y pasan `['validation']` como cuarto argumento a
  `renderWithLayout()`

### Agregado

- `public/js/modules/purchases/purchases-index.js` — DataTable estandarizado (exportOptions excluye columna Acciones,
  índice 7; PDF con título/subtítulo/fecha/footer paginado; Excel con messageTop/Bottom; botón ColVis = `'Columnas'`);
  `confirmarEliminar(id, idProducto, cantidad, nombre)` reemplaza `Swal.fire()` directo por `AlertUtils.confirm()` +
  `ToastUtils.loadingWithMinTime()` + `form.submit()` (sin pre-check AJAX — purchases no tiene `isReferenced()`)
- `public/js/modules/purchases/purchases-create.js` — jQuery Validate para `#purchaseCreateForm`; reglas:
  `fecha_compra` (required), `comprobante` (required, minlength:3), `id_producto`/`id_proveedor` (required),
  `precio_compra` (number, min:0.01), `cantidad` (digits, min:1); `errorPlacement` especial para selectores con wrapper
  `.d-flex` (botón "+" adyacente); `submitHandler` con `ToastUtils.loadingWithMinTime('Guardando compra...')`
- `public/js/modules/purchases/purchases-edit.js` — mismo patrón que `purchases-create.js`; `submitHandler` con
  `ToastUtils.loadingWithMinTime('Actualizando compra...')`

### Notas de Versión

- **Flujo de eliminación de compras:** La confirmación pasa de `Swal.fire()` directo a `AlertUtils.confirm()`. No hay
  pre-check AJAX (`check()` / `isReferenced()`) porque `tb_compras` no es referenciada por otras tablas; el flujo es:
  `AlertUtils.confirm` → asignar hidden fields → `ToastUtils.loadingWithMinTime` → `form.submit()`.
- **Patrón JS modularizado:** purchases se alinea al estándar del módulo users — todo el JS en archivos separados bajo
  `public/js/modules/purchases/`, sin lógica inline en vistas.

---

## [1.2.3] - 2026-04-04

### Cambiado

- `public/js/modules/users/users-index.js` — DataTable actualizado al estándar del proyecto:
  `exportOptions: { columns: [0,1,2,3] }` en todos los botones de exportación para excluir la columna Acciones; PDF con
  título, subtítulo, fecha de generación y footer paginado; Excel con `messageTop`/`messageBottom`; Print con estilo de
  tabla; botón ColVis renombrado a `'Columnas'`; limpieza de estilo (sin comillas en claves, camelCase)
- `public/js/modules/products/products-index.js` — mismas mejoras de DataTable;
  `exportOptions: { columns: [0,2,3,4,5,6] }` excluye además la columna Imagen (índice 1) además de Acciones (índice 7)

---

## [1.2.2] - 2026-04-04

### Cambiado

- `app/Models/Supplier.php` — agregado `nameExists(string $empresa, ?int $excludeId = null): bool` para validación de
  unicidad de empresa (excluye el registro actual al editar)
- `app/Controllers/SupplierController.php` — refactorizado a AJAX puro: `index()` renderiza la página con
  `['datatable', 'validation']`; `store()`, `show()`, `update()`, `destroy()` responden JSON; `checkNombre()` endpoint
  para jQuery Validate `remote`; eliminados `create()` y `edit()` como páginas separadas
- `routes/web.php` — rutas de proveedores actualizadas: `POST /suppliers/store`, `GET /suppliers/show/{id}`,
  `POST /suppliers/update/{id}`, `POST /suppliers/check-nombre`; eliminadas rutas de `create` y `edit` como páginas
- `views/suppliers/index.php` — reescrito con patrón modal + AJAX: DataTable `#supplierTable` + `#modalCreate` (
  bg-primary, modal-lg) + `#modalEdit` (bg-success, modal-lg); botones `btn-edit` y `btn-delete` con `data-id`/
  `data-nombre`; eliminados formulario oculto `#formEliminar` y script inline
- `views/layouts/partials/_sidebar.php` — enlace de proveedores simplificado a link directo; eliminado treeview con
  sub-ítems "Lista de proveedores" / "Crear proveedor"

### Agregado

- `public/js/modules/suppliers/suppliers-datatable.js` — inicialización DataTable con botones de exportación (Copy, PDF
  personalizado, Excel, CSV, Imprimir), ColVis, anti-FOUC y `exportOptions` que excluye la columna Acciones
- `public/js/modules/suppliers/suppliers-modals.js` — jQuery Validate con regla `remote` en `empresa` para ambos
  formularios; `crearProveedor()` / `actualizarProveedor()` con `ToastUtils.loadingWithMinTime()`; carga de datos para
  modal de edición via AJAX (`GET /suppliers/show/{id}`); eliminación con `AlertUtils.confirm()` + AJAX +
  `isReferenced()` inline; flag `isSubmitting` para prevenir doble submit

### Eliminado

- `views/suppliers/create.php` — reemplazado por `#modalCreate` en `index.php`
- `views/suppliers/edit.php` — reemplazado por `#modalEdit` en `index.php`

### Notas de Versión

- **Flujo de eliminación de proveedores:** Cambiado del patrón formulario oculto + `Swal.fire()` directo a
  `AlertUtils.confirm()` + AJAX inline. El controller verifica `isReferenced()` y retorna JSON — sin página de
  confirmación separada ni ruta `check/{id}`.
- **Validación de unicidad:** Campo `empresa` validado con `remote` en jQuery Validate + `nameExists()` en el backend.
  `nombre_proveedor` no se valida como único (puede repetirse legítimamente entre distintos contactos de distintas
  empresas).
- **Campos opcionales:** `telefono` y `email` se insertan como `NULL` cuando se dejan vacíos; el JS al pre-llenar el
  modal usa `data.telefono || ''` para evitar mostrar "null" en los inputs.

---

## [1.2.1] - 2026-04-03

### Refactorizado

- `views/products/index.php` — JS extraído a `products-index.js`; eliminado wrapper `table-responsive`;
  `confirmarEliminar()` reemplazado por verificación AJAX antes de redirigir a página de confirmación; eliminado
  formulario oculto `#formEliminar` inline
- `views/products/create.php` — JS de validación e imagen-preview extraído a `products-create.js`
- `views/products/edit.php` — JS de validación extraído a `products-edit.js`
- `app/Controllers/ProductController.php` — `create()` y `edit()` ahora pasan `['select2', 'validation']` al sistema de
  assets; `index()` ya no pasa `csrf_token` (innecesario sin formulario inline)
- `views/users/index.php`, `create.php`, `edit.php` — eliminados `<script src>` hardcodeados; scripts servidos vía
  `$pageScripts`
- `app/Controllers/UserController.php` — `index()`, `create()` y `edit()` pasan `pageScripts` con sus respectivos
  módulos JS

### Agregado

- `public/js/modules/products/products-index.js` — DataTable init + `confirmarEliminar(id, nombre)` con verificación
  AJAX vía `GET /products/check/{id}` antes de redirigir a página de confirmación; bloqueo de botones durante la
  verificación
- `public/js/modules/products/products-create.js` — jQuery Validate para formulario de creación con preview de imagen
- `public/js/modules/products/products-edit.js` — jQuery Validate para formulario de edición
- `views/products/delete.php` — página de confirmación de eliminación con datos del producto (código, nombre, categoría,
  imagen) y SweetAlert2 para confirmación final
- `app/Controllers/ProductController.php::check()` — endpoint JSON `GET /products/check/{id}`; responde
  `{ referenced, carrito, compras }`; devuelve 400/404 en entradas inválidas
- `app/Controllers/ProductController.php::delete()` — renderiza la vista de confirmación de eliminación con datos del
  producto y su categoría
- `app/Models/Product.php::getReferenceCount()` — consulta desglosada de referencias (`carrito`, `compras`) para exponer
  conteos individuales al frontend
- `routes/web.php` — `GET /products/check/{id}`, `GET /products/delete/{id}`
- `views/layouts/header.php` — soporte de `$pageStyles` (array de rutas relativas a `BASE_URL`) para inyección de CSS
  específico por vista
- `views/layouts/footer.php` — soporte de `$pageScripts` (array de rutas relativas a `BASE_URL`) para inyección de JS
  específico por vista

### Notas de Versión

- **Flujo de eliminación de productos:** Cambiado del patrón modal-inline a página dedicada con pre-verificación AJAX.
  El botón Eliminar primero consulta `/products/check/{id}`; si el producto no tiene referencias lo redirige a
  `/products/delete/{id}` (página de confirmación); si tiene referencias muestra detalle de los registros bloqueantes
  vía SweetAlert2.
- **Sistema de assets por vista:** `$pageStyles` / `$pageScripts` permiten cargar CSS/JS específicos de módulo sin
  modificar el layout global. Compatible con todos los módulos MVC.

---

## [1.2.0] - 2026-04-03

### Agregado

- `CONTRIBUTING.md` — guía completa para colaboradores: flujo de contribución, convenciones de commit, estructura de
  archivos MVC, checklist de testing
- `LICENSE` — licencia MIT para uso público y open-source
- `.gitignore` — actualizado con reglas para `.claude/` (ignorar config local) y `.mcp.json` (ignorar config local);
  excepción para `.mcp.example.json` (template para equipo)
- `.claudeignore` — configuración de exclusión para contexto de Claude (vendor/, public/templates/, public/uploads/)
- `.mcp.example.json` — plantilla de configuración MCP con servidores GitHub, MySQL y ClickUp (para equipo)
- `.claude/skills/code-review/` — skill automático para revisar código PHP MVC antes de merge (checklist security,
  conventions, logic, git)
- `.claude/skills/git-commit/` — skill automático para commits con Conventional Commits, análisis de diff e inteligencia
  de scope/type

### Cambiado

- `PROMPTS.md` — actualizado con referencias claras a AGENT.md y CLAUDE.md como contexto base; plantillas refactorizadas
  con ejemplos más realistas (reportes, features genéricas); énfasis en "Spec first" approach
- `README.md` — reorganizado con tabla de documentación para desarrolladores (AGENT.md, CLAUDE.md, PROMPTS.md,
  CONTRIBUTING.md); sección de contribuciones mejorada con pasos explícitos; footer actualizado con código de conducta

### Notas de Versión

- **Primera release open-source estable:** Documentación completa, skills de desarrollo, configuración de MCP lista,
  licencia MIT
- **Infraestructura de colaboración:** A partir de v1.2.0, el proyecto está listo para recibir contribuciones externas
- Todo el código MVC es v1.1.8 — esta versión agrega capas de documentación y tooling

---

## [1.1.8] - 2026-04-03

### Cambiado

- `app/Models/Category.php` — método `nameExists(string $name, ?int $excludeId = null): bool` para detección de nombres
  de categoría duplicados (excluye el registro actual en ediciones)
- `app/Controllers/CategoryController.php` — refactorizado a AJAX puro: `index()` renderiza la página; `store()`,
  `show()`, `update()` responden JSON; `checkNombre()` endpoint para jQuery Validate `remote`; eliminados `create()` y
  `edit()` como páginas separadas
- `routes/web.php` — rutas de categorías actualizadas: `POST /categories/store`, `GET /categories/show/{id}`,
  `POST /categories/update/{id}`, `POST /categories/check-nombre`; eliminadas rutas de `create` y `edit` como páginas
- `views/categories/index.php` — reescrito con patrón modal + AJAX: DataTable `#categoryTable` + `#modalCreate` (
  bg-primary) + `#modalEdit` (bg-success); sin páginas separadas de creación y edición
- `views/layouts/partials/_sidebar.php` — enlace de categorías simplificado a link directo; eliminado treeview con
  sub-ítems "Lista" / "Crear"

### Agregado

- `public/js/modules/categories/categories-datatable.js` — inicialización DataTable con botones de exportación (Copy,
  PDF, Excel, CSV, Imprimir), ColVis y anti-FOUC
- `public/js/modules/categories/categories-modals.js` — jQuery Validate con regla `remote` en ambos formularios;
  `crearCategoria()` / `actualizarCategoria()` con `ToastUtils.loadingWithMinTime()`; carga de datos para modal de
  edición via AJAX; flag `isSubmitting` para prevenir doble submit

### Eliminado

- `views/categories/create.php` — reemplazado por `#modalCreate` en `index.php`
- `views/categories/edit.php` — reemplazado por `#modalEdit` en `index.php`

---

## [1.1.7] - 2026-04-02

### Cambiado

- `app/Models/Role.php` — método `nameExists(string $name, ?int $excludeId = null): bool` para detección de nombres
  duplicados (excluye el registro actual en ediciones)
- `app/Controllers/RoleController.php` — refactorizado a AJAX puro: `index()` renderiza la página; `store()`, `show()`,
  `update()` responden JSON; `checkNombre()` endpoint para jQuery Validate `remote`
- `routes/web.php` — rutas de roles actualizadas: `POST /roles/store`, `GET /roles/show/{id}`,
  `POST /roles/update/{id}`, `POST /roles/check-nombre`; eliminadas rutas de `create` y `edit` como páginas
- `views/roles/index.php` — reescrito con patrón modal + AJAX: DataTable `#roleTable` + `#modalCreate` (bg-primary) +
  `#modalEdit` (bg-success); sin páginas separadas de creación y edición
- `views/layouts/partials/_sidebar.php` — enlace de roles simplificado a link directo; eliminado treeview con
  sub-ítems "Lista de roles" / "Crear rol"

### Agregado

- `public/js/modules/roles/roles-datatable.js` — inicialización DataTable con botones de exportación (Copy, PDF
  personalizado, Excel, CSV, Imprimir), colvis y anti-FOUC
- `public/js/modules/roles/roles-modals.js` — jQuery Validate con regla `remote` en ambos formularios; `crearRol()` /
  `actualizarRol()` con `ToastUtils.loadingWithMinTime()`; carga de datos para modal de edición via AJAX; flag
  `isSubmitting` para prevenir doble submit

### Eliminado

- `views/roles/create.php` — reemplazado por `#modalCreate` en `index.php`
- `views/roles/edit.php` — reemplazado por `#modalEdit` en `index.php`

---

## [1.1.6] - 2026-04-02

### Cambiado

- `renderWithLayout()` en `app/Core/Controller.php` — nuevo 4° parámetro `array $assets = []`; los plugins (`datatable`,
  `select2`, `validation`) solo se cargan en las páginas que los declaran explícitamente
- `views/layouts/header.php` — CSS de DataTables y Select2 envueltos en bloques `in_array()` condicionales
- `views/layouts/footer.php` — JS de DataTables (11 archivos), Select2 y jQuery Validate envueltos en bloques
  `in_array()` condicionales
- Controllers actualizados con `$assets` apropiados: `['datatable']` en todos los `index()`; `['select2', 'validation']`
  en `UserController::create()` y `edit()`; `['datatable']` en `SaleController::create()` por las tablas del modal POS

---

## [1.1.5] - 2026-04-01

### Cambiado

- Directorio `views/layout/` renombrado a `views/layouts/` — convención plural consistente con frameworks PHP modernos
- `views/layout/parte1.php` → `views/layouts/header.php`
- `views/layout/parte2.php` → `views/layouts/footer.php`
- `views/layout/mensajes.php` → `views/layouts/messages.php`
- Sidebar extraído de `header.php` a `views/layouts/partials/_sidebar.php` — partial independiente incluido desde
  `header.php`
- `app/Core/Controller.php` — 3 rutas de `require` actualizadas a `views/layouts/`
- `CLAUDE.md` y `AGENT.md` — referencias a archivos de layout actualizadas

### Eliminado

- `views/layout/sesion.php` — código muerto; la sesión se gestiona enteramente via `Auth` + `sessionData()`

---

## [1.1.4] - 2026-04-01

### Agregado

- `UserController::checkEmail()` en `app/Controllers/UserController.php` — endpoint `POST /users/check-email` que
  responde `true` (email libre) o string de error (ya registrado); utilizado por jquery.validate `remote` en create y
  edit
- Ruta `POST /users/check-email` en `routes/web.php` con middleware `auth, admin`
- Validación AJAX de email en tiempo real en `users-create.js` — regla `remote` que consulta `/users/check-email`;
  impide submit si el correo ya existe en el sistema sin necesidad de reload
- Validación AJAX de email en tiempo real en `users-edit.js` — igual que create pero envía `id_usuario` para excluir al
  usuario actual de la comprobación de duplicados
- Card "Vista previa" en sidebar de `views/users/create.php` — muestra nombre, email y rol actualizándose en tiempo real
  mientras el usuario completa el formulario (via listeners JS en `users-create.js`)
- Loading toast "Redirigiendo..." en `users-index.js` — aparece tras confirmar que el usuario no tiene referencias,
  antes de navegar a la vista de eliminación

### Cambiado

- `views/users/create.php` — formulario dividido en dos cards colapsables `card-primary card-outline` ("Datos del
  usuario" y "Credenciales de acceso"); `id="btnCreateUser"` en botón submit para control de spinner
- `views/users/edit.php` — formulario dividido en dos cards colapsables `card-success card-outline` ("Información de la
  cuenta" y "Seguridad"); `id="id_usuario"` agregado al input hidden para que el validador remote lo referencie;
  `id="btnEditUser"` en botón submit
- `users-index.js` — `confirmarEliminar()` ahora usa `ToastUtils.loadingWithMinTime('Verificando usuario...')` durante
  el AJAX y deshabilita todos los botones de eliminar mientras la verificación está en curso

---

## [1.1.3] - 2026-03-31

### Agregado

- `public/js/modules/users/users-create.js` — jQuery Validate para el formulario de creación: nombres (requerido, mín. 3
  chars), email (requerido, formato), rol (requerido), contraseña (requerida, mín. 6 chars), confirmación con `equalTo`;
  toggle de visibilidad de contraseña; spinner en submit con `ToastUtils.loadingWithMinTime()`
- `public/js/modules/users/users-edit.js` — jQuery Validate para el formulario de edición: mismas reglas que create pero
  contraseña opcional (si se llena, mín. 6 chars y repeat debe coincidir); spinner con "Actualizando..."
- `User::isReferenced(int $id)` en `app/Models/User.php` — verifica si el usuario tiene productos (`tb_almacen`) o
  compras (`tb_compras`) asociadas antes de permitir la eliminación
- `User::getReferenceCount(int $id)` en `app/Models/User.php` — devuelve desglose `{productos, compras}` para mostrar en
  el mensaje de error
- `UserController::check()` en `app/Controllers/UserController.php` — endpoint JSON `GET /users/check/{id}` que responde
  `{referenced, productos, compras}`; usado por AJAX antes de redirigir a la página de eliminación
- Ruta `GET /users/check/{id}` en `routes/web.php` con middleware `auth, admin`
- Constante JS `BASE_URL` en `views/layout/parte1.php` — disponible globalmente en todos los módulos JS que necesiten
  construir URLs dinámicas

### Cambiado

- `views/users/index.php` — botón Eliminar ahora hace verificación AJAX (`/users/check/{id}`) antes de redirigir; si el
  usuario tiene referencias muestra alerta de error con detalle de productos/compras; si está libre redirige a
  `/users/delete/{id}`
- `views/users/create.php` — reestructurado a un solo `card-primary`; campos con `input-group` e íconos FontAwesome;
  `autocomplete="off"`; botones toggle de contraseña; carga `users-create.js`
- `views/users/edit.php` — eliminado el bloque `widget-user-2` (header con fondo verde e imagen); reemplazado por
  `card-success` simple con badge de ID (patrón estándar del sistema); un solo card unificado datos+contraseña; carga
  `users-edit.js`
- `views/users/delete.php` — botón "Eliminar usuario" ahora abre SweetAlert2 de confirmación con nombre del usuario
  antes de hacer submit; corregido `$URL` → `BASE_URL` en todos los enlaces y acciones
- `views/users/show.php` — agregado `container-fluid` faltante que causaba desbordamiento del layout

### Corregido

- `views/users/delete.php` — variable `$URL` indefinida reemplazada por constante `BASE_URL`; enlace de inicio apuntaba
  a `/dashboard` en lugar de `/`
- `views/users/index.php` — `json_encode()` en `onclick` generaba comillas dobles dentro de atributo HTML con comillas
  dobles, rompiendo el JS; corregido con `htmlspecialchars(..., ENT_QUOTES)`

---

## [1.1.2] - 2026-03-31

### Agregado

- `public/css/core/ui-components.css` y `public/js/core/sweetalert-utils.js` — utilitarios compartidos con
  `sistema-hielo-cambita`; `sweetalert-utils.js` provee `ToastUtils`, `AlertUtils` y funciones legacy (`showToast()`,
  `confirmDelete()`, etc.); se carga en `<head>` (parte1) para que `showToast()` esté disponible antes de que ejecute
  `mensajes.php`
- `public/js/modules/users/users-index.js` — DataTable y `confirmarEliminar()` del módulo usuarios extraídos de JS
  inline en la vista
- `public/css/modules/auth/login.css` — estilos personalizados del login: gradiente de fondo, card con hover/sombra,
  inputs redondeados, `btn-custom` azul
- `public/js/modules/auth/login.js` — toggle de visibilidad de contraseña, jQuery Validate con mensajes en español,
  spinner de submit con `ToastUtils.loadingWithMinTime()`
- `define('BASE_PATH', dirname(__DIR__))` en `public/index.php` — constante de ruta absoluta para referencias internas
- `$_SESSION['welcome_user']` en `AuthController::store()` — activa el popup de bienvenida al llegar al dashboard tras
  login exitoso

### Cambiado

- `public/index.php` — inicialización bootstrappeada inline (antes delegada a `app/config.php`); agrega `BASE_PATH`
- `views/layout/mensajes.php` — simplificado con `showToast()` de `sweetalert-utils.js`; agrega manejo de
  `$_SESSION['welcome_user']` con `AlertUtils.welcome()`
- `views/layout/parte1.php` — agrega `ui-components.css` y `sweetalert-utils.js` en `<head>`
- `views/layout/parte2.php` — ruta de `control_sidebar.js` actualizada a `js/core/control_sidebar.js`
- `views/auth/login.php` — rediseño con patrón de `sistema-hielo-cambita`: logo local, toggle contraseña, jQuery
  Validate, `btn-custom`, toast via `showToast()`; sin JS inline
- `views/users/index.php` — JS inline extraído a `users-index.js`
- `views/users/create.php` — CSRF al inicio del form; formulario dividido en dos tarjetas ("Información de la cuenta"
  y "Seguridad")

### Eliminado

- `app/config.php` — lógica inlineada en `public/index.php`
- `app/config.example.php` — archivo legacy de credenciales pre-MVC
- `public/js/control_sidebar.js` — movido a `public/js/core/control_sidebar.js`

---

## [1.1.1] - 2026-03-30

### Agregado

- Vistas de error dedicadas en `views/errors/`: `404.php` (headline amarillo), `403.php` (headline rojo + SweetAlert2
  con flash message), `500.php` (headline rojo) — páginas standalone con contenido completamente centrado, sin header ni
  footer
- Ruta `GET /errors/403` en `routes/web.php` como closure sin middleware para servir la página 403

### Corregido

- Redirect 403 de `AdminMiddleware` y `SellerMiddleware` apuntaba a `APP_URL . '/error/error.php'` — URL inválida porque
  `APP_URL` termina en `/public` y el archivo estaba fuera de ese directorio; corregido a `APP_URL . '/errors/403'`
- `Router::dispatch()` ahora incluye `views/errors/404.php` en lugar del archivo legacy `error/error.php`

### Cambiado

- TCPDF gestionado vía Composer (`tecnickcom/tcpdf ^6.7`, instalado como `6.11.2`) en lugar de la copia manual en
  `app/TCPDF-main/`; eliminada la línea `require_once` en `SaleController::invoice()`

### Eliminado

- Directorio `error/` con el archivo legacy `error/error.php`
- Directorio `app/TCPDF-main/` reemplazado por `vendor/tecnickcom/tcpdf/`

---

## [1.1.0] - 2026-03-30

### Agregado

- Composer con PSR-4 autoloading y `vlucas/phpdotenv ^5.6`
- Credenciales movidas a `.env` (fuera del control de versiones); `.env.example` como plantilla
- Clases Core MVC: `App\Core\Database` (singleton PDO con `getConnection()`), `App\Core\Router`, `App\Core\Controller`,
  `App\Core\Model` (base abstracta), `App\Core\Config` (wrapper .env), `App\Core\Middleware` (interfaz)
- `App\Core\Auth` para centralizar sesión, usuario actual, login/logout y CSRF
- `app/Middleware/` con middlewares namespaced PSR-4: `AuthMiddleware`, `GuestMiddleware`, `AdminMiddleware`,
  `SellerMiddleware` (permite `Administrador` y `Vendedor`; registrado como `'seller'` en Router)
- Modelo `App\Models\User` (hereda de `App\Core\Model`) con métodos de autenticación y CRUD de usuarios
- Entry point `public/index.php` con Router; `.htaccess` en raíz para soporte de rutas; `routes/web.php` para registro
  de rutas
- `App\Controllers\AuthController` consolida login y logout; directorio `auth/` reemplaza `login/`
- `App\Controllers\UserController` con CRUD completo del módulo users
- `App\Controllers\DashboardController` — ruta `GET /` con conteos de todos los módulos
- Nuevas vistas MVC en `views/auth/login.php`, `views/users/` (index, create, edit, show, delete),
  `views/dashboard/index.php`
- `Controller::renderWithLayout()` para renderizar vistas envueltas en `parte1`/`mensajes`/`parte2` desde el controlador
- Constante `BASE_URL` definida en `app/config.php` — disponible globalmente sin necesidad de pasar como variable
- Modelo `App\Models\Role` — `$table = 'tb_roles'`; CRUD heredado; sin delete por ser datos de sistema
- `App\Controllers\RoleController` (index, create, store, edit, update); vistas `views/roles/` con DataTables y badge de
  total
- Rutas `/roles` en `routes/web.php` con middleware `['auth', 'admin']`
- Modelo `App\Models\Category` — `$table = 'tb_categorias'`
- `App\Controllers\CategoryController` (index, create, store, edit, update); vistas `views/categories/`
- Rutas `/categories` en `routes/web.php` con middleware `auth`
- Modelo `App\Models\Supplier` — `$table = 'tb_proveedores'`; `isReferenced()` verifica `tb_compras`
- `App\Controllers\SupplierController` con CRUD completo; vistas `views/suppliers/` con SweetAlert2 para eliminar
- Rutas `/suppliers` en `routes/web.php` con middleware `auth`
- Modelo `App\Models\Client` — `$table = 'tb_clientes'`; `isReferenced()` verifica `tb_ventas`
- `App\Controllers\ClientController` con CRUD completo; vistas `views/clients/` con SweetAlert2 para eliminar
- Rutas `/clients` en `routes/web.php` con middleware `auth`
- Modelo `App\Models\Product` — `$table = 'tb_almacen'`; `allWithCategories()` con JOIN; `nextCode()` genera código
  `P-XXXXX`; `isReferenced()` verifica `tb_carrito` y `tb_compras`
- `App\Controllers\ProductController` con CRUD completo más `show()`; `handleImageUpload()` con validación MIME,
  extensión whitelist y límite 2MB
- Vistas `views/products/` con alerta visual de stock por colores; vista `show.php` con auditoría
- Rutas `/products` en `routes/web.php` con middleware `auth`
- Directorio `public/uploads/products/` para imágenes; `producto_default.png` y `.gitkeep` trackeados; resto ignorado en
  `.gitignore`
- Modelo `App\Models\Purchase` — `$table = 'tb_compras'`; `storeWithStock()`, `updateWithStock()`, `destroyWithStock()`
  transaccionales; `allWithDetails()`, `findWithDetails()`, `nextNumber()`
- `App\Controllers\PurchaseController` con CRUD completo; `id_usuario` de `Auth::user()` — nunca del POST
- Vistas `views/purchases/` con campos hidden `old_id_producto`/`old_cantidad` para ajuste de stock en edición
- Rutas `/purchases` en `routes/web.php` con middleware `auth`
- Helper estático `App\Helpers\NumberToWords::convert(float)` — convierte número a palabras en español para facturas PDF
- Modelo `App\Models\Sale` — `$table = 'tb_ventas'`; `allWithDetails()`, `findWithDetails()`, `nextNumber()` con
  `MAX()+1`; `storeWithStock()` y `destroyWithStock()` transaccionales (DELETE `tb_ventas` antes que `tb_carrito` por
  FK)
- Modelo `App\Models\CartItem` — `$table = 'tb_carrito'`; `addItem()` con validación de stock, `getByNroVenta()`,
  `removeItem()`, `countByNroVenta()`
- `App\Controllers\SaleController` con 9 métodos: `index`, `create`, `addToCart`, `removeFromCart`, `store`, `show`,
  `confirmDelete`, `invoice` (TCPDF inline), `destroy`
- Vistas `views/sales/`: `index.php` (DataTables), `create.php` (POS: carrito + modales de producto y cliente + panel
  pago), `show.php`, `delete.php` (confirmación SweetAlert2)
- Rutas `/sales` en `routes/web.php` con middleware `['auth', 'seller']`

### Cambiado

- `APP_URL` en `.env` ahora incluye `/public` (`http://localhost/Sistema_de_Ventas_PHP/public`) — todas las rutas MVC
  apuntan al front controller
- `app/config.php`: agrega `define('BASE_URL', ...)` y mantiene `$URL = BASE_URL` como alias backward-compat para
  módulos legacy
- `App\Core\Model` ampliado con CRUD completo: `all/create/update/count/query/isReferenced` y aliases de compatibilidad
  `findAll/insert`; propiedad canonical `$db` (con `$pdo` como alias)
- `App\Models\User` actualizado para usar `$this->db` (propiedad canonical de Model)
- `App\Controllers\AuthController` usa `BASE_URL` directamente; redirige a `BASE_URL . '/'` tras login exitoso
- `App\Controllers\UserController` refactorizado: helper `sessionData()` centraliza variables de sesión para todas las
  vistas; todos los métodos usan `renderWithLayout()`
- `login/index.php` reemplazado por redirect a `/auth/` (backward compat)
- Root `index.php` reemplazado por redirect stub a `BASE_URL . '/'`
- Rutas de users depuradas para usar endpoints canónicos sin duplicados
- `App\Core\Router` actualizado con middlewares por ruta y soporte de parámetros dinámicos (`/users/edit/{id}`)
- `DashboardController` reemplaza todos los `require_once listado_de_*.php` por llamadas a `Model::count()` (roles,
  categories, suppliers, clients, products, purchases, sales)
- Sidebar `views/layout/parte1.php` actualizado con bloques MVC para todos los módulos migrados
- Dashboard `views/dashboard/index.php` actualiza links a rutas MVC (`/sales`, `/sales/create`, etc.)
- `.gitignore` corregido para trackear solo `producto_default.png` y `.gitkeep` en `public/uploads/products/`

### Corregido

- `$(document).ready()` en DataTables init de `views/users/index.php` — prevenía `DataTable is not a function` al
  ejecutar el script antes de que `parte2.php` cargara la librería
- Atributos `autocomplete` añadidos en formularios de usuarios (edit: `name`, `email`, `new-password`; show: `off`) —
  elimina error `autofillFieldData.autoCompleteType is null` del browser

### Eliminado

- Vistas legacy del módulo `usuarios/` y controladores `app/controllers/usuarios/` reemplazados por `views/users/` y
  `UserController`
- Vistas legacy `roles/`, `categorias/`, `proveedores/`, `clientes/` y sus controladores en `app/controllers/`
- Vistas legacy `almacen/` y controladores `app/controllers/almacen/`
- Vistas legacy `compras/` y controladores `app/controllers/compras/`
- Vistas legacy `ventas/` y controladores `app/controllers/ventas/` (incluido `literal.php`)
- Directorio `app/controllers/` completo — incluyendo `middleware/AuthMiddleware.php` legacy; no queda ningún archivo
  fuera de la arquitectura MVC

## [1.0.0] - 2026-03-24

### Agregado

- `database/schema.sql` con la estructura de todas las tablas
- `database/seeder.sql` con datos iniciales para todas las tablas: roles, categorías, proveedores, clientes, usuarios,
  productos, compras y ventas de ejemplo
- Usuarios de prueba: `admin@sistema.com` / `admin123`, `vendedor@sistema.com` / `vendedor123`,
  `comprador@sistema.com` / `comprador123`
- `UNIQUE KEY` en `tb_usuarios.email` para evitar emails duplicados
- `UNIQUE KEY` en `tb_almacen.codigo` para evitar códigos de producto duplicados
- `app/config.example.php` como plantilla de configuración sin credenciales
- **Protección CSRF**: Generación global de token y validación estricta para todos los endpoints POST de mutación.
- **Validación Fuerte del Servidor**: Filtros integrados (`is_numeric`, `filter_var`) en controladores críticos de
  usuarios, almacén y ventas.

### Cambiado

- **Refactorización de Verbos HTTP**: Las mutaciones lógicas como la creación de ventas ahora requieren estrictamente
  `POST` mitigando vulnerabilidades.
- **Transacciones PDO Íntegras**: El carrito y la cabecera de la venta se procesan bajo un único bloque
  `$pdo->beginTransaction()` garantizando consistencia del stock.
- `fyh_creacion` ahora tiene `DEFAULT CURRENT_TIMESTAMP` en todas las tablas — ya no es necesario insertarla manualmente
- `fyh_actualizacion` ahora es `DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP` en todas las tablas — queda NULL al crear y se
  actualiza automáticamente al modificar
- `tb_almacen.precio_compra` y `tb_almacen.precio_venta`: `VARCHAR(255)` → `DECIMAL(10,2)`
- `tb_compras.precio_compra`: `VARCHAR(50)` → `DECIMAL(10,2)`
- `tb_ventas.total_pagado`: `INT(11)` → `DECIMAL(10,2)` (corrige pérdida de decimales)
- `tb_usuarios.token`: `NOT NULL` → `DEFAULT NULL`
- `tb_proveedores.email`: `VARCHAR(50)` → `VARCHAR(254)` (estándar RFC 5321)
- `AUTO_INCREMENT` reseteado a 1 en todas las tablas
- `app/config.php` excluido del repositorio vía `.gitignore`

### Corregido

- Advertencias en consola del navegador reparadas al forzar `autocomplete="off"` en los formularios de
  `login/index.php`.
- Estandarización de `layout/mensajes.php` para usar Toasts globales de SweetAlert2 en lugar del modal intrusivo.
- Campos de contraseña corregidos de `type="text"` a `type="password"` en `usuarios/create.php` y
  `usuarios/update.php` — la contraseña ya no se muestra en texto plano
- `htmlspecialchars()` aplicado en todas las vistas donde se muestran datos de usuarios/BD en HTML: `almacen/index.php`,
  `almacen/show.php`, `almacen/update.php`, `ventas/index.php`, `compras/index.php`, `proveedores/index.php` y
  `usuarios/update.php` — previene XSS almacenado
- Confirmaciones SweetAlert2 agregadas antes de eliminar registros en `almacen/index.php`, `ventas/index.php`,
  `compras/index.php` y `usuarios/index.php` — los botones de eliminar ya no navegan directamente sin confirmación
- Typo `lamppstart` → `lampp start` en README.md y CLAUDE.md
- URL de ejemplo incorrecta en CLAUDE.md (`sistemaventas` → `Sistema_de_Ventas_PHP`)
- SQL injection en `layout/sesion.php`, `app/controllers/login/ingreso.php`, `app/controllers/roles/update_roles.php`,
  `app/controllers/clientes/cargar_cliente.php`, `ventas/factura.php`, `ventas/show.php`, `ventas/index.php`,
  `ventas/delete.php` y `ventas/create.php` — se reemplazó interpolación directa de variables en SQL por placeholders
  `?` con `execute([$var])`
- Subida de imágenes sin validación en `app/controllers/almacen/create.php` y `update.php` — se agregó whitelist de
  extensiones (jpg, jpeg, png, webp), validación de MIME type real y límite de 2MB
- Variables PHP interpoladas directamente en bloques JavaScript en `ventas/create.php` — se reemplazó por
  `json_encode()` para prevenir errores con caracteres especiales
- **Control Sidebar:** Funcionalidad original manual reemplazada por el archivo `public/js/control_sidebar.js` 100%
  nativo de la API de AdminLTE, traducido al español, con guardado persistente en `localStorage`.
- **FOUC (Flash of Unstyled Content):** Parpadeos visuales al navegar con temas oscuros prevenidos mediante una pequeña
  inyección JS al inicio del `<body>` en `layout/parte1.php`.
- Reposicionado de la barra lateral de configuración a `position: fixed` para evitar pérdida visual al hacer scroll
  excesivo.
