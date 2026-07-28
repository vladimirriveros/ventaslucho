<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $actor = Auth::user();

        $users = User::withTrashed()
            ->with(['roles', 'sucursal'])
            ->when(!$actor->esSuperAdministrador(), function ($query) use ($actor) {
                abort_unless($actor->tieneSucursalOperativa(), 403, 'Su usuario no tiene una sucursal activa asignada.');

                $query->where('sucursal_id', $actor->sucursal_id)
                    ->where('is_protected', false)
                    ->whereDoesntHave('roles', fn ($roles) => $roles->whereIn('name', ['superadmin', 'admin']));
            })
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->autorizarSuperadmin();

        $roles = $this->rolesAsignablesPorSuperadmin();
        $sucursales = Sucursal::where('activa', true)->orderBy('nombre')->get();

        return view('admin.users.create', compact('roles', 'sucursales'));
    }

    public function store(Request $request)
    {
        $this->autorizarSuperadmin();

        $rolesPermitidos = $this->rolesAsignablesPorSuperadmin()->pluck('id')->all();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursals', 'id')->where('activa', true)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::in($rolesPermitidos)],
        ], [
            'sucursal_id.required' => 'Debe asignar una sucursal al usuario.',
            'roles.*.in' => 'Uno de los roles seleccionados no está permitido.',
        ]);

        $this->validarCombinacionRoles($validated['roles']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'sucursal_id' => $validated['sucursal_id'],
        ]);
        $user->roles()->sync($validated['roles']);

        return redirect()->route('user.index')
            ->with('mensaje', 'Usuario creado con su sucursal y roles asignados.')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        $this->autorizarSuperadmin();

        $user = User::withTrashed()->with(['roles', 'sucursal'])->findOrFail($id);
        $roles = $this->rolesAsignablesPorSuperadmin();
        $sucursales = Sucursal::where('activa', true)->orderBy('nombre')->get();

        return view('admin.users.edit', compact('user', 'roles', 'sucursales'));
    }

    public function update(Request $request, $id)
    {
        $this->autorizarSuperadmin();

        $user = User::withTrashed()->findOrFail($id);

        if ($user->is_protected) {
            abort_unless($user->id === Auth::id(), 403, 'No puede modificar al Superadministrador principal.');

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'current_password' => ['required', 'string'],
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.'])->withInput();
            }

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            return redirect()->route('user.index')
                ->with('mensaje', 'Perfil del Superadministrador actualizado.')
                ->with('icono', 'success');
        }

        $rolesPermitidos = $this->rolesAsignablesPorSuperadmin()->pluck('id')->all();
        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::in($rolesPermitidos)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursals', 'id')->where('activa', true)],
        ], [
            'sucursal_id.required' => 'Debe asignar una sucursal al usuario.',
            'roles.*.in' => 'Uno de los roles seleccionados no está permitido.',
        ]);

        $this->validarCombinacionRoles($validated['roles']);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'sucursal_id' => $validated['sucursal_id'],
        ]);
        $user->roles()->sync($validated['roles']);

        return redirect()->route('user.index')
            ->with('mensaje', 'Usuario actualizado correctamente.')
            ->with('icono', 'success');
    }

    public function asignar(Request $request, $id)
    {
        return $this->asignarRoles($request, $id);
    }

    public function destroy($id)
    {
        $this->autorizarSuperadmin();

        $usuario = User::findOrFail($id);
        abort_if($usuario->is_protected, 403, 'No se puede eliminar al Superadministrador.');
        abort_if($usuario->id === Auth::id(), 403, 'No puede eliminar su propio usuario.');

        $usuario->delete();

        return redirect()->route('user.index')
            ->with('mensaje', 'Usuario eliminado correctamente.')
            ->with('icono', 'success');
    }

    public function restaurar($id)
    {
        $this->autorizarSuperadmin();

        $usuario = User::withTrashed()->findOrFail($id);
        abort_if($usuario->is_protected, 403, 'El Superadministrador no puede encontrarse eliminado.');
        $usuario->restore();

        return redirect()->route('user.index')
            ->with('mensaje', 'Usuario restaurado correctamente.')
            ->with('icono', 'success');
    }

    public function forceDelete($id)
    {
        $this->autorizarSuperadmin();

        $usuario = User::withTrashed()->findOrFail($id);
        abort_if($usuario->is_protected, 403, 'No se puede eliminar permanentemente al Superadministrador.');
        $usuario->forceDelete();

        return redirect()->route('user.index')
            ->with('mensaje', 'Usuario eliminado permanentemente.')
            ->with('icono', 'success');
    }

    public function getRoles($id)
    {
        $actor = Auth::user();
        $user = User::withTrashed()->with(['roles', 'sucursal'])->findOrFail($id);
        $this->autorizarAsignacionRoles($actor, $user);

        $roles = $actor->esSuperAdministrador()
            ? $this->rolesAsignablesPorSuperadmin()
            : $this->rolesAsignablesPorAdmin();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'sucursal' => $user->sucursal?->nombre,
            ],
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])->values(),
            'userRoles' => $user->roles->pluck('id')->values()->all(),
        ]);
    }

    public function asignarRoles(Request $request, $id)
    {
        $actor = Auth::user();
        $user = User::withTrashed()->findOrFail($id);
        $this->autorizarAsignacionRoles($actor, $user);

        $rolesPermitidos = ($actor->esSuperAdministrador()
            ? $this->rolesAsignablesPorSuperadmin()
            : $this->rolesAsignablesPorAdmin())
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::in($rolesPermitidos)],
        ], [
            'roles.*.in' => 'No puede asignar uno de los roles seleccionados.',
        ]);

        $this->validarCombinacionRoles($validated['roles']);

        if (!$user->tieneSucursalOperativa()) {
            throw ValidationException::withMessages([
                'sucursal_id' => 'El Superadministrador debe asignar primero una sucursal activa a este usuario.',
            ]);
        }

        $user->roles()->sync($validated['roles']);

        return redirect()->route('user.index')
            ->with('mensaje', 'Perfiles operativos asignados correctamente.')
            ->with('icono', 'success');
    }

    private function validarCombinacionRoles(array $roleIds): void
    {
        $nombres = Role::query()->whereIn('id', $roleIds)->pluck('name');

        if ($nombres->contains('admin') && $nombres->count() > 1) {
            throw ValidationException::withMessages([
                'roles' => 'El perfil administrador no debe combinarse con perfiles operativos.',
            ]);
        }
    }

    private function autorizarSuperadmin(): void
    {
        abort_unless(Auth::user()?->esSuperAdministrador(), 403, 'Solo el Superadministrador puede realizar esta acción.');
    }

    private function autorizarAsignacionRoles(User $actor, User $objetivo): void
    {
        abort_if($objetivo->is_protected || $objetivo->hasRole('superadmin'), 403, 'No se pueden modificar los roles del Superadministrador.');

        if ($actor->esSuperAdministrador()) {
            return;
        }

        abort_unless($actor->hasRole('admin'), 403, 'Solo un administrador puede asignar roles.');
        abort_if($objetivo->hasRole('admin'), 403, 'Un administrador no puede modificar los roles de otro administrador.');
        abort_unless($actor->tieneSucursalOperativa(), 403, 'Su usuario no tiene una sucursal activa asignada.');
        abort_if($actor->id === $objetivo->id, 403, 'No puede modificar sus propios roles.');
        abort_unless(
            (int) $actor->sucursal_id === (int) $objetivo->sucursal_id,
            403,
            'Solo puede administrar usuarios de su propia sucursal.'
        );
    }

    private function rolesAsignablesPorSuperadmin()
    {
        return Role::query()
            ->where('name', '!=', 'superadmin')
            ->orderBy('name')
            ->get();
    }

    private function rolesAsignablesPorAdmin()
    {
        // El administrador de sucursal solo distribuye perfiles operativos
        // previamente definidos por el sistema. Los roles administrativos o
        // personalizados quedan bajo control exclusivo del Superadministrador.
        return Role::query()
            ->whereIn('name', ['vendedor', 'cajero', 'almacen'])
            ->orderBy('name')
            ->get();
    }
}
