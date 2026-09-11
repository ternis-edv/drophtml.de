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
            <div class="glass-card p-6 sm:p-7 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                <div class="mb-5">
                    <h3 class="font-bold text-base sm:text-lg text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                        <div class="size-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center ring-1 ring-indigo-500/20">
                            <flux:icon.globe-alt class="size-4.5" />
                        </div>
                        <span>Link a Custom Domain</span>
                    </h3>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                        Route your apex domain or subdomains directly to this static site with free automated Let's Encrypt TLS certificates.
                    </p>
                </div>

                <!-- Add Domain Form -->
                <form wire:submit="addDomain" class="flex flex-col sm:flex-row gap-2.5 mb-6">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                            <flux:icon.globe-alt class="size-4" />
                        </div>
                        <input 
                            wire:model="newDomain" 
                            type="text" 
                            placeholder="e.g. docs.mybrand.com or www.myportfolio.de" 
                            class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm rounded-xl bg-zinc-50 dark:bg-zinc-800/70 border border-zinc-200/80 dark:border-zinc-700/80 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-mono"
                            required 
                        />
                    </div>
                    <button 
                        type="submit" 
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition-transform hover:-translate-y-0.5 cursor-pointer shrink-0"
                    >
                        <flux:icon.plus class="size-4" />
                        <span>Add Domain</span>
                    </button>
                </form>

                <!-- Configured Domains List -->
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3 flex items-center justify-between">
                        <span>Configured Domains</span>
                        <span class="font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 font-bold border border-zinc-200/60 dark:border-zinc-700/60">{{ $site->domains->count() }}</span>
                    </div>

                    @if($site->domains->isEmpty())
                        <div class="py-14 text-center rounded-2xl bg-zinc-50/60 dark:bg-zinc-900/30 border border-dashed border-zinc-200 dark:border-zinc-800/80">
                            <div class="size-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 flex items-center justify-center mx-auto mb-3 ring-1 ring-indigo-500/20">
                                <flux:icon.globe-alt class="size-6" />
                            </div>
                            <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">No custom domains added yet</h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-xs mx-auto mt-1 leading-relaxed">Enter your custom domain above and point its CNAME record to complete setup.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($site->domains as $domain)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 sm:p-5 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/50 shadow-xs hover:border-indigo-500/30 transition-all">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.globe-alt class="size-4 text-indigo-500 shrink-0" />
                                            <a href="http://{{ $domain->domain }}" target="_blank" class="font-bold text-sm text-zinc-900 dark:text-zinc-100 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate font-mono">
                                                {{ $domain->domain }}
                                            </a>
                                            <a href="http://{{ $domain->domain }}" target="_blank" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                            </a>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @if($domain->verified_at)
                                                <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                                                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    DNS Verified &bull; SSL Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-medium">
                                                    <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                    Pending DNS Verification
                                                </span>
                                            @endif
                                            <span class="text-zinc-300 dark:text-zinc-700">&bull;</span>
                                            <span class="font-mono text-[11px]">Added {{ $domain->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                        <button 
                                            type="button" 
                                            wire:click="verifyDomain('{{ $domain->id }}')" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer border border-zinc-200/60 dark:border-zinc-700/60"
                                            title="Check DNS propagation"
                                        >
                                            <flux:icon.arrow-path class="size-3.5" />
                                            <span>Verify DNS</span>
                                        </button>

                                        <button 
                                            type="button" 
                                            wire:click="removeDomain('{{ $domain->id }}')" 
                                            wire:confirm="Remove custom domain '{{ $domain->domain }}'?" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/30 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 transition-colors cursor-pointer border border-rose-200/40 dark:border-rose-900/40"
                                            title="Remove Domain"
                                        >
                                            <flux:icon.trash class="size-3.5" />
                                            <span>Remove</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- DNS Configuration Instructions Sidebar (1 Col) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Platform URL -->
            <div class="glass-card p-5 sm:p-6 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-2.5 flex items-center gap-1.5">
                    <flux:icon.link class="size-3.5 text-indigo-500" />
                    <span>Default Platform URL</span>
                </div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900/90 rounded-2xl break-all text-xs font-mono border border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-2">
                    <a href="/s/{{ $site->slug }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline truncate font-semibold">
                        {{ url('/s/' . $site->slug) }}
                    </a>
                    <button 
                        type="button" 
                        @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug) }}'); Flux.toast('URL copied!')" 
                        class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-1.5 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors cursor-pointer shrink-0"
                        title="Copy URL"
                    >
                        <flux:icon.clipboard class="size-3.5" />
                    </button>
                </div>
            </div>

            <!-- DNS Setup Helper -->
            <div class="glass-card p-5 sm:p-6 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 mb-1 flex items-center gap-2">
                    <flux:icon.cpu-chip class="size-4 text-purple-500" />
                    <span>DNS Configuration</span>
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4 leading-relaxed">Add this CNAME record at your DNS provider (Cloudflare, dnbx.de, etc.):</p>

                <div class="space-y-2.5 text-xs font-mono">
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900/90 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-zinc-400 font-sans text-[11px] uppercase font-semibold">Type</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">CNAME</span>
                    </div>

                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900/90 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-zinc-400 font-sans text-[11px] uppercase font-semibold">Host / Name</span>
                        <div class="flex items-center gap-1.5 font-bold text-zinc-800 dark:text-zinc-200">
                            <span>@ or subdomain</span>
                        </div>
                    </div>

                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900/90 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-zinc-400 font-sans text-[11px] uppercase font-semibold">Target / Value</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold">sites.drophtml.de</span>
                            <button 
                                type="button" 
                                @click="navigator.clipboard.writeText('sites.drophtml.de'); Flux.toast('Target copied!')" 
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-md hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                title="Copy target"
                            >
                                <flux:icon.clipboard class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3.5 border-t border-zinc-100 dark:border-zinc-800 text-[11px] text-zinc-400 dark:text-zinc-500 flex items-center justify-between">
                    <span>Domain Registration Partner:</span>
                    <a href="https://dnbx.de" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">dnbx.de &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>