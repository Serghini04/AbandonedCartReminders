<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CartReminder;
use App\Models\CartItem;

class Cart extends Model
{
    protected $fillable = [
        'customer_email',
        'status',
        'finalized_at'
    ];
    
    protected $casts = [
        'finalized_at' => 'datetime',
    ];
    
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
    
    public function reminders()
    {
        return $this->hasMany(CartReminder::class);
    }
    
    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
    
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
