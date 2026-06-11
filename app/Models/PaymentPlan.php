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
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancelled_client_id',
        'cancellation_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'cancelled_at' => 'datetime',
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

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function cancelledClient()
    {
        return $this->belongsTo(Client::class, 'cancelled_client_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}