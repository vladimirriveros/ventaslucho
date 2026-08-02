<?php

namespace Tests\Feature;

use App\Livewire\Admin\Ventas\ItemsCotizacion;
use App\Models\Producto;
use App\Models\ProductoSucursal;
use App\Models\User;
use App\Services\AlertaInventarioService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisionV7SucursalCotizacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_producto_solo_genera_alerta_en_la_sucursal_que_lo_maneja(): void
    {
        $usuarioPrincipal = User::where('email', 'abc@abc.com')->firstOrFail();
        $usuarioNorte = User::where('email', 'vendedor.norte@demo.com')->firstOrFail();
        $producto = Producto::firstOrFail();
        $producto->update(['estado' => true, 'stock_minimo' => 3]);

        ProductoSucursal::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $usuarioPrincipal->sucursal_id,
            'activo' => true,
        ]);

        $principal = app(AlertaInventarioService::class)->resumen($usuarioPrincipal);
        $norte = app(AlertaInventarioService::class)->resumen($usuarioNorte);

        $this->assertSame(1, $principal['stock_bajo']);
        $this->assertSame(0, $norte['stock_bajo']);
    }

    public function test_cotizacion_busca_y_agrega_productos_inactivos_y_sin_stock(): void
    {
        $usuario = User::where('email', 'abc@abc.com')->firstOrFail();
        $producto = Producto::where('estado', false)->firstOrFail();

        Livewire::actingAs($usuario)
            ->test(ItemsCotizacion::class)
            ->set('busqueda_producto', $producto->codigo)
            ->assertSet('productos_filtrados.0.id', $producto->id)
            ->call('seleccionarProducto', $producto->id)
            ->call('agregarAlCarrito')
            ->assertHasNoErrors()
            ->assertSet('carrito.0.producto_id', $producto->id)
            ->assertSet('carrito.0.sin_stock', true);
    }
}
