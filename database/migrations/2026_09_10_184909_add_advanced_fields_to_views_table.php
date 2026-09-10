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
        Schema::table('views', function (Blueprint $table) {
            $table->string('path', 2048)->nullable()->after('referer');
            $table->string('referer_domain')->nullable()->index()->after('path');
            $table->string('country_code', 2)->nullable()->index()->after('referer_domain');
            $table->string('browser', 60)->nullable()->index()->after('country_code');
            $table->string('os', 60)->nullable()->index()->after('browser');
            $table->string('device', 30)->nullable()->index()->after('os');
            $table->boolean('is_bot')->default(false)->index()->after('device');
            $table->string('utm_source')->nullable()->index()->after('is_bot');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->index()->after('utm_medium');
            $table->string('utm_term')->nullable()->after('utm_campaign');
            $table->string('utm_content')->nullable()->after('utm_term');

            $table->index(['site_id', 'is_quiet', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->dropIndex(['site_id', 'is_quiet', 'created_at']);
            $table->dropColumn([
                'path',
                'referer_domain',
                'country_code',
                'browser',
                'os',
                'device',
                'is_bot',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
            ]);
        });
    }
};
