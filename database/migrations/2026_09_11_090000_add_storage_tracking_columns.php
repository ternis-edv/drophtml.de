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
            $table->unsignedBigInteger('storage_limit_bytes')->default(1073741824)->after('password_set_at'); // 1 GB default
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_bytes')->default(0)->after('views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('storage_limit_bytes');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('storage_bytes');
        });
    }
};
