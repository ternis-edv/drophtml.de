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
        Schema::table('views', function (Blueprint $table) {
            $table->unsignedSmallInteger('status_code')->default(200)->index()->after('path');
            $table->boolean('is_redirect')->default(false)->index()->after('is_quiet');
            $table->uuid('redirect_id')->nullable()->index()->after('is_redirect');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->dropColumn(['status_code', 'is_redirect', 'redirect_id']);
        });
    }
};
