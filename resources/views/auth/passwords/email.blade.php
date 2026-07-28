@extends('layouts.auth')
@section('title', 'Recuperar contraseña')
@section('content')
<div class="auth-card-header"><span class="auth-kicker">Recuperación</span><h2>Restablecer contraseña</h2><p>Te enviaremos un enlace al correo registrado.</p></div>
@if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('password.email') }}">@csrf
    <div class="form-group"><label for="email">Correo electrónico</label><input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <button type="submit" class="btn btn-primary btn-block auth-submit">Enviar enlace</button>
    <a href="{{ route('login') }}" class="btn btn-link btn-block mt-2">Volver al acceso</a>
</form>
@endsection
