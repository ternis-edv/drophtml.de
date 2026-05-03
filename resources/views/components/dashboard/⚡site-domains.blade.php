<?php

use Livewire\Component;
use App\Models\Site;
use App\Models\Domain;

new class extends Component
{
    public $siteId;
    public $site;
    public $newDomain = '';
    
    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
    }
    
    public function addDomain()
    {
        $this->validate([
            'newDomain' => 'required|string|unique:domains,domain',
        ]);
        
        $this->site->domains()->create([
            'domain' => $this->newDomain,
            'is_custom' => true,
        ]);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'domain_added',
            'description' => "Added custom domain: {$this->newDomain}",
            'ip_address' => request()->ip(),
        ]);
        
        $this->newDomain = '';
        $this->site->load('domains');
        Flux::toast('Domain added successfully.');
    }
    
    public function removeDomain($domainId)
    {
        $domain = $this->site->domains()->findOrFail($domainId);
        $domainName = $domain->domain;
        $domain->delete();
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'domain_removed',
            'description' => "Removed custom domain: {$domainName}",
            'ip_address' => request()->ip(),
        ]);
        
        $this->site->load('domains');
        Flux::toast('Domain removed.');
    }

    public function render()
    {
        return view('components.dashboard.⚡site-domains');
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')">Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Domains</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold mt-2">Manage Domains</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <flux:card>
                <h3 class="font-semibold mb-4 text-lg">Custom Domains</h3>
                <p class="text-sm text-zinc-500 mb-6">Link your own custom domains to this site. Ensure you have configured a CNAME record pointing to <code>sites.drophtml.de</code>.</p>
                
                <form wire:submit="addDomain" class="flex gap-4 mb-8">
                    <div class="flex-1">
                        <flux:input wire:model="newDomain" placeholder="e.g., www.my-awesome-site.com" required />
                    </div>
                    <flux:button type="submit" variant="primary">Add Domain</flux:button>
                </form>
                
                @if($site->domains->isEmpty())
                    <div class="py-8 text-center text-zinc-500 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                        No custom domains configured for this site yet.
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($site->domains as $domain)
                            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-800 rounded-lg">
                                <div>
                                    <div class="font-medium flex items-center gap-2">
                                        <flux:icon.globe-alt class="w-4 h-4 text-zinc-400" />
                                        {{ $domain->domain }}
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-1">
                                        @if($domain->verified_at)
                                            <span class="text-green-500 flex items-center gap-1"><flux:icon.check-circle class="w-3 h-3" /> Verified</span>
                                        @else
                                            <span class="text-amber-500 flex items-center gap-1"><flux:icon.exclamation-circle class="w-3 h-3" /> Pending Verification</span>
                                        @endif
                                    </div>
                                </div>
                                <flux:button wire:click="removeDomain({{ $domain->id }})" variant="danger" size="sm" icon="trash">Remove</flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        </div>
        
        <div class="md:col-span-1 space-y-6">
            <flux:card>
                <h3 class="font-semibold mb-2">Default URL</h3>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-md break-all text-sm font-mono border border-zinc-200 dark:border-zinc-800">
                    <a href="/s/{{ $site->slug }}" target="_blank" class="text-blue-500 hover:underline">
                        {{ url('/s/' . $site->slug) }}
                    </a>
                </div>
            </flux:card>
            
            <flux:card>
                <h3 class="font-semibold mb-2">DNS Instructions</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">To connect your custom domain, add the following record to your DNS provider:</p>
                
                <div class="space-y-2 text-sm font-mono">
                    <div class="flex justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800">
                        <span class="text-zinc-500">Type:</span> <span>CNAME</span>
                    </div>
                    <div class="flex justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800">
                        <span class="text-zinc-500">Name:</span> <span>@ (or www)</span>
                    </div>
                    <div class="flex justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800">
                        <span class="text-zinc-500">Value:</span> <span>sites.drophtml.de</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>