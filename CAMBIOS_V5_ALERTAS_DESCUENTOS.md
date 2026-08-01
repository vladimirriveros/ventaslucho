# Cambios V5: usuarios, alertas, rebajas y responsive

## 1. Usuarios y control administrativo

- Solo el rol `superadmin` puede crear, editar, cambiar de sucursal, eliminar o restaurar usuarios.
- El administrador de sucursal no crea usuarios. Puede consultar únicamente a los usuarios operativos de su sucursal y asignarles los perfiles `vendedor`, `cajero` o `almacen`.
- Todos los usuarios operativos deben tener una sucursal activa.
- Se incluyen dos superadministradores protegidos para soporte:
  - Propietario: `vlavlavlariver@gmail.com` / `@dmin123`
  - Desarrollador: `desarrollador@conserdei.com` / `@dev12345`
- Ninguno de los dos superadministradores puede eliminarse desde el sistema.

## 2. Alertas de inventario

- Se creó `AlertaInventarioService` para centralizar la detección de alertas.
- Se detectan por sucursal:
  - productos sin stock;
  - productos con stock igual o inferior al mínimo;
  - lotes que vencen en los próximos 7 días;
  - lotes vencidos que todavía tienen existencia.
- Se agregó un Centro de alertas en `/admin/alertas`.
- El encabezado y el menú muestran el total de alertas.
- El dashboard muestra un resumen visible.
- Las rutas antiguas de alertas redirigen al Centro de alertas y ya no muestran una página negra con JSON.
- El Superadministrador ve todas las sucursales; los demás usuarios solamente su sucursal.

## 3. Ventas y cuentas bancarias

- La selección de cuenta bancaria se realiza mediante un modal.
- El modal muestra banco, nombre, número de cuenta e imagen QR.
- Al seleccionar QR o pago mixto se solicita una cuenta activa con imagen QR.
- El total final se puede editar para aplicar una rebaja, independientemente de que el pago sea efectivo, QR o mixto.
- La rebaja se controla con el permiso `ventas.aplicar-descuento`.
- El servidor vuelve a validar subtotal, rebaja y total antes de guardar.
- El total no puede ser cero, negativo ni superior al subtotal.
- Al modificar el total se actualizan el efectivo recibido, la parte QR y el cambio según el método de pago.
- La nota de venta muestra subtotal, rebaja y total final.

## 4. Cotizaciones

- El total final de la cotización se puede editar para aplicar una rebaja.
- La rebaja se controla con el permiso `cotizaciones.aplicar-descuento`.
- El servidor recalcula todos los importes antes de guardar.
- La cotización impresa muestra subtotal, rebaja y total final.
- Al enviar una cotización al carrito de ventas se conserva la rebaja.
- Si el método es efectivo, el campo «efectivo recibido» se completa con el total final de la cotización.
- La cotización sigue sin reservar inventario; el stock se valida al convertirla en venta.

## 5. Responsive

- Se mejoraron el Centro de alertas, carritos, controles de total, selector de banca y modales.
- En celular, los modales ocupan la pantalla disponible y mantienen botones accesibles.
- Las tablas de alertas se transforman en tarjetas legibles.
- Se corrigieron anchos, espaciados y distribución de formularios en pantallas pequeñas.

## 6. Pruebas incluidas

Se agregó `tests/Feature/RevisionV5Test.php` para comprobar:

- existencia de dos superadministradores protegidos;
- productos sin stock dentro de las alertas;
- redirección de rutas antiguas de alertas;
- rebaja de venta con QR;
- rebaja de cotización;
- copia automática del total al efectivo recibido.
