<?php

namespace Tests\Feature;

use App\Livewire\Admin\Compras\ItemsCompra;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultisucursalCompraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_compra_usa_la_sucursal_del_usuario_y_no_la_del_formulario(): void
    {
        $usuario = User::where('email', 'admin@admin.com')->firstOrFail();
        $otraSucursal = Sucursal::where('id', '!=', $usuario->sucursal_id)->firstOrFail();
        $proveedor = Proveedor::firstOrFail();

        $respuesta = $this->actingAs($usuario)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Compra de prueba',
            // Debe ser ignorado aunque se intente manipular desde el navegador.
            'sucursal_id' => $otraSucursal->id,
        ]);

        $compra = Compra::latest('id')->firstOrFail();

        $respuesta->assertRedirect(route('compras.edit', ['id' => $compra->id]));
        $this->assertSame((int) $usuario->sucursal_id, (int) $compra->sucursal_id);
        $this->assertNotSame((int) $otraSucursal->id, (int) $compra->sucursal_id);
    }

    public function test_un_producto_puede_agregarse_al_carrito_de_una_compra_autorizada(): void
    {
        $usuario = User::where('email', 'admin@admin.com')->firstOrFail();
        $compra = Compra::create([
            'proveedor_id' => Proveedor::firstOrFail()->id,
            'sucursal_id' => $usuario->sucursal_id,
            'user_id' => $usuario->id,
            'fecha' => now()->toDateString(),
            'total' => 0,
            'estado' => 'pendiente',
            'observaciones' => null,
        ]);
        $producto = Producto::where('estado', true)->firstOrFail();

        Livewire::actingAs($usuario)
            ->test(ItemsCompra::class, ['compra' => $compra])
            ->set('productoId', $producto->id)
            ->call('agregarAlCarrito')
            ->assertHasNoErrors()
            ->assertSet('sucursal_id', $usuario->sucursal_id)
            ->assertSet('carrito.0.producto_id', $producto->id)
            ->assertSet('carrito.0.cantidad', 1);
    }

    public function test_un_usuario_no_puede_modificar_compras_de_otra_sucursal(): void
    {
        $usuarioPrincipal = User::where('email', 'admin@admin.com')->firstOrFail();
        $usuarioNorte = User::where('email', 'almacen.norte@demo.com')->firstOrFail();
        $compra = Compra::create([
            'proveedor_id' => Proveedor::firstOrFail()->id,
            'sucursal_id' => $usuarioPrincipal->sucursal_id,
            'user_id' => $usuarioPrincipal->id,
            'fecha' => now()->toDateString(),
            'total' => 0,
            'estado' => 'pendiente',
            'observaciones' => null,
        ]);

        $this->actingAs($usuarioNorte)
            ->get(route('compras.edit', ['id' => $compra->id]))
            ->assertForbidden();
    }

    public function test_solo_superadmin_crea_usuarios_y_admin_asigna_perfiles_de_su_sucursal(): void
    {
        $superadmin = User::where('email', 'vlavlavlariver@gmail.com')->firstOrFail();
        $admin = User::where('email', 'admin@admin.com')->firstOrFail();
        $usuarioPrincipal = User::where('email', 'abc@abc.com')->firstOrFail();
        $usuarioOtraSucursal = User::where('email', 'vendedor.norte@demo.com')->firstOrFail();
        $rolCajero = Role::where('name', 'cajero')->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('user.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('user.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('user.asignar-roles', $usuarioPrincipal->id), [
                'roles' => [$rolCajero->id],
            ])
            ->assertRedirect(route('user.index'));

        $this->assertTrue($usuarioPrincipal->fresh()->hasRole('cajero'));

        $this->actingAs($admin)
            ->post(route('user.asignar-roles', $usuarioOtraSucursal->id), [
                'roles' => [$rolCajero->id],
            ])
            ->assertForbidden();
    }
}
