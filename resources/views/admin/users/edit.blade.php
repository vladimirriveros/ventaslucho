@extends('layouts.admin')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">Usuario</a></li>
            <li class="breadcrumb-item active" aria-current="page">Editar Usuario</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-7">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><b>Editar Usuario</b></h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- ALERTA PARA SUPER ADMIN --}}
                        @if ($user->is_protected)
                            <div class="alert alert-warning alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Atención!</h5>
                                Este es un <strong>Super Administrador</strong> y tiene permisos globales.
                                @if ($user->id === auth()->id())
                                    <br><span class="text-danger">⚠️ Estás editando tu propio usuario.</span>
                                @endif
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-12">
                                {{-- NOMBRE DEL USUARIO --}}
                                <div class="form-group">
                                    <label for="name">Nombre del Usuario <b style="color: red">(*)</b></label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-check"></i></span>
                                        </div>
                                        <input type="text" value="{{ old('name', $user->name) }}"
                                            class="form-control @error('name') is-invalid @enderror" id="name"
                                            name="name" required
                                            {{ $user->is_protected && $user->id !== auth()->id() ? 'disabled' : '' }}>
                                    </div>
                                    @error('name')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- CORREO ELECTRÓNICO --}}
                                <div class="form-group">
                                    <label for="email">Correo Electrónico <b style="color: red">(*)</b></label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" value="{{ old('email', $user->email) }}"
                                            class="form-control @error('email') is-invalid @enderror" id="email"
                                            name="email" required
                                            {{ $user->is_protected && $user->id !== auth()->id() ? 'disabled' : '' }}>
                                    </div>
                                    @error('email')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>

                                @if ($user->is_protected && $user->id === auth()->id())
                                {{-- CONTRASEÑA ACTUAL - CAMPO VACÍO --}}
                                {{-- CONTRASEÑA ACTUAL - CAMPO VACÍO --}}
                                <div class="form-group">
                                    <label for="current_password">Contraseña Actual <b style="color: red">(*)</b></label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        </div>
                                        <input type="password"
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            id="current_password" name="current_password"
                                            placeholder="Ingrese su contraseña actual para confirmar los cambios"
                                            autocomplete="new-password" value="" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button"
                                                data-target="current_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @error('current_password')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                    <small class="text-muted text-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Requerido: Debe ingresar su contraseña actual para guardar cualquier cambio
                                    </small>
                                </div>

                                @endif

                                @unless ($user->is_protected)
                                    <div class="form-group">
                                        <label for="sucursal_id">Sucursal asignada <b class="text-danger">(*)</b></label>
                                        <select name="sucursal_id" id="sucursal_id" class="form-control @error('sucursal_id') is-invalid @enderror" required>
                                            <option value="">Seleccione una sucursal</option>
                                            @foreach ($sucursales as $sucursal)
                                                <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $user->sucursal_id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">La sucursal determina dónde puede comprar, vender, retirar stock, cotizar y abrir caja este usuario.</small>
                                        @error('sucursal_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endunless

                                <div class="form-group">
                                    <label for="roles">
                                        Seleccionar Roles
                                        @if(!$user->is_protected)
                                            <b style="color: red">(*)</b>
                                        @endif
                                    </label>

                                    @if($user->is_protected)
                                        {{-- Mensaje para Super Admin --}}
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            El Super Administrador tiene todos los permisos por defecto.
                                            No es necesario asignarle roles específicos.
                                        </div>

                                        {{-- Mostrar roles actuales solo como información --}}
                                        @if($user->roles->isNotEmpty())
                                            <div class="mb-3">
                                                <strong>Roles actuales:</strong>
                                                @foreach($user->roles as $role)
                                                    <span class="badge badge-primary">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Input hidden para mantener los roles actuales --}}
                                        @foreach($user->roles as $role)
                                            <input type="hidden" name="roles[]" value="{{ $role->id }}">
                                        @endforeach
                                    @else
                                        {{-- Checkboxes normales para usuarios no protegidos --}}
                                        <div class="mb-3">
                                            @foreach($roles as $role)
                                                <div class="form-check">
                                                    <input type="checkbox"
                                                           class="form-check-input @error('roles') is-invalid @enderror"
                                                           id="role{{ $role->id }}"
                                                           name="roles[]"
                                                           value="{{ $role->id }}"
                                                           {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="role{{ $role->id }}">
                                                        {{ $role->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @error('roles')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>


                                {{-- ESTADO DE PROTECCIÓN --}}
                                @if ($user->is_protected)
                                    <div class="form-group">
                                        <label>Estado de Protección</label>
                                        <div class="mb-3">
                                            <span class="badge badge-danger">
                                                <i class="fas fa-shield-alt"></i> SUPER ADMIN PROTEGIDO
                                            </span>
                                            <input type="hidden" name="is_protected" value="1">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ route('user.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-success"
                                    {{ $user->is_protected && $user->id !== auth()->id() ? 'disabled' : '' }}>
                                    <i class="fas fa-save"></i>
                                    @if ($user->is_protected && $user->id === auth()->id())
                                        Actualizar mi perfil
                                    @elseif($user->is_protected)
                                        Ver Solo (Solo lectura)
                                    @else
                                        Actualizar Usuario
                                    @endif
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Función para mostrar/ocultar contraseñas
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (input) {
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            });
        });
    </script>
@stop
