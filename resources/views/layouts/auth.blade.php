<!doctype html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') · CONSERDEI</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/conserdei-v2.css') }}">
</head>
<body class="auth-shell">
    <button type="button" class="auth-theme-toggle" id="theme-toggle" aria-label="Cambiar tema" title="Cambiar tema">
        <i class="far fa-moon"></i>
    </button>

    <main class="auth-page">
        <section class="auth-brand-panel" aria-label="Información del sistema">
            <div class="auth-brand-content">
                <img src="{{ asset('img/conserdei4.png') }}" alt="CONSERDEI" class="auth-logo">
                <span class="auth-kicker">Gestión comercial</span>
                <h1>Ventas e inventario, organizados por sucursal.</h1>
                <p>Controla stock, lotes, compras, cotizaciones, caja y ventas desde una interfaz rápida y adaptable.</p>
                <div class="auth-feature-list">
                    <span><i class="fas fa-check"></i> Inventario trazable</span>
                    <span><i class="fas fa-check"></i> Caja y pagos mixtos</span>
                    <span><i class="fas fa-check"></i> Acceso por roles</span>
                </div>
            </div>
        </section>

        <section class="auth-form-panel">
            <div class="auth-card">
                @yield('content')
            </div>
        </section>
    </main>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        (() => {
            const root = document.documentElement;
            const key = 'conserdei-theme';
            const button = document.getElementById('theme-toggle');
            const preferred = localStorage.getItem(key) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            const apply = theme => {
                root.dataset.theme = theme;
                localStorage.setItem(key, theme);
                if (button) button.innerHTML = theme === 'dark' ? '<i class="far fa-sun"></i>' : '<i class="far fa-moon"></i>';
            };
            apply(preferred);
            button?.addEventListener('click', () => apply(root.dataset.theme === 'dark' ? 'light' : 'dark'));
        })();
    </script>
    @yield('js')
</body>
</html>
