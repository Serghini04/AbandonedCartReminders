<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartReminder;
use App\Jobs\SendCartReminderEmail;
use Carbon\Carbon;

class ReminderService
{
    public function scheduleReminders(Cart $cart): void
    {
        if (!config('cart.reminder_enabled')) {
            return;
        }
        
        $intervals = config('cart.reminder_intervals');
        $baseTime = now();
        
        foreach ($intervals as $reminderNumber => $minutes) {
            $scheduledAt = $baseTime->copy()->addMinutes((int) $minutes);
            
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
            
            // Dispatch job with delay
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

    public function processDueReminders(): int
    {
        $dueReminders = CartReminder::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with('cart')
            ->get();
        
        $processed = 0;
        
        foreach ($dueReminders as $reminder) {
            if ($reminder->cart->isFinalized()) {
                $reminder->cancel();
                continue;
            }
            
            // SendCartReminderEmail::dispatch($reminder);
            $processed++;
        }
        
        return $processed;
    }
}