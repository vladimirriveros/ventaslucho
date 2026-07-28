<?php

namespace App\Livewire\Admin\Bancas;

use App\Models\Banca;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ItemsBanca extends Component
{
    use WithFileUploads;

    public ?Banca $banca = null;
    public ?int $banca_id = null;
    public string $banco = '';
    public string $numero_cuenta = '';
    public string $nombre = '';
    public $qr_code_temp = null;
    public bool $activa = true;

    public bool $mostrar_modal_carga = false;
    public float $monto_carga = 0;
    public string $referencia_carga = '';
    public string $observaciones_carga = '';

    protected $listeners = ['procesarCarga' => 'procesarCarga'];

    public function mount($banca = null): void
    {
        if (!$banca) {
            return;
        }

        $this->banca = $banca;
        $this->banca_id = (int) $banca->id;
        $this->banco = (string) $banca->banco;
        $this->numero_cuenta = (string) $banca->numero_cuenta;
        $this->nombre = (string) $banca->nombre;
        $this->activa = (bool) $banca->activa;
    }

    protected function rules(): array
    {
        return [
            'banco' => ['required', 'string', 'max:100'],
            'numero_cuenta' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:120'],
            'qr_code_temp' => ['nullable', 'image', 'max:3072'],
            'activa' => ['boolean'],
        ];
    }

    public function guardar()
    {
        abort_unless(Auth::user()->can($this->banca ? 'bancas.edit' : 'bancas.create'), 403);
        $this->validate();

        $data = [
            'banco' => mb_strtoupper(trim($this->banco)),
            'numero_cuenta' => trim($this->numero_cuenta),
            'nombre' => mb_strtoupper(trim($this->nombre)),
            'activa' => $this->activa,
        ];

        if ($this->qr_code_temp) {
            $data['qr_code'] = $this->qr_code_temp->store('bancas/qr', 'public');
            if ($this->banca?->qr_code) {
                Storage::disk('public')->delete($this->banca->qr_code);
            }
        }

        if ($this->banca) {
            $this->banca->update($data);
            session()->flash('success', 'Cuenta bancaria actualizada correctamente.');
        } else {
            $this->banca = Banca::create($data);
            $this->banca_id = (int) $this->banca->id;
            session()->flash('success', 'Cuenta bancaria creada correctamente.');
        }

        return redirect()->route('bancas.index');
    }

    public function eliminarQR(): void
    {
        abort_unless(Auth::user()->can('bancas.edit'), 403);
        if (!$this->banca?->qr_code) {
            return;
        }

        Storage::disk('public')->delete($this->banca->qr_code);
        $this->banca->update(['qr_code' => null]);
        $this->banca->refresh();
        $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Código QR eliminado.');
    }

    public function abrirModalCarga(): void
    {
        abort_unless(Auth::user()->can('bancas.cargar'), 403);
        $this->reset(['monto_carga', 'referencia_carga', 'observaciones_carga']);
        $this->mostrar_modal_carga = true;
    }

    public function cerrarModalCarga(): void
    {
        $this->mostrar_modal_carga = false;
    }

    public function confirmarCarga(): void
    {
        abort_unless(Auth::user()->can('bancas.cargar'), 403);
        $this->validate(['monto_carga' => ['required', 'numeric', 'min:0.01']]);
        $this->procesarCarga([
            'banca_id' => $this->banca_id,
            'monto' => $this->monto_carga,
            'referencia' => $this->referencia_carga,
            'observaciones' => $this->observaciones_carga,
        ]);
    }

    public function procesarCarga($data = null): void
    {
        abort_unless(Auth::user()->can('bancas.cargar'), 403);
        $data = is_array($data) ? $data : [];
        $banca = Banca::findOrFail((int) ($data['banca_id'] ?? $this->banca_id));
        $monto = round((float) ($data['monto'] ?? $this->monto_carga), 2);
        if ($monto <= 0) {
            $this->dispatch('mostrar-alerta', icono: 'warning', mensaje: 'Ingrese un monto válido.');
            return;
        }

        try {
            $banca->registrarMovimiento('carga', $monto, Auth::id(), null,
                $data['referencia'] ?? null, $data['observaciones'] ?? null);
            $this->banca = $banca->fresh();
            $this->cerrarModalCarga();
            $this->dispatch('mostrar-alerta', icono: 'success', mensaje: 'Depósito registrado. Nuevo saldo: Bs ' . number_format((float) $this->banca->saldo_actual, 2));
        } catch (\Throwable $e) {
            $this->dispatch('mostrar-alerta', icono: 'error', mensaje: 'No se registró el depósito: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.bancas.items-banca');
    }
}
