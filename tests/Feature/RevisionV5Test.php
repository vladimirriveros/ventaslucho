<?php

namespace Tests\Feature;

use App\Livewire\Admin\Ventas\ItemsCotizacion;
use App\Livewire\Admin\Ventas\ItemsVenta;
use App\Models\Producto;
use App\Models\ProductoSucursal;
use App\Models\User;
use App\Services\AlertaInventarioService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisionV5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_existen_dos_superadministradores_protegidos(): void
    {
        $superadministradores = User::role('superadmin')->where('is_protected', true)->get();

        $this->assertCount(2, $superadministradores);
        $this->assertTrue($superadministradores->contains('email', 'vlavlavlariver@gmail.com'));
        $this->assertTrue($superadministradores->contains('email', 'desarrollador@conserdei.com'));
        $this->assertTrue($superadministradores->every(fn (User $user) => $user->tieneSucursalOperativa()));
    }

    public function test_las_alertas_incluyen_productos_sin_stock_y_las_rutas_antiguas_no_muestran_json(): void
    {
        $usuario = User::where('email', 'abc@abc.com')->firstOrFail();
        $this->assertSame(0, Producto::where('estado', true)->count());

        $resumenInicial = app(AlertaInventarioService::class)->resumen($usuario);
        $this->assertSame(0, $resumenInicial['stock_bajo']);
        $this->assertFalse($resumenInicial['alerta']);

        $producto = Producto::firstOrFail();
        $producto->update(['estado' => true]);
        ProductoSucursal::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $usuario->sucursal_id,
            'activo' => true,
        ]);
        $resumen = app(AlertaInventarioService::class)->resumen($usuario);

        $this->assertSame(1, $resumen['stock_bajo']);
        $this->assertTrue($resumen['alerta']);

        $this->actingAs($usuario)
            ->get(route('alerta.stock'))
            ->assertRedirect(route('alertas.index', ['seccion' => 'stock']));

        $this->actingAs($usuario)
            ->get(route('alerta.lotes-por-vencer'))
            ->assertRedirect(route('alertas.index', ['seccion' => 'lotes']));

        $this->actingAs($usuario)
            ->get(route('alertas.index'))
            ->assertOk();
    }

    public function test_la_rebaja_de_venta_funciona_aunque_el_metodo_sea_qr(): void
    {
        $usuario = User::where('email', 'abc@abc.com')->firstOrFail();

        Livewire::actingAs($usuario)
            ->test(ItemsVenta::class)
            ->set('carrito', [[
                'producto_id' => Producto::firstOrFail()->id,
                'cantidad' => 1,
                'precio_unitario' => 100,
                'subtotal' => 100,
            ]])
            ->call('calcularTotal')
            ->set('metodo_pago', 'qr')
            ->call('actualizarTotalFinal', 90)
            ->assertSet('subtotalVenta', 100.0)
            ->assertSet('descuento_monto', 10.0)
            ->assertSet('totalVenta', 90.0)
            ->assertSet('nuevo_total', 90.0);
    }

    public function test_la_rebaja_de_cotizacion_calcula_subtotal_descuento_y_total(): void
    {
        $usuario = User::where('email', 'abc@abc.com')->firstOrFail();

        Livewire::actingAs($usuario)
            ->test(ItemsCotizacion::class)
            ->set('carrito', [[
                'producto_id' => Producto::firstOrFail()->id,
                'cantidad' => 2,
                'precio_unitario' => 50,
                'subtotal' => 100,
            ]])
            ->call('calcularTotal')
            ->call('actualizarTotalCotizacion', 85)
            ->assertSet('subtotalCotizacion', 100.0)
            ->assertSet('descuentoCotizacion', 15.0)
            ->assertSet('totalCotizacion', 85.0)
            ->assertSet('nuevoTotalCotizacion', 85.0);
    }

    public function test_al_recalcular_una_venta_en_efectivo_se_copia_el_total_a_efectivo_recibido(): void
    {
        $usuario = User::where('email', 'abc@abc.com')->firstOrFail();

        Livewire::actingAs($usuario)
            ->test(ItemsVenta::class)
            ->set('carrito', [[
                'producto_id' => Producto::firstOrFail()->id,
                'cantidad' => 1,
                'precio_unitario' => 120,
                'subtotal' => 120,
            ]])
            ->set('metodo_pago', 'efectivo')
            ->call('calcularTotal')
            ->call('actualizarTotalFinal', 105)
            ->assertSet('totalVenta', 105.0)
            ->assertSet('efectivo_recibido', 105.0);
    }
}
