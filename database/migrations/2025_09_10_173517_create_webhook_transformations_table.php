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
        Schema::create('webhook_transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['jq', 'javascript', 'template', 'field_mapping']);
            $table->json('transformation_rules'); // Store the actual transformation logic
            $table->json('conditions')->nullable(); // Optional conditions for when to apply
            $table->integer('priority')->default(0); // Order of execution for multiple transformations
            $table->boolean('is_active')->default(true);
            $table->json('test_input')->nullable(); // Sample input for testing
            $table->json('expected_output')->nullable(); // Expected output for testing
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
            
            $table->index(['webhook_endpoint_id', 'is_active']);
            $table->index(['webhook_endpoint_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_transformations');
    }
};
