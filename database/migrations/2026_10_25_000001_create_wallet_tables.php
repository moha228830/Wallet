<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('webhook_payloads', function (Blueprint $table) {
            $table->id();
            $table->string('bank');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->longText('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('bank', 50)->index();
            $table->string('reference')->index();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['bank', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('webhook_payloads');
        Schema::dropIfExists('clients');
    }
};
