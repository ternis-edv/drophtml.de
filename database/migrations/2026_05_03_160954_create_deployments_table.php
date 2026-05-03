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
        Schema::create('deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('site_id');
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('commit_hash')->nullable();
            $table->string('commit_message')->nullable();
            $table->string('source')->nullable(); // manual, github_webhook, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
