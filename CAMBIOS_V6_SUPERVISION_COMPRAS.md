# Cambios V6 — Compras, supervisión y productos de prueba

## 1. Buscador de productos en compras

- Se reemplazó el buscador anterior del paso 2 por una búsqueda Livewire en el servidor.
- La búsqueda comienza desde el primer carácter y consulta código, nombre y marca.
- Los resultados aparecen debajo del campo y permiten agregar el producto con un clic.
- Se incluyen productos activos e inactivos, porque un producto nuevo debe poder comprarse antes de habilitarse para venta.
- Al recibir/finalizar la compra, el producto comprado se activa y se registra en el inventario de la sucursal del usuario.
- La búsqueda ya no depende de cargar todo el catálogo en JavaScript, por lo que funciona mejor con catálogos grandes y en celulares.

## 2. Superadministrador de solo supervisión operativa

El rol `superadmin` quedó con alcance global, pero sin permisos para registrar o modificar operaciones del negocio.

Puede:

- consultar compras, ventas, salidas, cotizaciones, inventario, lotes, movimientos, cajas y cuentas bancarias de todas las sucursales;
- generar reportes y archivos PDF autorizados;
- crear, editar, desactivar/restaurar usuarios y asignarles sucursal;
- asignar roles y configurar los permisos de los perfiles operativos.

No puede:

- crear, editar, finalizar, corregir ni eliminar compras;
- crear, editar o anular ventas y cotizaciones;
- registrar salidas, pagos, movimientos de caja o movimientos bancarios;
- abrir o cerrar cajas;
- modificar productos, proveedores, categorías, cuentas bancarias o sucursales.

Las restricciones se aplican mediante permisos de rutas, validaciones del servidor y ocultamiento de botones operativos.

## 3. Supervisión de caja por sucursal

- El Superadministrador puede seleccionar una sucursal únicamente como filtro de consulta.
- Puede revisar la caja activa, historial, movimientos y reportes de la sucursal seleccionada.
- No se muestran controles de apertura, cierre ni movimientos manuales.
- Los usuarios operativos continúan trabajando automáticamente con su sucursal asignada y no pueden cambiarla.

## 4. Productos del seeder

- Todos los productos demostrativos ahora se crean con `estado = false`.
- Los productos inactivos no generan alertas de stock bajo.
- Continúan disponibles en el buscador de compras.
- Al recibir una compra, el producto se activa para comenzar su operación normal.
- `ProductoFactory` también utiliza `estado = false` para mantener el mismo comportamiento en pruebas.

## 5. Pruebas de regresión

Se agregó `tests/Feature/RevisionV6SupervisionCompraTest.php` para comprobar:

- productos iniciales inactivos y sin alertas;
- búsqueda y agregado de productos inactivos en compras;
- acceso global de consulta del Superadministrador;
- bloqueo de creación, edición y eliminación de compras;
- supervisión de cajas por sucursal sin permisos operativos.
