<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class order_item extends Model
{
    protected $fillable = [
    'order_id', 
    'product_id', 
    'quantity', 
    'unit_price', 
    'total_price'
];
    public function orders()
{
    return $this->belongsTo(Order::class);
}

public function products()
{
    return $this->belongsTo(Product::class);
}
}

#please check do i have to enter fillable here?
