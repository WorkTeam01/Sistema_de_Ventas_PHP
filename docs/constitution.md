# Constitución — Sistema de Ventas PHP

Principios no negociables. Toda spec, plan y tarea debe cumplirlos. Cada
principio está respaldado por una regla ya vigente en `AGENTS.md` o en el código.

1. **Alcance del producto**: sistema de gestión de ventas con inventario,
   facturación en PDF, compras a proveedores, clientes y acceso por roles, para
   un negocio único. **NO es**: multi-empresa / multi-sucursal, no expone API
   pública, no maneja contabilidad ni facturación fiscal electrónica.

2. **Stack fijo**: PHP 8.2+ sin framework, MVC custom con PSR-4 vía Composer,
   MySQL/MariaDB por PDO, AdminLTE 3.2 + Bootstrap 4 + jQuery + DataTables +
   SweetAlert2. No se introducen librerías nuevas sin decisión explícita
   justificada en el `plan.md`.

3. **Manda la spec**: no se implementa comportamiento que no esté en la spec
   activa. Si falta una decisión, el trabajo se detiene y se pregunta.

4. **Fat model, thin controller, dumb view**: la lógica de negocio (hashing,
   validación de formato, normalización, cálculos, reglas de integridad,
   transacciones) vive en el modelo; el controlador solo orquesta; la vista solo
   formatea valores ya calculados. Nunca `Auth::` ni cálculos (`foreach +=`) en
   vistas.

5. **Métodos de controlador cerrados**: `index`, `create`, `store`, `edit`,
   `update`, `destroy` más los auxiliares ya documentados en `AGENTS.md`
   (`check`, `delete`, `show`, `checkNombre`, `profile`…). No se inventan verbos
   nuevos (`toggle`, `activate`, `complete`) sin justificación.

6. **Datos**: siempre PDO con placeholders `?` y `execute([...])`; nunca
   interpolar variables en el SQL. **Borrado físico** protegido por
   `isReferenced()` antes del `DELETE`; nunca borrado lógico con `is_active`.
   Los montos y el stock se calculan desde la BD dentro de la transacción,
   jamás desde el `$_POST`.

7. **Autorización**: todo endpoint no público pasa por `can:permiso`; el scoping
   por dueño (`*_all`) se aplica al listado **y** al detalle / edición / borrado.
   CSRF en todo formulario POST. En vistas se usa `$can[...]`, nunca `Auth::`.

8. **Verificación como puerta**: ninguna tarea se cierra sin `composer test`
   verde. Si el cambio toca UI, además axe-core sin violaciones en modo claro y
   oscuro. No se testean Controllers, Middleware, Vistas ni Router.

9. **Idioma**: specs, planes, validaciones y documentación en español; los
   identificadores de código (variables, métodos, clases nuevas) en inglés,
   aunque el archivo use identificadores históricos en español.

10. **Moneda configurable**: montos vía `APP_CURRENCY_SYMBOL` (`.env`, default
    `"Bs."`). Nunca hardcodear `"Bs."`; nunca nombrar una constante
    `CURRENCY_SYMBOL` (colisiona con la extensión `intl`).
