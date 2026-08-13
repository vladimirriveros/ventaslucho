<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoGuestSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalId = Sucursal::query()
            ->where('activa', true)
            ->orderBy('id')
            ->value('id');

        if (! $sucursalId) {
            $this->command?->warn('No se creó el usuario invitado porque no existe una sucursal activa.');
            return;
        }

        $email = (string) config('demo.guest_email', 'invitado@demo.local');

        $guest = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Invitado · Portafolio',
                'sucursal_id' => $sucursalId,
                // No se publica ni utiliza esta contraseña: el ingreso se hace
                // exclusivamente mediante el botón "Ingresar como invitado".
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
                'is_protected' => false,
            ]
        );

        if ($guest->trashed()) {
            $guest->restore();
        }

        $guest->syncRoles(['invitado']);

        $this->command?->info('✅ Usuario invitado para portafolio configurado.');
    }
}
