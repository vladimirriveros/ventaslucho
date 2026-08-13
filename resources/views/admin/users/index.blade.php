@extends('layouts.admin')

@section('title', 'Usuarios')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">Usuarios</a></li>
            <li class="breadcrumb-item active" aria-current="page">Listado de Usuarios</li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b>Usuarios registrados</b></h3>
                    @can('user.create')
                        <div class="card-tools">
                            <a class="btn btn-primary" href="{{ route('user.create') }}">
                                <i class="fas fa-plus"></i> Crear nuevo
                            </a>
                        </div>
                    @endcan
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="mostrar-todos">
                                    <i class="fas fa-users"></i> Todos
                                </button>
                                <button type="button" class="btn btn-outline-success" id="mostrar-activos">
                                    <i class="fas fa-user-check"></i> Activos
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="mostrar-eliminados">
                                    <i class="fas fa-user-slash"></i> Eliminados
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="mostrar-protegidos">
                                    <i class="fas fa-shield-alt"></i> Protegidos
                                </button>
                            </div>
                        </div>
                    </div>

                    <table id="example1" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Nro</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Sucursal</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="{{ $user->trashed() ? 'table-danger' : ($user->is_protected ? 'table-primary' : '') }}">
                                    <td style="text-align: center">{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $user->name }}
                                        @if ($user->is_protected)
                                            <span class="badge badge-danger" data-toggle="tooltip"
                                                title="Administrador protegido - No se puede eliminar">
                                                <i class="fas fa-shield-alt"></i> PROTEGIDO
                                            </span>
                                        @elseif($user->hasRole('invitado'))
                                            <span class="badge badge-info" data-toggle="tooltip" title="Cuenta pública de demostración · solo lectura">
                                                <i class="fas fa-eye"></i> DEMO
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if ($user->roles->isEmpty())
                                            <span class="badge badge-warning">Sin roles</span>
                                        @else
                                            @foreach ($user->roles as $role)
                                                <span class="badge badge-primary">{{ $role->name }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->sucursal)
                                            <span class="badge badge-info"><i class="fas fa-store"></i> {{ $user->sucursal->nombre }}</span>
                                        @else
                                            <span class="badge badge-warning">Sin sucursal</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center">
                                        @if ($user->trashed())
                                            <span class="badge badge-danger">
                                                <i class="fas fa-user-slash"></i> Eliminado
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                <i class="fas fa-user-check"></i> Activo
                                            </span>
                                        @endif
                                    </td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            @if ($user->trashed())
                                                @can('user.destroy')
                                                    <form action="{{ route('user.restaurar', $user->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-success" onclick="confirmarRestauracion(event, this)" title="Restaurar">
                                                            <i class="fas fa-trash-restore"></i>
                                                        </button>
                                                    </form>

                                                    @if (!$user->is_protected)
                                                        <form action="{{ route('user.forceDelete', $user->id) }}" method="POST" class="d-inline" id="form-force-delete-{{ $user->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-danger" onclick="confirmarEliminacionPermanente({{ $user->id }})" title="Eliminar permanentemente">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            @else
                                                @can('user.assign-roles')
                                                    <button type="button" class="btn btn-info"
                                                        onclick="abrirModalRoles({{ $user->id }})"
                                                        @if ($user->is_protected || $user->hasRole('invitado') || (!auth()->user()->esSuperAdministrador() && $user->id === auth()->id())) disabled @endif
                                                        title="Asignar perfiles operativos">
                                                        <i class="fas fa-tags"></i>
                                                    </button>
                                                @endcan

                                                @can('user.update')
                                                    @unless($user->hasRole('invitado'))
                                                        <a href="{{ route('user.edit', $user->id) }}" class="btn btn-success" title="Editar usuario y sucursal">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endunless
                                                @endcan

                                                @can('user.destroy')
                                                    @if (!$user->is_protected && !$user->hasRole('invitado') && $user->id !== auth()->id())
                                                        <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="d-inline" id="form-eliminar-{{ $user->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-danger" onclick="confirmarEliminacion({{ $user->id }})" title="Eliminar usuario">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA ASIGNAR ROLES -->
    <div class="modal fade" id="modalRoles" tabindex="-1" role="dialog" aria-labelledby="modalRolesLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="modalRolesLabel">
                        <i class="fas fa-user-tag"></i> Asignar perfiles operativos
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formAsignarRoles" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Usuario:</label>
                            <p class="form-control-static mb-1" id="usuarioNombre"></p>
                            <small class="text-muted"><i class="fas fa-store"></i> <span id="usuarioSucursal"></span></small>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Seleccionar perfiles:</label>
                            <div id="rolesCheckboxes" class="mt-2">
                                <!-- Los roles se cargarán vía AJAX -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Guardar perfiles
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        #example1_wrapper .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-default {
            background-color: #6e7176;
            color: #212529;
            border: none;
        }

        .table-danger {
            background-color: #f8d7da !important;
        }

        .table-primary {
            background-color: #cfe2ff !important;
        }

        .table-danger td {
            color: #721c24;
        }

        .table-primary td {
            color: #084298;
        }

        .badge {
            font-size: 0.8rem;
            padding: 5px 8px;
        }

        .badge.badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn[disabled] {
            cursor: not-allowed;
            opacity: 0.65;
        }

        /* Estilo para los checkboxes de roles */
        .role-checkbox {
            margin-bottom: 8px;
        }

        .role-checkbox input {
            margin-right: 10px;
            transform: scale(1.1);
            vertical-align: middle;
        }

        .role-checkbox label {
            margin-bottom: 0;
            cursor: pointer;
            vertical-align: middle;
        }

        .roles-container {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Inicializar DataTable
            $("#example1").DataTable({
                "pageLength": 5,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Usuarios",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Usuarios",
                    "infoFiltered": "(Filtrado de _MAX_ total Usuarios)",
                    "lengthMenu": "Mostrar _MENU_ Usuarios",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "order": [
                    [0, 'asc']
                ],
                "columnDefs": [{
                        "orderable": false,
                        "targets": 6
                    }
                ]
            });
        });

        let usuarioIdActual = null;

        // Función para abrir el modal de roles
        function abrirModalRoles(userId) {
            usuarioIdActual = userId;

            // Mostrar loading
            Swal.fire({
                title: 'Cargando...',
                text: 'Obteniendo información del usuario',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Obtener los roles del usuario vía AJAX
            $.ajax({
                url: '/admin/users/' + userId + '/roles',
                method: 'GET',
                success: function(response) {
                    Swal.close();

                    // Mostrar usuario y sucursal fija de operación
                    $('#usuarioNombre').text(response.user.name);
                    $('#usuarioSucursal').text(response.user.sucursal || 'Sin sucursal activa');

                    // Generar checkboxes de roles
                    let html = '<div class="roles-container">';
                    response.roles.forEach(function(role) {
                        let isChecked = response.userRoles.includes(role.id);
                        html += `
                            <div class="role-checkbox">
                                <input type="checkbox"
                                       name="roles[]"
                                       value="${role.id}"
                                       id="role_${role.id}"
                                       ${isChecked ? 'checked' : ''}>
                                <label for="role_${role.id}" class="badge ${isChecked ? 'badge-primary' : 'badge-secondary'}">
                                    ${role.name}
                                </label>
                            </div>
                        `;
                    });
                    html += '</div>';

                    $('#rolesCheckboxes').html(html);

                    // Configurar el action del formulario
                    $('#formAsignarRoles').attr('action', '/admin/users/' + userId + '/asignar-roles');

                    // Abrir el modal
                    $('#modalRoles').modal('show');
                },
                error: function(xhr) {
                    Swal.close();
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudieron cargar los roles del usuario',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }

        // Enviar formulario de asignación de roles
        $('#formAsignarRoles').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let url = form.attr('action');
            let roles = [];

            // Obtener roles seleccionados
            $('input[name="roles[]"]:checked').each(function() {
                roles.push($(this).val());
            });

            Swal.fire({
                title: '¿Guardar cambios?',
                text: 'Se asignarán los perfiles seleccionados al usuario de esta sucursal',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Guardando...',
                        text: 'Asignando roles al usuario',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar petición AJAX
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            roles: roles
                        },
                        success: function(response) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: 'Roles asignados correctamente',
                                icon: 'success',
                                confirmButtonColor: '#17a2b8',
                                timer: 2000
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            let errorMsg = 'Error al asignar roles';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                title: 'Error',
                                text: errorMsg,
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });

        // Limpiar modal al cerrarlo
        $('#modalRoles').on('hidden.bs.modal', function() {
            $('#rolesCheckboxes').empty();
            $('#usuarioNombre').text('');
            $('#usuarioSucursal').text('');
            usuarioIdActual = null;
        });

        // Filtros de la tabla
        let filtroActual = {
            estado: '',
            protegido: ''
        };

        $('#mostrar-todos').click(function() {
            var table = $('#example1').DataTable();
            table.column(5).search('').draw();
            table.column(1).search('').draw();
            filtroActual.estado = '';
            filtroActual.protegido = '';
        });

        $('#mostrar-activos').click(function() {
            var table = $('#example1').DataTable();
            table.column(5).search('Activo').draw();
            filtroActual.estado = 'Activo';
        });

        $('#mostrar-eliminados').click(function() {
            var table = $('#example1').DataTable();
            table.column(5).search('Eliminado').draw();
            filtroActual.estado = 'Eliminado';
        });

        $('#mostrar-protegidos').click(function() {
            var table = $('#example1').DataTable();
            if (filtroActual.protegido === 'PROTEGIDO') {
                table.column(1).search('').draw();
                filtroActual.protegido = '';
            } else {
                table.column(1).search('PROTEGIDO', true, false).draw();
                filtroActual.protegido = 'PROTEGIDO';
            }
        });

        // Funciones de confirmación
        function confirmarEliminacion(userId) {
            Swal.fire({
                title: "¿Desea eliminar este usuario?",
                text: "El usuario se moverá a la papelera y podrá ser restaurado después",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("form-eliminar-" + userId).submit();
                }
            });
        }

        function confirmarRestauracion(event, element) {
            event.preventDefault();
            Swal.fire({
                title: "¿Desea restaurar este usuario?",
                text: "El usuario volverá a estar activo en el sistema",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, restaurar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    element.closest('form').submit();
                }
            });
        }

        function confirmarEliminacionPermanente(userId) {
            Swal.fire({
                title: "¿Eliminar permanentemente?",
                text: "Esta acción no se puede deshacer. El usuario se eliminará definitivamente",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Sí, eliminar permanentemente",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("form-force-delete-" + userId).submit();
                }
            });
        }

        // Mensajes flash
        @if (session('mensaje') && session('icono') == 'error')
            Swal.fire({
                title: "Error",
                text: "{{ session('mensaje') }}",
                icon: "error",
                confirmButtonColor: "#3085d6"
            });
        @endif

        @if (session('mensaje') && session('icono') == 'success')
            Swal.fire({
                title: "Éxito",
                text: "{{ session('mensaje') }}",
                icon: "success",
                confirmButtonColor: "#3085d6",
                timer: 3000
            });
        @endif
    </script>
@stop
