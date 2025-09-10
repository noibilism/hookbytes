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
        Schema::table('projects', function (Blueprint $table) {
            $table->json('allowed_ips')->nullable()->after('webhook_secret');
            $table->json('rate_limits')->nullable()->after('allowed_ips');
            $table->json('permissions')->nullable()->after('rate_limits');
            $table->boolean('require_https')->default(false)->after('permissions');
            $table->boolean('encrypt_payloads')->default(false)->after('require_https');
            $table->string('encryption_key')->nullable()->after('encrypt_payloads');
            $table->timestamp('last_activity_at')->nullable()->after('encryption_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_ips',
                'rate_limits',
                'permissions',
                'require_https',
                'encrypt_payloads',
                'encryption_key',
                'last_activity_at'
            ]);
        });
    }
};
