@extends('layouts.auth')
@section('title', 'Registro deshabilitado')
@section('content')
<div class="auth-card-header"><span class="auth-kicker">Acceso controlado</span><h2>Registro deshabilitado</h2><p>Los usuarios deben ser creados por un administrador.</p></div>
<a href="{{ route('login') }}" class="btn btn-primary btn-block">Volver al acceso</a>
@endsection
