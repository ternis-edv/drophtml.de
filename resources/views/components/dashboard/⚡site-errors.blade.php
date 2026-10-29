<?php

use App\Models\Site;
use App\Models\SiteErrorPage;
use App\Services\ActivityLogService;
use App\Services\SiteErrorPageService;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $siteId;

    public int $statusCode = 404;

    public string $html = '';

    public bool $enabled = true;

    public function mount(string $siteId): void
    {
        $this->siteId = $siteId;
        $this->loadPage();
    }

    private function ownedSite(): Site
    {
        return auth()->user()->sites()->findOrFail($this->siteId);
    }

    private function paidSite(): Site
    {
        $site = $this->ownedSite();
        abort_unless(auth()->user()->fresh()->hasPaidPlan(), 403, 'Custom error pages require a paid account.');

        return $site;
    }

    private function loadPage(): void
    {
        $site = $this->ownedSite();
        $this->validate(['statusCode' => 'required|integer|in:403,404,500,503']);
        $page = auth()->user()->fresh()->hasPaidPlan()
            ? $site->errorPages()->where('status_code', $this->statusCode)->first()
            : null;
        $this->html = $page?->html ?? app(SiteErrorPageService::class)->starter($this->statusCode);
        $this->enabled = $page?->enabled ?? true;
    }

    public function selectStatus(int $status): void
    {
        abort_unless(in_array($status, SiteErrorPage::STATUSES, true), 422);
        $this->statusCode = $status;
        $this->loadPage();
        $this->resetValidation();
    }

    public function useStarter(): void
    {
        $this->paidSite();
        $this->validate(['statusCode' => 'required|integer|in:403,404,500,503']);
        $this->html = app(SiteErrorPageService::class)->starter($this->statusCode);
        $this->dispatch('error-page-template-loaded');
    }

    public function save(ActivityLogService $activity): void
    {
        $site = $this->paidSite();
        $this->validate([
            'statusCode' => 'required|integer|in:403,404,500,503',
            'html' => 'required|string|max:100000',
            'enabled' => 'boolean',
        ]);
        $site->errorPages()->updateOrCreate(['status_code' => $this->statusCode], [
            'html' => $this->html, 'enabled' => $this->enabled,
        ]);
        $activity->log(auth()->user(), $site, 'site_error_page_updated', "Updated {$this->statusCode} error page for {$site->slug}");
        $this->dispatch('error-page-saved');
        Flux::toast('Error page saved.');
    }

    public function removeOverride(ActivityLogService $activity): void
    {
        $site = $this->paidSite();
        $this->validate(['statusCode' => 'required|integer|in:403,404,500,503']);
        $site->errorPages()->where('status_code', $this->statusCode)->delete();
        $activity->log(auth()->user(), $site, 'site_error_page_removed', "Removed {$this->statusCode} error page for {$site->slug}");
        $this->loadPage();
        $this->dispatch('error-page-saved');
        Flux::toast('Override removed. The fallback page is active.');
    }

    public function render()
    {
        $site = $this->ownedSite();
        $paid = auth()->user()->fresh()->hasPaidPlan();
        $previewStatus = in_array($this->statusCode, SiteErrorPage::STATUSES, true) ? $this->statusCode : 404;

        return view('components.dashboard.⚡site-errors', [
            'site' => $site, 'paid' => $paid,
            'savedPage' => $site->errorPages()->where('status_code', $previewStatus)->first(),
            'previewHtml' => $paid ? app(SiteErrorPageService::class)->renderHtml($this->html, $site, $previewStatus, url('/s/'.$site->slug).'/', '/example-missing-page') : '',
        ]);
    }
};
?>
<div x-data="{ dirty: false }" x-on:error-page-saved.window="dirty = false" x-on:error-page-template-loaded.window="dirty = true"
    @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }"
    x-on:livewire:navigate.window="if (dirty) { if (!confirm('Leave without saving your error page?')) $event.preventDefault(); else dirty = false }">
    <x-site-subnav :site="$site" current="errors" />
    @if(!$paid)
        <section class="dashboard-panel p-6 sm:p-8 max-w-3xl">
            <div class="dashboard-icon-lg mb-5"><flux:icon.document-text class="size-6" /></div>
            <x-dashboard.badge color="indigo">Paid feature</x-dashboard.badge>
            <h2 class="dashboard-section-title mt-4">Make every detour feel like your website.</h2>
            <p class="dashboard-lede mt-2 max-w-xl">Paid accounts can design a separate page for 404, 403, 500, and 503 responses, with an HTML editor and a live preview.</p>
            <div class="dashboard-inset dashboard-rounded p-4 mt-6 text-xs dashboard-muted">Free sites can still use a <code>404.html</code> file included in their deployment. Without one, visitors see a site-specific error page.</div>
            <a href="mailto:support@drophtml.de?subject=Paid%20account%20access" class="dashboard-button mt-6">Ask about paid access <flux:icon.arrow-up-right class="size-4" /></a>
        </section>
    @else
        <div class="dashboard-error-workspace">
            <section class="dashboard-panel overflow-hidden min-w-0">
                <div class="dashboard-error-toolbar">
                    <label class="dashboard-muted text-xs" for="error-status">Error page</label>
                    <select id="error-status" aria-label="Error status" wire:loading.attr="disabled" wire:target="save,selectStatus" @change="if (!dirty || confirm('Discard unsaved changes and switch pages?')) { dirty = false; $wire.selectStatus(Number($event.target.value)) } else { $event.target.value = String($wire.statusCode) }">
                        @foreach([404 => '404 · Not found', 403 => '403 · Unavailable', 500 => '500 · Server error', 503 => '503 · Temporarily unavailable'] as $status => $label)
                            <option value="{{ $status }}" @selected($statusCode === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="ml-auto text-[10px] dashboard-muted" x-text="dirty ? 'Unsaved changes' : @js($savedPage ? 'Saved override' : 'Starter template')"></span>
                </div>
                <form wire:submit="save">
                    <label class="sr-only" for="error-html">Custom error page HTML</label>
                    <textarea id="error-html" class="dashboard-error-source" wire:model.live.debounce.400ms="html" @input="dirty = true" spellcheck="false" maxlength="100000"></textarea>
                    <div class="px-4 py-3 border-t dashboard-border space-y-3">
                        @error('html')<p class="dashboard-danger text-xs" role="alert">{{ $message }}</p>@enderror
                        @error('statusCode')<p class="dashboard-danger text-xs" role="alert">{{ $message }}</p>@enderror
                        <flux:checkbox wire:model="enabled" @change="dirty = true" label="Use this custom page" description="When disabled, your site's fallback page is shown instead." />
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button data-dashboard-variant="primary" variant="primary" type="submit" wire:loading.attr="disabled" wire:target="save,html,selectStatus">Save error page</flux:button>
                            <flux:button data-dashboard-variant="ghost" variant="ghost" wire:click="useStarter" wire:confirm="Replace this draft with the starter template? Save to publish it.">Use starter</flux:button>
                            @if($savedPage)<flux:button data-dashboard-variant="ghost" variant="ghost" class="dashboard-danger" wire:click="removeOverride" wire:confirm="Remove this saved override and use the fallback page?">Remove override</flux:button>@endif
                        </div>
                    </div>
                </form>
            </section>
            <section class="dashboard-panel overflow-hidden min-w-0">
                <div class="dashboard-error-toolbar"><span class="dashboard-muted text-xs">Live preview</span><x-dashboard.badge color="zinc">{{ $statusCode }}</x-dashboard.badge><span class="ml-auto dashboard-muted text-[10px]">Scripts disabled in preview</span></div>
                <iframe class="dashboard-error-preview" title="Custom error page preview" sandbox="" referrerpolicy="no-referrer" srcdoc="{{ $previewHtml }}"></iframe>
            </section>
        </div>
        <section class="dashboard-panel mt-6 p-5">
            <h2 class="dashboard-section-title">A page that stays yours.</h2>
            <p class="dashboard-lede mt-2">Use HTML and inline CSS, or reference assets in your site bundle. Saved pages work on your site URL, subdomain, and custom domains. They keep the original HTTP error status and survive redeploys.</p>
            <div class="dashboard-error-variables mt-4">
                @foreach(['site_name' => 'The site name', 'site_url' => 'The home URL on the current domain', 'status_code' => 'The HTTP error code', 'requested_path' => 'The requested path, without query parameters'] as $variable => $description)
                    <div><code>{{ '{'.'{'.$variable.'}'.'}' }}</code><span>{{ $description }}</span></div>
                @endforeach
            </div>
            <p class="dashboard-lede mt-4">For 404s, an enabled saved override takes priority over your uploaded <code>404.html</code>. Password prompts are unchanged. If the account returns to Free, saved overrides are retained but the fallback is served.</p>
        </section>
    @endif
</div>
