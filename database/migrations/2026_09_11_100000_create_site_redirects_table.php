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
        Schema::create('site_redirects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('site_id');
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->string('from_path', 2048);
            $table->string('to_url', 2048);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'is_active']);
            $table->index(['site_id', 'from_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_redirects');
    }
};
