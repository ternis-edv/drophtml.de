<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->renameColumn('path', 'storage_key');
            $table->renameColumn('storage_driver', 'storage_location');
            $table->string('extension')->nullable();
        });
        // Keep legacy public_url values until object migration; existing embeds must survive.
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->renameColumn('storage_key', 'path');
            $table->renameColumn('storage_location', 'storage_driver');
            $table->dropColumn('extension');
        });
    }
};
