@extends('layouts.auth')
@section('title', 'Confirmar contraseña')
@section('content')
<div class="auth-card-header"><span class="auth-kicker">Verificación</span><h2>Confirma tu contraseña</h2><p>Esta acción requiere validar nuevamente tu identidad.</p></div>
<form method="POST" action="{{ route('password.confirm') }}">@csrf
    <div class="form-group"><label for="password">Contraseña</label><input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <button type="submit" class="btn btn-primary btn-block auth-submit">Confirmar</button>
</form>
@endsection
