<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoInvitadoPortafolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_muestra_acceso_de_invitado(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Ingresar como invitado');
    }

    public function test_invitado_ingresa_sin_usuario_ni_contrasena_y_es_solo_lectura(): void
    {
        $guest = User::where('email', config('demo.guest_email'))->firstOrFail();

        $this->assertTrue($guest->hasRole('invitado'));
        $this->assertTrue($guest->can('ventas.index'));
        $this->assertTrue($guest->can('compras.index'));
        $this->assertTrue($guest->can('operaciones.todas-sucursales'));
        $this->assertFalse($guest->can('ventas.create'));
        $this->assertFalse($guest->can('compras.create'));
        $this->assertFalse($guest->can('caja.apertura'));
        $this->assertFalse($guest->can('user.index'));
        $this->assertFalse($guest->can('bancas.index'));

        $this->post(route('guest.login'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($guest);

        $this->get(route('ventas.index'))->assertOk();
        $this->get(route('compras.index'))->assertOk();
        $this->get(route('ventas.create'))->assertForbidden();
        $this->get(route('compras.create'))->assertForbidden();
        $this->get(route('password.change'))->assertForbidden();
    }
}
