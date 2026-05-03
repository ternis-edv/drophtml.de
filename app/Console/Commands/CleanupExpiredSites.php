<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('sites:cleanup')]
#[Description('Remove expired temporary sites and their files.')]
class CleanupExpiredSites extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredSites = Site::where('is_permanent', false)
            ->where('expires_at', '<=', now())
            ->get();

        $count = $expiredSites->count();
        $this->info("Found {$count} expired sites.");

        foreach ($expiredSites as $site) {
            $this->comment("Deleting site: {$site->slug}...");
            
            // Delete files
            Storage::disk('public')->deleteDirectory($site->path);
            
            // Delete record
            $site->delete();
        }

        $this->info('Cleanup completed.');
    }
}
