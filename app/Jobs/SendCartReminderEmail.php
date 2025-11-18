<?php

namespace App\Jobs;

use App\Models\CartReminder;
use App\Mail\CartReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCartReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900];
    
    public function __construct(
        public CartReminder $reminder
    ) {}
    
    public function handle(): void
    {
        $this->reminder->refresh();
        
        if ($this->reminder->status !== 'pending') {
            Log::info('Reminder already processed', [
                'reminder_id' => $this->reminder->id,
                'status' => $this->reminder->status
            ]);
            return;
        }
        
        $cart = $this->reminder->cart()->with('items.product')->first();
        
        if ($cart->isFinalized()) {
            $this->reminder->cancel();
            Log::info('Cart finalized, cancelling reminder', [
                'cart_id' => $cart->id,
                'reminder_id' => $this->reminder->id
            ]);
            return;
        }
        
        Mail::to($cart->customer_email)
            ->send(new CartReminderMail($cart, $this->reminder));
        
        $this->reminder->markAsSent();
        
        Log::info('Cart reminder sent', [
            'cart_id' => $cart->id,
            'reminder_number' => $this->reminder->reminder_number,
            'customer_email' => $cart->customer_email
        ]);
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send cart reminder', [
            'reminder_id' => $this->reminder->id,
            'error' => $exception->getMessage()
        ]);
    }
}