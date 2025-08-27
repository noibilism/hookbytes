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
        Schema::create('delivery_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('tenant_id');
            $table->ulid('subscription_id');
            $table->string('event_type');
            $table->integer('sent_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('fail_count')->default(0);
            $table->integer('avg_latency_ms')->default(0);
            $table->timestamps();
            
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            
            $table->unique(['date', 'tenant_id', 'subscription_id', 'event_type'], 'daily_metrics_unique');
            $table->index(['date', 'tenant_id']);
            $table->index(['subscription_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_metrics_daily');
    }
};
