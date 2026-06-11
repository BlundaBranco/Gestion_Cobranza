<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Owner;
use App\Models\User;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'owner_id',
        'user_id',
        'amount_paid',
        'payment_date',
        'folio_number',
        'type',
        'currency',
        'payment_method',
        'notes',
        'status',
        'cancelled_by',
        'print_count',
        'last_printed_at',
    ];
    protected $casts = [
        'payment_date' => 'date',
        'last_printed_at' => 'datetime',
    ];

    public const PAYMENT_METHODS = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'cheque'        => 'Cheque',
        'deposito'      => 'Depósito',
    ];

    public function getPaymentMethodLabelAttribute(): ?string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? null;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function installments()
    {
        return $this->belongsToMany(Installment::class)->withPivot('amount_applied');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function creditBalanceMovements()
    {
        return $this->hasMany(CreditBalanceMovement::class);
    }
}