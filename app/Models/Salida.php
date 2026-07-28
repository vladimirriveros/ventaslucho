<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    protected $table = 'salidas';

    protected $fillable = [
        'sucursal_id',
        'user_id',
        'fecha',
        'motivo',
        'total',
        'estado',
        'observaciones',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleSalida::class, 'salida_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id'); // Asegúrate de que 'user_id' sea el usuario correcto
    }
}
