<?php 
namespace App\Services\Monitoring;

use App\Models\Cart;
use App\Models\CartReminder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CartMonitoringService
{
    public function getStatistics(): array
    {
        return Cache::remember('cart_statistics', 300, function () {
            return [
                'active_carts' => Cart::where('status', 'active')->count(),
                'finalized_today' => Cart::where('status', 'finalized')
                    ->whereDate('finalized_at', today())
                    ->count(),
                'pending_reminders' => CartReminder::where('status', 'pending')->count(),
                'sent_reminders_today' => CartReminder::where('status', 'sent')
                    ->whereDate('sent_at', today())
                    ->count(),
            ];
        });
    }
    
    public function logMetrics(): void
    {
        $stats = $this->getStatistics();
        
        Log::channel('metrics')->info('Cart Metrics', $stats);
    }
}