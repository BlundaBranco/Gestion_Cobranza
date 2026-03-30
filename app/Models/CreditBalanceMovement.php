<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditBalanceMovement extends Model
{
    protected $fillable = ['client_id', 'amount', 'type', 'transaction_id', 'notes', 'created_by'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
