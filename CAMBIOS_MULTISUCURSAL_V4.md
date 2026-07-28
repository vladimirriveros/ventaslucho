# Corrección multisucursal V4

## Objetivo

Esta versión corrige el flujo de compras y establece una regla única para todas las operaciones: la sucursal se obtiene del usuario autenticado en el servidor. El navegador no decide dónde entra o sale el inventario.

## Compras

- Se eliminó la selección manual de sucursal al crear y editar compras.
- La compra se guarda directamente con `auth()->user()->sucursal_id`.
- El valor `sucursal_id` enviado desde el navegador se ignora, aunque sea manipulado.
- Livewire vuelve a consultar la sucursal activa en cada operación sensible.
- Se corrigió el bloqueo que impedía agregar productos al carrito.
- La validación del carrito ya no exige campos que no existen en el formulario inicial.
- Las compras pendientes antiguas sin sucursal pueden vincularse de forma segura, siempre que todavía no tengan detalles ni movimientos de stock.
- Una compra de otra sucursal no puede abrirse, modificarse ni recibirse.

## Usuarios y jerarquía

### Superadministrador

- Crea usuarios.
- Asigna una sucursal activa obligatoria.
- Puede asignar el perfil `admin` o perfiles operativos.
- Crea y administra sucursales.
- Conserva acceso global para supervisión.

### Administrador de sucursal

- No crea, elimina ni cambia de sucursal a los usuarios.
- Solo ve usuarios operativos de su propia sucursal.
- Puede asignar perfiles predefinidos: `vendedor`, `cajero` y `almacen`.
- No puede modificar a otro administrador ni al Superadministrador.

### Usuarios operativos

- Trabajan únicamente con su sucursal asignada.
- Compras, salidas, ventas, cotizaciones y caja usan esa sucursal automáticamente.

## Modelo de productos e inventario

El catálogo de productos se mantiene global para evitar duplicar el mismo producto por sucursal. Lo que se separa por sucursal es:

- Existencia disponible.
- Lotes asignados.
- Movimientos.
- Compras y salidas.
- Ventas y cotizaciones.
- Cajas y cobros.

## Base de datos

En una instalación nueva, `users.sucursal_id` y `compras.sucursal_id` son obligatorios y tienen restricción de clave foránea. Una sucursal con usuarios u operaciones no debe eliminarse; debe desactivarse después de reasignar usuarios y cerrar cajas.

## Pruebas agregadas

Se añadieron pruebas de regresión para comprobar que:

- Una compra ignora una sucursal manipulada desde el formulario.
- Un producto se agrega al carrito de una compra autorizada.
- Un usuario no modifica compras de otra sucursal.
- Solo el Superadministrador crea usuarios.
- El administrador solo asigna perfiles a usuarios de su sucursal.
