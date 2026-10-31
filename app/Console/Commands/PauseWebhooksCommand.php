<?php

namespace App\Console\Commands;

use App\Services\Bank\WebhookIngestionService;
use Illuminate\Console\Command;

class PauseWebhooksCommand extends Command
{
    protected $signature = 'webhooks:pause';

    protected $description = 'Pause webhook ingestion processing without dropping incoming webhooks';

    public function handle(WebhookIngestionService $service): int
    {
        $service->pause();
        $this->info('Webhook ingestion paused. Incoming webhooks will be queued.');

        return self::SUCCESS;
    }
}
