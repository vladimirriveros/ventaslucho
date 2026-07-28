<div>
    <form wire:submit="guardar" class="app-form-stack">
        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card card-outline card-primary h-100">
                    <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-university mr-2"></i>Datos de la cuenta</h3></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Banco <span class="text-danger">*</span></label>
                                <input type="text" wire:model="banco" class="form-control @error('banco') is-invalid @enderror" placeholder="Ej. Banco Nacional de Bolivia">
                                @error('banco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Número de cuenta <span class="text-danger">*</span></label>
                                <input type="text" wire:model="numero_cuenta" class="form-control @error('numero_cuenta') is-invalid @enderror" placeholder="Ej. 100-0000000">
                                @error('numero_cuenta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nombre de la cuenta <span class="text-danger">*</span></label>
                                <input type="text" wire:model="nombre" class="form-control @error('nombre') is-invalid @enderror" placeholder="Nombre o titular visible">
                                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Imagen QR</label>
                                <input type="file" wire:model="qr_code_temp" class="form-control @error('qr_code_temp') is-invalid @enderror" accept="image/*">
                                @error('qr_code_temp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">JPG, PNG o WEBP. Máximo 3 MB.</small>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="activa" id="banca-activa">
                                    <label class="form-check-label" for="banca-activa">Cuenta activa y disponible para cobros</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('bancas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                            <i class="fas fa-save mr-1"></i>{{ $banca ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title mb-0">Vista previa</h3></div>
                    <div class="card-body text-center">
                        @if ($qr_code_temp)
                            <img src="{{ $qr_code_temp->temporaryUrl() }}" class="img-fluid rounded border p-2 mb-3" style="max-height:260px" alt="Vista previa QR">
                        @elseif ($banca?->qr_code)
                            <img src="{{ asset('storage/' . $banca->qr_code) }}" class="img-fluid rounded border p-2 mb-3" style="max-height:260px" alt="Código QR">
                        @else
                            <div class="empty-state py-5"><i class="fas fa-qrcode fa-4x text-muted mb-3"></i><p class="text-muted mb-0">Aún no se cargó un QR</p></div>
                        @endif

                        @if ($banca?->qr_code)
                            <button type="button" class="btn btn-sm btn-outline-danger mb-3" wire:click="eliminarQR"><i class="fas fa-trash mr-1"></i>Eliminar QR</button>
                        @endif

                        @if ($banca)
                            <hr>
                            <p class="text-muted mb-1">Saldo registrado</p>
                            <h3>Bs {{ number_format((float) $banca->saldo_actual, 2) }}</h3>
                            @can('bancas.cargar')
                                <button type="button" class="btn btn-success btn-block" wire:click="abrirModalCarga"><i class="fas fa-plus-circle mr-1"></i>Registrar depósito</button>
                            @endcan
                            <a href="{{ route('bancas.movimientos', $banca->id) }}" class="btn btn-outline-primary btn-block mt-2"><i class="fas fa-history mr-1"></i>Ver movimientos</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if ($mostrar_modal_carga)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(15,23,42,.62)">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Registrar depósito</h5><button type="button" class="btn-close" wire:click="cerrarModalCarga"></button></div>
                    <div class="modal-body">
                        <label class="form-label">Monto <span class="text-danger">*</span></label>
                        <div class="input-group mb-3"><span class="input-group-text">Bs</span><input type="number" step="0.01" min="0.01" wire:model="monto_carga" class="form-control"></div>
                        <label class="form-label">Referencia</label>
                        <input type="text" wire:model="referencia_carga" class="form-control mb-3" placeholder="Comprobante o referencia">
                        <label class="form-label">Observaciones</label>
                        <textarea wire:model="observaciones_carga" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" wire:click="cerrarModalCarga">Cancelar</button><button type="button" class="btn btn-success" wire:click="confirmarCarga">Registrar</button></div>
                </div>
            </div>
        </div>
    @endif
</div>
