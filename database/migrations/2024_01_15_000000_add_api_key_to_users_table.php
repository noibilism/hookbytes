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
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_key', 64)->unique()->nullable()->after('email_verified_at');
            $table->timestamp('api_key_created_at')->nullable()->after('api_key');
            $table->timestamp('api_key_last_used_at')->nullable()->after('api_key_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'api_key_created_at', 'api_key_last_used_at']);
        });
    }
};