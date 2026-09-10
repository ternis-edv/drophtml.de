<?php

use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\GitHubService;
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

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);

        $this->autoDeploy = (bool) $this->site->auto_deploy;
        $this->isPermanent = (bool) $this->site->is_permanent;
        $this->status = $this->site->status;
        $this->isPasswordProtected = (bool) $this->site->is_password_protected;
    }

    public function save(GitHubService $gitHubService, ActivityLogService $activityLogService)
    {
        $rules = [
            'status' => 'required|in:active,suspended',
            'isPermanent' => 'boolean',
            'autoDeploy' => 'boolean',
            'isPasswordProtected' => 'boolean',
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

    public function render()
    {
        return view('components.dashboard.⚡site-settings');
    }
};
?>

<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $this->site->original_name ?: $this->site->slug }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <h1 class="text-2xl font-bold mt-2">Site Settings</h1>
    </div>

    <div class="max-w-2xl space-y-6">
        <flux:card>
            <form wire:submit="save" class="space-y-8">
                <!-- General Settings -->
                <flux:fieldset>
                    <flux:legend>General Settings</flux:legend>
                    
                    <div class="space-y-4 mt-4">
                        <flux:field>
                            <flux:label>Deployment Status</flux:label>
                            <flux:select wire:model="status">
                                <flux:select.option value="active">Active (Online & Accessible)</flux:select.option>
                                <flux:select.option value="suspended">Suspended (Offline / 403)</flux:select.option>
                            </flux:select>
                        </flux:field>

                        <flux:checkbox 
                            wire:model="isPermanent" 
                            label="Mark as Permanent" 
                            description="Prevents this site from ever expiring. Disables the 30-day automatic deletion timer." 
                        />
                    </div>
                </flux:fieldset>

                <!-- Security & Password Protection -->
                <flux:fieldset>
                    <flux:legend>Access & Security</flux:legend>
                    
                    <div class="space-y-4 mt-4">
                        <flux:checkbox 
                            wire:model.live="isPasswordProtected" 
                            label="Password Protection" 
                            description="Lock this site with a password so only visitors with the password can view it." 
                        />

                        @if($isPasswordProtected)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg space-y-3">
                                <flux:field>
                                    <flux:label>Site Password</flux:label>
                                    <flux:input 
                                        wire:model="sitePassword" 
                                        type="password" 
                                        placeholder="{{ $site->site_password ? 'Leave blank to keep existing password' : 'Enter a secure password (min. 4 characters)' }}" 
                                        viewable
                                    />
                                    @if($site->site_password)
                                        <flux:description class="text-xs text-zinc-500">
                                            A password is currently set and active. Entering a new password will update it.
                                        </flux:description>
                                    @endif
                                </flux:field>
                            </div>
                        @endif
                    </div>
                </flux:fieldset>

                <!-- GitHub Integration -->
                @if($site->github_repo_full_name)
                    <flux:fieldset>
                        <flux:legend>GitHub Integration</flux:legend>
                        <div class="space-y-4 mt-4">
                            <flux:field>
                                <flux:label>Repository</flux:label>
                                <flux:input value="{{ $site->github_repo_full_name }}" disabled />
                            </flux:field>
                            
                            <flux:checkbox 
                                wire:model="autoDeploy" 
                                label="Enable Auto-Deploy" 
                                description="Automatically re-deploy when you push to the {{ $site->github_branch }} branch via webhook." 
                            />
                        </div>
                    </flux:fieldset>
                @endif

                <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</div>