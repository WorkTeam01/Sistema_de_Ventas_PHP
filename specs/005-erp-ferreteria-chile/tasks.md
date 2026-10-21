# Tareas

- [x] Añadir las migraciones MySQL y el esquema SQLite para unidades, cantidades enteras, documentos comerciales y el libro de inventario.
- [x] Actualizar validaciones, cálculos y vistas de POS, compras, devoluciones e inventario para unidades de ferretería.
- [x] Implementar cotizaciones multiítem con creación, edición, estados y salida imprimible sin impacto en stock.
- [x] Implementar órdenes de compra multiítem con creación, edición, estados y salida imprimible sin impacto en stock.
- [x] Añadir historial append-only e informe descargable de saldos y movimientos con filtros de producto y fecha.
- [x] Añadir registro auditable de correcciones de documentos confirmados.
- [x] Configurar ejemplos regionales para Chile y documentar instalación LAN y respaldos/restauración.
- [x] Agregar y ejecutar pruebas de integración para unidades enteras, saldos, estados, auditoría, alcance por usuario, reversión ante fallos y concurrencia de ediciones en MariaDB.
- [ ] Verificar manualmente permisos/rutas y aislamiento desde dos equipos en LAN.
- [x] Ejecutar `composer test` y resolver todos los fallos atribuibles a los cambios.

## Bloqueos conocidos

- Migraciones 016/017 y suite MariaDB verificadas localmente en MariaDB 10.11.11; falta probar la operación entre dos equipos en la LAN y la autorización navegando con cuentas distintas.
