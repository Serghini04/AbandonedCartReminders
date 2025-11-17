<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cart;
use App\Services\Cart\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReminderService $reminderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reminderService = app(ReminderService::class);
        Queue::fake();
    }

    public function test_schedules_three_reminders_for_new_cart(): void
    {
        $cart = Cart::create([
            'customer_email' => 'test@example.com',
            'status' => 'active'
        ]);

        $this->reminderService->scheduleReminders($cart);

        $this->assertEquals(3, $cart->reminders()->count());
        
        $reminders = $cart->reminders()->orderBy('reminder_number')->get();
        
        $this->assertEquals(1, $reminders[0]->reminder_number);
        $this->assertEquals(2, $reminders[1]->reminder_number);
        $this->assertEquals(3, $reminders[2]->reminder_number);
        
        foreach ($reminders as $reminder) {
            $this->assertEquals('pending', $reminder->status);
            $this->assertNotNull($reminder->scheduled_at);
        }
    }

    public function test_cancels_pending_reminders(): void
    {
        $cart = Cart::create([
            'customer_email' => 'test@example.com',
            'status' => 'active'
        ]);

        $this->reminderService->scheduleReminders($cart);

        $this->assertEquals(3, $cart->reminders()->where('status', 'pending')->count());

        $this->reminderService->cancelPendingReminders($cart);

        $this->assertEquals(0, $cart->reminders()->where('status', 'pending')->count());
        $this->assertEquals(3, $cart->reminders()->where('status', 'cancelled')->count());
    }

    public function test_only_cancels_pending_reminders_not_sent_ones(): void
    {
        $cart = Cart::create([
            'customer_email' => 'test@example.com',
            'status' => 'active'
        ]);

        $this->reminderService->scheduleReminders($cart);

        $cart->reminders()->first()->update(['status' => 'sent']);

        $this->reminderService->cancelPendingReminders($cart);

        $this->assertEquals(1, $cart->reminders()->where('status', 'sent')->count());
        $this->assertEquals(2, $cart->reminders()->where('status', 'cancelled')->count());
    }
}
