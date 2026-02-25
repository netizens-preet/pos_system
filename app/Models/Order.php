<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'status',
        'subtotal',
        'discount',
        'total',
        'note',
        'ordered_at',
    ];

   protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function isCancellable(): bool
    {
        return $this->status === 'pending';
    }
}
