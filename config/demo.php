<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Acceso de demostración
    |--------------------------------------------------------------------------
    |
    | Permite mostrar un botón de acceso con un solo clic para el portafolio.
    | La cuenta utiliza el rol "invitado", que solo recibe permisos de lectura.
    | Puede deshabilitarse sin tocar el código usando DEMO_GUEST_LOGIN=false.
    |
    */
    'guest_login_enabled' => env('DEMO_GUEST_LOGIN', true),
    'guest_email' => env('DEMO_GUEST_EMAIL', 'invitado@demo.local'),

    // Lista blanca: si el rol invitado recibe un permiso fuera de esta lista,
    // el acceso público se bloquea automáticamente hasta corregir sus permisos.
    'guest_permissions' => [
        'operaciones.todas-sucursales',
        'caja.index',
        'caja.reportes',
        'categorias.index',
        'categorias.show',
        'compras.index',
        'compras.show',
        'cotizaciones.imprimir',
        'cotizaciones.index',
        'cotizaciones.show',
        'inventario.stock_bajo.pdf',
        'inventario.stock_bajo_sucursal',
        'inventario.sucursal.pdf',
        'lotes.index',
        'lotes.pdf',
        'lotes.show',
        'lotes.vencidos',
        'lotes.vencidos.sucursal',
        'mostrar_inventario_por_sucursal.show',
        'movimientos.index',
        'productos.index',
        'productos.show',
        'reportes.vendedores',
        'reportes.ventas',
        'reportes.ventas.diario',
        'reportes.ventas.mensual',
        'salidas.index',
        'salidas.show',
        'sucursal_por_lotes.index',
        'sucursales.index',
        'sucursales.show',
        'ventas.index',
        'ventas.show',
    ],
];
