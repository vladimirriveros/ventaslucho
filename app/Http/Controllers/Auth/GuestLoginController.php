<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless((bool) config('demo.guest_login_enabled', true), 404);

        $email = (string) config('demo.guest_email', 'invitado@demo.local');

        $guest = User::query()
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        if (! $guest || ! $guest->hasRole('invitado') || $guest->getRoleNames()->count() !== 1) {
            return redirect()
                ->route('login')
                ->with('guest_error', 'El acceso de invitado todavía no está configurado correctamente. Ejecute el seeder de demostración.');
        }

        $permitidos = collect(config('demo.guest_permissions', []));
        $permisosNoPermitidos = $guest->getAllPermissions()->pluck('name')->diff($permitidos);

        if ($permisosNoPermitidos->isNotEmpty()) {
            return redirect()
                ->route('login')
                ->with('guest_error', 'El acceso invitado fue bloqueado por seguridad porque tiene permisos de escritura. Restablezca el RoleSeeder.');
        }

        Auth::login($guest, false);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('home'))
            ->with('mensaje', 'Ingresaste en modo invitado. El sistema está disponible únicamente para consulta.')
            ->with('icono', 'info');
    }
}
