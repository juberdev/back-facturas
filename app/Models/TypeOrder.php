<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'description',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
