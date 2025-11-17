<?php 

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Events\CartItemAdded;
use App\Events\CartFinalized;
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
            $product = Product::findOrFail($productId);
            
            $cartItem = $cart->items()->where('product_id', $productId)->first();
            
            $isNewItem = !$cartItem;
            
            if ($cartItem) {
                $cartItem->increment('quantity', $quantity);
            } else {
                $cartItem = $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);
            }
            
            // Schedule reminders for new items or reschedule if cart has no pending reminders
            if ($isNewItem || !$cart->reminders()->where('status', 'pending')->exists()) {
                $this->reminderService->scheduleReminders($cart);
            }
            
            event(new CartItemAdded($cart, $cartItem));
            
            return $cartItem->fresh();
        });
    }
    
    private function getOrCreateActiveCart(string $customerEmail): Cart
    {
        return Cart::firstOrCreate(
            [
                'customer_email' => $customerEmail,
                'status' => 'active'
            ],
            ['customer_email' => $customerEmail]
        );
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
            
            event(new CartFinalized($cart));
            
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