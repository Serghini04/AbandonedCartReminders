<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartReminder;
use App\Jobs\SendCartReminderEmail;

class ReminderService
{
    public function scheduleReminders(Cart $cart): void
    {
        if (!config('cart.reminder_enabled')) {
            return;
        }
        
        $intervals = config('cart.reminder_intervals');
        $baseTime = now();
        
        foreach ($intervals as $reminderNumber => $hours) {
            $scheduledAt = $baseTime->copy()->addHour((int) $hours);
            
            $reminder = CartReminder::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'reminder_number' => $reminderNumber,
                ],
                [
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending'
                ]
            );
            
            SendCartReminderEmail::dispatch($reminder)
                ->delay($scheduledAt);
        }
    }
    
    public function cancelPendingReminders(Cart $cart): void
    {
        $cart->reminders()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }
}