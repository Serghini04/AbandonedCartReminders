<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartReminder extends Model
{
    protected $fillable = [
        'cart_id',
        'reminder_number',
        'scheduled_at',
        'sent_at',
        'status'
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
    
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
    
    public function markAsSent(): void
    {
        $this->update([
            'sent_at' => now(),
            'status' => 'sent'
        ]);
    }
    
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
