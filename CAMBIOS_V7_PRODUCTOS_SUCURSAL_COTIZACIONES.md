# Cambios V7 — Productos por sucursal, cotizaciones y total de venta

## 1. Producto global y operación por sucursal

Se separó el catálogo general del control operativo de cada sucursal.

- `productos.estado` continúa indicando si el producto está disponible en el catálogo general.
- La nueva tabla `producto_sucursal` indica si una sucursal maneja ese producto.
- Una entrada de inventario activa el producto únicamente en la sucursal que recibe existencias.
- Una sucursal que nunca compró o recibió el producto no genera alerta de stock bajo.
- Si una sucursal manejó el producto y luego queda sin stock, sí genera alerta para reposición.
- La migración recupera automáticamente las relaciones de sucursal para inventario existente.

## 2. Alertas

- Las alertas de stock bajo ahora consultan `producto_sucursal`.
- El mínimo puede personalizarse por sucursal; mientras no se configure, usa el mínimo global del producto.
- Las alertas de lotes por vencer y vencidos también respetan la habilitación del producto en la sucursal.
- Los reportes de stock bajo usan la misma lógica para evitar resultados contradictorios.

## 3. Cotizaciones

- El buscador muestra todos los productos del catálogo, activos o inactivos y con o sin stock.
- La cotización permite agregar cualquier cantidad sin reservar inventario.
- Cada producto muestra el stock actual y la cantidad faltante.
- La conversión a venta se verifica nuevamente en el servidor.
- Si falta stock, se detalla producto, cantidad requerida, disponible y faltante.
- No se abre la venta hasta que todos los productos estén abastecidos.
- Se eliminó el evento JavaScript duplicado que podía mostrar dos confirmaciones diferentes.

## 4. Carrito de cotización

- Se reemplazó la tabla angosta por tarjetas responsivas.
- Cantidad, precio, subtotal, stock y botón eliminar quedan alineados.
- En celular cada producto se reorganiza sin desplazamiento horizontal innecesario.
- El resumen de subtotal, rebaja y total final se muestra en un panel separado.

## 5. Carrito de venta

- El total final editable fue movido al pie del carrito de venta.
- El usuario con permiso `ventas.aplicar-descuento` puede escribir directamente el nuevo total.
- El sistema recalcula rebaja, efectivo, QR, pago mixto y cambio.
- El servidor mantiene la validación: el total debe ser mayor a cero y no superar el subtotal.

## 6. Archivos principales

- `database/migrations/2026_08_01_000002_create_producto_sucursal_table.php`
- `app/Models/ProductoSucursal.php`
- `app/Services/AlertaInventarioService.php`
- `app/Services/CotizacionStockService.php`
- `app/Services/InventarioService.php`
- `app/Livewire/Admin/Ventas/ItemsCotizacion.php`
- `resources/views/livewire/admin/ventas/items-cotizacion.blade.php`
- `resources/views/livewire/admin/ventas/items-venta.blade.php`
- `public/css/conserdei-v2.css`
