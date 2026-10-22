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
            $table->string('ternis_id')->nullable()->unique()->after('github_refresh_token');
            $table->text('ternis_token')->nullable()->after('ternis_id');
            $table->text('ternis_refresh_token')->nullable()->after('ternis_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ternis_id', 'ternis_token', 'ternis_refresh_token']);
        });
    }
};
