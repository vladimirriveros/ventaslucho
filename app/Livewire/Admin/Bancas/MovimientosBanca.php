<?php

namespace App\Livewire\Admin\Bancas;

use Illuminate\Support\Facades\Auth;
use App\Models\Banca;
use Livewire\Component;
use Livewire\WithPagination;

class MovimientosBanca extends Component
{
    use WithPagination;

    public $banca_id;
    public $banca;
    public $fecha_desde;
    public $fecha_hasta;
    public $tipo = '';

    protected $queryString = ['fecha_desde', 'fecha_hasta', 'tipo'];

    public function mount($bancaId)
    {
        abort_unless(Auth::user()->can('bancas.movimientos'), 403);
        $this->banca_id = $bancaId;
        $this->banca = Banca::findOrFail($bancaId);
    }

    public function limpiarFiltros()
    {
        $this->reset(['fecha_desde', 'fecha_hasta', 'tipo']);
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->banca->movimientos()->with('user');

        if ($this->fecha_desde) {
            $query->whereDate('fecha', '>=', $this->fecha_desde);
        }
        if ($this->fecha_hasta) {
            $query->whereDate('fecha', '<=', $this->fecha_hasta);
        }
        if ($this->tipo) {
            $query->where('tipo', $this->tipo);
        }

        $movimientos = $query->orderBy('fecha', 'desc')->paginate(20);

        return view('livewire.admin.bancas.movimientos-banca', [
            'movimientos' => $movimientos
        ]);
    }
}
