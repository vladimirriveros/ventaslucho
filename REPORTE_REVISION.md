# Reporte de revisión técnica y de negocio

## Alcance

Se revisaron y modificaron los módulos de:

- Inventario y movimientos.
- Compras.
- Salidas.
- Ventas y anulaciones.
- Caja y pagos.
- Cuentas bancarias.
- Cotizaciones.
- Usuarios, roles, permisos y sucursales.
- Alertas.
- Reportes.
- Layout, responsive y tema claro/oscuro.

## Enfoque aplicado

La revisión se concentró en integridad de datos, trazabilidad, aislamiento por sucursal, validación del lado servidor, prevención de operaciones simultáneas inconsistentes y experiencia de uso móvil.

## Validaciones estáticas

Antes de empaquetar se comprueba:

- Sintaxis de archivos PHP.
- Carga de rutas de Laravel.
- Existencia de métodos de controladores usados por rutas.
- Existencia de nombres de rutas referenciados por las vistas.
- Correspondencia entre permisos declarados y permisos usados.
- Compilación de vistas Blade.
- Ausencia de llamadas de depuración y archivos de respaldo.
- Ausencia de referencias activas a vistas o configuración de AdminLTE.

## Limitación del entorno de revisión

El entorno usado para empaquetar no dispone de los controladores PHP de base de datos ni de todas las extensiones necesarias para ejecutar migraciones y PHPUnit. Por ese motivo, la migración real debe ejecutarse localmente con:

```bash
php artisan migrate:fresh --seed
```

La revisión no afirma haber ejecutado operaciones reales contra MySQL en este entorno.

## Archivos relevantes

- `app/Services/InventarioService.php`
- `app/Console/Commands/AuditarNegocio.php`
- `app/Http/Controllers/AlertaController.php`
- `app/Livewire/Admin/Ventas/ItemsVenta.php`
- `app/Livewire/Admin/Ventas/ItemsCaja.php`
- `database/migrations/2026_07_25_000001_harden_business_integrity.php`
- `public/css/conserdei-v2.css`
- `public/js/conserdei-v2.js`
- `resources/views/layouts/admin.blade.php`
- `CAMBIOS_REVISION_EXPERTA.md`
