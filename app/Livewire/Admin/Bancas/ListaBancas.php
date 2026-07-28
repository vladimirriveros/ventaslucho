<?php

namespace App\Livewire\Admin\Bancas;

use App\Models\Banca;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ListaBancas extends Component
{
    use WithPagination;

    public $search = '';
    public $estado = '';
    public $perPage = 10;

    // Propiedades para el modal de carga
public $mostrar_modal_carga = false;
public $banca_carga_id = null;
public $banca_carga_nombre = null;
public $banca_carga_saldo = 0;
public $monto_carga = 0;
public $referencia_carga = '';
public $observaciones_carga = '';

    protected $queryString = ['search', 'estado'];

    protected $listeners = [
        'procesarCarga' => 'procesarCarga',
    ];

    // Método para abrir modal de carga
    public function abrirModalCarga($id)
    {
        abort_unless(Auth::user()->can('bancas.cargar'), 403);
        $banca = Banca::findOrFail($id);
        $this->banca_carga_id = $banca->id;
        $this->banca_carga_nombre = $banca->nombre;
        $this->banca_carga_saldo = $banca->saldo_actual;
        $this->monto_carga = 0;
        $this->referencia_carga = '';
        $this->observaciones_carga = '';
        $this->mostrar_modal_carga = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function eliminar($id)
{
    abort_unless(Auth::user()->can('bancas.destroy'), 403);
    $banca = Banca::findOrFail($id);

    // Verificar si tiene movimientos
    if ($banca->movimientos()->count() > 0) {
        $this->dispatch('mostrar-alerta', [
            'icono' => 'error',
            'titulo' => 'Error',
            'text' => 'No se puede eliminar la cuenta porque tiene movimientos registrados.'
        ]);
        return;
    }

    $banca->delete();

    $this->dispatch('mostrar-alerta', [
        'icono' => 'success',
        'titulo' => 'Éxito',
        'text' => 'Cuenta bancaria eliminada exitosamente.'
    ]);
}

public function toggleActiva($id)
{
    abort_unless(Auth::user()->can('bancas.edit'), 403);
    $banca = Banca::findOrFail($id);
    $banca->activa = !$banca->activa;
    $banca->save();

    $this->dispatch('mostrar-alerta', [
        'icono' => 'success',
        'titulo' => 'Éxito',
        'text' => 'Estado de la banca actualizado'
    ]);
}

    public function limpiarFiltros()
    {
        $this->reset(['search', 'estado']);
        $this->resetPage();
    }



    public function render()
    {
        $query = Banca::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nombre', 'LIKE', "%{$this->search}%")
                  ->orWhere('banco', 'LIKE', "%{$this->search}%")
                  ->orWhere('numero_cuenta', 'LIKE', "%{$this->search}%");
            });
        }

        if ($this->estado !== '') {
            $query->where('activa', $this->estado == 'activa');
        }

        $bancas = $query->orderBy('banco')->orderBy('nombre')->paginate($this->perPage);

        return view('livewire.admin.bancas.lista-bancas', [
            'bancas' => $bancas
        ]);
    }

    // Método para cerrar modal
    public function cerrarModalCarga()
    {
        $this->mostrar_modal_carga = false;
        $this->reset(['banca_carga_id', 'banca_carga_nombre', 'banca_carga_saldo', 'monto_carga', 'referencia_carga', 'observaciones_carga']);
    }

    public function confirmarCarga()
{
    abort_unless(Auth::user()->can('bancas.cargar'), 403);
    $this->validate([
        'monto_carga' => 'required|numeric|min:0.01',
    ]);

    // Disparar evento de confirmación con SweetAlert2
    $this->dispatch('mostrar-confirmacion-carga', [
        'banca_id' => $this->banca_carga_id,
        'banca_nombre' => $this->banca_carga_nombre,
        'monto' => $this->monto_carga,
        'referencia' => $this->referencia_carga,
        'observaciones' => $this->observaciones_carga
    ]);
}
    // Método para procesar la carga
    public function procesarCarga($data)
    {
        abort_unless(Auth::user()->can('bancas.cargar'), 403);

        try {
            if (!is_array($data) || empty($data['banca_id']) || !is_numeric($data['monto'] ?? null) || (float) $data['monto'] <= 0) {
                throw new \InvalidArgumentException('Los datos del depósito no son válidos.');
            }
            $banca = Banca::findOrFail((int) $data['banca_id']);

            $movimiento = $banca->registrarMovimiento(
                'carga',
                $data['monto'],
                Auth::id(),
                null,
                $data['referencia'] ?? null,
                $data['observaciones'] ?? null
            );


            // Recargar la banca para obtener el saldo actualizado
            $banca->refresh();
            $nuevoSaldo = $banca->saldo_actual;

            $this->cerrarModalCarga();

            $this->dispatch('mostrar-alerta', [
                'icono' => 'success',
                'titulo' => '✅ Carga Exitosa',
                'text' => "Se ha cargado Bs " . number_format($data['monto'], 2) . " a la cuenta {$banca->nombre}.\n\nNuevo saldo: Bs " . number_format($nuevoSaldo, 2)
            ]);


        } catch (\Exception $e) {
            $this->dispatch('mostrar-alerta', [
                'icono' => 'error',
                'titulo' => '❌ Error',
                'text' => 'Error al cargar dinero: ' . $e->getMessage()
            ]);
        }
    }
}
