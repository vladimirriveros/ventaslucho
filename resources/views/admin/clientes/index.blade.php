@extends('layouts.admin')

@section('title', 'Clientes')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-address-book mr-2"></i>Clientes</h1>
            <p class="text-muted mb-0">Datos comerciales, crédito y saldo pendiente.</p>
        </div>
        @can('clientes.create')
            <a href="{{ route('clientes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nuevo cliente
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>NIT</th>
                            <th>Teléfono</th>
                            <th>Tipo</th>
                            <th class="text-right">Límite</th>
                            <th class="text-right">Pendiente</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                            <tr>
                                <td><strong>{{ $cliente->nombre }}</strong></td>
                                <td>{{ $cliente->nit ?? '—' }}</td>
                                <td>{{ $cliente->telefono ?? '—' }}</td>
                                <td><span class="badge badge-{{ $cliente->tipo === 'credito' ? 'warning' : 'info' }}">{{ $cliente->tipo === 'credito' ? 'Crédito' : 'Regular' }}</span></td>
                                <td class="text-right">Bs {{ number_format((float) $cliente->limite_credito, 2) }}</td>
                                <td class="text-right">Bs {{ number_format((float) $cliente->saldo_pendiente, 2) }}</td>
                                <td><span class="badge badge-{{ $cliente->activo ? 'success' : 'secondary' }}">{{ $cliente->activo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-right">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can('clientes.show')
                                            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-info" title="Ver"><i class="fas fa-eye"></i></a>
                                        @endcan
                                        @can('clientes.edit')
                                            <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button type="button" class="btn btn-{{ $cliente->activo ? 'secondary' : 'success' }}"
                                                onclick="confirmarCambioEstado({{ $cliente->id }}, {{ Js::from($cliente->activo ? 'desactivar' : 'activar') }})"
                                                title="{{ $cliente->activo ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas fa-{{ $cliente->activo ? 'ban' : 'check' }}"></i>
                                            </button>
                                            <form id="form-estado-{{ $cliente->id }}" action="{{ route('clientes.toggle-activo', $cliente->id) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                        @endcan
                                        @can('clientes.destroy')
                                            <button type="button" class="btn btn-danger"
                                                onclick="confirmarEliminacion({{ $cliente->id }}, {{ Js::from($cliente->nombre) }})"
                                                title="Eliminar"><i class="fas fa-trash"></i></button>
                                            <form id="form-eliminar-{{ $cliente->id }}" action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-5 text-muted">No hay clientes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($clientes->hasPages())
            <div class="card-footer">{{ $clientes->links() }}</div>
        @endif
    </div>
@endsection

@push('js')
<script>
    function confirmarCambioEstado(id, accion) {
        const activar = accion === 'activar';
        Swal.fire({
            title: `${activar ? 'Activar' : 'Desactivar'} cliente`,
            text: `El cliente quedará ${activar ? 'disponible' : 'inhabilitado'} para nuevas operaciones.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `Sí, ${accion}`,
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) document.getElementById(`form-estado-${id}`).submit();
        });
    }

    function confirmarEliminacion(id, nombre) {
        Swal.fire({
            title: 'Eliminar cliente',
            text: `¿Eliminar a “${nombre}”? Esta acción solo es posible si no tiene ventas.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) document.getElementById(`form-eliminar-${id}`).submit();
        });
    }
</script>
@endpush
