<?php

use App\Models\Domain;
use App\Models\Site;
use App\Rules\DomainNameRule;
use App\Services\DomainService;
use Livewire\Component;

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

    public function addDomain(DomainService $domainService)
    {
        $this->validate([
            'newDomain' => [
                'required',
                'string',
                'max:255',
                'unique:domains,domain',
                new DomainNameRule($domainService),
            ],
        ]);

        try {
            $domain = $domainService->createDomain(
                $this->site,
                $this->newDomain,
                true,
                auth()->user()
            );

            $this->newDomain = '';
            $this->site->load('domains');
            Flux::toast('Domain added successfully.');
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function removeDomain($domainId, DomainService $domainService)
    {
        $domain = $this->site->domains()->findOrFail($domainId);
        $domainService->deleteDomain($domain, auth()->user());

        $this->site->load('domains');
        Flux::toast('Domain removed.');
    }

    public function verifyDomain($domainId, DomainService $domainService)
    {
        $domain = $this->site->domains()->findOrFail($domainId);
        $isConfigured = $domainService->verifyDns($domain);

        $this->site->load('domains');

        if ($isConfigured) {
            Flux::toast('Domain DNS verified successfully!');
        } else {
            Flux::toast(variant: 'warning', text: 'DNS record not detected yet. Propagation may take a few minutes.');
        }
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
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Domains</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold mt-2">Manage Domains</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <flux:card>
                <h3 class="font-semibold mb-2 text-lg">Custom Domains</h3>
                <p class="text-sm text-zinc-500 mb-6">Link your own custom domain or subdomain to this site. Ensure you have configured a CNAME record pointing to <code>sites.drophtml.de</code>.</p>
                
                <form wire:submit="addDomain" class="flex gap-3 mb-8">
                    <div class="flex-1">
                        <flux:input wire:model="newDomain" placeholder="e.g. www.my-awesome-site.com" required />
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
                            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-zinc-50/50 dark:bg-zinc-900/50">
                                <div>
                                    <div class="font-medium flex items-center gap-2">
                                        <flux:icon.globe-alt class="w-4 h-4 text-indigo-500" />
                                        <a href="http://{{ $domain->domain }}" target="_blank" class="hover:underline text-zinc-900 dark:text-zinc-100">
                                            {{ $domain->domain }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-1 flex items-center gap-3">
                                        @if($domain->verified_at)
                                            <span class="text-green-500 flex items-center gap-1"><flux:icon.check-circle class="w-3.5 h-3.5" /> Verified (Active)</span>
                                        @else
                                            <span class="text-amber-500 flex items-center gap-1"><flux:icon.exclamation-circle class="w-3.5 h-3.5" /> Pending DNS</span>
                                        @endif
                                        <span class="text-zinc-400">&bull;</span>
                                        <span>Added {{ $domain->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:button wire:click="verifyDomain('{{ $domain->id }}')" variant="ghost" size="sm" icon="arrow-path">
                                        Verify DNS
                                    </flux:button>
                                    <flux:button wire:click="removeDomain('{{ $domain->id }}')" wire:confirm="Are you sure you want to remove this domain?" variant="danger" size="sm" icon="trash">
                                        Remove
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        </div>
        
        <div class="md:col-span-1 space-y-6">
            <flux:card>
                <h3 class="font-semibold mb-2">Default Platform URL</h3>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-md break-all text-sm font-mono border border-zinc-200 dark:border-zinc-800">
                    <a href="/s/{{ $site->slug }}" target="_blank" class="text-indigo-500 hover:underline">
                        {{ url('/s/' . $site->slug) }}
                    </a>
                </div>
            </flux:card>
            
            <flux:card>
                <h3 class="font-semibold mb-2">DNS Setup Instructions</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">To route your domain to DropHTML, add the following CNAME record in your domain registrar or DNS provider (e.g. Cloudflare, dnbx.de, GoDaddy):</p>
                
                <div class="space-y-2 text-sm font-mono">
                    <div class="flex justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800">
                        <span class="text-zinc-500 font-sans text-xs">Record Type:</span> <span class="font-bold">CNAME</span>
                    </div>
                    <div class="flex justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800">
                        <span class="text-zinc-500 font-sans text-xs">Name / Host:</span> <span>@ (or subdomain)</span>
                    </div>
                    <div class="flex justify-between p-2 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800">
                        <span class="text-zinc-500 font-sans text-xs">Target / Value:</span> <span class="text-indigo-500">sites.drophtml.de</span>
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">
                    Need domain registration? Powered by <a href="https://dnbx.de" target="_blank" rel="noopener" class="text-indigo-500 hover:underline">dnbx.de</a>.
                </div>
            </flux:card>
        </div>
    </div>
</div>