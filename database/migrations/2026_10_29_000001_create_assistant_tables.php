<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable()->index();
            $table->string('kind');
            $table->string('status');
            $table->text('payload');
            $table->timestamp('created_at')->useCurrent()->index();
        });
        Schema::create('assistant_budgets', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedInteger('used')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_budgets');
        Schema::dropIfExists('assistant_audits');
    }
};
