<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Sucursal;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\TipoCambio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Primero, llamar al seeder de Roles (necesario para asignar roles después)
        $this->call(RoleSeeder::class);

        // Crear marcas primero
        $marcas = [
            ['nombre' => 'DEWALT'],
            ['nombre' => 'MAKITA'],
            ['nombre' => 'STANLEY'],
            ['nombre' => 'TIGRE'],
            ['nombre' => 'MONOPOL'],
            ['nombre' => 'BOSCH'],
            ['nombre' => 'BLACK+DECKER'],
            ['nombre' => 'TRUPER'],
        ];

        foreach ($marcas as $marca) {
            Marca::create($marca);
        }

        // Resto de tus seeders...
        Proveedor::factory(3)->create();

        $sucursal = Sucursal::updateOrCreate(
            ['nombre' => 'Sucursal CONSERDEI'],
            [
                'direccion' => 'Zona 12 de Octubre #123',
                'telefono' => '77712345',
                'activa' => true,
            ]
        );

        $sucursalNorte = Sucursal::updateOrCreate(
            ['nombre' => 'Sucursal Norte (Demo)'],
            [
                'direccion' => 'Dirección de prueba para operaciones multisucursal',
                'telefono' => '70000002',
                'activa' => true,
            ]
        );

        // El Superadministrador se crea después de las sucursales para que toda
        // operación tenga una sucursal de trabajo asignada desde el inicio.
        $this->call(AdminUserSeeder::class);

        $categorias = [
            ['nombre' => 'HERRAMIENTAS ELECTRICAS', 'descripcion' => 'Taladros, esmeriles, sierras'],
            ['nombre' => 'HERRAMIENTAS MANUALES', 'descripcion' => 'Martillos, destornilladores, llaves'],
            ['nombre' => 'MATERIAL ELECTRICO', 'descripcion' => 'Cables, interruptores, tomacorrientes'],
            ['nombre' => 'PLOMERIA', 'descripcion' => 'Tuberías, conexiones, grifería'],
            ['nombre' => 'PINTURAS', 'descripcion' => 'Pinturas, brochas, rodillos'],
            ['nombre' => 'FERRETERIA EN GENERAL', 'descripcion' => 'Clavos, tornillos, pernos'],
        ];

        foreach ($categorias as $cat) {
            Categoria::create([
                'nombre' => $cat['nombre'],
                'descripcion' => $cat['descripcion'],
            ]);
        }

        TipoCambio::create(['id' => 1, 'precio_dolar' => 6.96, 'fecha' => now(), 'estado' => true, 'is_oficial' => true]);

        // Obtener IDs de marcas creadas
        $dewalt = Marca::where('nombre', 'DEWALT')->first();
        $makita = Marca::where('nombre', 'MAKITA')->first();
        $stanley = Marca::where('nombre', 'STANLEY')->first();
        $tigre = Marca::where('nombre', 'TIGRE')->first();
        $monopol = Marca::where('nombre', 'MONOPOL')->first();

        // Definir los productos manualmente usando marca_id
        $productosManuales = [
            [
                'nombre' => 'TALADRO PERCUTOR 1/2',
                'marca_id' => $dewalt->id,
                'codigo' => 'TP-001',
                'precio_compra' => 450.00,
                'precio_venta' => 580.00,
                'categoria_id' => 1,
            ],
            [
                'nombre' => 'ESMERIL ANGULAR 4 1/2',
                'marca_id' => $makita->id,
                'codigo' => 'EA-002',
                'precio_compra' => 380.50,
                'precio_venta' => 490.00,
                'categoria_id' => 1,
            ],
            [
                'nombre' => 'JUEGO DE DESTORNILLADORES 6PZ',
                'marca_id' => $stanley->id,
                'codigo' => 'JD-003',
                'precio_compra' => 85.00,
                'precio_venta' => 120.00,
                'categoria_id' => 2,
            ],
            [
                'nombre' => 'TUBO PVC 1/2 PULGADA',
                'marca_id' => $tigre->id,
                'codigo' => 'TV-004',
                'precio_compra' => 15.00,
                'precio_venta' => 25.00,
                'categoria_id' => 4,
            ],
            [
                'nombre' => 'PINTURA LATEX SUPREMA 1GL',
                'marca_id' => $monopol->id,
                'codigo' => 'PT-005',
                'precio_compra' => 110.00,
                'precio_venta' => 150.00,
                'categoria_id' => 5,
            ],
        ];

        foreach ($productosManuales as $prod) {
            Producto::create([
                'categoria_id'        => $prod['categoria_id'],
                'marca_id'            => $prod['marca_id'], // Usando marca_id en lugar de marca
                'codigo'              => $prod['codigo'],
                'nombre'              => $prod['nombre'],
                'descripcion'         => 'Descripción para ' . $prod['nombre'],
                'imagen'              => null,
                'precio_compra'       => $prod['precio_compra'],
                'precio_venta'        => $prod['precio_venta'],
                'porcentaje_ganancia' => 25.00,
                'stock_minimo'        => 5,
                'stock_maximo'        => 50,
                'unidad_medida'       => 'unidad',
                'estado'              => true,
            ]);
        }

        // Crear usuarios de prueba
        $userAdmin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Luis Admin',
                'sucursal_id' => $sucursal->id,
                'password' => bcrypt('123456789'),
            ]
        );
        $userAdmin->syncRoles(['admin']);

        $userVendedor = User::updateOrCreate(
            ['email' => 'abc@abc.com'],
            [
                'name' => 'Vendedor',
                'sucursal_id' => $sucursal->id,
                'password' => bcrypt('123456789'),
            ]
        );
        $userVendedor->syncRoles(['vendedor']);


        $userCajero = User::updateOrCreate(
            ['email' => 'cajero@demo.com'],
            [
                'name' => 'Cajero Sucursal Principal',
                'sucursal_id' => $sucursal->id,
                'password' => bcrypt('123456789'),
            ]
        );
        $userCajero->syncRoles(['cajero']);

        $userVendedorNorte = User::updateOrCreate(
            ['email' => 'vendedor.norte@demo.com'],
            [
                'name' => 'Vendedor Sucursal Norte',
                'sucursal_id' => $sucursalNorte->id,
                'password' => bcrypt('123456789'),
            ]
        );
        $userVendedorNorte->syncRoles(['vendedor']);

        $userAlmacenNorte = User::updateOrCreate(
            ['email' => 'almacen.norte@demo.com'],
            [
                'name' => 'Almacén Sucursal Norte',
                'sucursal_id' => $sucursalNorte->id,
                'password' => bcrypt('123456789'),
            ]
        );
        $userAlmacenNorte->syncRoles(['almacen']);
    }
}
