<?php

namespace App\Livewire\Admin\Ventas;

use App\Models\Cotizacion;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ListaCotizaciones extends Component
{
    use WithPagination;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $estado = '';
    public $sucursal_id = '';
    public $perPage = 10;

    protected $queryString = ['search', 'fecha_desde', 'fecha_hasta', 'estado', 'sucursal_id'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'fecha_desde', 'fecha_hasta', 'estado', 'sucursal_id']);
        $this->resetPage();
    }

    public function anularCotizacion($id)
    {
        if (!Auth::user()->can('cotizaciones.destroy')) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No tiene permiso para anular cotizaciones.');
            return;
        }
        $cotizacion = Cotizacion::findOrFail($id);
        if (!Auth::user()->puedeGestionarSucursal((int) $cotizacion->sucursal_id)) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'La cotización pertenece a otra sucursal.');
            return;
        }
        if ($cotizacion->estado === 'convertida') {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se puede anular una cotización convertida.');
            return;
        }
        $cotizacion->update(['estado' => 'anulada']);
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: "Cotización {$cotizacion->codigo} anulada.");
    }

    public function render()
    {
        Cotizacion::where('estado', 'activa')->whereDate('valida_hasta', '<', today())->update(['estado' => 'vencida']);

        $query = Cotizacion::with(['cliente', 'user', 'sucursal']);
        $user = Auth::user();
        if (!$user->can('operaciones.todas-sucursales')) {
            if ($user->sucursal_id) {
                $query->where('sucursal_id', $user->sucursal_id);
                $this->sucursal_id = (string) $user->sucursal_id;
            } else {
                $query->whereRaw('1 = 0');
                $this->sucursal_id = '';
            }
        }

        // Filtro por búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $q->where('codigo', 'LIKE', "%{$this->search}%")
                  ->orWhereHas('cliente', function($cq) {
                      $cq->where('nombre', 'LIKE', "%{$this->search}%")
                         ->orWhere('nit', 'LIKE', "%{$this->search}%");
                  });
            });
        }

        // Filtro por fechas
        if ($this->fecha_desde) {
            $query->whereDate('fecha', '>=', $this->fecha_desde);
        }
        if ($this->fecha_hasta) {
            $query->whereDate('fecha', '<=', $this->fecha_hasta);
        }

        // Filtro por estado
        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        // Filtro por sucursal (solo el usuario global puede escogerla).
        if ($user->can('operaciones.todas-sucursales') && $this->sucursal_id) {
            $query->where('sucursal_id', $this->sucursal_id);
        }

        $cotizaciones = $query->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.ventas.lista-cotizaciones', [
            'cotizaciones' => $cotizaciones
        ]);
    }
}
