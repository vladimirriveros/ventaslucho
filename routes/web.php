<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\InventarioSucuralLoteController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoCambioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\Auth\GuestLoginController;

// use App\Models\DetalleSalida;
// use App\Models\Salida;
use Illuminate\Support\Facades\Auth;

// use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return redirect()->route('login');
});

// Acceso de demostración para el portafolio. Usa POST + CSRF y una cuenta
// de solo lectura; no expone usuario ni contraseña en la interfaz.
Route::post('/ingresar-invitado', GuestLoginController::class)
    ->name('guest.login')
    ->middleware('throttle:20,1');

// El sistema trabaja con usuarios creados por administración/seeders.
// Se deshabilita el registro público para evitar altas no autorizadas.
Auth::routes(['register' => false]);

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\AdminController::class, 'index'])->name('home');
    Route::get('/admin', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/admin/alertas/resumen', [AlertaController::class, 'resumen'])->name('alertas.resumen');
    Route::get('/alerta-stock', [AlertaController::class, 'stock'])->name('alerta.stock');
    Route::get('/password/change', [App\Http\Controllers\ProfileController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/password/change', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('password.change.update');
});

// ── Categorías ───────────────────────────────────────────────────────────────
Route::get('/admin/categorias', [CategoriaController::class, 'index'])->name('categorias.index')->middleware('auth', 'can:categorias.index');
Route::get('/admin/categorias/create', [CategoriaController::class, 'create'])->name('categorias.create')->middleware('auth', 'can:categorias.create');
Route::post('/admin/categorias/create', [CategoriaController::class, 'store'])->name('categorias.store')->middleware('auth', 'can:categorias.store');
Route::get('/admin/categoria/{id}', [CategoriaController::class, 'show'])->name('categorias.show')->middleware('auth', 'can:categorias.show');
Route::get('/admin/categoria/{id}/edit', [CategoriaController::class, 'edit'])->name('categorias.edit')->middleware('auth', 'can:categorias.edit');
Route::put('/admin/categorias/{id}', [CategoriaController::class, 'update'])->name('categorias.update')->middleware('auth', 'can:categorias.update');
Route::delete('/admin/categoria/{id}', [CategoriaController::class, 'destroy'])->name('categorias.destroy')->middleware('auth', 'can:categorias.destroy');


// ── Sucursales ───────────────────────────────────────────────────────────────
Route::get('/admin/sucursales', [SucursalController::class, 'index'])->name('sucursales.index')->middleware('auth', 'can:sucursales.index');
Route::get('/admin/sucursales/create', [SucursalController::class, 'create'])->name('sucursales.create')->middleware('auth', 'can:sucursales.create');
Route::post('/admin/sucursales/create', [SucursalController::class, 'store'])->name('sucursales.store')->middleware('auth', 'can:sucursales.store');
Route::get('/admin/sucursales/{id}', [SucursalController::class, 'show'])->name('sucursales.show')->middleware('auth', 'can:sucursales.show');
Route::get('/admin/sucursales/{id}/edit', [SucursalController::class, 'edit'])->name('sucursales.edit')->middleware('auth', 'can:sucursales.edit');
Route::put('/admin/sucursales/{id}', [SucursalController::class, 'update'])->name('sucursales.update')->middleware('auth', 'can:sucursales.update');
Route::delete('/admin/sucursales/{id}', [SucursalController::class, 'destroy'])->name('sucursales.destroy')->middleware('auth', 'can:sucursales.destroy');


// ── Productos ────────────────────────────────────────────────────────────────
Route::get('admin/productos', [ProductoController::class, 'index'])->name('productos.index')->middleware('auth', 'can:productos.index');
Route::get('admin/productos/create', [ProductoController::class, 'create'])->name('productos.create')->middleware('auth', 'can:productos.create');
Route::post('admin/productos/create', [ProductoController::class, 'store'])->name('productos.store')->middleware('auth', 'can:productos.store');
Route::get('admin/producto/{id}', [ProductoController::class, 'show'])->name('productos.show')->middleware('auth', 'can:productos.show');
Route::get('admin/producto/{id}/edit', [ProductoController::class, 'edit'])->name('productos.edit')->middleware('auth', 'can:productos.edit');
Route::put('admin/producto/{id}', [ProductoController::class, 'update'])->name('productos.update')->middleware('auth', 'can:productos.update');
Route::delete('admin/producto/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy')->middleware('auth', 'can:productos.destroy');
Route::get('productos/verificar-codigo', [ProductoController::class, 'verificarCodigo'])->name('productos.verificar-codigo')->middleware('auth', 'can:productos.verificar-codigo');
Route::get('productos/ultimo-codigo', [ProductoController::class, 'ultimoCodigo'])->name('productos.ultimo-codigo')->middleware('auth', 'can:productos.ultimo-codigo');
Route::put('/admin/productos/{producto}/desactivar', [ProductoController::class, 'desactivar'])->name('productos.desactivar')->middleware('auth', 'can:productos.desactivar');
// En routes/web.php, dentro del grupo de productos
Route::get('/admin/producto/{producto}/historial-precios', [App\Http\Controllers\ProductoController::class, 'historialPrecios'])
    ->name('productos.historial')
    ->middleware('auth', 'can:productos.show');


// ── Proveedores ──────────────────────────────────────────────────────────────
Route::get('/admin/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index')->middleware('auth', 'can:proveedores.index');
Route::post('/admin/proveedor/create', [ProveedorController::class, 'store'])->name('proveedores.store')->middleware('auth', 'can:proveedores.store');
Route::put('/admin/proveedor/{id}', [ProveedorController::class, 'update'])->name('proveedores.update')->middleware('auth', 'can:proveedores.update');
Route::delete('/admin/proveedor/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy')->middleware('auth', 'can:proveedores.destroy');
// Agrega estas líneas si no existen:
Route::get('/admin/proveedor/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit')->middleware('auth', 'can:proveedores.update');


// ── Compras ──────────────────────────────────────────────────────────────────
Route::get('/admin/compras', [CompraController::class, 'index'])->name('compras.index')->middleware('auth', 'can:compras.index');
Route::get('/admin/compras/create', [CompraController::class, 'create'])->name('compras.create')->middleware('auth', 'can:compras.create');
Route::post('/admin/compras/create', [CompraController::class, 'store'])->name('compras.store')->middleware('auth', 'can:compras.store');
Route::get('/admin/compra/{id}', [CompraController::class, 'show'])->name('compras.show')->middleware('auth', 'can:compras.show');
Route::get('/admin/compra/{id}/edit', [CompraController::class, 'edit'])->name('compras.edit')->middleware('auth', 'can:compras.edit');
Route::delete('/admin/compra/{id}', [CompraController::class, 'destroy'])->name('compras.destroy')->middleware('auth', 'can:compras.destroy');
Route::post('/admin/compra/{compra}/enviar-correo', [CompraController::class, 'enviarCorreo'])->name('compras.enviarCorreo')->middleware('auth', 'can:compras.enviarCorreo');
//Enviar a Whatsapp
Route::get('/admin/compra/{compra}/enviar-whatsapp', [CompraController::class, 'enviarWhatsapp'])->name('compras.enviarWhatsapp')->middleware('auth', 'can:compras.show');
// Descargar PDF
Route::get('/admin/compra/{compra}/descargar-pdf', [CompraController::class, 'generarPdf'])
    ->name('compras.descargarPdf')
    ->middleware('auth', 'can:compras.show');
// ── Nota de Compra (PDF) ─────────────────────────────────────────────────
Route::get('/admin/compra/{compra}/nota-pdf', [CompraController::class, 'notaCompraPdf'])
    ->name('compras.nota-pdf')
    ->middleware('auth', 'can:compras.show');

Route::get('/admin/compra/{compra}/descargar-nota', [CompraController::class, 'descargarNotaCompra'])
    ->name('compras.descargar-nota')
    ->middleware('auth', 'can:compras.show');


// Enviar por WhatsApp con PDF
Route::get('/admin/compra/{compra}/enviar-whatsapp-pdf', [CompraController::class, 'enviarWhatsappPdf'])
    ->name('compras.enviarWhatsappPdf')
    ->middleware('auth', 'can:compras.show');


// ── Lotes ────────────────────────────────────────────────────────────────────
Route::get('/admin/lotes/vencidos', [LoteController::class, 'vencidos_index'])->name('lotes.vencidos')->middleware('auth', 'can:lotes.vencidos');
Route::get('/admin/lotes/vencidos/sucursal/{id}', [LoteController::class, 'vencidos_sucursal'])->name('lotes.vencidos.sucursal')->middleware('auth', 'can:lotes.vencidos.sucursal');
Route::get('/admin/lotes', [LoteController::class, 'index'])->name('lotes.index')->middleware('auth', 'can:lotes.index');
Route::put('/livewire/admin/lote/{id}', [LoteController::class, 'actualizar'])->name('admin.lote.update')->middleware('auth', 'can:admin.lote.update');
// ── Lotes PDF ──────────────────────────────────────────────────────
Route::get('/admin/lotes/pdf', [LoteController::class, 'generarPDF'])
    ->name('lotes.pdf')
    ->middleware('auth', 'can:lotes.index');
    // En tu archivo routes/web.php
Route::get('/alerta/lotes-por-vencer', [AlertaController::class, 'lotes'])->name('alerta.lotes-por-vencer')->middleware('auth');


// ── Inventario ───────────────────────────────────────────────────────────────
Route::get('/admin/inventario/sucursales_por_lotes', [InventarioSucuralLoteController::class, 'index'])->name('sucursal_por_lotes.index')->middleware('auth', 'can:sucursal_por_lotes.index');
Route::get('/admin/inventario/inventario_por_sucursal/sucursal/{id}', [InventarioSucuralLoteController::class, 'mostrar_inventario_por_sucursal'])->name('mostrar_inventario_por_sucursal.show')->middleware('auth', 'can:mostrar_inventario_por_sucursal.show');
Route::get('admin/inventario/sucursal/{id}/stock-bajo', [InventarioSucuralLoteController::class, 'stock_bajo_por_sucursal'])->name('inventario.stock_bajo_sucursal')->middleware('auth', 'can:inventario.stock_bajo_sucursal');
// En routes/web.php, agrega el middleware 'can' con el nombre del permiso:
Route::get('/admin/inventario/sucursal/{id}/pdf',[InventarioSucuralLoteController::class, 'generarPDF'])
    ->name('inventario.sucursal.pdf')
    ->middleware('auth', 'can:inventario.stock_bajo.pdf');
// ── Movimientos PDF ──────────────────────────────────────────────────────
Route::get('/admin/inventario/movimientos/pdf', [MovimientoInventarioController::class, 'generarPDF'])
    ->name('movimientos.pdf')
    ->middleware('auth', 'can:movimientos.index');
Route::get('/admin/inventario/sucursal/{id}/stock-bajo/pdf', [InventarioSucuralLoteController::class, 'generarPDFStockBajo'])->name('inventario.stock_bajo.pdf')->middleware('auth', 'can:inventario.sucursal.pdf');


// ── Movimientos ──────────────────────────────────────────────────────────────
Route::get('/admin/inventario/movimientos', [MovimientoInventarioController::class, 'index'])->name('movimientos.index')->middleware('auth', 'can:movimientos.index');

// ── Tipo de Cambio ───────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/tipo_cambio', [TipoCambioController::class, 'index'])->name('tipo_cambio.index')->middleware('can:tipo_cambio.index');
    Route::post('/tipo_cambio', [TipoCambioController::class, 'store'])->name('tipo_cambio.store')->middleware('can:tipo_cambio.store');
    Route::put('/tipo_cambio/{tipoCambio}', [TipoCambioController::class, 'update'])->name('tipo_cambio.update')->middleware('can:tipo_cambio.update');
    Route::delete('/tipo_cambio/{tipoCambio}', [TipoCambioController::class, 'destroy'])->name('tipo_cambio.destroy')->middleware('can:tipo_cambio.destroy');

    // Nuevas rutas
    Route::post('/tipo_cambio/set-oficial', [TipoCambioController::class, 'setOficial'])->name('tipo_cambio.set-oficial')->middleware('can:tipo_cambio.update');
    Route::post('/tipo_cambio/activar', [TipoCambioController::class, 'activar'])->name('tipo_cambio.activar')->middleware('can:tipo_cambio.update');
    Route::post('/tipo_cambio/actualizar-precios', [TipoCambioController::class, 'actualizarPreciosVenta'])->name('tipo_cambio.actualizar-precios')->middleware('can:tipo_cambio.recalcular-venta');
    Route::get('/tipo_cambio/recalcular-venta', [TipoCambioController::class, 'recalcularPorGanancia'])->name('tipo_cambio.recalcular-venta')->middleware('can:tipo_cambio.recalcular-venta');
});


// ── Roles ────────────────────────────────────────────────────────────────────
// ── Roles ────────────────────────────────────────────────────────────────────
Route::get('/admin/roles', [RoleController::class, 'index'])->name('roles.index')->middleware('auth', 'can:roles.index');
Route::get('/admin/roles/create', [RoleController::class, 'create'])->name('roles.create')->middleware('auth', 'can:roles.create');
Route::post('/admin/roles/create', [RoleController::class, 'store'])->name('roles.store')->middleware('auth', 'can:roles.store');
Route::get('/admin/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit')->middleware('auth', 'can:roles.edit');
Route::get('/admin/roles/{id}/permisos', [RoleController::class, 'permisos'])->name('roles.permisos')->middleware('auth', 'can:roles.permisos');
Route::post('/admin/roles/{id}', [RoleController::class, 'update_permisos'])->name('roles.update_permisos')->middleware('auth', 'can:roles.update_permisos');
Route::put('/admin/roles/{id}', [RoleController::class, 'update'])->name('roles.update')->middleware('auth', 'can:roles.update');
Route::delete('/admin/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('auth', 'can:roles.destroy');


// ── Usuarios ──────────────────────────────────────────────────────────────────
// ── Usuarios ──────────────────────────────────────────────────────────────────
Route::get('/admin/users', [UserController::class, 'index'])->name('user.index')->middleware('auth', 'can:user.index');
Route::get('/admin/users/create', [UserController::class, 'create'])->name('user.create')->middleware('auth', 'can:user.create');
Route::post('/admin/users/create', [UserController::class, 'store'])->name('user.store')->middleware('auth', 'can:user.store');
Route::get('/admin/users/{id}/edit', [UserController::class, 'edit'])->name('user.edit')->middleware('auth', 'can:user.update');
Route::put('/admin/users/{id}', [UserController::class, 'update'])->name('user.update')->middleware('auth', 'can:user.update');
Route::delete('/admin/users/{id}', [UserController::class, 'destroy'])->name('user.destroy')->middleware('auth', 'can:user.destroy');
// NUEVAS RUTAS PARA SOFT DELETES
Route::patch('/admin/users/restaurar/{id}', [UserController::class, 'restaurar'])->name('user.restaurar')->middleware('auth', 'can:user.destroy');
Route::delete('/admin/users/force-delete/{id}', [UserController::class, 'forceDelete'])->name('user.forceDelete')->middleware('auth', 'can:user.destroy');
// 👇 NUEVA RUTA PARA OBTENER ROLES DEL USUARIO (AJAX)
Route::get('/admin/users/{id}/roles', [UserController::class, 'getRoles'])->name('user.roles')->middleware('auth', 'can:user.assign-roles');
// 👇 NUEVA RUTA PARA ASIGNAR ROLES SIN CONTRASEÑA
Route::post('/admin/users/{id}/asignar-roles', [UserController::class, 'asignarRoles'])->name('user.asignar-roles')->middleware('auth', 'can:user.assign-roles');


// ── Salidas ───────────────────────────────────────────────────────────────────
Route::get('/admin/salidas', [SalidaController::class, 'index'])->name('salidas.index')->middleware('auth', 'can:salidas.index');
Route::get('/admin/salidas/create', [SalidaController::class, 'create'])->name('salidas.create')->middleware('auth', 'can:salidas.create');
Route::post('/admin/salidas/create', [SalidaController::class, 'store'])->name('salidas.store')->middleware('auth', 'can:salidas.store');
Route::get('/admin/salida/{id}', [SalidaController::class, 'show'])->name('salidas.show')->middleware('auth', 'can:salidas.show');
Route::get('/admin/salida/{id}/edit', [SalidaController::class, 'edit'])->name('salidas.edit')->middleware('auth', 'can:salidas.edit');
Route::delete('/admin/salida/{id}', [SalidaController::class, 'destroy'])->name('salidas.destroy')->middleware('auth', 'can:salidas.destroy');
Route::post('/admin/salidas/{salida}/finalizar', [SalidaController::class, 'finalizarSalida'])->name('salidas.finalizarSalida')->middleware('auth', 'can:salidas.finalizarSalida');
// ── Nota de Salida (PDF) ─────────────────────────────────────────────────
Route::get('/admin/salida/{salida}/nota-pdf', [SalidaController::class, 'notaSalidaPdf'])
    ->name('salidas.nota-pdf')
    ->middleware('auth', 'can:salidas.show');
Route::get('/admin/salida/{salida}/descargar-nota', [SalidaController::class, 'descargarNotaSalida'])
    ->name('salidas.descargar-nota')
    ->middleware('auth', 'can:salidas.show');


// ── Salidas de Vencidos ───────────────────────────────────────────────────────
Route::post('/admin/lotes/vencidos/agregar', [LoteController::class, 'agregarASalida'])->name('lotes.vencidos.agregar')->middleware('auth', 'can:lotes.vencidos.agregar');
Route::post('/admin/lotes/vencidos/eliminar/{lote_id}', [LoteController::class, 'eliminarDeSalida'])->name('lotes.vencidos.eliminar')->middleware('auth', 'can:lotes.vencidos.eliminar');
Route::post('/admin/lotes/vencidos/finalizar', [LoteController::class, 'finalizarSalidaVencidos'])->name('lotes.vencidos.finalizar')->middleware('auth', 'can:lotes.vencidos.finalizar');
// En web.php
Route::post('/admin/lotes/vencidos/agregar-todos', [LoteController::class, 'agregarTodosASalida'])->name('lotes.vencidos.agregar-todos')->middleware('auth', 'can:lotes.vencidos.agregar');
Route::post('/admin/lotes/vencidos/vaciar-carrito', [LoteController::class, 'vaciarCarrito'])->name('lotes.vencidos.vaciar-carrito')->middleware('auth', 'can:lotes.vencidos.eliminar');


// ── Migraciones de Lotes ──────────────────────────────────────────────────────


// ── Corrección de Compras ───────────────────────────────────────────────────
Route::get('/admin/compra/{compraId}/corregir', [App\Http\Controllers\CorreccionCompraController::class, 'edit'])
    ->name('compras.correccion.edit')
    ->middleware('auth', 'can:compras.correccion');

Route::post('/admin/compra/{compraId}/corregir', [App\Http\Controllers\CorreccionCompraController::class, 'update'])
    ->name('compras.correccion.update')
    ->middleware('auth', 'can:compras.correccion');

Route::get('/admin/lotes/{loteId}/stock', [App\Http\Controllers\CorreccionCompraController::class, 'getStockLote'])
    ->name('lotes.stock')
    ->middleware('auth', 'can:compras.correccion');


// ── Ventas ───────────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/ventas', [App\Http\Controllers\VentaController::class, 'index'])->name('ventas.index')->middleware('can:ventas.index');
    Route::get('/ventas/create', [App\Http\Controllers\VentaController::class, 'create'])->name('ventas.create')->middleware('can:ventas.create');
    Route::get('/ventas/{id}/edit', [App\Http\Controllers\VentaController::class, 'edit'])->name('ventas.edit')->middleware('can:ventas.edit');
    Route::get('/ventas/{id}/nota-pdf', [App\Http\Controllers\VentaController::class, 'notaVentaPdf'])->name('ventas.nota-pdf')->middleware('can:ventas.show');
    Route::get('/ventas/{id}/descargar-nota', [App\Http\Controllers\VentaController::class, 'descargarNotaVenta'])->name('ventas.descargar-nota')->middleware('can:ventas.show');
});

// ── Cotizaciones ────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/cotizaciones', [App\Http\Controllers\CotizacionController::class, 'index'])->name('cotizaciones.index')->middleware('can:cotizaciones.index');
    Route::get('/cotizaciones/create', [App\Http\Controllers\CotizacionController::class, 'create'])->name('cotizaciones.create')->middleware('can:cotizaciones.create');
    Route::get('/cotizaciones/{id}/edit', [App\Http\Controllers\CotizacionController::class, 'edit'])->name('cotizaciones.edit')->middleware('can:cotizaciones.edit');
    Route::get('/cotizaciones/{id}/imprimir', [App\Http\Controllers\CotizacionController::class, 'imprimir'])->name('cotizaciones.imprimir')->middleware('can:cotizaciones.imprimir');
    Route::post('/cotizaciones/{id}/convertir', [App\Http\Controllers\CotizacionController::class, 'convertirAVenta'])->name('cotizaciones.convertir')->middleware('can:cotizaciones.convertir');
});

// ── Caja ────────────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/caja', [App\Http\Controllers\CajaController::class, 'index'])->name('caja.index')->middleware('can:caja.index');
    Route::get('/caja/reporte/{cajaId}', [App\Http\Controllers\CajaController::class, 'reportePdf'])->name('caja.reporte')->middleware('can:caja.reportes');
    Route::get('/caja/ventas-pdf/{cajaId}', [App\Http\Controllers\CajaController::class, 'ventasPdf'])->name('caja.ventas-pdf')->middleware('can:caja.reportes');
});
Route::get('/admin/cotizaciones/verificar-stock/{id}', [CotizacionController::class, 'verificarStock'])
    ->name('cotizaciones.verificar-stock')
    ->middleware('auth', 'can:cotizaciones.convertir');

// ── Reportes ────────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/reportes/ventas', [App\Http\Controllers\ReporteController::class, 'ventas'])->name('reportes.ventas')->middleware('can:reportes.ventas');
    Route::get('/reportes/ventas/pdf', [App\Http\Controllers\ReporteController::class, 'ventasPdf'])->name('reportes.ventas.pdf')->middleware('can:reportes.ventas');
});


// ── Bancas ───────────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/bancas', [App\Http\Controllers\BancaController::class, 'index'])->name('bancas.index')->middleware('can:bancas.index');
    Route::get('/bancas/create', [App\Http\Controllers\BancaController::class, 'create'])->name('bancas.create')->middleware('can:bancas.create');
    Route::get('/bancas/{id}/edit', [App\Http\Controllers\BancaController::class, 'edit'])->name('bancas.edit')->middleware('can:bancas.edit');
    Route::get('/bancas/{id}/movimientos', [App\Http\Controllers\BancaController::class, 'movimientos'])->name('bancas.movimientos')->middleware('can:bancas.movimientos');
    Route::get('/bancas/{id}/cargar', [App\Http\Controllers\BancaController::class, 'cargar'])->name('bancas.cargar')->middleware('can:bancas.cargar');

    // Route::get('/bancas/movimientos/{banca}', [App\Http\Controllers\BancaController::class, 'movimientos'])->name('bancas.movimientos');
});


// ── Clientes ──────────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth')->group(function () {
    // Las rutas específicas deben ir antes de /clientes/{id}.
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar'])->name('clientes.buscar')->middleware('can:clientes.index');
    Route::patch('/clientes/{id}/toggle-activo', [ClienteController::class, 'toggleActivo'])->name('clientes.toggle-activo')->middleware('can:clientes.edit');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index')->middleware('can:clientes.index');
    Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create')->middleware('can:clientes.create');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store')->middleware('can:clientes.store');
    Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show')->middleware('can:clientes.show');
    Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->name('clientes.edit')->middleware('can:clientes.edit');
    Route::match(['put', 'patch'], '/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update')->middleware('can:clientes.update');
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy')->middleware('can:clientes.destroy');
});


Route::prefix('admin')->middleware('auth')->group(function () {
    Route::post('/marcas/store', [MarcaController::class, 'store'])->name('marcas.store')->middleware('can:productos.create');
});
