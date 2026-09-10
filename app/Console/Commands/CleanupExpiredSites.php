<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteDeploymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sites:cleanup')]
#[Description('Remove expired temporary sites and their storage files.')]
class CleanupExpiredSites extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SiteDeploymentService $siteDeploymentService): void
    {
        $expiredSites = Site::where('is_permanent', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = $expiredSites->count();
        $this->info("Found {$count} expired sites.");

        foreach ($expiredSites as $site) {
            $this->comment("Deleting expired site: {$site->slug} ({$site->original_name})...");
            $siteDeploymentService->deleteSite($site);
        }

        $this->info('Cleanup completed successfully.');
    }
}
