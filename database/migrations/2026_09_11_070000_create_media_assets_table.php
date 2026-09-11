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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('site_id')->nullable();
            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('media');
            $table->string('storage_driver')->default('asset_storage'); // 'asset_storage' (S3/R2) or 'code' (Site Code Bundle)
            $table->string('file_type'); // 'image', 'video', 'audio', 'document', 'other'
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('public_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'file_type']);
            $table->index(['site_id', 'storage_driver']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
