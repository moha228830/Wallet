<?php

namespace App\Console\Commands;

use App\Services\Bank\WebhookIngestionService;
use Illuminate\Console\Command;

class ResumeWebhooksCommand extends Command
{
    protected $signature = 'webhooks:resume {--process : Process pending webhooks immediately after resuming}';

    protected $description = 'Resume webhook ingestion processing';

    public function handle(WebhookIngestionService $service): int
    {
        $service->resume();
        $this->info('Webhook ingestion resumed.');

        if ($this->option('process')) {
            $processed = $service->processPending();
            $this->info("Processed {$processed} transactions from pending webhooks.");
        }

        return self::SUCCESS;
    }
}
