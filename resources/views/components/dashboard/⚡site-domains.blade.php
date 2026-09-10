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
    public $dnsType = 'cname'; // cname, apex

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
    }

    public function addDomain(DomainService $domainService)
    {
        // Normalize input: strip protocol and trailing paths
        $clean = preg_replace('#^https?://#i', '', trim($this->newDomain));
        $clean = trim(explode('/', $clean)[0]);
        $this->newDomain = strtolower($clean);

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
            Flux::toast('Domain added successfully. Follow the DNS instructions below to complete setup.');
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
            Flux::toast('DNS verified successfully! SSL certificate is active.');
        } else {
            Flux::toast(variant: 'warning', text: 'DNS record not detected yet. DNS propagation may take up to 10 minutes.');
        }
    }

    public function render()
    {
        return view('components.dashboard.⚡site-domains');
    }
};
?>

<div>
    <!-- Subnavigation Header -->
    <x-site-subnav :site="$site" current="domains" />

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Domains List & Add Form (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <div class="mb-4">
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Link a Domain</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Route any custom domain or subdomain directly to this static site.</p>
                </div>

                <!-- Add Domain Form -->
                <form wire:submit="addDomain" class="flex flex-col sm:flex-row gap-2.5 mb-6">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <flux:icon.globe-alt class="size-4" />
                        </div>
                        <input 
                            wire:model="newDomain" 
                            type="text" 
                            placeholder="e.g. docs.mybrand.com or www.myportfolio.de" 
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required 
                        />
                    </div>
                    <flux:button type="submit" variant="primary" class="bg-indigo-600 hover:bg-indigo-500 text-white">
                        <flux:icon.plus class="size-4 mr-1" />
                        Add Domain
                    </flux:button>
                </form>

                <!-- Configured Domains List -->
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">Configured Domains ({{ $site->domains->count() }})</div>

                    @if($site->domains->isEmpty())
                        <div class="py-12 text-center rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-dashed border-zinc-200 dark:border-zinc-800">
                            <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400 flex items-center justify-center mx-auto mb-3">
                                <flux:icon.globe-alt class="size-6" />
                            </div>
                            <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">No custom domains added yet</h4>
                            <p class="text-xs text-zinc-400 max-w-xs mx-auto mt-1">Enter your domain name above to connect it with automatic HTTPS/SSL.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($site->domains as $domain)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.globe-alt class="size-4 text-indigo-500 shrink-0" />
                                            <a href="http://{{ $domain->domain }}" target="_blank" class="font-bold text-sm text-zinc-900 dark:text-zinc-100 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate">
                                                {{ $domain->domain }}
                                            </a>
                                            <a href="http://{{ $domain->domain }}" target="_blank" class="text-zinc-400 hover:text-zinc-600">
                                                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                            </a>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-zinc-500">
                                            @if($domain->verified_at)
                                                <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                                    <flux:icon.check-circle class="size-3.5" />
                                                    DNS Verified & SSL Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium">
                                                    <flux:icon.clock class="size-3.5" />
                                                    Pending DNS Verification
                                                </span>
                                            @endif
                                            <span class="text-zinc-300 dark:text-zinc-700">&bull;</span>
                                            <span>Added {{ $domain->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 self-end sm:self-center">
                                        <flux:button wire:click="verifyDomain('{{ $domain->id }}')" variant="ghost" size="xs" icon="arrow-path" title="Check DNS status">
                                            Verify DNS
                                        </flux:button>
                                        <flux:button wire:click="removeDomain('{{ $domain->id }}')" wire:confirm="Remove custom domain '{{ $domain->domain }}'?" variant="danger" size="xs" icon="trash">
                                            Remove
                                        </flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- DNS Configuration Instructions Sidebar (1 Col) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Platform URL -->
            <flux:card>
                <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Default Platform URL</div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg break-all text-xs font-mono border border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-2">
                    <a href="/s/{{ $site->slug }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline truncate">
                        {{ url('/s/' . $site->slug) }}
                    </a>
                    <button type="button" @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug) }}'); Flux.toast('URL copied!')" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-1">
                        <flux:icon.clipboard class="size-3.5" />
                    </button>
                </div>
            </flux:card>

            <!-- DNS Setup Helper -->
            <flux:card>
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 mb-1">DNS Configuration</h3>
                <p class="text-xs text-zinc-500 mb-4">Add this CNAME record at your DNS provider (Cloudflare, dnbx.de, GoDaddy, etc.):</p>

                <div class="space-y-2 text-xs font-mono">
                    <div class="p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-zinc-400 font-sans text-[11px] uppercase">Type</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">CNAME</span>
                    </div>

                    <div class="p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-zinc-400 font-sans text-[11px] uppercase">Host / Name</span>
                        <div class="flex items-center gap-1.5 font-bold text-zinc-800 dark:text-zinc-200">
                            <span>@ or subdomain</span>
                        </div>
                    </div>

                    <div class="p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-zinc-400 font-sans text-[11px] uppercase">Target / Value</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold">sites.drophtml.de</span>
                            <button type="button" @click="navigator.clipboard.writeText('sites.drophtml.de'); Flux.toast('Target copied!')" class="text-zinc-400 hover:text-zinc-600">
                                <flux:icon.clipboard class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-[11px] text-zinc-400 flex items-center justify-between">
                    <span>Domain Registration Partner:</span>
                    <a href="https://dnbx.de" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">dnbx.de &rarr;</a>
                </div>
            </flux:card>
        </div>
    </div>
</div>