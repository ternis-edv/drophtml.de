<?php

use App\Jobs\DispatchSiteWebhookJob;
use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\GitHubService;
use App\Services\SiteDeploymentService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public $siteId;
    public $site;

    public $autoDeploy;
    public $isPermanent;
    public $status;
    public $isPasswordProtected;
    public $sitePassword = '';
    public $isSpa = false;
    public $webhookUrl = '';
    public $webhookSecret = '';

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);

        $this->autoDeploy = (bool) $this->site->auto_deploy;
        $this->isPermanent = (bool) $this->site->is_permanent;
        $this->status = $this->site->status;
        $this->isPasswordProtected = (bool) $this->site->is_password_protected;
        $this->isSpa = (bool) $this->site->is_spa;
        $this->webhookUrl = (string) ($this->site->webhook_url ?? '');
        $this->webhookSecret = (string) ($this->site->webhook_secret ?? '');
    }

    public function save(GitHubService $gitHubService, ActivityLogService $activityLogService)
    {
        $rules = [
            'status' => 'required|in:active,suspended',
            'isPermanent' => 'boolean',
            'autoDeploy' => 'boolean',
            'isPasswordProtected' => 'boolean',
            'isSpa' => 'boolean',
            'webhookUrl' => 'nullable|url|max:2048',
            'webhookSecret' => 'nullable|string|max:255',
        ];

        if ($this->isPasswordProtected && empty($this->site->site_password)) {
            $rules['sitePassword'] = 'required|string|min:4';
        } elseif (! empty($this->sitePassword)) {
            $rules['sitePassword'] = 'string|min:4';
        }

        $this->validate($rules);

        $oldAutoDeploy = $this->site->auto_deploy;

        $updateData = [
            'auto_deploy' => $this->autoDeploy,
            'is_permanent' => $this->isPermanent,
            'status' => $this->status,
            'is_password_protected' => $this->isPasswordProtected,
            'is_spa' => $this->isSpa,
            'webhook_url' => ! empty($this->webhookUrl) ? $this->webhookUrl : null,
            'webhook_secret' => ! empty($this->webhookSecret) ? $this->webhookSecret : null,
        ];

        if ($this->isPermanent) {
            $updateData['expires_at'] = null;
        } elseif ($this->site->is_permanent && ! $this->isPermanent) {
            $updateData['expires_at'] = now()->addDays(30);
        }

        if (! empty($this->sitePassword)) {
            $updateData['site_password'] = Hash::make($this->sitePassword);
        } elseif (! $this->isPasswordProtected) {
            $updateData['site_password'] = null;
        }

        $this->site->update($updateData);

        // Handle webhook creation/deletion if auto-deploy changed
        $token = auth()->user()->github_token;
        if ($token) {
            if ($this->autoDeploy && ! $oldAutoDeploy && $this->site->github_repo_full_name) {
                $gitHubService->setupWebhook($this->site, $token);
            } elseif (! $this->autoDeploy && $oldAutoDeploy && $this->site->github_webhook_id) {
                $gitHubService->removeWebhook($this->site, $token);
            }
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'site_settings_updated',
            "Updated site settings for {$this->site->slug}"
        );

        $this->sitePassword = '';
        $this->site->refresh();
        Flux::toast('Site settings saved successfully.');
    }

    public function extendExpiry($days, ActivityLogService $activityLogService)
    {
        $this->site->expires_at = ($this->site->expires_at && $this->site->expires_at->isFuture())
            ? $this->site->expires_at->addDays($days)
            : now()->addDays($days);

        $this->site->save();

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'expiry_extended',
            "Extended expiry by {$days} days for site: {$this->site->slug}"
        );

        $this->site->refresh();
        Flux::toast("Lifetime extended by {$days} days.");
    }

    public function duplicateSite(SiteDeploymentService $deploymentService)
    {
        $cloned = $deploymentService->duplicateSite($this->site, auth()->user());
        Flux::toast("Site cloned as '{$cloned->slug}'!");

        return redirect()->route('dashboard.sites.settings', $cloned->id);
    }

    public function sendWebhookTestPing()
    {
        if (empty($this->webhookUrl) || ! filter_var($this->webhookUrl, FILTER_VALIDATE_URL)) {
            Flux::toast('Please enter a valid Webhook URL first.', variant: 'danger');

            return;
        }

        $this->site->update([
            'webhook_url' => $this->webhookUrl,
            'webhook_secret' => ! empty($this->webhookSecret) ? $this->webhookSecret : null,
        ]);

        DispatchSiteWebhookJob::dispatch($this->site, 'test.ping', [
            'triggered_by' => auth()->user()->email,
            'message' => 'Test ping dispatched from DropHTML dashboard',
        ]);

        Flux::toast('Test ping dispatched to webhook URL!');
    }

    public function deleteSite(SiteDeploymentService $deploymentService)
    {
        $deploymentService->deleteSite($this->site, auth()->user());
        Flux::toast('Site deleted successfully.');

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('components.dashboard.⚡site-settings');
    }
};
?>

<div>
    <!-- Subnavigation Header -->
    <x-site-subnav :site="$site" current="settings" />

    <div class="max-w-3xl space-y-6">
        <!-- Main Settings Form -->
        <div class="glass-card p-6 sm:p-8 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <form wire:submit="save" class="space-y-8">
                <!-- General & Status -->
                <div>
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:icon.adjustments-horizontal class="size-4.5 text-indigo-500" />
                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">General & Availability</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Deployment Access Status</flux:label>
                            <flux:select wire:model="status">
                                <flux:select.option value="active">Active (Online & Publicly Accessible)</flux:select.option>
                                <flux:select.option value="suspended">Suspended (Offline / 403 Forbidden)</flux:select.option>
                            </flux:select>
                            <flux:description class="text-xs text-zinc-500 mt-1">
                                Temporarily disable visitor access without deleting any files or records.
                            </flux:description>
                        </flux:field>
                    </div>
                </div>

                <!-- Lifecycle & Expiry -->
                <div>
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:icon.clock class="size-4.5 text-amber-500" />
                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Site Lifecycle & Expiry</h3>
                    </div>

                    <div class="space-y-4">
                        <flux:checkbox 
                            wire:model="isPermanent" 
                            label="Permanent Site" 
                            description="Prevents this site from ever expiring. Disables the automatic deletion timer." 
                        />

                        @if(!$isPermanent)
                            <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs text-zinc-500">Current Expiration:</div>
                                    <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">
                                        @if($site->expires_at)
                                            {{ $site->expires_at->format('M d, Y H:i') }} ({{ $site->expires_at->diffForHumans() }})
                                        @else
                                            No expiry set
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button 
                                        type="button" 
                                        wire:click="extendExpiry(7)" 
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors cursor-pointer"
                                    >
                                        +7 Days
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="extendExpiry(30)" 
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors cursor-pointer"
                                    >
                                        +30 Days
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Security & Password Protection -->
                <div>
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:icon.lock-closed class="size-4.5 text-purple-500" />
                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Access & Security</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <flux:checkbox 
                            wire:model.live="isPasswordProtected" 
                            label="Password Protection" 
                            description="Require visitors to enter a password before viewing this site or any of its files." 
                        />

                        @if($isPasswordProtected)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800 rounded-xl space-y-3">
                                <flux:field>
                                    <flux:label>Site Password</flux:label>
                                    <flux:input 
                                        wire:model="sitePassword" 
                                        type="password" 
                                        placeholder="{{ $site->site_password ? 'Leave blank to keep existing password' : 'Enter a password (min. 4 characters)' }}" 
                                        viewable
                                    />
                                    @if($site->site_password)
                                        <flux:description class="text-xs text-emerald-600 dark:text-emerald-400 mt-1.5 flex items-center gap-1.5 font-medium">
                                            <flux:icon.check-circle class="size-3.5" />
                                            Password protection is active. Enter a new password above only if you wish to change it.
                                        </flux:description>
                                    @endif
                                </flux:field>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- GitHub Integration -->
                @if($site->github_repo_full_name)
                    <div>
                        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                            <flux:icon.folder-git-2 class="size-4.5 text-zinc-500" />
                            <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">GitHub Continuous Deployment</h3>
                        </div>
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Connected Repository</flux:label>
                                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 font-mono text-xs text-zinc-800 dark:text-zinc-200">
                                    <svg class="size-4 text-zinc-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                                    <span class="font-semibold">{{ $site->github_repo_full_name }}</span>
                                    <span class="text-zinc-400">({{ $site->github_branch }})</span>
                                </div>
                            </flux:field>
                            
                            <flux:checkbox 
                                wire:model="autoDeploy" 
                                label="Automatic Push Deployments" 
                                description="Automatically redeploy whenever commits are pushed to the {{ $site->github_branch }} branch via GitHub webhooks." 
                            />
                        </div>
                    </div>
                @endif

                <!-- Routing & Framework Support -->
                <div>
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:icon.arrows-right-left class="size-4.5 text-blue-500" />
                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Routing &amp; Frameworks</h3>
                    </div>

                    <div class="space-y-4">
                        <flux:checkbox 
                            wire:model="isSpa" 
                            label="Single Page Application (SPA) Fallback" 
                            description="Automatically rewrite non-file URLs (like /dashboard or /profile) to /index.html with status 200. Essential for React, Vue, Vite, and Next.js static exports." 
                        />

                        <div class="p-4 rounded-xl bg-zinc-50/70 dark:bg-zinc-900/40 border border-zinc-200/70 dark:border-zinc-800 text-xs text-zinc-600 dark:text-zinc-400 space-y-2">
                            <div class="font-semibold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                <flux:icon.information-circle class="size-3.5 text-blue-500" />
                                <span>Advanced Routing &amp; Headers</span>
                            </div>
                            <p class="leading-relaxed">
                                You can also commit a <code class="font-mono bg-zinc-200/60 dark:bg-zinc-800 px-1 py-0.5 rounded text-zinc-900 dark:text-zinc-100">_redirects</code> or <code class="font-mono bg-zinc-200/60 dark:bg-zinc-800 px-1 py-0.5 rounded text-zinc-900 dark:text-zinc-100">_headers</code> file at your project root for custom 301/302 redirects, wildcard splats, CORS, and security policies.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Outgoing Deployment Webhooks -->
                <div>
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:icon.signal class="size-4.5 text-emerald-500" />
                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Outgoing Webhooks</h3>
                    </div>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Webhook Delivery URL</flux:label>
                            <flux:input 
                                wire:model="webhookUrl" 
                                type="url" 
                                placeholder="https://api.yourdomain.com/webhooks or Slack/Discord webhook URL" 
                            />
                            <flux:description class="text-xs text-zinc-500 mt-1">
                                We will send an HTTP POST payload whenever deployments, rollbacks, or site clones occur.
                            </flux:description>
                        </flux:field>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Webhook Secret Key (Optional)</flux:label>
                                <flux:input 
                                    wire:model="webhookSecret" 
                                    type="password" 
                                    placeholder="Secret key for HMAC signature verification" 
                                    viewable
                                />
                                <flux:description class="text-xs text-zinc-500 mt-1">
                                    Signed via <code class="font-mono text-zinc-700 dark:text-zinc-300">X-DropHTML-Signature: sha256=...</code>
                                </flux:description>
                            </flux:field>

                            <div class="flex items-end pb-1">
                                <button 
                                    type="button" 
                                    wire:click="sendWebhookTestPing" 
                                    class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors cursor-pointer flex items-center justify-center gap-2"
                                >
                                    <flux:icon.paper-airplane class="size-3.5 text-emerald-500" />
                                    <span>Send Test Ping</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-5 border-t border-zinc-100 dark:border-zinc-800">
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-xl font-semibold text-xs bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition-colors cursor-pointer"
                    >
                        Save Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Universal Backup & Export Card -->
        <div class="glass-card p-6 sm:p-7 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.arrow-down-tray class="size-4 text-emerald-500" />
                        <span>Data Export &amp; Site Archive</span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-md leading-relaxed">
                        Download a complete portable ZIP archive of your site. Choose between code files only or a universal archive bundling both code and hosted cloud media.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a 
                        href="{{ route('dashboard.sites.download', $site) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors"
                    >
                        <flux:icon.arrow-down-tray class="size-3.5 text-zinc-400" />
                        <span>Code ZIP</span>
                    </a>
                    <a 
                        href="{{ route('dashboard.sites.download', ['site' => $site, 'media' => 1]) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition-colors"
                    >
                        <flux:icon.archive-box class="size-3.5 text-emerald-100" />
                        <span>Universal Archive (with Cloud Media)</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Duplicate Site Card -->
        <div class="glass-card p-6 sm:p-7 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.document-duplicate class="size-4 text-indigo-500" />
                        <span>Clone &amp; Duplicate Site</span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-md leading-relaxed">
                        Create an exact duplicate of this site with all code and cloud media assets under a new unique slug. Ideal for staging or A/B testing.
                    </p>
                </div>
                <button 
                    type="button"
                    wire:click="duplicateSite" 
                    wire:confirm="Clone this site into a new copy?"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors cursor-pointer shrink-0"
                >
                    <flux:icon.document-duplicate class="size-3.5 text-indigo-500" />
                    <span>Clone Site</span>
                </button>
            </div>
        </div>

        <!-- Danger Zone Card -->
        <div class="glass-card p-6 sm:p-7 rounded-3xl border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/10 dark:bg-rose-950/10 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-sm text-rose-600 dark:text-rose-400 flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4" />
                        <span>Danger Zone: Delete Site</span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-md leading-relaxed">
                        Permanently delete this site, all uploaded HTML and asset files, custom domain links, and visitor telemetry. This action cannot be undone.
                    </p>
                </div>
                <button 
                    type="button"
                    wire:click="deleteSite" 
                    wire:confirm="Are you sure you want to permanently delete '{{ $site->original_name ?: $site->slug }}'? This action cannot be undone." 
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 hover:bg-rose-500 text-white shadow-xs transition-colors cursor-pointer shrink-0"
                >
                    <flux:icon.trash class="size-3.5" />
                    <span>Delete This Site</span>
                </button>
            </div>
        </div>
    </div>
</div>