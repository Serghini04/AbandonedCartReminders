<?php 
namespace App\Services\Monitoring;

use App\Repositories\CartRepository;
use App\Repositories\CartReminderRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CartMonitoringService
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartReminderRepository $cartReminderRepository
    ) {}
    
    public function getStatistics(): array
    {
        return Cache::remember('cart_statistics', 300, function () {
            return [
                'active_carts' => $this->cartRepository->countActive(),
                'finalized_today' => $this->cartRepository->countFinalizedToday(),
                'pending_reminders' => $this->cartReminderRepository->countPending(),
                'sent_reminders_today' => $this->cartReminderRepository->countSentToday(),
            ];
        });
    }
    
    public function logMetrics(): void
    {
        $stats = $this->getStatistics();
        
        Log::channel('metrics')->info('Cart Metrics', $stats);
    }
}