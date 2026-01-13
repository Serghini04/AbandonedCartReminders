<?php

namespace App\Repositories;

use App\Models\Cart;

class CartRepository
{
    public function findOrFail(int $cartId): Cart
    {
        return Cart::findOrFail($cartId);
    }

    public function getActiveByEmail(string $customerEmail): ?Cart
    {
        return Cart::where('customer_email', $customerEmail)
            ->where('status', 'active')
            ->first();
    }

    public function getActiveByEmailWithItems(string $customerEmail): ?Cart
    {
        return Cart::where('customer_email', $customerEmail)
            ->where('status', 'active')
            ->with('items.product')
            ->first();
    }

    public function create(array $data): Cart
    {
        return Cart::create($data);
    }

    public function update(Cart $cart, array $data): bool
    {
        return $cart->update($data);
    }

    public function countActive(): int
    {
        return Cart::where('status', 'active')->count();
    }

    public function countFinalizedToday(): int
    {
        return Cart::where('status', 'finalized')
            ->whereDate('finalized_at', today())
            ->count();
    }

    public function getWithItemsAndProducts(int $cartId): ?Cart
    {
        return Cart::with('items.product')->find($cartId);
    }
}
