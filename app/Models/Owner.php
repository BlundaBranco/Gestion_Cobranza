<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_info', 'is_billing_entity'];

    protected $casts = [
        'is_billing_entity' => 'boolean',
    ];

    /** Nombre del emisor de folios de electrificación. */
    public const NAME_ELECTRIFICACION = 'ELECTRIFICACIÓN';

    /**
     * Socios reales (excluye emisores de folios como ELECTRIFICACIÓN).
     * Usar al ofrecer la selección de "dueño de lote".
     */
    public function scopeSocios($query)
    {
        return $query->where('is_billing_entity', false);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'owner_user');
    }

    public function sequence()
    {
        return $this->hasOne(OwnerSequence::class);
    }
}