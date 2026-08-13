@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="auth-card-header">
        <span class="auth-mobile-logo"><img src="{{ asset('img/conserdei4.png') }}" alt="CONSERDEI"></span>
        <span class="auth-kicker">Bienvenido</span>
        <h2>Iniciar sesión</h2>
        <p>Ingresa con la cuenta asignada por el administrador.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
        </div>
    @endif

    @if (session('guest_error'))
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('guest_error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <div class="input-group auth-input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-envelope"></i></span></div>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                    name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                    placeholder="usuario@correo.com">
            </div>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="input-group auth-input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                    name="password" required autocomplete="current-password" placeholder="Tu contraseña">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Mostrar contraseña">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div class="custom-control custom-checkbox">
                <input class="custom-control-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="custom-control-label" for="remember">Mantener sesión</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">Olvidé mi contraseña</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-block auth-submit">
            <i class="fas fa-sign-in-alt mr-2"></i>Ingresar al sistema
        </button>
    </form>

    @if (config('demo.guest_login_enabled', true))
        <div class="auth-demo-divider"><span>o prueba la demostración</span></div>
        <form method="POST" action="{{ route('guest.login') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-block auth-guest-submit">
                <i class="fas fa-eye mr-2"></i>Ingresar como invitado
            </button>
        </form>
        <div class="auth-demo-note">
            <i class="fas fa-shield-alt"></i>
            <span><strong>Modo demostración:</strong> acceso de solo lectura para recorrer el sistema sin usuario ni contraseña.</span>
        </div>
    @endif
@endsection

@section('js')
<script>
    document.querySelector('.toggle-password')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        this.innerHTML = visible ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
    });
</script>
@endsection
