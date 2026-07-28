@extends('layouts.auth')
@section('title', 'Nueva contraseña')
@section('content')
<div class="auth-card-header"><span class="auth-kicker">Seguridad</span><h2>Nueva contraseña</h2><p>Define una contraseña segura para tu cuenta.</p></div>
<form method="POST" action="{{ route('password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="form-group"><label for="email">Correo</label><input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="password">Nueva contraseña</label><input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="password-confirm">Confirmar contraseña</label><input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password"></div>
    <button type="submit" class="btn btn-primary btn-block auth-submit">Guardar contraseña</button>
</form>
@endsection
