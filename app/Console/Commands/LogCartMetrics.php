<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Monitoring\CartMonitoringService;

class LogCartMetrics extends Command
{
    protected $signature = 'cart:log-metrics';
    protected $description = 'Log cart metrics to metrics channel';

    public function handle(CartMonitoringService $monitoring)
    {
        $monitoring->logMetrics();
        $this->info('Cart metrics logged!');
    }
}
