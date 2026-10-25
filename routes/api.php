<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/{bank}/{client?}', [WebhookController::class, 'handle'])
    ->name('webhooks.handle');

Route::post('/payments/xml', [PaymentController::class, 'generateXml'])
    ->name('payments.xml');
