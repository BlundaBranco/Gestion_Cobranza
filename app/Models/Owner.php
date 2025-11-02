<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_info'];

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'owner_user');
    }
}