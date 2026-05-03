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
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('is_permanent')->default(false);
            $table->string('status')->default('active'); // active, suspended, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'views', 'is_permanent', 'status']);
        });
    }
};
