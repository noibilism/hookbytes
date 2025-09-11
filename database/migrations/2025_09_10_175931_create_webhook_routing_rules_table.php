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
        Schema::create('webhook_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('action', ['route', 'drop'])->default('route');
            $table->integer('priority')->default(10); // Lower = higher priority
            $table->boolean('is_active')->default(true);
            
            // Conditions for when this rule applies
            $table->json('conditions'); // Array of condition objects
            
            // Destinations (only for route actions)
            $table->json('destinations')->nullable(); // Array of destination objects with URLs and priorities
            
            // Statistics
            $table->integer('match_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['webhook_endpoint_id', 'priority']);
            $table->index(['webhook_endpoint_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_routing_rules');
    }
};
