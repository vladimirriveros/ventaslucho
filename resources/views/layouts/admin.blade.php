<!doctype html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CONSERDEI') · CONSERDEI</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="//cdn.datatables.net/buttons/2.4.0/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('css/conserdei-v2.css') }}">
    @livewireStyles
    @stack('css')
    @yield('css')
</head>
<body class="app-shell">
    <div class="app-backdrop" id="app-backdrop" aria-hidden="true"></div>

    <aside class="app-sidebar" id="app-sidebar" aria-label="Navegación principal">
        <a class="app-brand" href="{{ route('home') }}">
            <img src="{{ asset('img/conserdei4.png') }}" alt="CONSERDEI">
            <span><strong>CONSERDEI</strong><small>Ventas e inventario</small></span>
        </a>

        <div class="app-user-card">
            <div class="app-avatar">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
            <div class="min-w-0">
                <strong class="d-block text-truncate">{{ auth()->user()->name }}</strong>
                <small class="d-block text-truncate">{{ auth()->user()->sucursal?->nombre ?? 'Acceso global' }}</small>
            </div>
        </div>

        <nav class="app-nav">
            <span class="app-nav-label">PRINCIPAL</span>
            <a class="app-nav-link {{ request()->routeIs('home', 'admin.index') ? 'active' : '' }}" href="{{ route('home') }}">
                <i class="fas fa-chart-pie"></i><span>Panel principal</span>
            </a>

            @canany(['ventas.index','ventas.create','caja.index','cotizaciones.index','clientes.index','bancas.index'])
                <span class="app-nav-label">VENTAS Y CAJA</span>
                @can('ventas.create')
                    <a class="app-nav-link {{ request()->routeIs('ventas.create') ? 'active' : '' }}" href="{{ route('ventas.create') }}"><i class="fas fa-cash-register"></i><span>Nueva venta</span></a>
                @endcan
                @can('ventas.index')
                    <a class="app-nav-link {{ request()->routeIs('ventas.index','ventas.edit') ? 'active' : '' }}" href="{{ route('ventas.index') }}"><i class="fas fa-receipt"></i><span>Ventas</span></a>
                @endcan
                @can('caja.index')
                    <a class="app-nav-link {{ request()->routeIs('caja.*') ? 'active' : '' }}" href="{{ route('caja.index') }}"><i class="fas fa-wallet"></i><span>Caja</span></a>
                @endcan
                @can('cotizaciones.index')
                    <a class="app-nav-link {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}" href="{{ route('cotizaciones.index') }}"><i class="fas fa-file-invoice-dollar"></i><span>Cotizaciones</span></a>
                @endcan
                @can('clientes.index')
                    <a class="app-nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}"><i class="fas fa-address-book"></i><span>Clientes</span></a>
                @endcan
                @can('bancas.index')
                    <a class="app-nav-link {{ request()->routeIs('bancas.*') ? 'active' : '' }}" href="{{ route('bancas.index') }}"><i class="fas fa-university"></i><span>Cuentas bancarias</span></a>
                @endcan
            @endcanany

            @canany(['compras.index','salidas.index','productos.index','lotes.index','movimientos.index','proveedores.index','categorias.index'])
                <span class="app-nav-label">INVENTARIO</span>
                @can('productos.index')
                    <a class="app-nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}" href="{{ route('productos.index') }}"><i class="fas fa-box"></i><span>Productos</span></a>
                @endcan
                @can('compras.index')
                    <a class="app-nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}" href="{{ route('compras.index') }}"><i class="fas fa-shopping-cart"></i><span>Compras</span></a>
                @endcan
                @can('salidas.index')
                    <a class="app-nav-link {{ request()->routeIs('salidas.*') ? 'active' : '' }}" href="{{ route('salidas.index') }}"><i class="fas fa-sign-out-alt"></i><span>Salidas y ajustes</span></a>
                @endcan
                @can('lotes.index')
                    <a class="app-nav-link {{ request()->routeIs('lotes.*') ? 'active' : '' }}" href="{{ route('lotes.index') }}"><i class="fas fa-barcode"></i><span>Lotes</span></a>
                    <a class="app-nav-link {{ request()->routeIs('sucursal_por_lotes.*') ? 'active' : '' }}" href="{{ route('sucursal_por_lotes.index') }}"><i class="fas fa-warehouse"></i><span>Inventario por sucursal</span></a>
                @endcan
                @can('movimientos.index')
                    <a class="app-nav-link {{ request()->routeIs('movimientos.*') ? 'active' : '' }}" href="{{ route('movimientos.index') }}"><i class="fas fa-exchange-alt"></i><span>Movimientos</span></a>
                @endcan
                @can('proveedores.index')
                    <a class="app-nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" href="{{ route('proveedores.index') }}"><i class="fas fa-truck"></i><span>Proveedores</span></a>
                @endcan
                @can('categorias.index')
                    <a class="app-nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}" href="{{ route('categorias.index') }}"><i class="fas fa-tags"></i><span>Categorías</span></a>
                @endcan
            @endcanany

            @canany(['reportes.ventas','sucursales.index','user.index','roles.index','tipo_cambio.index'])
                <span class="app-nav-label">ADMINISTRACIÓN</span>
                @can('reportes.ventas')
                    <a class="app-nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.ventas') }}"><i class="fas fa-chart-line"></i><span>Reportes</span></a>
                @endcan
                @can('sucursales.index')
                    <a class="app-nav-link {{ request()->routeIs('sucursales.*') ? 'active' : '' }}" href="{{ route('sucursales.index') }}"><i class="fas fa-building"></i><span>Sucursales</span></a>
                @endcan
                @can('user.index')
                    <a class="app-nav-link {{ request()->routeIs('user.*') ? 'active' : '' }}" href="{{ route('user.index') }}"><i class="fas fa-users"></i><span>Usuarios</span></a>
                @endcan
                @can('roles.index')
                    <a class="app-nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="fas fa-user-shield"></i><span>Roles y permisos</span></a>
                @endcan
                @can('tipo_cambio.index')
                    <a class="app-nav-link {{ request()->routeIs('tipo_cambio.*') ? 'active' : '' }}" href="{{ route('tipo_cambio.index') }}"><i class="fas fa-coins"></i><span>Tipo de cambio</span></a>
                @endcan
            @endcanany
        </nav>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center min-w-0">
                <button type="button" class="app-icon-btn d-lg-none mr-2" id="sidebar-toggle" aria-label="Abrir menú"><i class="fas fa-bars"></i></button>
                <button type="button" class="app-icon-btn d-none d-lg-inline-flex mr-3" id="sidebar-collapse" aria-label="Contraer menú"><i class="fas fa-bars"></i></button>
                <div class="topbar-context min-w-0">
                    <strong class="text-truncate d-block">@yield('title', 'CONSERDEI')</strong>
                    <small class="text-truncate d-block">{{ auth()->user()->sucursal?->nombre ?? 'Todas las sucursales' }}</small>
                </div>
            </div>

            <div class="app-topbar-actions">
                <div class="dropdown">
                    <button class="app-icon-btn notification-button" id="notifications-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Alertas">
                        <i class="far fa-bell"></i><span class="notification-dot d-none" id="notification-dot"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right notifications-menu" aria-labelledby="notifications-button">
                        <div class="notifications-header"><strong>Alertas del sistema</strong><small id="notifications-scope">Cargando…</small></div>
                        <div id="notifications-content" class="notifications-content"><div class="notification-empty"><i class="fas fa-spinner fa-spin"></i> Consultando inventario…</div></div>
                    </div>
                </div>
                <button type="button" class="app-icon-btn" id="theme-toggle" aria-label="Cambiar tema" title="Cambiar tema"><i class="far fa-moon"></i></button>
                <div class="dropdown">
                    <button class="app-profile-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="app-avatar small">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        <span class="d-none d-md-inline text-truncate">{{ auth()->user()->name }}</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ route('password.change') }}"><i class="fas fa-key mr-2"></i>Cambiar contraseña</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt mr-2"></i>Cerrar sesión</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-content">
            @if ($errors->any())
                <div class="alert alert-danger app-alert"><strong>Revise los datos:</strong><ul class="mb-0 pl-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content_header')
            @yield('content')
        </main>

        <footer class="app-footer"><span>CONSERDEI · Sistema de ventas e inventario</span><span>Laravel {{ app()->version() }}</span></footer>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
    <script src="//cdn.datatables.net/buttons/2.4.0/js/dataTables.buttons.min.js"></script>
    <script src="//cdn.datatables.net/buttons/2.4.0/js/buttons.bootstrap4.min.js"></script>
    <script src="//cdn.datatables.net/buttons/2.4.0/js/buttons.html5.min.js"></script>
    <script src="//cdn.datatables.net/buttons/2.4.0/js/buttons.print.min.js"></script>
    <script src="//cdn.datatables.net/buttons/2.4.0/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
    @livewireScripts
    <script>
        window.Conserdei = {
            alertasUrl: @json(route('alertas.resumen')),
            stockUrl: @json(auth()->user()->can('sucursal_por_lotes.index') ? route('sucursal_por_lotes.index') : route('home')),
            lotesUrl: @json(auth()->user()->can('lotes.index') ? route('lotes.index') : route('home')),
            userId: {{ (int) auth()->id() }}
        };
    </script>
    <script src="{{ asset('js/conserdei-v2.js') }}"></script>

    @if (($mensaje = session('mensaje')) && ($icono = session('icono')))
        <script>document.addEventListener('DOMContentLoaded',()=>window.appToast(@json($mensaje), @json($icono)));</script>
    @elseif(session('success'))
        <script>document.addEventListener('DOMContentLoaded',()=>window.appToast(@json(session('success')), 'success'));</script>
    @endif
    @stack('js')
    @yield('js')
</body>
</html>
