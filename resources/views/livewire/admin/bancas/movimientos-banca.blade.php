<div>
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title">
                        <i class="fas fa-university"></i> {{ $banca->nombre }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">Saldo: Bs {{ number_format($banca->saldo_actual, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history"></i> Historial de Movimientos
            </h3>
            <div class="card-tools">
                <a href="{{ route('bancas.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="date" wire:model.live="fecha_desde" class="form-control" placeholder="Fecha Desde">
                </div>
                <div class="col-md-3">
                    <input type="date" wire:model.live="fecha_hasta" class="form-control" placeholder="Fecha Hasta">
                </div>
                <div class="col-md-3">
                    <select wire:model.live="tipo" class="form-control">
                        <option value="">Todos los tipos</option>
                        <option value="carga">Carga</option>
                        <option value="retiro">Retiro</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-secondary btn-block" wire:click="limpiarFiltros">
                        <i class="fas fa-undo"></i> Limpiar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th class="text-right">Monto</th>
                            <th class="text-right">Saldo Anterior</th>
                            <th class="text-right">Saldo Nuevo</th>
                            <th>Referencia</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $mov)
                            <tr>
                                <td class="text-center">{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    @if($mov->tipo == 'carga')
                                        <span class="badge badge-success">CARGA</span>
                                    @elseif($mov->tipo == 'retiro')
                                        <span class="badge badge-warning">RETIRO</span>
                                    @else
                                        <span class="badge badge-info">AJUSTE</span>
                                    @endif
                                </td>
                                <td class="text-right {{ $mov->tipo == 'carga' ? 'text-success' : 'text-danger' }}">
                                    {{ $mov->tipo == 'carga' ? '+' : '-' }} Bs {{ number_format($mov->monto, 2) }}
                                </td>
                                <td class="text-right">Bs {{ number_format($mov->saldo_anterior, 2) }}</td>
                                <td class="text-right">Bs {{ number_format($mov->saldo_nuevo, 2) }}</td>
                                <td>{{ $mov->referencia ?? '-' }}</td>
                                <td>{{ $mov->user->name }}</td>
                                <td>{{ $mov->observaciones ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay movimientos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $movimientos->links() }}
            </div>
        </div>
    </div>
</div>
