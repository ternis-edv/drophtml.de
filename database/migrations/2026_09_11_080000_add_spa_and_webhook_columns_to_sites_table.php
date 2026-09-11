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
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('is_spa')->default(false)->after('is_permanent');
            $table->string('webhook_url', 2048)->nullable()->after('site_password');
            $table->string('webhook_secret', 255)->nullable()->after('webhook_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['is_spa', 'webhook_url', 'webhook_secret']);
        });
    }
};
