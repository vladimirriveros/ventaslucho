# Cambios de lógica de negocio

Este documento resume las reglas más importantes incorporadas.

1. **El inventario se modifica solamente dentro de transacciones.** Ventas, salidas, vencidos y anulaciones bloquean lote e inventario de sucursal antes de cambiar cantidades.
2. **El lote y la sucursal deben coincidir.** No se puede descontar un lote que no pertenece a la sucursal de la operación.
3. **Las compras se reciben una sola vez.** Los datos del carrito se normalizan y recalculan en el servidor.
4. **Las cotizaciones no reservan stock.** La disponibilidad se vuelve a evaluar al convertir a venta.
5. **La venta conserva el costo histórico.** Cada detalle guarda el costo unitario usado al momento de vender.
6. **La caja física solo contiene efectivo.** Los cobros por QR, transferencia y tarjeta se registran como ingresos operativos y bancarios, pero no aumentan el efectivo esperado.
7. **Los pagos parciales no pueden exceder el saldo.** La venta se bloquea y el saldo se recalcula antes de registrar cada pago.
8. **Una venta con pagos no se anula directamente.** Esto evita perder trazabilidad de caja y banca.
9. **Toda operación respeta la sucursal del usuario.** El acceso global requiere el permiso `operaciones.todas-sucursales`.
10. **La auditoría no repara silenciosamente.** `php artisan negocio:auditar` detecta inconsistencias y deja la decisión de corrección al responsable.
