<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;

class CartItemRepository
{
    public function findByProductInCart(Cart $cart, int $productId): ?CartItem
    {
        return $cart->items()->where('product_id', $productId)->first();
    }

    public function create(Cart $cart, array $data): CartItem
    {
        return $cart->items()->create($data);
    }

    public function incrementQuantity(CartItem $cartItem, int $quantity): bool
    {
        return $cartItem->increment('quantity', $quantity);
    }

    public function fresh(CartItem $cartItem): CartItem
    {
        return $cartItem->fresh();
    }
}
