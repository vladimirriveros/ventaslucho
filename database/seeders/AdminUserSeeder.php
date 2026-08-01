<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalPrincipalId = Sucursal::query()
            ->where('activa', true)
            ->orderBy('id')
            ->value('id');

        $superadministradores = [
            [
                'email' => 'vlavlavlariver@gmail.com',
                'name' => 'Superadministrador Propietario',
                'password' => '@dmin123',
            ],
            [
                'email' => 'desarrollador@conserdei.com',
                'name' => 'Superadministrador Desarrollador',
                'password' => '@dev12345',
            ],
        ];

        foreach ($superadministradores as $datos) {
            $superadmin = User::withTrashed()->updateOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'sucursal_id' => $sucursalPrincipalId,
                    'password' => Hash::make($datos['password']),
                    'email_verified_at' => now(),
                    'is_protected' => true,
                ]
            );

            if ($superadmin->trashed()) {
                $superadmin->restore();
            }

            $superadmin->syncRoles(['superadmin']);
        }

        $this->command?->info('✅ Dos superadministradores protegidos fueron creados: propietario y desarrollador.');
    }
}
