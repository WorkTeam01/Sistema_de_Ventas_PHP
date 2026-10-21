# Sistema web local para ferretería en Chile

## Contexto

El sistema actual ya incluye usuarios con permisos, productos, stock, compras, ventas, proveedores, pagos, devoluciones, reportes y auditoría. Se ampliará sobre esa base con una experiencia adaptada al uso local por dos personas en una red LAN y los flujos de trabajo de una ferretería.

La licencia del repositorio es MIT. La implementación no debe afirmar que emite documentos tributarios electrónicos (DTE) ni que está certificada o conectada al SII.

## Requisitos

1. El inventario debe permitir identificar producto, código, unidad de medida, saldo inicial, ingresos, salidas y saldo actual; la existencia actual debe seguir siendo consistente con las operaciones transaccionales existentes. En esta primera entrega las cantidades conservan la precisión entera del sistema actual.
2. Deben registrarse compras/ingresos, ventas/salidas, proveedor o cliente, tipo y referencia del documento, usuario y fecha.
3. Se deben poder preparar cotizaciones y órdenes de compra con múltiples productos. Preparar esos documentos no cambia el inventario; recibir una compra sí.
4. Deben poder consultarse y descargarse informes de inventario, compras y ventas.
5. Los cambios a movimientos ya confirmados no deben borrar el registro original: deben quedar auditados con usuario y fecha y reflejarse en el libro de movimientos mediante entradas compensatorias.
6. El sistema debe poder ser accedido por dos equipos de una red local, con una sesión autenticada independiente para cada persona. La base de datos activa permanece en el equipo servidor; el disco USB se reserva para respaldos.
7. Configuración regional inicial: Chile, zona horaria `America/Santiago` y moneda configurable CLP. No se incorporan reglas tributarias de DTE sin proveedor/credenciales y validación profesional.
8. Los usuarios autorizados pueden crear y editar datos maestros y documentos según los permisos vigentes.

## Fuera de alcance

- Integración, envío, certificación o declaración de documentos electrónicos ante SII.
- Garantía de cumplimiento tributario o asesoría contable.
- Operación simultánea sin conexión a la base de datos del servidor.
- Almacenar contraseñas de servicios externos en el repositorio.

## Criterios de aceptación

- Las operaciones que alteren stock son atómicas y no pierden actualizaciones concurrentes.
- La cantidad entera y unidad de medida se muestran junto al producto en inventario, compras y ventas.
- Cotizaciones y órdenes de compra admiten líneas múltiples, usuario, fecha y estado; crear o editar un borrador no modifica stock.
- Los reportes muestran el período y filtros utilizados y se descargan en un formato utilizable por hoja de cálculo.
- Los documentos corregidos conservan evidencia del estado previo, usuario y fecha.
- El informe de movimientos consulta un libro append-only que conserva también las correcciones de stock.
- La guía de instalación incluye acceso LAN y procedimiento de respaldo/restauración.
- Toda salida tributaria se presenta como registro interno; la integración SII queda expresamente pendiente.
