<?php

namespace App\Events;

use App\Models\Cart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartFinalized
{
    use Dispatchable, SerializesModels;
    
    public function __construct(public Cart $cart) {}
}
