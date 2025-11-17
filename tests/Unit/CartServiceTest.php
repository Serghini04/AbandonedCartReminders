<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->cartService = app(CartService::class);
        
        $this->product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'description' => 'Test description'
        ]);
    }

    public function test_creates_new_cart_for_new_customer(): void
    {
        $cartItem = $this->cartService->addProduct(
            'new@example.com',
            $this->product->id,
            2
        );

        $this->assertInstanceOf(CartItem::class, $cartItem);
        
        $cart = $cartItem->cart;
        $this->assertEquals('new@example.com', $cart->customer_email);
        $this->assertEquals('active', $cart->status);
        $this->assertEquals(2, $cartItem->quantity);
    }

    public function test_increments_quantity_for_existing_product(): void
    {
        $email = 'test@example.com';

        $this->cartService->addProduct($email, $this->product->id, 2);
        $cartItem = $this->cartService->addProduct($email, $this->product->id, 3);

        $cart = $cartItem->cart;
        $this->assertEquals(1, $cart->items()->count());
        
        $cartItem = $cart->items()->first();
        $this->assertEquals(5, $cartItem->quantity);
    }

    public function test_saves_product_price_at_time_of_adding(): void
    {
        $cartItem = $this->cartService->addProduct(
            'test@example.com',
            $this->product->id,
            1
        );
        $this->assertEquals($this->product->price, $cartItem->price);

        $this->product->update(['price' => 149.99]);

        $cartItem->refresh();
        $this->assertEquals(99.99, $cartItem->price);
    }

    public function test_schedules_reminders_when_adding_first_product(): void
    {
        $cartItem = $this->cartService->addProduct(
            'test@example.com',
            $this->product->id,
            1
        );

        $cart = $cartItem->cart;
        $this->assertEquals(3, $cart->reminders()->count());
    }

    public function test_finalizes_cart_successfully(): void
    {
        $cartItem = $this->cartService->addProduct(
            'test@example.com',
            $this->product->id,
            1
        );

        $cart = $cartItem->cart;
        
        $finalizedCart = $this->cartService->finalizeCart($cart->id);

        $this->assertEquals('finalized', $finalizedCart->status);
        $this->assertNotNull($finalizedCart->finalized_at);
    }

    public function test_finalize_cancels_pending_reminders(): void
    {
        $cartItem = $this->cartService->addProduct(
            'test@example.com',
            $this->product->id,
            1
        );

        $cart = $cartItem->cart;
        
        $this->assertEquals(3, $cart->reminders()->where('status', 'pending')->count());

        $this->cartService->finalizeCart($cart->id);

        $cart->refresh();
        $this->assertEquals(0, $cart->reminders()->where('status', 'pending')->count());
        $this->assertEquals(3, $cart->reminders()->where('status', 'cancelled')->count());
    }

    public function test_throws_exception_when_finalizing_already_finalized_cart(): void
    {
        $cartItem = $this->cartService->addProduct(
            'test@example.com',
            $this->product->id,
            1
        );

        $cart = $cartItem->cart;
        
        $this->cartService->finalizeCart($cart->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cart is already finalized');
        
        $this->cartService->finalizeCart($cart->id);
    }

    public function test_creates_separate_carts_after_finalization(): void
    {
        $email = 'test@example.com';
        
        $cartItem1 = $this->cartService->addProduct($email, $this->product->id, 1);
        $cart1 = $cartItem1->cart;
        $this->cartService->finalizeCart($cart1->id);
        $cart1->refresh();

        $cartItem2 = $this->cartService->addProduct($email, $this->product->id, 1);
        $cart2 = $cartItem2->cart;

        $this->assertNotEquals($cart1->id, $cart2->id);
        $this->assertEquals('finalized', $cart1->status);
        $this->assertEquals('active', $cart2->status);
    }
}
