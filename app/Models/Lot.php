<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'client_id', 'block_number', 'lot_number', 'identifier', 'total_price', 'status', 'notes'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    }
    
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($lot) {
            $lot->identifier = "Manzana " . $lot->block_number . ", Lote " . $lot->lot_number;
        });
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function ownershipHistory()
    {
        return $this->hasMany(LotOwnershipHistory::class)->latest();
    }


}