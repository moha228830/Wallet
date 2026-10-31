<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Transaction;
use App\Services\Bank\WebhookIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingests_paytech_webhook_and_updates_client_balance(): void
    {
        $client = Client::factory()->create([
            'account_number' => '9000001',
            'balance' => 0.00,
        ]);

        $payload = '20250615156,50#202506159000001#note/debt payment march/internal_reference/A462JE81';

        $response = $this->call(
            'POST',
            '/api/webhooks/paytech',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $payload
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'processing_status' => 'processed',
            ]);

        $this->assertDatabaseHas('transactions', [
            'bank' => 'paytech',
            'reference' => '202506159000001',
            'amount' => '156.50',
            'client_id' => $client->id,
        ]);

        $client->refresh();
        $this->assertEquals(156.50, (float) $client->balance);
    }

    public function test_ingests_acme_webhook(): void
    {
        $client = Client::factory()->create([
            'account_number' => '9000001',
            'balance' => 100.00,
        ]);

        $payload = '156,50//202506159000001//20250615';

        $response = $this->call(
            'POST',
            "/api/webhooks/acme/{$client->id}",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $payload
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('transactions', [
            'bank' => 'acme',
            'reference' => '202506159000001',
            'amount' => '156.50',
            'client_id' => $client->id,
        ]);

        $client->refresh();
        $this->assertEquals(256.50, (float) $client->balance);
    }

    public function test_duplicate_transactions_have_no_effect_on_client_transactions(): void
    {
        $client = Client::factory()->create([
            'account_number' => '9000001',
            'balance' => 0.00,
        ]);

        $payload = '20250615156,50#202506159000001#note/first call';

        $this->call('POST', "/api/webhooks/paytech/{$client->id}", [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload)
            ->assertStatus(200);

        $this->assertSame(1, Transaction::where('reference', '202506159000001')->count());
        $this->assertEquals(156.50, (float) $client->fresh()->balance);

        $duplicatePayload = '20250615156,50#202506159000001#note/duplicate call';

        $this->call('POST', "/api/webhooks/paytech/{$client->id}", [], [], [], ['CONTENT_TYPE' => 'text/plain'], $duplicatePayload)
            ->assertStatus(200);

        $this->assertSame(1, Transaction::where('reference', '202506159000001')->count());
        $this->assertEquals(156.50, (float) $client->fresh()->balance);
    }

    public function test_can_stop_processing_webhooks_without_dropping_incoming_requests(): void
    {
        $service = app(WebhookIngestionService::class);
        $service->pause();

        $payload = '20250615156,50#202506159000001#note/queued';

        $response = $this->call(
            'POST',
            '/api/webhooks/paytech',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $payload
        );

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'success',
                'processing_status' => 'pending',
            ]);

        $this->assertDatabaseHas('webhook_payloads', [
            'status' => 'pending',
            'bank' => 'paytech',
        ]);

        $this->assertSame(0, Transaction::count());

        $this->artisan('webhooks:resume', ['--process' => true])
            ->assertSuccessful();

        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseHas('webhook_payloads', [
            'status' => 'processed',
        ]);
    }

    public function test_generates_payment_xml_via_endpoint(): void
    {
        $response = $this->postJson('/api/payments/xml', [
            'reference' => 'e0f4763d-28ea-42d4-ac1c-c4013c242105',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 177.39,
            'currency' => 'SAR',
            'sender_account_number' => 'SA6980000204608016212908',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account_number' => 'SA6980000204608016211111',
            'beneficiary_name' => 'Jane Doe',
            'notes' => ['Lorem Epsum', 'Dolor Sit Amet'],
            'payment_type' => 421,
            'charge_details' => 'RB',
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');

        $content = $response->getContent();
        $this->assertStringContainsString('<Reference>e0f4763d-28ea-42d4-ac1c-c4013c242105</Reference>', $content);
        $this->assertStringContainsString('<Amount>177.39</Amount>', $content);
        $this->assertStringContainsString('<PaymentType>421</PaymentType>', $content);
        $this->assertStringContainsString('<ChargeDetails>RB</ChargeDetails>', $content);
    }
}
