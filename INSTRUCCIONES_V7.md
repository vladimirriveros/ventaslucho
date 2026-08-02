# Ejecución de la versión V7

Esta versión agrega la tabla `producto_sucursal`. Como la base es de prueba, se recomienda reconstruirla.

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan permission:cache-reset
php artisan storage:link
php artisan negocio:auditar
php artisan serve
```

`migrate:fresh --seed` elimina los datos actuales y vuelve a ejecutar todas las migraciones y seeders.

No es necesario ejecutar `composer install`, `npm install` ni `npm run build` si la versión anterior ya funcionaba, porque no se añadieron dependencias.

Si `php artisan storage:link` informa que el enlace ya existe, no es un error.

## Prueba recomendada

1. Ingresar con un usuario de la sucursal principal.
2. Recibir una compra del producto X.
3. Verificar que el producto X quede habilitado y con stock en esa sucursal.
4. Ingresar con un usuario de otra sucursal.
5. Verificar que no aparezca alerta del producto X mientras esa sucursal nunca lo haya manejado.
6. Crear una cotización en la segunda sucursal con el producto X sin stock.
7. Comprobar que la cotización se guarda, pero no se convierte en venta.
8. Recibir una compra del producto X en la segunda sucursal.
9. Volver a convertir la cotización y comprobar que ahora se habilita.
10. En una venta, editar el total final desde el pie del carrito.
