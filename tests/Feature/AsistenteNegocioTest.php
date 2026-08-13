<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenteNegocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_operativo_no_puede_consultar_otra_sucursal_manipulando_el_request(): void
    {
        $propia = Sucursal::create([
            'nombre' => 'Sucursal Propia',
            'direccion' => 'Dirección A',
            'activa' => true,
        ]);
        $otra = Sucursal::create([
            'nombre' => 'Sucursal Ajena',
            'direccion' => 'Dirección B',
            'activa' => true,
        ]);

        $user = User::create([
            'name' => 'Usuario de prueba',
            'email' => 'asistente@test.local',
            'password' => 'secret123',
            'sucursal_id' => $propia->id,
            'is_protected' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('asistente.consultar'), [
                'mensaje' => '¿Cuántos productos tenemos?',
                'sucursal_id' => $otra->id,
            ])
            ->assertOk()
            ->assertJsonPath('scope', 'Sucursal Propia');
    }

    public function test_asistente_responde_ayuda_sin_modificar_datos(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Demo',
            'direccion' => 'Dirección Demo',
            'activa' => true,
        ]);

        $user = User::create([
            'name' => 'Invitado prueba',
            'email' => 'ayuda@test.local',
            'password' => 'secret123',
            'sucursal_id' => $sucursal->id,
            'is_protected' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('asistente.consultar'), ['mensaje' => 'Ayuda'])
            ->assertOk()
            ->assertJsonPath('scope', 'Sucursal Demo')
            ->assertJsonStructure(['reply', 'scope', 'suggestions', 'table']);
    }
}
