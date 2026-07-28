<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'sucursal_id',
        'name',
        'email',
        'password',
        'is_protected',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_protected' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->is_protected) {
                throw new \RuntimeException('No se puede eliminar el Superadministrador protegido.');
            }
        });

        static::forceDeleting(function (User $user) {
            if ($user->is_protected) {
                throw new \RuntimeException('No se puede eliminar físicamente el Superadministrador protegido.');
            }
        });

        static::updating(function (User $user) {
            if ($user->getOriginal('is_protected') && ! $user->is_protected) {
                throw new \RuntimeException('No se puede desproteger al Superadministrador principal.');
            }
        });
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function esSuperAdministrador(): bool
    {
        return (bool) $this->is_protected || $this->hasRole('superadmin');
    }

    public function isAdmin(): bool
    {
        return $this->esSuperAdministrador() || $this->hasRole('admin');
    }

    /**
     * Autoriza consultas de supervisión. El permiso global permite ver todas
     * las sucursales, pero nunca cambia la sucursal de una operación.
     */
    public function puedeGestionarSucursal(?int $sucursalId): bool
    {
        if (! $sucursalId) {
            return false;
        }

        return $this->can('operaciones.todas-sucursales')
            || ((int) $this->sucursal_id > 0 && (int) $this->sucursal_id === $sucursalId);
    }

    /**
     * Compras, salidas, ventas, cotizaciones y caja siempre usan la sucursal
     * asignada al usuario. También se verifica directamente que siga activa.
     */
    public function puedeOperarSucursal(?int $sucursalId): bool
    {
        if (! $sucursalId || ! $this->sucursal_id || (int) $this->sucursal_id !== (int) $sucursalId) {
            return false;
        }

        return Sucursal::query()
            ->whereKey($this->sucursal_id)
            ->where('activa', true)
            ->exists();
    }

    public function tieneSucursalOperativa(): bool
    {
        if (! $this->sucursal_id) {
            return false;
        }

        return Sucursal::query()
            ->whereKey($this->sucursal_id)
            ->where('activa', true)
            ->exists();
    }

    public function sucursalOperativa(): ?Sucursal
    {
        if (! $this->sucursal_id) {
            return null;
        }

        return Sucursal::query()
            ->whereKey($this->sucursal_id)
            ->where('activa', true)
            ->first();
    }

    public function isProtected(): bool
    {
        return (bool) $this->is_protected;
    }
}
