# Plan: sistema web local para ferretería en Chile

## Enfoque

Extender los módulos MVC y el esquema existente, conservando sus transacciones de stock, permisos por rol, PDO y auditoría. Las nuevas pantallas deben seguir los layouts, rutas y componentes ya establecidos.

## Fases

1. **Especificación y contrato de datos:** definir unidades de medida, referencias de documentos, estados de cotización/orden y límites del alcance tributario.
2. **Persistencia:** migración aditiva para unidades de producto, documentos comerciales, libro inmutable de movimientos e índices; mantener equivalencias SQLite para integración.
3. **Lógica de negocio:** persistencia transaccional, validaciones, registro de eventos compensatorios y auditabilidad de correcciones; los borradores no afectan stock.
4. **Interfaz:** listas, altas, edición, detalle e impresión/descarga de documentos; navegación desde el sidebar.
5. **Localización y operación:** configuración inicial para Chile, guía de servidor LAN y respaldo fuera del servidor.
6. **Validación:** ejecutar PHPUnit y revisión de los casos de stock, autorización, documentos y concurrencia.

## Decisiones técnicas

- Usar el motor MySQL/MariaDB InnoDB ya adoptado; la BD activa reside en el PC que presta el servicio.
- Guardar importes con DECIMAL y comparar montos mediante el helper de dinero existente. Mantener cantidades enteras para no cambiar la semántica del POS, las devoluciones y los históricos en esta entrega.
- No hacer llamadas a SII ni simular estados tributarios.
- Preservar compatibilidad con los registros existentes mediante una migración explícita y pruebas SQLite.
- No almacenar secretos en el código ni en la documentación.

## Riesgos

- Cambiar cantidades de enteras a decimales toca POS, compras, devoluciones, stock y reportes; exige actualizar todas las superficies antes del despliegue.
- La operación por LAN depende de configuración de Apache/firewall y copias de seguridad fuera del disco de base de datos.
- Los requisitos DTE deben definirse por separado al seleccionar un proveedor o desarrollar una integración oficial validada.
