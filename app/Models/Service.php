<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'billing_owner_id'];

    public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    }

    /**
     * Emisor de folios propio del servicio (ej. ELECTRIFICACIÓN).
     * Si es null, los cobros usan el socio dueño del lote.
     */
    public function billingOwner()
    {
        return $this->belongsTo(Owner::class, 'billing_owner_id');
    }
}