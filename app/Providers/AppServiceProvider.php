<?php

namespace App\Providers;

use App\Models\Sucursal;
use App\Services\AlertaInventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $alertas = [
                'alerta' => false,
                'total' => 0,
                'stock_bajo' => 0,
                'lotes_por_vencer' => 0,
                'lotes_vencidos' => 0,
                'dias_vencimiento' => AlertaInventarioService::DIAS_VENCIMIENTO,
                'alcance' => 'Sucursal asignada',
            ];

            try {
                if ($user = Auth::user()) {
                    $alertas = app(AlertaInventarioService::class)->resumen($user);
                }
            } catch (Throwable) {
                // Durante instalación o migraciones la interfaz debe seguir cargando.
            }

            $asistenteSucursales = collect();
            $asistenteAccesoGlobal = false;

            try {
                if ($user = Auth::user()) {
                    $asistenteAccesoGlobal = ! $user->hasRole('invitado') && $user->can('operaciones.todas-sucursales');
                    $asistenteSucursales = $asistenteAccesoGlobal
                        ? Sucursal::query()->select(['id', 'nombre', 'activa'])->orderBy('nombre')->get()
                        : collect();
                }
            } catch (Throwable) {
                // La instalación inicial no debe fallar si las tablas aún no existen.
            }

            $view->with([
                'alertasSistema' => $alertas,
                'asistenteSucursales' => $asistenteSucursales,
                'asistenteAccesoGlobal' => $asistenteAccesoGlobal,
            ]);
        });
    }
}
