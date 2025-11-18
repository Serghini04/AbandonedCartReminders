<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CartItem;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'description'];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
