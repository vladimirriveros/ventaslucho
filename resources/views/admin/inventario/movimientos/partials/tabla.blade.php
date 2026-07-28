<table id="example1" class="table table-striped table-bordered table-hover table-sm">
    <thead>
        <tr>
            <th>Nro</th>
            <th>Tipo de movimiento</th>
            <th>Lote</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Sucursal</th>
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Observación</th>
        </tr>
    </thead>
    <tbody id="tabla-movimientos-body">
        @forelse ($movimientos as $movimiento)
            <tr>
                <td style="text-align: center">{{$loop->iteration}}</td>
                <td>
                    @if($movimiento->tipo_movimiento == 'Entrada')
                        <span class="badge badge-success">Entrada</span>
                    @elseif($movimiento->tipo_movimiento == 'Salida')
                        <span class="badge badge-danger">Salida</span>
                    @else
                        {{ $movimiento->tipo_movimiento }}
                    @endif
                </td>
                <td>{{ $movimiento->lote->codigo_lote ?? 'N/A' }}</td>
                <td>{{ $movimiento->producto->nombre ?? 'N/A' }}</td>
                <td style="text-align: center">{{ $movimiento->cantidad }}</td>
                <td>{{ $movimiento->sucursal->nombre ?? 'N/A' }}</td>
                <<td>
                    @if(isset($movimiento->usuario))
                        {{ $movimiento->usuario->name }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($movimiento->fecha)->format('Y-m-d H:i') }}</td>
                <td style="max-width: 300px; white-space: normal;">
                    @if(str_contains($movimiento->observaciones, 'ELIMINACIÓN POR CADUCIDAD'))
                        <span class="badge badge-warning">CADUCIDAD</span>
                        <br>
                        <small>{{ $movimiento->observaciones }}</small>
                    @else
                        {{ $movimiento->observaciones }}
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center">No hay movimientos que coincidan con los filtros</td>
            </tr>
        @endforelse
    </tbody>
</table>
