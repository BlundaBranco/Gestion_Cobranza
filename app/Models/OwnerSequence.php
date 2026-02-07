<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerSequence extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'current_value'];

    /**
     * Obtiene el Owner (Socio) al que pertenece esta secuencia.
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Incrementa atómicamente la secuencia y devuelve el nuevo valor.
     * Usa lockForUpdate() para evitar duplicados en concurrencia.
     *
     * IMPORTANTE: Debe llamarse dentro de una transacción DB.
     */
    public static function getNextValue(int $ownerId): int
    {
        $sequence = self::lockForUpdate()->firstOrCreate(
            ['owner_id' => $ownerId],
            ['current_value' => 0]
        );

        $sequence->increment('current_value');

        return $sequence->current_value;
    }
}
