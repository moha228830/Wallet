<?php

namespace App\Services\Bank;

use App\Models\Client;
use App\Models\Transaction;
use App\Models\WebhookPayload;
use App\Services\Bank\DTO\ParsedTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class WebhookIngestionService
{
    protected const PAUSE_CACHE_KEY = 'webhooks:ingestion_paused';

    public function __construct(
        protected BankParserFactory $parserFactory,
    ) {}

    public function isPaused(): bool
    {
        return (bool) Cache::get(self::PAUSE_CACHE_KEY, false);
    }

    public function pause(): void
    {
        Cache::forever(self::PAUSE_CACHE_KEY, true);
    }

    public function resume(): void
    {
        Cache::forget(self::PAUSE_CACHE_KEY);
    }

    public function receive(string $bank, string $rawPayload, ?int $clientId = null): WebhookPayload
    {
        $payload = WebhookPayload::create([
            'bank' => strtolower(trim($bank)),
            'client_id' => $clientId,
            'payload' => $rawPayload,
            'status' => WebhookPayload::STATUS_PENDING,
        ]);

        if (! $this->isPaused()) {
            $this->processPayload($payload);
        }

        return $payload;
    }

    public function processPayload(WebhookPayload $webhookPayload): int
    {
        $parser = $this->parserFactory->get($webhookPayload->bank);
        $importedCount = 0;

        try {
            DB::transaction(function () use ($webhookPayload, $parser, &$importedCount) {
                $clientCache = [];
                $explicitClient = $webhookPayload->client_id
                    ? Client::find($webhookPayload->client_id)
                    : null;

                foreach ($parser->parse($webhookPayload->payload) as $parsed) {
                    $client = $explicitClient ?? $this->resolveClient($parsed, $clientCache);

                    $existing = Transaction::where('bank', $webhookPayload->bank)
                        ->where('reference', $parsed->reference)
                        ->exists();

                    if ($existing) {
                        continue;
                    }

                    Transaction::create([
                        'client_id' => $client->id,
                        'bank' => $webhookPayload->bank,
                        'reference' => $parsed->reference,
                        'amount' => $parsed->amount,
                        'date' => $parsed->date,
                        'metadata' => $parsed->metadata,
                    ]);

                    $client->increment('balance', $parsed->amount);
                    $importedCount++;
                }

                $webhookPayload->update([
                    'status' => WebhookPayload::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'error' => null,
                ]);
            });
        } catch (Throwable $e) {
            $webhookPayload->update([
                'status' => WebhookPayload::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $importedCount;
    }

    public function processPending(?int $limit = null): int
    {
        $query = WebhookPayload::where('status', WebhookPayload::STATUS_PENDING)
            ->orderBy('id', 'asc');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $pending = $query->get();
        $totalImported = 0;

        foreach ($pending as $payload) {
            $totalImported += $this->processPayload($payload);
        }

        return $totalImported;
    }

    protected function resolveClient(ParsedTransaction $parsed, array &$cache): Client
    {
        $identifier = $parsed->clientIdentifier ?? 'DEFAULT';

        if (isset($cache[$identifier])) {
            return $cache[$identifier];
        }

        if ($parsed->clientIdentifier) {
            $client = Client::where('account_number', $parsed->clientIdentifier)->first();
            if ($client) {
                return $cache[$identifier] = $client;
            }
        }

        $fallback = Client::firstOrCreate(
            ['account_number' => $parsed->clientIdentifier ?? 'ACC-'.substr($parsed->reference, -8)],
            [
                'name' => 'Client '.($parsed->clientIdentifier ?? substr($parsed->reference, -8)),
                'email' => ($parsed->clientIdentifier ?? substr($parsed->reference, -8)).'@wallet.local',
                'balance' => 0.00,
            ]
        );

        return $cache[$identifier] = $fallback;
    }
}
