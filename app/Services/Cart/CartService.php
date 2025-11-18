<?php 

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private ReminderService $reminderService
    ) {}

    public function addProduct(string $customerEmail, int $productId, int $quantity = 1): CartItem
    {
        return DB::transaction(function () use ($customerEmail, $productId, $quantity) {
            $cart = $this->getOrCreateActiveCart($customerEmail);
            $isNewCart = $cart->wasRecentlyCreated;
            
            $product = Product::findOrFail($productId);
            
            $cartItem = $cart->items()->where('product_id', $productId)->first();
            
            if ($cartItem) {
                $cartItem->increment('quantity', $quantity);
            } else {
                $cartItem = $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);
            }
            
            if ($isNewCart) {
                $this->reminderService->scheduleReminders($cart);
            }
            
            return $cartItem->fresh();
        });
    }
    
    private function getOrCreateActiveCart(string $customerEmail): Cart
    {
        $cart = Cart::where('customer_email', $customerEmail)
            ->where('status', 'active')
            ->first();
        
        if (!$cart) {
            $cart = Cart::create([
                'customer_email' => $customerEmail,
                'status' => 'active'
            ]);
        }
        
        return $cart;
    }
    
    public function finalizeCart(int $cartId): Cart
    {
        return DB::transaction(function () use ($cartId) {
            $cart = Cart::findOrFail($cartId);
            
            if ($cart->isFinalized()) {
                throw new \Exception('Cart is already finalized');
            }
            
            $cart->update([
                'status' => 'finalized',
                'finalized_at' => now()
            ]);
            
            $this->reminderService->cancelPendingReminders($cart);
            
            return $cart->fresh();
        });
    }
    
    public function getActiveCart(string $customerEmail): ?Cart
    {
        return Cart::where('customer_email', $customerEmail)
            ->where('status', 'active')
            ->with('items.product')
            ->first();
    }
}