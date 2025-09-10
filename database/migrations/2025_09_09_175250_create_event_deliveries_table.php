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
        Schema::create('event_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('destination_url');
            $table->integer('attempt_number');
            $table->string('status'); // success, failed, timeout
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            $table->index(['event_id', 'attempt_number']);
            $table->index(['status', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_deliveries');
    }
};
