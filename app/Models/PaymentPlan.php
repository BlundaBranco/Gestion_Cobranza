<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'service_id',
        'currency',
        'total_amount',
        'number_of_installments',
        'start_date',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }
}