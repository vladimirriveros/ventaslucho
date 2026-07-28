# Cambios de la revisión experta

Esta versión reemplaza los parches aislados por reglas consistentes para inventario, ventas, caja, cotizaciones, sucursales, permisos y experiencia móvil.

## 1. Movimientos de inventario

- Se corrigió `Undefined array key "fecha_desde"` agregando valores predeterminados para filtros ausentes.
- Los filtros de fecha, búsqueda y PDF usan la misma consulta autorizada.
- Un usuario operativo solo consulta movimientos de su sucursal.
- Cada movimiento puede registrar usuario, origen, identificador de la operación, stock anterior y stock nuevo.
- Se añadió el comando `php artisan negocio:auditar` para detectar incoherencias.

## 2. Compras, salidas y buscadores de productos

- Se corrigió el error JavaScript causado por sombrear y reasignar la constante `resultados`.
- Los resultados de búsqueda ya no quedan encerrados en una ventana pequeña.
- En escritorio, el listado aparece como un desplegable amplio por encima del contenido.
- En celular, se muestra en flujo completo y con área táctil adecuada.
- Compras y salidas vuelven a validar sucursal, lote, cantidades y estado en el servidor.
- Las operaciones de inventario son transaccionales para evitar stock negativo o doble procesamiento.
- El envío de compras por correo pasó de GET a POST con CSRF.

## 3. Configuraciones y plantilla

- Se eliminó el módulo funcional de Configuraciones, sus rutas, controlador, modelo, vistas, menú y permisos.
- La interfaz activa ya no depende de las vistas ni de la configuración de AdminLTE.
- Se creó un layout propio para el sistema con barra superior, menú lateral y componentes visuales unificados.
- La dependencia antigua de Composer se conserva únicamente para no invalidar el archivo `composer.lock`; no participa en la interfaz activa.

## 4. Responsive y tema visual

- Se renovó el layout para escritorio, tableta y celular.
- El menú lateral se convierte en panel deslizable en pantallas pequeñas.
- Tablas extensas tienen desplazamiento horizontal controlado.
- Formularios, tarjetas, modales, filtros y botones se reorganizan en móvil.
- Se añadió selector de tema claro/oscuro.
- La preferencia del tema se conserva en `localStorage`.

## 5. Alertas

- Se creó un resumen único de alertas por usuario y sucursal.
- El stock bajo incluye productos activos con stock cero o sin registros de inventario.
- Se excluyen lotes vencidos o inactivos del stock disponible.
- Las alertas de lotes por vencer respetan la sucursal autorizada.
- Al abrir directamente un endpoint de alerta ya no aparece una pantalla negra con JSON: se redirige a la pantalla correspondiente.
- Las llamadas AJAX siguen recibiendo JSON para actualizar la campana del sistema.

## 6. Multisucursal y usuarios

- Se agregó `sucursal_id` a usuarios.
- Todo usuario operativo debe tener una sucursal activa asignada.
- Solo roles con `operaciones.todas-sucursales` pueden trabajar globalmente.
- Compras, ventas, salidas, inventario, caja, movimientos, alertas y reportes vuelven a validar la sucursal en el servidor.
- Los selectores de sucursal quedan bloqueados o limitados para usuarios operativos.
- Se agregaron usuarios de demostración en dos sucursales para probar aislamiento.

## 7. Caja y cobros

- La caja se abre en la sucursal asignada al usuario.
- Solo puede existir una caja abierta por sucursal.
- Apertura, cierre y movimientos usan transacciones y bloqueos.
- El efectivo físico esperado no incluye QR, transferencia ni tarjeta.
- El fondo inicial dejó de contarse dos veces.
- El efectivo recibido en ventas al contado debe ser mayor o igual al importe que se paga en efectivo.
- Pagos concurrentes no pueden superar el saldo pendiente.

## 8. Borrador de venta

- La venta en proceso se guarda en sesión por usuario.
- Recargar la página conserva cliente, productos, cantidades, descuentos, tipo y forma de pago.
- Se corrigió el indicador Livewire que causaba la pérdida del borrador entre solicitudes.
- El borrador se elimina al finalizar la venta o mediante el botón `Descartar borrador`.

## 9. Pago QR y pago mixto

- Al seleccionar QR se muestra la imagen de la cuenta elegida.
- La cuenta debe estar activa y tener QR cargado.
- Se añadió pago mixto `Efectivo + QR`.
- Ambas partes deben ser mayores a cero y sumar exactamente el total.
- Se crean movimientos separados para mantener trazabilidad de efectivo y banco.

## 10. Cuentas bancarias

El formulario se redujo a:

- Banco.
- Número de cuenta.
- Nombre o titular de la cuenta.
- Imagen QR.
- Estado activo/inactivo.

Los campos internos de saldo y movimientos siguen protegidos y no se editan directamente desde el formulario.

## 11. Cotizaciones

- Se puede cotizar cualquier producto activo aunque no tenga stock.
- La cotización ya no reserva ni exige un lote.
- Abrir la pantalla de conversión no cambia el estado.
- Antes de convertir a venta se comprueba el stock real de la sucursal.
- Los lotes se asignan al vender, priorizando vencimiento y antigüedad.
- Si no existe stock suficiente, la conversión se bloquea sin alterar la cotización.

## 12. Inventario y ventas

- Se centralizaron las entradas y salidas en `InventarioService`.
- Se sincroniza el stock del lote con el inventario de la sucursal.
- Se añadió una restricción única para impedir duplicar el mismo lote en una sucursal.
- Las ventas recalculan precios, descuentos, subtotales y total en el servidor.
- El costo histórico queda guardado en el detalle para reportar utilidad correctamente.
- Una venta sin pagos puede anularse y devolver stock; una venta cobrada no puede anularse de forma directa.
- Códigos de venta y cotización usan secuencias bloqueadas para evitar duplicados simultáneos.

## 13. Permisos y seguridad

- Se reforzaron autorizaciones dentro de controladores y componentes Livewire.
- No basta con ocultar un botón: cada operación sensible vuelve a comprobar permisos.
- Se retiraron rutas de prueba y migración manual de inventario.
- Se protegieron descargas, reportes y operaciones por sucursal.
- Roles incluidos: `admin`, `vendedor`, `cajero` y `almacen`.
