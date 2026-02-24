<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function order_items(): HasMany
   {
       return $this->hasMany(order_item::class);
   } 
}
