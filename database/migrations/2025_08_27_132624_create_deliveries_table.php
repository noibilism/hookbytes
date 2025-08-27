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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('event_id');
            $table->ulid('subscription_id');
            $table->smallInteger('attempt')->default(1);
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled', 'dead_lettered'])->default('pending');
            $table->integer('response_code')->nullable();
            $table->mediumText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->datetime('next_retry_at')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();
            
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            
            $table->index(['event_id', 'subscription_id']);
            $table->index(['subscription_id', 'status']);
            $table->index(['status', 'next_retry_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
