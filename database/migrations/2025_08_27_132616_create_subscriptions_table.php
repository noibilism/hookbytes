<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('endpoint_url');
            $table->text('secret'); // Will be encrypted at application level
            $table->json('event_types');
            $table->boolean('active')->default(true);
            $table->integer('rate_limit_per_minute')->default(300);
            $table->tinyInteger('max_retries')->default(7);
            $table->json('headers')->nullable();
            $table->enum('signature_algo', ['HMAC_SHA256'])->default('HMAC_SHA256');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'active']);
            // Note: JSON columns cannot be directly indexed in MySQL
            // Use generated columns if specific JSON path indexing is needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
