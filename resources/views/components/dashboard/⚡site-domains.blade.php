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
        <div class="lg:col-span-2">
            <div class="dashboard-panel p-6 sm:p-7">
                <div class="mb-5">
                    <span class="dashboard-eyebrow mb-2.5">Custom Domains</span>
                    <h3 class="dashboard-section-title     ">Link a Custom Domain</h3>
                    <p class="text-sm dashboard-muted mt-1.5 leading-relaxed">
                        Route your apex domain or subdomains directly to this static site with free automated Let's Encrypt TLS certificates.
                    </p>
                </div>

                <!-- Add Domain Form -->
                <form wire:submit="addDomain" class="flex flex-col sm:flex-row gap-2.5 mb-7">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none dashboard-muted">
                            <flux:icon.globe-alt class="size-4" />
                        </div>
                        <input
                            wire:model="newDomain"
                            type="text"
                            placeholder="e.g. docs.mybrand.com or www.myportfolio.de"
                            class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm dashboard-control-rounded dashboard-surface border dashboard-border dashboard-text placeholder-zinc-400    transition-all font-mono"
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        class="dashboard-button dashboard-button-primary px-5 py-2.5 text-xs shrink-0"
                    >
                        <flux:icon.plus class="size-4" />
                        <span>Add Domain</span>
                    </button>
                </form>

                <!-- Configured Domains List -->
                <div>
                    <div class="text-xs font-medium uppercase tracking-wider dashboard-muted mb-3 flex items-center justify-between">
                        <span>Configured Domains</span>
                        <span class="font-mono text-[11px] px-2.5 py-0.5 rounded-full dashboard-raised dashboard-muted font-medium border dashboard-border">{{ $site->domains->count() }}</span>
                    </div>

                    @if($site->domains->isEmpty())
                        <div class="py-14 text-center dashboard-rounded dashboard-surface border border-dashed dashboard-border">
                            <div class="size-12 dashboard-rounded dashboard-accent-tint dashboard-accent  flex items-center justify-center mx-auto mb-3 ">
                                <flux:icon.globe-alt class="size-6" />
                            </div>
                            <h4 class="text-sm font-medium tracking-tight dashboard-text">No custom domains added yet</h4>
                            <p class="text-xs dashboard-muted max-w-xs mx-auto mt-1 leading-relaxed">Enter your custom domain above and point its CNAME record to complete setup.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($site->domains as $domain)
                                <div class="dashboard-panel dashboard-panel-interactive p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.globe-alt class="size-4 dashboard-accent  shrink-0" />
                                            <a href="http://{{ $domain->domain }}" target="_blank" class="font-medium text-sm dashboard-text dashboard-accent  hover:underline truncate font-mono">
                                                {{ $domain->domain }}
                                            </a>
                                            <a href="http://{{ $domain->domain }}" target="_blank" class="dashboard-muted dashboard-hover-text">
                                                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                            </a>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs dashboard-muted">
                                            @if($domain->verified_at)
                                                <span class="inline-flex items-center gap-1.5 dashboard-success font-medium">
                                                    <span class="size-1.5 rounded-full dashboard-success-solid"></span>
                                                    DNS Verified &bull; SSL Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 dashboard-warning font-medium">
                                                    <span class="size-1.5 rounded-full dashboard-warning-solid"></span>
                                                    Pending DNS Verification
                                                </span>
                                            @endif
                                            <span class="dashboard-text">&bull;</span>
                                            <span class="font-mono text-[11px]">Added {{ $domain->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                        <button
                                            type="button"
                                            wire:click="verifyDomain('{{ $domain->id }}')"
                                            class="dashboard-button px-3 py-1.5 text-xs"
                                            title="Check DNS propagation"
                                        >
                                            <flux:icon.arrow-path class="size-3.5" />
                                            <span>Verify DNS</span>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="removeDomain('{{ $domain->id }}')"
                                            wire:confirm="Remove custom domain '{{ $domain->domain }}'?"
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium dashboard-control-rounded dashboard-danger-tint dashboard-danger transition-colors cursor-pointer border dashboard-danger-border"
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
            <div class="dashboard-panel p-5 sm:p-6">
                <div class="text-xs font-medium uppercase tracking-wider dashboard-muted mb-2.5 flex items-center gap-1.5">
                    <flux:icon.link class="size-3.5 dashboard-accent " />
                    <span>Default Platform URL</span>
                </div>
                <div class="p-3 dashboard-surface dashboard-control-rounded break-all text-xs font-mono border dashboard-border flex items-center justify-between gap-2">
                    <a href="/s/{{ $site->slug }}" target="_blank" class="dashboard-accent  hover:underline truncate font-medium">
                        {{ url('/s/' . $site->slug) }}
                    </a>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug) }}'); Flux.toast('URL copied!')"
                        class="dashboard-muted dashboard-hover-text p-1.5 dashboard-control-rounded dashboard-hover-surface transition-colors cursor-pointer shrink-0"
                        title="Copy URL"
                    >
                        <flux:icon.clipboard class="size-3.5" />
                    </button>
                </div>
            </div>

            <!-- DNS Setup Helper -->
            <div class="dashboard-panel p-5 sm:p-6">
                <h3 class="dashboard-section-title  text-sm   mb-1 flex items-center gap-2">
                    <flux:icon.cpu-chip class="size-4 dashboard-accent " />
                    <span>DNS Configuration</span>
                </h3>
                <p class="text-xs dashboard-muted mb-4 leading-relaxed">Add this CNAME record at your DNS provider (Cloudflare, dnbx.de, etc.):</p>

                <div class="space-y-2.5 text-xs font-mono">
                    <div class="p-3 dashboard-surface dashboard-control-rounded border dashboard-border flex items-center justify-between">
                        <span class="dashboard-muted font-sans text-[11px] uppercase font-medium">Type</span>
                        <span class="font-medium dashboard-text">CNAME</span>
                    </div>

                    <div class="p-3 dashboard-surface dashboard-control-rounded border dashboard-border flex items-center justify-between">
                        <span class="dashboard-muted font-sans text-[11px] uppercase font-medium">Host / Name</span>
                        <div class="flex items-center gap-1.5 font-medium dashboard-text">
                            <span>@ or subdomain</span>
                        </div>
                    </div>

                    <div class="p-3 dashboard-surface dashboard-control-rounded border dashboard-border flex items-center justify-between">
                        <span class="dashboard-muted font-sans text-[11px] uppercase font-medium">Target / Value</span>
                        <div class="flex items-center gap-1.5">
                            <span class="dashboard-accent  font-medium">sites.drophtml.de</span>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('sites.drophtml.de'); Flux.toast('Target copied!')"
                                class="dashboard-muted dashboard-hover-text p-1 rounded-md dashboard-hover-surface transition-colors cursor-pointer"
                                title="Copy target"
                            >
                                <flux:icon.clipboard class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3.5 border-t dashboard-border text-[11px] dashboard-muted flex items-center justify-between">
                    <span>Domain Registration Partner:</span>
                    <a href="https://dnbx.de" target="_blank" rel="noopener" class="dashboard-accent  font-medium hover:underline">dnbx.de &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
