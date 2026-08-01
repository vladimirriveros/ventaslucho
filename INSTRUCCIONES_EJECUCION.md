# Instrucciones de ejecución — V6

## Instalación recomendada para esta base de prueba

> `php artisan migrate:fresh --seed` elimina todas las tablas y datos de la base configurada en `.env`.

Desde la carpeta del proyecto ejecute:

```bash
composer install
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan permission:cache-reset
php artisan storage:link
php artisan negocio:auditar
php artisan serve
```

Abra:

```text
http://127.0.0.1:8000
```

Si `storage:link` indica que el enlace ya existe, puede continuar. No es necesario ejecutar `npm install` ni `npm run build`, porque esta versión modifica PHP, Blade y archivos públicos ya incluidos.

## Motivo para usar fresh

Esta versión no agrega una migración estructural nueva. Se recomienda reconstruir la base de prueba porque cambiaron:

- la matriz de permisos del Superadministrador;
- los permisos reservados al administrador de sucursal;
- el estado inicial de los productos del seeder;
- las pruebas y datos de demostración.

`migrate:fresh --seed` ejecuta automáticamente `DatabaseSeeder`, `RoleSeeder` y `AdminUserSeeder`. No los ejecute de nuevo por separado.

## Superadministradores de prueba

```text
Propietario
Correo: vlavlavlariver@gmail.com
Contraseña: @dmin123
```

```text
Desarrollador
Correo: desarrollador@conserdei.com
Contraseña: @dev12345
```

## Usuarios operativos de prueba

```text
Administrador: admin@admin.com / 123456789
Vendedor: abc@abc.com / 123456789
Cajero: cajero@demo.com / 123456789
Vendedor Norte: vendedor.norte@demo.com / 123456789
Almacén Norte: almacen.norte@demo.com / 123456789
```

## Orden recomendado para probar

1. Ingrese como Superadministrador y compruebe que puede consultar compras de ambas sucursales, pero no crear, editar ni eliminar.
2. Abra Caja como Superadministrador, cambie el filtro de sucursal y compruebe que solo aparecen controles de consulta.
3. Ingrese como `admin@admin.com` o `almacen.norte@demo.com`.
4. Cree una compra y avance al paso 2.
5. Escriba el código o nombre de un producto, por ejemplo `TP` o `taladro`.
6. Seleccione el resultado, revise el carrito y finalice la compra.
7. Compruebe que el producto se activó y que el lote/stock quedó en la sucursal del usuario.
8. Ejecute:

```bash
php artisan negocio:auditar
php artisan test
```
