<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model
{
    protected $fillable = [
        "customer_id",
        "status",
        "subtotal",
        "discount",
        "total",
        "note",
        "ordered_at"
    ];
    public function order_items(): HasMany
   {
       return $this->hasMany(order_item::class);
   } 
   public function customers()
   {
       return $this->belongsTo(Customer::class);
   }
   public function isCancellable(): bool {
        return $this->status === 'pending';
    }
}

