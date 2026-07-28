# Instrucciones de ejecución — V4 multisucursal

## Instalación limpia recomendada

Esta versión cambia seeders, permisos y obligatoriedad de sucursal. Como la base es de prueba, use una reconstrucción completa.

> `migrate:fresh --seed` elimina todas las tablas y datos de la base configurada en `.env`.

Ejecute dentro del proyecto:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan permission:cache-reset
php artisan storage:link
php artisan negocio:auditar
php artisan serve
```

Si acaba de descomprimir el proyecto y la carpeta `vendor` no existe, ejecute primero:

```bash
composer install
```

No necesita `npm install` ni `npm run build` para estos cambios, porque no se modificaron recursos que dependan de Vite.

Abra:

```text
http://127.0.0.1:8000
```

## Credenciales de prueba

### Superadministrador

- Correo: `vlavlavlariver@gmail.com`
- Contraseña: `@dmin123`

### Administrador de sucursal principal

- Correo: `admin@admin.com`
- Contraseña: `123456789`

### Vendedor de sucursal principal

- Correo: `abc@abc.com`
- Contraseña: `123456789`

### Cajero de sucursal principal

- Correo: `cajero@demo.com`
- Contraseña: `123456789`

### Vendedor de sucursal norte

- Correo: `vendedor.norte@demo.com`
- Contraseña: `123456789`

### Almacén de sucursal norte

- Correo: `almacen.norte@demo.com`
- Contraseña: `123456789`

## Primera prueba recomendada

1. Ingrese como Superadministrador y revise Usuarios.
2. Cree un usuario, asígnele una sucursal y el perfil `admin` o uno operativo.
3. Ingrese como `admin@admin.com`.
4. Cree una compra sin seleccionar sucursal.
5. Agregue productos al carrito y finalice la compra.
6. Ejecute:

```bash
php artisan negocio:auditar
```

El resultado esperado es que no se detecten inconsistencias.
