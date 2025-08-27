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
        Schema::create('events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('event_type');
            $table->json('payload');
            $table->string('source');
            $table->string('idempotency_key')->nullable()->unique();
            $table->enum('status', ['accepted', 'partially_delivered', 'delivered', 'failed'])->default('accepted');
            $table->timestamps();
            
            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
