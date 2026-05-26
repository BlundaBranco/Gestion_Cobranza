<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'phone_label', 'additional_phones', 'address', 'notes', 'credit_balance'];

    protected $casts = [
        'credit_balance' => 'decimal:2',
    ];

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function documents()
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function creditBalanceMovements()
    {
        return $this->hasMany(CreditBalanceMovement::class);
    }
}