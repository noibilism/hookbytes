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
        Schema::create('dead_letters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('delivery_id');
            $table->text('reason');
            $table->json('dump');
            $table->timestamp('created_at');
            
            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('cascade');
            
            $table->index('delivery_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dead_letters');
    }
};
