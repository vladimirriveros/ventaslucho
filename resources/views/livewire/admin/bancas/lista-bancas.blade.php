<div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5"><label class="form-label">Buscar</label><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Banco, cuenta o nombre"></div>
                <div class="col-7 col-md-3"><label class="form-label">Estado</label><select wire:model.live="estado" class="form-select"><option value="">Todos</option><option value="activa">Activas</option><option value="inactiva">Inactivas</option></select></div>
                <div class="col-5 col-md-2"><label class="form-label">Mostrar</label><select wire:model.live="perPage" class="form-select"><option>10</option><option>25</option><option>50</option></select></div>
                <div class="col-12 col-md-2"><button class="btn btn-outline-secondary w-100" wire:click="limpiarFiltros"><i class="fas fa-undo mr-1"></i>Limpiar</button></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="fas fa-university mr-2"></i>Cuentas bancarias</h3>
            @can('bancas.create')<a href="{{ route('bancas.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Nueva cuenta</a>@endcan
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Cuenta</th><th>Número</th><th>QR</th><th class="text-end">Saldo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @forelse($bancas as $banca)
                        <tr>
                            <td><strong>{{ $banca->nombre }}</strong><div class="small text-muted">{{ $banca->banco }}</div></td>
                            <td>{{ $banca->numero_cuenta }}</td>
                            <td>@if($banca->qr_code)<img src="{{ asset('storage/' . $banca->qr_code) }}" alt="QR" class="rounded border" style="width:52px;height:52px;object-fit:cover">@else<span class="text-muted">Sin QR</span>@endif</td>
                            <td class="text-end fw-semibold">Bs {{ number_format((float)$banca->saldo_actual, 2) }}</td>
                            <td><button class="btn btn-sm {{ $banca->activa ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="toggleActiva({{ $banca->id }})">{{ $banca->activa ? 'Activa' : 'Inactiva' }}</button></td>
                            <td class="text-end"><div class="btn-group btn-group-sm">
                                @can('bancas.movimientos')<a href="{{ route('bancas.movimientos', $banca->id) }}" class="btn btn-outline-info" title="Movimientos"><i class="fas fa-history"></i></a>@endcan
                                @can('bancas.cargar')<button class="btn btn-outline-success" wire:click="abrirModalCarga({{ $banca->id }})" title="Depósito"><i class="fas fa-plus-circle"></i></button>@endcan
                                @can('bancas.edit')<a href="{{ route('bancas.edit', $banca->id) }}" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>@endcan
                                @can('bancas.destroy')<button class="btn btn-outline-danger" wire:click="eliminar({{ $banca->id }})" wire:confirm="¿Eliminar esta cuenta?" title="Eliminar"><i class="fas fa-trash"></i></button>@endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-university fa-2x d-block mb-2"></i>No hay cuentas registradas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bancas->hasPages())<div class="card-footer">{{ $bancas->links() }}</div>@endif
    </div>

    @if ($mostrar_modal_carga)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(15,23,42,.62)">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Registrar depósito — {{ $banca_carga_nombre }}</h5><button type="button" class="btn-close" wire:click="cerrarModalCarga"></button></div>
                <div class="modal-body"><p class="alert alert-info">Saldo actual: <strong>Bs {{ number_format((float)$banca_carga_saldo, 2) }}</strong></p><label class="form-label">Monto</label><div class="input-group mb-3"><span class="input-group-text">Bs</span><input type="number" min="0.01" step="0.01" wire:model="monto_carga" class="form-control"></div><label class="form-label">Referencia</label><input wire:model="referencia_carga" class="form-control mb-3"><label class="form-label">Observaciones</label><textarea wire:model="observaciones_carga" class="form-control" rows="2"></textarea></div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" wire:click="cerrarModalCarga">Cancelar</button><button class="btn btn-success" wire:click="confirmarCarga">Registrar</button></div>
            </div></div>
        </div>
    @endif
</div>
