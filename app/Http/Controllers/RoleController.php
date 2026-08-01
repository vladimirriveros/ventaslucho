<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles|max:255'
        ]);

        $rol = new Role();
        $rol->name = $request->name;
        $rol->guard_name = 'web';
        $rol->save();

        return redirect()->route('roles.index')
            ->with('mensaje', 'El rol se ha creado exitosamente.')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        $rol = Role::findOrFail($id);
        return view('admin.roles.edit', compact('rol'));
    }

    public function update(Request $request, $id)
    {
        $rol = Role::findOrFail($id);

        // 🔒 PROTECCIÓN: No permitir cambiar el nombre del rol admin
        if ($rol->name === 'superadmin' && $request->name !== 'superadmin') {
            return redirect()->route('roles.index')
                ->with('mensaje', '❌ No se puede cambiar el nombre del rol "superadmin".')
                ->with('icono', 'error');
        }

        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
        ]);

        $rol->name = $request->name;
        // $rol->guard_name = 'web';
        $rol->save();

        return redirect()->route('roles.index')
            ->with('mensaje', 'El rol se ha actualizado exitosamente.')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $rol = Role::findOrFail($id);

            // 🔒 PROTECCIÓN: No permitir eliminar el rol admin
            if (in_array($rol->name, ['superadmin', 'admin', 'vendedor', 'cajero', 'almacen'], true)) {
                return redirect()->route('roles.index')
                    ->with('mensaje', '❌ No se pueden eliminar los roles predeterminados del sistema.')
                    ->with('icono', 'error');
            }

            // Verificar si hay usuarios con este rol
            $usuariosConRol = $rol->users()->count();
            if ($usuariosConRol > 0) {
                return redirect()->route('roles.index')
                    ->with('mensaje', "❌ No se puede eliminar el rol porque tiene {$usuariosConRol} usuario(s) asignado(s).")
                    ->with('icono', 'error');
            }

            $rol->delete();

            return redirect()->route('roles.index')
                ->with('mensaje', 'El rol se ha eliminado exitosamente.')
                ->with('icono', 'success');

        } catch (\Exception $e) {
            return redirect()->route('roles.index')
                ->with('mensaje', 'Error al eliminar el rol: ' . $e->getMessage())
                ->with('icono', 'error');
        }
    }

    public function permisos($id)
{
    $rol = Role::findOrFail($id);

    // Obtener TODOS los permisos
    $todosLosPermisos = Permission::all();

    // Mapeo de módulos (para agrupar permisos)
    $mapaModulos = [
        'categorias' => 'Categorías',
        'sucursales' => 'Sucursales',
        'productos' => 'Productos',
        'proveedores' => 'Proveedores',
        'compras' => 'Compras',
        'inventario' => 'Inventario',
        'lotes' => 'Lotes',
        'movimientos' => 'Movimientos',
        'tipo_cambio' => 'Tipo de Cambio',
        'roles' => 'Roles',
        'user' => 'Usuarios',
        'salidas' => 'Salidas',
        'ventas' => 'Ventas',
        'cotizaciones' => 'Cotizaciones',
        'clientes' => 'Clientes',
        'caja' => 'Caja',
        'reportes' => 'Reportes',
        'bancas' => 'Bancas',
    ];

    // Lista de módulos visibles para roles no admin
    $modulosVisiblesParaNoAdmin = [
        'Categorías',
        'Sucursales',
        'Productos',
        'Proveedores',
        'Compras',
        'Inventario',
        'Lotes',
        'Movimientos',
        'Tipo de Cambio',
        'Roles',
        'Usuarios',
        'Salidas',
        'Ventas',
        'Cotizaciones',
        'Clientes',
        'Caja',
        'Reportes',
        'Bancas',
        // 'Configuración',
    ];

    // Agrupar permisos por módulo
    $permisosAgrupados = [];

    foreach ($todosLosPermisos as $permiso) {
        // Obtener el módulo (primer segmento del nombre del permiso)
        $partes = explode('.', $permiso->name);
        $moduloKey = $partes[0];

        // Buscar el nombre amigable del módulo
        $moduloNombre = $mapaModulos[$moduloKey] ?? 'Otros Permisos';

        // 👇 FILTRO: Si NO es admin, excluir "Otros Permisos"
        if ($rol->name !== 'superadmin' && $moduloNombre === 'Otros Permisos') {
            continue; // Saltar este permiso, no lo incluimos
        }

        // Agrupar el permiso en su módulo correspondiente
        if (!isset($permisosAgrupados[$moduloNombre])) {
            $permisosAgrupados[$moduloNombre] = collect();
        }
        $permisosAgrupados[$moduloNombre]->push($permiso);
    }

    // Para roles no admin, también filtrar módulos que no deberían ver
    if ($rol->name !== 'superadmin') {
        $permisosAgrupadosFiltrados = [];
        foreach ($permisosAgrupados as $modulo => $grupo) {
            if (in_array($modulo, $modulosVisiblesParaNoAdmin)) {
                $permisosAgrupadosFiltrados[$modulo] = $grupo;
            }
        }
        $permisosAgrupados = $permisosAgrupadosFiltrados;
    }

    // Ordenar los módulos alfabéticamente, pero con 'Otros Permisos' al final
    $permisosOrdenados = [];
    $otrosPermisos = null;

    foreach ($permisosAgrupados as $modulo => $grupo) {
        if ($modulo === 'Otros Permisos') {
            $otrosPermisos = $grupo;
        } else {
            $permisosOrdenados[$modulo] = $grupo;
        }
    }

    // Ordenar alfabéticamente los módulos conocidos
    ksort($permisosOrdenados);

    // Agregar 'Otros Permisos' al final solo si existe y es admin
    if ($rol->name === 'superadmin' && $otrosPermisos) {
        $permisosOrdenados['Otros Permisos'] = $otrosPermisos;
    }

    // Ordenar los permisos dentro de cada módulo alfabéticamente
    foreach ($permisosOrdenados as $modulo => $grupo) {
        $permisosOrdenados[$modulo] = $grupo->sortBy('name');
    }

    // Asignar a la variable que espera la vista
    $permisos = $permisosOrdenados;

    return view('admin.roles.permisos', compact('rol', 'permisos'));
}

    /**
     * Actualizar permisos del rol
     */
    public function update_permisos(Request $request, $id)
    {
        $rol = Role::findOrFail($id);

        // El rol Superadministrador tiene una matriz fija de supervisión.
        // No puede ampliarse con permisos operativos desde la interfaz.
        if ($rol->name === 'superadmin') {
            return redirect()->route('roles.index')
                ->with('mensaje', 'El rol Superadministrador es de supervisión y sus permisos están protegidos.')
                ->with('icono', 'info');
        }

        // Sincronizar permisos seleccionados
        $permisosSeleccionados = $request->input('permisos', []);
        $rol->permissions()->sync($permisosSeleccionados);

        $cantidadPermisos = count($permisosSeleccionados);

        return redirect()->route('roles.index')
            ->with('mensaje', "✅ Se han asignado {$cantidadPermisos} permiso(s) al rol {$rol->name}.")
            ->with('icono', 'success');
    }
}
