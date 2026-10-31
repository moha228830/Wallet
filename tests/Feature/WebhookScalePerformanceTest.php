<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Transaction;
use App\Services\Bank\WebhookIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookScalePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_parse_and_ingest_webhook_with_1000_transactions(): void
    {
        $client = Client::factory()->create([
            'account_number' => 'ACC90001',
            'balance' => 0.00,
        ]);

        $lines = [];
        for ($i = 1; $i <= 1000; $i++) {
            $ref = '20250615'.str_pad((string) $i, 7, '0', STR_PAD_LEFT);
            $lines[] = "2025061510,50#{$ref}#note/batch item {$i}/index/{$i}";
        }
        $payload = implode("\n", $lines);

        $startTime = microtime(true);

        $service = app(WebhookIngestionService::class);
        $webhookPayload = $service->receive('paytech', $payload, $client->id);

        $duration = microtime(true) - $startTime;

        $this->assertSame(1000, Transaction::count());
        $this->assertEquals(10500.00, (float) $client->fresh()->balance);
        $this->assertSame('processed', $webhookPayload->status);

        $this->assertLessThan(
            3.0,
            $duration,
            "Parsing and ingesting 1,000 transactions took {$duration}s, exceeding target threshold."
        );
    }
}
