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
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->string('url_path')->unique();
            $table->json('destination_urls');
            $table->string('auth_method')->default('hmac'); // hmac, shared_secret, none
            $table->string('auth_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('retry_config')->nullable();
            $table->json('headers_config')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
