@extends('layouts.admin')

@section('title', 'Salida en proceso')

@section('content_header')
    <nav aria-label="breadcrumb" style="font-size: 18pt">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}">Inicio</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ url('/admin/salidas') }}">Salidas</a>
            </li>
            <li class="breadcrumb-item active">
                Salida nro {{ $salida->id }}
            </li>
        </ol>
    </nav>
    <hr>
@stop

@section('content')

    {{-- CARD 1 → DATOS DE LA SALIDA --}}

    <div class="row">
        <div class="col-md-12">

            <div class="card card-info">

                <div class="card-header">
                    <h3 class="card-title">
                        <b>Paso 1 | Salida creada</b>
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row">

                        {{-- SUCURSAL --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Sucursal</label>
                                <p>{{ $salida->sucursal->nombre }}</p>
                            </div>
                        </div>

                        {{-- FECHA --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Fecha</label>
                                <p>{{ $salida->fecha }}</p>
                            </div>
                        </div>

                        {{-- MOTIVO --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Motivo</label>
                                <p>{{ $salida->motivo }}</p>
                            </div>
                        </div>

                        {{-- OBSERVACIONES --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <p>{{ $salida->observaciones }}</p>
                            </div>
                        </div>

                        {{-- ESTADO --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Estado</label>
                                <p>{{ $salida->estado }}</p>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- CARD 2 → AGREGAR PRODUCTOS --}}

    <div class="row">
        <div class="col-md-12">

            <div class="card card-primary">

                <div class="card-header">
                    <h3 class="card-title">
                        <b>Paso 2 | Agregar productos a la salida</b>
                    </h3>
                </div>

                <div class="card-body">

                    <livewire:admin.salidas.items-salida :salida="$salida" />

                </div>
            </div>
        </div>
    </div>

    {{-- CARD 3 → FINALIZAR SALIDA --}}

    {{-- CARD 3 → FINALIZAR SALIDA --}}
    {{-- CARD 3 → FINALIZAR SALIDA --}}
    {{-- CARD 3 → FINALIZAR SALIDA --}}
    <div class="row" x-data="{
        tieneProductos: {{ $salida->detalles->count() > 0 ? 'true' : 'false' }},
        totalProductos: {{ $salida->detalles->count() }},
        totalMonto: {{ $salida->total }},
        actualizarContadores() {
            setTimeout(() => {
                const filas = document.querySelectorAll('tbody tr');
                this.tieneProductos = filas.length > 0;
                this.totalProductos = filas.length;

                const totalElement = document.querySelector('tfoot th:last-child');
                if (totalElement) {
                    const totalTexto = totalElement.innerText.replace('$', '').replace(/,/g, '');
                    this.totalMonto = parseFloat(totalTexto) || 0;
                }
            }, 100);
        }
    }" @producto-agregado.window="actualizarContadores()"
        @producto-eliminado.window="actualizarContadores()">

        <div class="col-md-12">
            {{-- <div class="card card-success"> --}}


            <div class="card-body">
                {{-- VERIFICAR ESTADO DE LA SALIDA --}}
                @if ($salida->estado == 'Entregado')
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Esta salida ya ha sido finalizada.</strong>
                    </div>
                @else
                    {{-- SALIDA PENDIENTE - MOSTRAR SEGÚN EL ESTADO DEL CARRITO --}}
                    <template x-if="tieneProductos">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Paso 3 | ¡Listo para finalizar!</strong>
                                    Tienes <strong x-text="totalProductos"></strong> producto(s) en la salida.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form action="{{ route('salidas.finalizarSalida', $salida) }}" method="POST"
                                    onsubmit="return confirmarFinalizacion(event, this)">
                                    @csrf
                                    <button class="btn btn-success btn-lg btn-block" type="submit">
                                        <i class="fas fa-check-circle"></i>
                                        FINALIZAR SALIDA
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>
                @endif
            </div>
            {{-- </div> --}}
        </div>
    </div>

@stop

@section('js')

    <script>
        function confirmarFinalizacion(event, form) {
            event.preventDefault();

            Swal.fire({
                title: '¿Finalizar salida?',
                text: 'Una vez finalizada, no podrá modificar los productos. El stock se descontará automáticamente.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Deshabilitar el botón para evitar doble envío
                    const btn = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                    // Enviar el formulario
                    form.submit();
                }
            });

            return false;
        }
    </script>
@stop
