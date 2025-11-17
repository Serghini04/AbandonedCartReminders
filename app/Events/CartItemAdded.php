<?php

namespace App\Events;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemAdded
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public Cart $cart,
        public CartItem $cartItem
    ) {}
}