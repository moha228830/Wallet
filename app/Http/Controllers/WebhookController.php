<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WebhookPayload;
use App\Services\Bank\WebhookIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookIngestionService $ingestionService,
    ) {}

    public function handle(Request $request, string $bank, ?Client $client = null): JsonResponse
    {
        $rawPayload = $request->getContent();

        if (empty($rawPayload) && $request->has('payload')) {
            $rawPayload = (string) $request->input('payload');
        }

        if (trim($rawPayload) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Empty webhook payload',
            ], 422);
        }

        $clientId = $client?->id
            ?? ($request->filled('client_id') ? (int) $request->input('client_id') : null)
            ?? ($request->hasHeader('X-Client-Id') ? (int) $request->header('X-Client-Id') : null);

        try {
            $webhookPayload = $this->ingestionService->receive($bank, $rawPayload, $clientId);

            $isPending = $webhookPayload->status === WebhookPayload::STATUS_PENDING;

            return response()->json([
                'status' => 'success',
                'webhook_id' => $webhookPayload->id,
                'processing_status' => $webhookPayload->status,
                'message' => $isPending
                    ? 'Webhook received and queued for ingestion'
                    : 'Webhook ingested successfully',
            ], $isPending ? 202 : 200);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
