<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 
        'user_id', // <-- ESTA LÍNEA DEBE ESTAR PRESENTE
        'amount_paid', 
        'payment_date', 
        'folio_number', 
        'notes'
    ];
    protected $casts = [
        'payment_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function installments()
    {
        return $this->belongsToMany(Installment::class)->withPivot('amount_applied');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}