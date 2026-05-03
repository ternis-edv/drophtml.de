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
        Schema::table('users', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('sites', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('domains', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('activity_logs', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('deployments', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('views', function (Blueprint $table) { $table->softDeletes(); });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('sites', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('domains', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('activity_logs', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('deployments', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('views', function (Blueprint $table) { $table->dropSoftDeletes(); });
    }
};
