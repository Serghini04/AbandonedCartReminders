<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Jobs\SendCartReminderEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Product::create([
            'name' => 'Test Laptop',
            'price' => 999.99,
            'description' => 'Test gaming laptop'
        ]);
        
        Product::create([
            'name' => 'Test Mouse',
            'price' => 29.99,
            'description' => 'Test wireless mouse'
        ]);
    }

    public function test_can_add_product_to_cart(): void
    {
        $response = $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 2
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product added to cart successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'cart_id',
                    'cart_item' => [
                        'id',
                        'cart_id',
                        'product_id',
                        'quantity',
                        'price'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('carts', [
            'customer_email' => 'test@example.com',
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => 1,
            'quantity' => 2
        ]);
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $email = 'test@example.com';

        $this->postJson('/api/cart/add-product', [
            'customer_email' => $email,
            'product_id' => 1,
            'quantity' => 2
        ]);

        $response = $this->postJson('/api/cart/add-product', [
            'customer_email' => $email,
            'product_id' => 1,
            'quantity' => 3
        ]);

        $response->assertStatus(201);

        $cartItem = CartItem::where('product_id', 1)->first();
        $this->assertEquals(5, $cartItem->quantity);
    }

    public function test_reminders_are_scheduled_when_product_added(): void
    {
        Queue::fake();
        
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();
        
        $this->assertDatabaseHas('cart_reminders', [
            'cart_id' => $cart->id,
            'reminder_number' => 1,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('cart_reminders', [
            'cart_id' => $cart->id,
            'reminder_number' => 2,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('cart_reminders', [
            'cart_id' => $cart->id,
            'reminder_number' => 3,
            'status' => 'pending'
        ]);

        $this->assertEquals(3, $cart->reminders()->count());
    }

    public function test_can_get_active_cart(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 2
        ]);

        $response = $this->getJson('/api/cart/active?customer_email=test@example.com');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_email',
                    'status',
                    'items'
                ]
            ]);
    }

    public function test_returns_404_when_no_active_cart_exists(): void
    {
        $response = $this->getJson('/api/cart/active?customer_email=nonexistent@example.com');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No active cart found'
            ]);
    }

    public function test_can_finalize_cart(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();

        $response = $this->postJson("/api/cart/{$cart->id}/finalize");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cart finalized successfully'
            ]);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'status' => 'finalized'
        ]);

        $cart->refresh();
        $this->assertNotNull($cart->finalized_at);
    }

    public function test_finalizing_cart_cancels_pending_reminders(): void
    {
        Queue::fake();
        
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();

        $this->assertEquals(3, $cart->reminders()->where('status', 'pending')->count());

        $this->postJson("/api/cart/{$cart->id}/finalize");

        $this->assertEquals(0, $cart->reminders()->where('status', 'pending')->count());
        $this->assertEquals(3, $cart->reminders()->where('status', 'cancelled')->count());
    }

    public function test_cannot_finalize_already_finalized_cart(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();

        // Finalize cart first time
        $this->postJson("/api/cart/{$cart->id}/finalize");

        // Try to finalize again
        $response = $this->postJson("/api/cart/{$cart->id}/finalize");

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to finalize cart. Please try again later.'
            ]);
    }

    public function test_can_complete_cart_from_email_with_valid_token(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();
        $token = hash_hmac('sha256', $cart->id, config('app.key'));

        $response = $this->getJson("/api/cart/{$cart->id}/complete?token={$token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cart finalized successfully'
            ]);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'status' => 'finalized'
        ]);
    }

    public function test_cannot_complete_cart_with_invalid_token(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();
        $invalidToken = 'invalid_token_123';

        $response = $this->getJson("/api/cart/{$cart->id}/complete?token={$invalidToken}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid token'
            ]);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'status' => 'active'
        ]);
    }

    public function test_validation_fails_with_missing_customer_email(): void
    {
        $response = $this->postJson('/api/cart/add-product', [
            'product_id' => 1,
            'quantity' => 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_validation_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/cart/add-product', [
            'customer_email' => 'not-an-email',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_validation_fails_with_missing_product_id(): void
    {
        $response = $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'quantity' => 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_validation_fails_with_invalid_quantity(): void
    {
        $response = $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 0
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_validation_fails_with_nonexistent_product(): void
    {
        $response = $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 9999,
            'quantity' => 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_reminder_email_job_is_dispatched(): void
    {
        Queue::fake();

        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();

        Queue::assertPushed(SendCartReminderEmail::class, 3);

        Queue::assertPushed(SendCartReminderEmail::class, function ($job) use ($cart) {
            $reminder = \App\Models\CartReminder::find($job->reminderId);
            return $reminder && $reminder->cart_id === $cart->id;
        });
    }

    public function test_cart_total_is_calculated_correctly(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 2
        ]);

        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 2,
            'quantity' => 3
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();
        $total = $cart->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $this->assertEquals(2089.95, $total);
    }

    public function test_multiple_carts_for_different_customers(): void
    {
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'customer1@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'customer2@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $this->assertEquals(2, Cart::count());
        
        $cart1 = Cart::where('customer_email', 'customer1@example.com')->first();
        $cart2 = Cart::where('customer_email', 'customer2@example.com')->first();

        $this->assertNotEquals($cart1->id, $cart2->id);
        $this->assertEquals(3, $cart1->reminders()->count());
        $this->assertEquals(3, $cart2->reminders()->count());
    }

    public function test_reminder_intervals_are_correctly_configured(): void
    {
        Queue::fake();
        
        $this->postJson('/api/cart/add-product', [
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'quantity' => 1
        ]);

        $cart = Cart::where('customer_email', 'test@example.com')->first();
        $reminders = $cart->reminders()->orderBy('reminder_number')->get();

        $interval1 = (int) config('cart.reminder_intervals.1');
        $interval2 = (int) config('cart.reminder_intervals.2');
        $interval3 = (int) config('cart.reminder_intervals.3');

        $baseTime = $reminders[0]->created_at;

        $this->assertEquals(
            $baseTime->copy()->addHours($interval1)->format('Y-m-d H:i'),
            $reminders[0]->scheduled_at->format('Y-m-d H:i')
        );

        $this->assertEquals(
            $baseTime->copy()->addHours($interval2)->format('Y-m-d H:i'),
            $reminders[1]->scheduled_at->format('Y-m-d H:i')
        );

        $this->assertEquals(
            $baseTime->copy()->addHours($interval3)->format('Y-m-d H:i'),
            $reminders[2]->scheduled_at->format('Y-m-d H:i')
        );
    }
}
