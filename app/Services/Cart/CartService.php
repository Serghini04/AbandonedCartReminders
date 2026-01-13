<?php 

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\CartRepository;
use App\Repositories\CartItemRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private ReminderService $reminderService,
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository,
        private ProductRepository $productRepository
    ) {}

    public function addProduct(string $customerEmail, int $productId, int $quantity = 1): CartItem
    {
        return DB::transaction(function () use ($customerEmail, $productId, $quantity) {
            $cart = $this->getOrCreateActiveCart($customerEmail);
            $isNewCart = $cart->wasRecentlyCreated;
            
            $product = $this->productRepository->findOrFail($productId);
            
            $cartItem = $this->cartItemRepository->findByProductInCart($cart, $productId);
            
            if ($cartItem) {
                $this->cartItemRepository->incrementQuantity($cartItem, $quantity);
            } else {
                $cartItem = $this->cartItemRepository->create($cart, [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);
            }
            
            if ($isNewCart) {
                $this->reminderService->scheduleReminders($cart);
            }
            
            return $this->cartItemRepository->fresh($cartItem);
        });
    }
    
    private function getOrCreateActiveCart(string $customerEmail): Cart
    {
        $cart = $this->cartRepository->getActiveByEmail($customerEmail);
        
        if (!$cart) {
            $cart = $this->cartRepository->create([
                'customer_email' => $customerEmail,
                'status' => 'active'
            ]);
        }
        
        return $cart;
    }
    
    public function finalizeCart(int $cartId): Cart
    {
        return DB::transaction(function () use ($cartId) {
            $cart = $this->cartRepository->findOrFail($cartId);
            
            if ($cart->isFinalized()) {
                throw new \Exception('Cart is already finalized');
            }
            
            $this->cartRepository->update($cart, [
                'status' => 'finalized',
                'finalized_at' => now()
            ]);
            
            $this->reminderService->cancelPendingReminders($cart);
            
            return $cart->fresh();
        });
    }
    
    public function getActiveCart(string $customerEmail): ?Cart
    {
        return $this->cartRepository->getActiveByEmailWithItems($customerEmail);
    }
}