<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model
{
    protected $fillable = [
        "customer_id",
        "order_date",
        "total_amount",
        "status",
        "notes",
        "payment_method",
        "discount_amount"
    ];
    public function order_items(): HasMany
   {
       return $this->hasMany(order_item::class);
   } 
   public function customers()
   {
       return $this->belongsTo(Customer::class);
   }
   
}

