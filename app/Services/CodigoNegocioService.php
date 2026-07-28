<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class CodigoNegocioService
{
    public function siguiente(string $clave, string $prefijo, int $longitud = 6): string
    {
        return DB::transaction(function () use ($clave, $prefijo, $longitud) {
            $secuencia = DB::table('business_sequences')
                ->where('clave', $clave)
                ->lockForUpdate()
                ->first();

            if (! $secuencia) {
                throw new RuntimeException("No existe la secuencia de negocio {$clave}. Ejecute las migraciones pendientes.");
            }

            $numero = (int) $secuencia->ultimo_numero + 1;
            DB::table('business_sequences')
                ->where('clave', $clave)
                ->update([
                    'ultimo_numero' => $numero,
                    'updated_at' => now(),
                ]);

            return $prefijo.str_pad((string) $numero, $longitud, '0', STR_PAD_LEFT);
        }, 3);
    }
}
