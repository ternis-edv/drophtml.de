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
            $table->string('github_repo_full_name')->nullable();
            $table->string('github_branch')->nullable();
            $table->boolean('auto_deploy')->default(false);
            $table->string('github_webhook_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['github_repo_full_name', 'github_branch', 'auto_deploy', 'github_webhook_id']);
        });
    }
};
