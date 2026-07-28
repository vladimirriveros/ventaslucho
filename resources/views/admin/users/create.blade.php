@extends('layouts.admin')

@section('title', 'Nuevo usuario')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item active" aria-current="page">Creación de Usuario</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-7">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b>Crear usuario y asignar sucursal</b></h3>
                    <!-- /.card-tools -->
                </div>
                <!-- /.card-header -->
                <div class="card-body" style="display: block;">
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Solo el Superadministrador crea usuarios y define su sucursal. Después, el administrador de esa sucursal puede asignar perfiles operativos como ventas, caja o almacén.
                    </div>
                    {{-- ------------------------------FORMULARIO---------------------------------------- --}}
                    {{-- -------------------------------------------------------------------------------- --}}
                    <form action=" {{ route('user.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">

                                {{-- nombre de usuario --}}
                                <div class="form-group">
                                    <label for="nombre">Nombre del usuario <b style="color: red">(*)</b></label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-check"></i></span>
                                        </div>
                                        <input type="text" value="{{ old('name') }}" class="form-control"
                                            id="name" name="name" placeholder="Ingrese el nombre usuario" required>
                                    </div>
                                    @error('name')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- //correo electronico --}}
                                <div class="form-group">
                                    <label for="email">Correo electronico <b style="color: red">(*)</b></label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-check"></i></span>
                                        </div>
                                        <input type="email" value="{{ old('email') }}" class="form-control"
                                            id="email" name="email" placeholder="Ingrese correo electronico" required>
                                    </div>
                                    @error('email')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group col-md-12">
                                <label for="sucursal_id">Sucursal asignada <b class="text-danger">(*)</b></label>
                                <select name="sucursal_id" id="sucursal_id" class="form-control @error('sucursal_id') is-invalid @enderror" required>
                                    <option value="">Seleccione una sucursal</option>
                                    @foreach ($sucursales as $sucursal)
                                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id') == $sucursal->id)>{{ $sucursal->nombre }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Todos los usuarios trabajan únicamente con la sucursal asignada por el Superadministrador.</small>
                                @error('sucursal_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group col-md-12">
                                <label>Roles <b class="text-danger">(*)</b></label>
                                <div class="row">
                                    @foreach ($roles as $role)
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input class="custom-control-input" type="checkbox" id="role_{{ $role->id }}" name="roles[]"
                                                    value="{{ $role->id }}" @checked(in_array($role->id, old('roles', [])))>
                                                <label for="role_{{ $role->id }}" class="custom-control-label text-capitalize">{{ $role->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('roles')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            {{-- contraseña --}}
                            <div class="form-group col-md-12">
                                <label for="password">Contraseña <b style="color: red">(*)</b></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Ingrese la contraseña" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                @error('password')
                                    <small style="color: red">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- confirmación de contraseña --}}
                            <div class="form-group col-md-12">
                                <label for="password_confirmation">Confirmar contraseña <b
                                        style="color: red">(*)</b></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation" placeholder="Confirme la contraseña" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="password_confirmation">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
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
            });
        });
    </script>
@stop
