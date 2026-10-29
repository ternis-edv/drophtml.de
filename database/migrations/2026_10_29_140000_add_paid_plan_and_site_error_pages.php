<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('free');
        });

        Schema::create('site_error_pages', function (Blueprint $table) {
            $table->id();
            $table->string('site_id');
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->unsignedSmallInteger('status_code');
            $table->mediumText('html');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'status_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_error_pages');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('plan'));
    }
};
