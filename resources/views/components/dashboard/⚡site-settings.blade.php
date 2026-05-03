<?php

use Livewire\Component;
use App\Models\Site;
use Illuminate\Support\Facades\Http;
use App\Models\ActivityLog;

new class extends Component
{
    public $siteId;
    public $site;
    
    public $autoDeploy;
    public $isPermanent;
    public $status;
    
    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
        
        $this->autoDeploy = (bool) $this->site->auto_deploy;
        $this->isPermanent = (bool) $this->site->is_permanent;
        $this->status = $this->site->status;
    }
    
    public function save()
    {
        $oldAutoDeploy = $this->site->auto_deploy;
        
        $this->site->update([
            'auto_deploy' => $this->autoDeploy,
            'is_permanent' => $this->isPermanent,
            'status' => $this->status,
        ]);
        
        // Handle webhook creation/deletion if auto-deploy changed
        if ($this->autoDeploy && !$oldAutoDeploy && $this->site->github_repo_full_name) {
            $this->setupWebhook();
        } elseif (!$this->autoDeploy && $oldAutoDeploy && $this->site->github_webhook_id) {
            $this->removeWebhook();
        }
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'site_settings_updated',
            'description' => "Updated site settings",
            'ip_address' => request()->ip(),
        ]);
        
        Flux::toast('Settings saved.');
    }
    
    protected function setupWebhook()
    {
        $token = auth()->user()->github_token;
        $fullName = $this->site->github_repo_full_name;
        $webhookUrl = url('/webhooks/github');

        try {
            $response = Http::withToken($token)->post("https://api.github.com/repos/{$fullName}/hooks", [
                'name' => 'web',
                'active' => true,
                'events' => ['push'],
                'config' => [
                    'url' => $webhookUrl,
                    'content_type' => 'json',
                    'insecure_ssl' => '0',
                ],
            ]);

            if ($response->successful()) {
                $this->site->update(['github_webhook_id' => $response->json()['id']]);
            }
        } catch (\Exception $e) {
             \Illuminate\Support\Facades\Log::error("Webhook setup failed: " . $e->getMessage());
        }
    }
    
    protected function removeWebhook()
    {
        $token = auth()->user()->github_token;
        $fullName = $this->site->github_repo_full_name;
        $hookId = $this->site->github_webhook_id;

        try {
            Http::withToken($token)->delete("https://api.github.com/repos/{$fullName}/hooks/{$hookId}");
        } catch (\Exception $e) {}
        
        $this->site->update(['github_webhook_id' => null]);
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
            <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <h1 class="text-2xl font-bold mt-2">Site Settings</h1>
    </div>

    <div class="max-w-2xl">
        <flux:card>
            <form wire:submit="save" class="space-y-8">
                <flux:fieldset>
                    <flux:legend>General Settings</flux:legend>
                    
                    <div class="space-y-4 mt-4">
                        <flux:field>
                            <flux:label>Deployment Status</flux:label>
                            <flux:select wire:model="status">
                                <flux:select.option value="active">Active</flux:select.option>
                                <flux:select.option value="suspended">Suspended</flux:select.option>
                            </flux:select>
                        </flux:field>

                        <flux:checkbox wire:model="isPermanent" label="Mark as Permanent" description="Prevents this site from ever expiring." />
                    </div>
                </flux:fieldset>

                @if($site->github_repo_full_name)
                    <flux:fieldset>
                        <flux:legend>GitHub Integration</flux:legend>
                        <div class="space-y-4 mt-4">
                            <flux:field>
                                <flux:label>Repository</flux:label>
                                <flux:input value="{{ $site->github_repo_full_name }}" disabled />
                            </flux:field>
                            
                            <flux:checkbox wire:model="autoDeploy" label="Enable Auto-Deploy" description="Automatically re-deploy when you push to the {{ $site->github_branch }} branch." />
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