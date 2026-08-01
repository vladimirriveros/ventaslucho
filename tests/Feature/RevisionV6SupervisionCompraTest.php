<?php

namespace Tests\Feature;

use App\Livewire\Admin\Compras\ItemsCompra;
use App\Livewire\Admin\Ventas\ItemsCaja;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\AlertaInventarioService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisionV6SupervisionCompraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_los_productos_del_seeder_nacen_inactivos_y_no_generan_alertas(): void
    {
        $usuario = User::where('email', 'abc@abc.com')->firstOrFail();

        $this->assertGreaterThan(0, Producto::count());
        $this->assertSame(0, Producto::where('estado', true)->count());

        $resumen = app(AlertaInventarioService::class)->resumen($usuario);
        $this->assertSame(0, $resumen['stock_bajo']);
        $this->assertFalse($resumen['alerta']);
    }

    public function test_el_buscador_de_compras_encuentra_productos_inactivos_y_los_agrega(): void
    {
        $usuario = User::where('email', 'admin@admin.com')->firstOrFail();
        $producto = Producto::firstOrFail();
        $compra = Compra::create([
            'proveedor_id' => Proveedor::firstOrFail()->id,
            'sucursal_id' => $usuario->sucursal_id,
            'user_id' => $usuario->id,
            'fecha' => now()->toDateString(),
            'total' => 0,
            'estado' => 'pendiente',
            'observaciones' => null,
        ]);

        Livewire::actingAs($usuario)
            ->test(ItemsCompra::class, ['compra' => $compra])
            ->set('busqueda_producto', $producto->codigo)
            ->assertSet('productos_filtrados.0.id', $producto->id)
            ->call('seleccionarProductoYAgregar', $producto->id)
            ->assertHasNoErrors()
            ->assertSet('carrito.0.producto_id', $producto->id);
    }

    public function test_el_superadministrador_observa_compras_globales_pero_no_puede_operarlas(): void
    {
        $superadmin = User::where('email', 'vlavlavlariver@gmail.com')->firstOrFail();
        $usuarioNorte = User::where('email', 'almacen.norte@demo.com')->firstOrFail();
        $sucursalNorte = Sucursal::findOrFail($usuarioNorte->sucursal_id);
        $compra = Compra::create([
            'proveedor_id' => Proveedor::firstOrFail()->id,
            'sucursal_id' => $sucursalNorte->id,
            'user_id' => $usuarioNorte->id,
            'fecha' => now()->toDateString(),
            'total' => 0,
            'estado' => 'pendiente',
            'observaciones' => 'Compra visible para supervisión',
        ]);

        $this->assertTrue($superadmin->can('compras.index'));
        $this->assertTrue($superadmin->can('compras.show'));
        $this->assertFalse($superadmin->can('compras.create'));
        $this->assertFalse($superadmin->can('compras.store'));
        $this->assertFalse($superadmin->can('compras.edit'));
        $this->assertFalse($superadmin->can('compras.destroy'));
        $this->assertTrue($superadmin->can('user.create'));
        $this->assertTrue($superadmin->can('user.assign-roles'));
        $this->assertTrue($superadmin->can('reportes.ventas'));

        $this->actingAs($superadmin)->get(route('compras.index'))->assertOk();
        $this->actingAs($superadmin)->get(route('compras.show', $compra->id))->assertOk();
        $this->actingAs($superadmin)->get(route('compras.create'))->assertForbidden();
        $this->actingAs($superadmin)->get(route('compras.edit', $compra->id))->assertForbidden();
        $this->actingAs($superadmin)->delete(route('compras.destroy', $compra->id))->assertForbidden();
    }
    public function test_el_superadministrador_puede_filtrar_cajas_por_sucursal_sin_operarlas(): void
    {
        $superadmin = User::where('email', 'vlavlavlariver@gmail.com')->firstOrFail();
        $sucursalNorte = Sucursal::where('nombre', 'like', '%Norte%')->firstOrFail();

        $this->assertTrue($superadmin->can('caja.index'));
        $this->assertTrue($superadmin->can('caja.reportes'));
        $this->assertFalse($superadmin->can('caja.apertura'));
        $this->assertFalse($superadmin->can('caja.cierre'));
        $this->assertFalse($superadmin->can('caja.movimientos'));

        Livewire::actingAs($superadmin)
            ->test(ItemsCaja::class)
            ->set('sucursal_id', $sucursalNorte->id)
            ->assertSet('sucursal_id', $sucursalNorte->id);
    }

}
