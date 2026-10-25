<?php

namespace App\Console\Commands;

use App\Services\Bank\WebhookIngestionService;
use Illuminate\Console\Command;

class ProcessPendingWebhooksCommand extends Command
{
    protected $signature = 'webhooks:process-pending {--limit= : Maximum number of payloads to process}';
    protected $description = 'Process queued pending webhook payloads';

    public function handle(WebhookIngestionService $service): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $count = $service->processPending($limit);

        $this->info("Successfully processed {$count} transactions from queued webhooks.");

        return self::SUCCESS;
    }
}
