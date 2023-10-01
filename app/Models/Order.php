<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type_orders_id',
        'state'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeOrder()
    {
        return $this->belongsTo(TypeOrder::class, 'type_orders_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity');
    }
}
