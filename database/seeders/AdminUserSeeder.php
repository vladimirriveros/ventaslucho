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

        $superadmin = User::withTrashed()->updateOrCreate(
            ['email' => 'vlavlavlariver@gmail.com'],
            [
                'name' => 'Super Administrador',
                'sucursal_id' => $sucursalPrincipalId,
                'password' => Hash::make('@dmin123'),
                'email_verified_at' => now(),
                'is_protected' => true,
            ]
        );

        $superadmin->syncRoles(['superadmin']);

        if ($superadmin->trashed()) {
            $superadmin->restore();
        }

        $this->command?->info('✅ Superadministrador protegido creado y asignado a la sucursal principal.');
    }
}
