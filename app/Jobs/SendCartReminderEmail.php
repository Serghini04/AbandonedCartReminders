<?php

namespace App\Jobs;

use App\Mail\CartReminderMail;
use App\Repositories\CartRepository;
use App\Repositories\CartReminderRepository;
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
        public int $reminderId
    ) {}
    
    public function handle(
        CartReminderRepository $cartReminderRepository,
        CartRepository $cartRepository
    ): void {
        $reminder = $cartReminderRepository->find($this->reminderId);
        
        if (!$reminder) {
            Log::warning('Reminder not found', [
                'reminder_id' => $this->reminderId
            ]);
            return;
        }
        
        if ($reminder->status !== 'pending') {
            Log::info('Reminder already processed', [
                'reminder_id' => $reminder->id,
                'status' => $reminder->status
            ]);
            return;
        }
        
        $cart = $cartRepository->getWithItemsAndProducts($reminder->cart_id);
        
        if (!$cart) {
            Log::warning('Cart not found for reminder', [
                'reminder_id' => $reminder->id,
                'cart_id' => $reminder->cart_id
            ]);
            return;
        }
        
        if ($cart->isFinalized()) {
            $reminder->cancel();
            Log::info('Cart finalized, cancelling reminder', [
                'cart_id' => $cart->id,
                'reminder_id' => $reminder->id
            ]);
            return;
        }
        
        Mail::to($cart->customer_email)
            ->send(new CartReminderMail($cart, $reminder));
        
        $reminder->markAsSent();
        
        Log::info('Cart reminder sent', [
            'cart_id' => $cart->id,
            'reminder_number' => $reminder->reminder_number,
            'customer_email' => $cart->customer_email
        ]);
    }
    
    public function failed(
        \Throwable $exception,
        CartReminderRepository $cartReminderRepository
    ): void {
        $reminder = $cartReminderRepository->find($this->reminderId);
        
        if (!$reminder) {
            Log::error('Failed to send cart reminder - reminder not found', [
                'reminder_id' => $this->reminderId,
                'error_message' => $exception->getMessage(),
                'error_trace' => $exception->getTraceAsString()
            ]);
            return;
        }
        
        Log::error('Failed to send cart reminder', [
            'reminder_id' => $reminder->id,
            'cart_id' => $reminder->cart_id,
            'reminder_number' => $reminder->reminder_number,
            'error_message' => $exception->getMessage(),
            'error_trace' => $exception->getTraceAsString()
        ]);
        
        $reminder->markAsFailed();
    }
}