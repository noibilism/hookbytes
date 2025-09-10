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
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('event_type')->nullable();
            $table->json('payload');
            $table->json('headers');
            $table->string('source_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status')->default('pending'); // pending, processing, delivered, failed
            $table->integer('delivery_attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index('event_type');
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
