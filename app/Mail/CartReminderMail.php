<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Cart;
use App\Models\CartReminder;

class CartReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    use Queueable, SerializesModels;
    
    public function __construct(
        public Cart $cart,
        public CartReminder $reminder
    ) {}
    
    public function build()
    {
        $cartTotal = $this->cart->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $completionUrl = url('/api/cart/' 
            . $this->cart->id 
            . '/complete?token=' 
            . $this->generateToken());
        
        return $this->subject($this->getSubject())
            ->view('emails.cart-reminder')
            ->with([
                'cart' => $this->cart,
                'reminderNumber' => $this->reminder->reminder_number,
                'cartTotal' => $cartTotal,
                'completionUrl' => $completionUrl
            ]);
    }
    
    private function getSubject(): string
    {
        $subjects = [
            1 => 'You left items in your cart!',
            2 => 'Your cart is waiting for you',
            3 => 'Last chance - Complete your order'
        ];
        
        return $subjects[$this->reminder->reminder_number] ?? 'Cart Reminder';
    }
    
    private function generateToken(): string
    {
        return hash_hmac('sha256', $this->cart->id, config('app.key'));
    }
}
