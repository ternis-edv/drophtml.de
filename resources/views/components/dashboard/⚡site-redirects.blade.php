<?php

use App\Models\Site;
use App\Models\SiteRedirect;
use App\Rules\SafeRedirectTarget;
use App\Services\ActivityLogService;
use App\Services\SiteServingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public $siteId;
    public $site;

    // Filters
    public $search = '';
    public $statusFilter = 'all';

    // Create / Edit modal state
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingRedirectId = null;

    // Form fields
    public $fromPath = '';
    public $toUrl = '';
    public $statusCode = 301;
    public $description = '';
    public $isActive = true;

    // Delete confirmation
    public $confirmingDeletionId = null;

    public function mount($siteId)
    {
        $this->siteId = $siteId instanceof Site ? $siteId->id : $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
    }

    public function openCreateModal(?string $preset = null)
    {
        $this->resetValidation();
        $this->editingRedirectId = null;
        $this->statusCode = 301;
        $this->isActive = true;

        if ($preset === 'shortlink') {
            $this->fromPath = '/social';
            $this->toUrl = 'https://twitter.com/';
            $this->description = 'Social media short link';
        } elseif ($preset === 'migration') {
            $this->fromPath = '/old-page';
            $this->toUrl = '/new-page';
            $this->description = '301 permanent page rename';
        } elseif ($preset === 'wildcard') {
            $this->fromPath = '/docs/*';
            $this->toUrl = '/documentation/:splat';
            $this->description = 'Wildcard documentation splat rewrite';
        } else {
            $this->fromPath = '';
            $this->toUrl = '';
            $this->description = '';
        }

        $this->showCreateModal = true;
    }

    public function createRedirect(ActivityLogService $activityLogService)
    {
        $this->validate([
            'fromPath' => ['required', 'string', 'max:2048'],
            'toUrl' => ['required', 'string', 'max:2048', new SafeRedirectTarget],
            'statusCode' => ['required', 'integer', Rule::in([200, 301, 302, 307, 308])],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedFrom = '/'.ltrim(trim($this->fromPath), '/');

        $redirect = $this->site->redirects()->create([
            'from_path' => $normalizedFrom,
            'to_url' => trim($this->toUrl),
            'status_code' => (int) $this->statusCode,
            'is_active' => true,
            'description' => ! empty($this->description) ? trim($this->description) : null,
        ]);

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'site_redirect_created',
            "Created redirect rule {$normalizedFrom} -> {$this->toUrl} ({$this->statusCode}) for site {$this->site->slug}"
        );

        $this->showCreateModal = false;
        $this->reset(['fromPath', 'toUrl', 'description', 'statusCode']);
        Flux::toast('Redirect rule created successfully.');
    }

    public function editRedirect(string $id)
    {
        $this->resetValidation();
        $redirect = $this->site->redirects()->findOrFail($id);

        $this->editingRedirectId = $redirect->id;
        $this->fromPath = $redirect->from_path;
        $this->toUrl = $redirect->to_url;
        $this->statusCode = (int) $redirect->status_code;
        $this->isActive = (bool) $redirect->is_active;
        $this->description = (string) ($redirect->description ?? '');

        $this->showEditModal = true;
    }

    public function updateRedirect(ActivityLogService $activityLogService)
    {
        $this->validate([
            'fromPath' => ['required', 'string', 'max:2048'],
            'toUrl' => ['required', 'string', 'max:2048', new SafeRedirectTarget],
            'statusCode' => ['required', 'integer', Rule::in([200, 301, 302, 307, 308])],
            'description' => ['nullable', 'string', 'max:255'],
            'isActive' => ['boolean'],
        ]);

        $redirect = $this->site->redirects()->findOrFail($this->editingRedirectId);
        $normalizedFrom = '/'.ltrim(trim($this->fromPath), '/');

        $redirect->update([
            'from_path' => $normalizedFrom,
            'to_url' => trim($this->toUrl),
            'status_code' => (int) $this->statusCode,
            'is_active' => (bool) $this->isActive,
            'description' => ! empty($this->description) ? trim($this->description) : null,
        ]);

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'site_redirect_updated',
            "Updated redirect rule {$normalizedFrom} for site {$this->site->slug}"
        );

        $this->showEditModal = false;
        $this->reset(['editingRedirectId', 'fromPath', 'toUrl', 'description', 'statusCode']);
        Flux::toast('Redirect rule updated.');
    }

    public function toggleActive(string $id)
    {
        $redirect = $this->site->redirects()->findOrFail($id);
        $redirect->is_active = ! $redirect->is_active;
        $redirect->save();

        Flux::toast($redirect->is_active ? 'Redirect rule enabled.' : 'Redirect rule paused.');
    }

    public function deleteRedirect(string $id, ActivityLogService $activityLogService)
    {
        $redirect = $this->site->redirects()->findOrFail($id);
        $from = $redirect->from_path;
        $redirect->delete();

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'site_redirect_deleted',
            "Deleted redirect rule {$from} for site {$this->site->slug}"
        );

        $this->confirmingDeletionId = null;
        Flux::toast('Redirect rule removed.');
    }

    public function importFromFile(SiteServingService $servingService, ActivityLogService $activityLogService)
    {
        $disk = Storage::disk('public');
        $redirectsFilePath = "{$this->site->path}/_redirects";

        if (! $disk->fileExists($redirectsFilePath)) {
            Flux::toast('No _redirects file found in site repository.', variant: 'warning');

            return;
        }

        $content = $disk->get($redirectsFilePath);
        $rules = $servingService->parseRedirectRules($content);

        $imported = 0;
        $skipped = 0;
        foreach ($rules as $rule) {
            if (! SafeRedirectTarget::isSafe($rule['to'] ?? null)) {
                $skipped++;

                continue;
            }

            $fromPath = '/'.ltrim($rule['from'], '/');
            $exists = $this->site->redirects()->where('from_path', $fromPath)->exists();

            if (! $exists) {
                $this->site->redirects()->create([
                    'from_path' => $fromPath,
                    'to_url' => $rule['to'],
                    'status_code' => $rule['status'] ?? 301,
                    'is_active' => true,
                    'description' => 'Imported from _redirects file',
                ]);
                $imported++;
            }
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'site_redirects_imported',
            "Imported {$imported} redirect rules from _redirects file"
        );

        $toast = "Imported {$imported} redirect rule(s) from _redirects file.";
        if ($skipped > 0) {
            $toast .= " Skipped {$skipped} rule(s) with unsafe targets.";
        }

        Flux::toast($toast);
    }

    public function render()
    {
        $query = $this->site->redirects();

        if (! empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('from_path', 'like', "%{$s}%")
                    ->orWhere('to_url', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'active') {
                $query->where('is_active', true);
            } elseif ($this->statusFilter === 'inactive') {
                $query->where('is_active', false);
            } elseif (is_numeric($this->statusFilter)) {
                $query->where('status_code', (int) $this->statusFilter);
            }
        }

        $redirects = $query->orderByDesc('hit_count')->get();

        $totalRedirects = $this->site->redirects()->count();
        $activeRedirects = $this->site->redirects()->where('is_active', true)->count();
        $totalHits = (int) $this->site->redirects()->sum('hit_count');
        $topRule = $this->site->redirects()->orderByDesc('hit_count')->first();

        $hasFileRedirects = Storage::disk('public')->fileExists("{$this->site->path}/_redirects");

        return view('components.dashboard.⚡site-redirects', [
            'redirects' => $redirects,
            'totalRedirects' => $totalRedirects,
            'activeRedirects' => $activeRedirects,
            'totalHits' => $totalHits,
            'topRule' => $topRule,
            'hasFileRedirects' => $hasFileRedirects,
        ]);
    }
};
?>
<div class="space-y-6">
    <!-- Subnavigation Header -->
    <x-site-subnav :site="$site" current="redirects" />

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Rules -->
        <div class="panel panel-interactive p-5 rise-in" style="--i: 0">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Configured Rules</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-accent dark:text-accent-content">
                    <flux:icon.arrows-right-left class="size-3.5" />
                    Routing
                </span>
            </div>
            <div class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalRedirects) }}
            </div>
            <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-2 font-mono">
                {{ $activeRedirects }} active &bull; {{ $totalRedirects - $activeRedirects }} paused
            </div>
        </div>

        <!-- Lifetime Redirect Hits -->
        <div class="panel panel-interactive p-5 rise-in" style="--i: 1">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Total Redirect Hits</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-accent dark:text-accent-content">
                    <flux:icon.arrow-trending-up class="size-3.5" />
                    Traffic
                </span>
            </div>
            <div class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalHits) }}
            </div>
            <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-2 font-mono">
                Telemetry tracked per visit
            </div>
        </div>

        <!-- Most Active Rule -->
        <div class="panel panel-interactive p-5 rise-in" style="--i: 2">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Top Performing Rule</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-accent dark:text-accent-content">
                    <flux:icon.sparkles class="size-3.5" />
                    Popular
                </span>
            </div>
            @if($topRule)
                <div class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100 truncate font-mono" title="{{ $topRule->from_path }}">
                    {{ $topRule->from_path }}
                </div>
                <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-2 font-mono">
                    {{ number_format($topRule->hit_count) }} hits recorded
                </div>
            @else
                <div class="text-base font-medium text-zinc-400 mt-1">No rules yet</div>
                <div class="text-xs text-zinc-400 mt-2 font-mono">&mdash;</div>
            @endif
        </div>

        <!-- Direct Serving Status -->
        <div class="panel panel-interactive p-5 rise-in" style="--i: 3">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Engine Integration</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-accent dark:text-accent-content">
                    <flux:icon.bolt class="size-3.5" />
                    Instant
                </span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-accent dark:text-accent-content">
                Edge Ready
            </div>
            <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-2 font-mono">
                301, 302, 307, 308 &amp; 200 rewrites
            </div>
        </div>
    </div>

    <!-- Actions & Filter Controls Bar -->
    <div class="panel p-4 rise-in flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4" style="--i: 4">
        <!-- Left: Search and Filters -->
        <div class="flex flex-wrap items-center gap-3 flex-1">
            <div class="relative w-full sm:w-64">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search paths or targets..."
                    icon="magnifying-glass"
                    size="sm"
                    clearable
                />
            </div>

            <div class="pill-group" role="group" aria-label="Filter redirects by status">
                @foreach (['all' => 'All', 'active' => 'Active', '301' => '301', '302' => '302', '200' => '200 Rewrite'] as $fKey => $fLabel)
                    <button
                        type="button"
                        wire:click="$set('statusFilter', '{{ $fKey }}')"
                        aria-pressed="{{ $statusFilter === $fKey ? 'true' : 'false' }}"
                        @class(['pill', 'is-active' => $statusFilter === $fKey])
                    >{{ $fLabel }}</button>
                @endforeach
            </div>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            @if($hasFileRedirects)
                <button
                    type="button"
                    wire:click="importFromFile"
                    wire:confirm="Import existing rules from your _redirects file into the dashboard?"
                    class="btn-secondary px-3 py-1.5 text-xs"
                    title="Import rules from static _redirects file into database"
                >
                    <flux:icon.arrow-down-tray class="size-3.5" />
                    <span>Import _redirects</span>
                </button>
            @endif

            <flux:dropdown>
                <flux:button size="sm" variant="filled">
                    <flux:icon.plus class="size-3.5 mr-1" />
                    <span>New Redirect</span>
                </flux:button>

                <flux:menu>
                    <flux:menu.item wire:click="openCreateModal()" icon="plus">Custom Redirect Rule</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item wire:click="openCreateModal('shortlink')" icon="link">Marketing Shortlink (/social)</flux:menu.item>
                    <flux:menu.item wire:click="openCreateModal('migration')" icon="arrow-path">Page Rename (/old -> /new)</flux:menu.item>
                    <flux:menu.item wire:click="openCreateModal('wildcard')" icon="arrows-pointing-out">Wildcard Splat (/docs/*)</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <!-- Redirects Table -->
    <div class="panel rise-in overflow-hidden" style="--i: 5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-500 uppercase tracking-wider bg-zinc-50 dark:bg-zinc-950/40">
                        <th class="py-3.5 px-5">Source Path</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Destination Target</th>
                        <th class="py-3.5 px-4 text-center">Hits</th>
                        <th class="py-3.5 px-4">Last Triggered</th>
                        <th class="py-3.5 px-4 text-center">Active</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse($redirects as $redirect)
                        <tr class="group hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30 transition-colors {{ ! $redirect->is_active ? 'opacity-60' : '' }}">
                            <!-- Source Path -->
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-semibold text-xs text-zinc-900 dark:text-zinc-100 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700">
                                        {{ $redirect->from_path }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug . $redirect->from_path) }}'); Flux.toast('Full redirect URL copied!')"
                                        class="text-zinc-400 hover:text-accent dark:hover:text-accent-content opacity-0 group-hover:opacity-100 transition-opacity p-0.5"
                                        title="Copy live redirect link"
                                    >
                                        <flux:icon.clipboard class="size-3.5" />
                                    </button>
                                </div>
                                @if($redirect->description)
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 truncate max-w-xs">{{ $redirect->description }}</p>
                                @endif
                            </td>

                            <!-- HTTP Status Code -->
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold font-mono bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                                    @if($redirect->status_code === 301)
                                        301 Perm
                                    @elseif($redirect->status_code === 302)
                                        302 Temp
                                    @elseif($redirect->status_code === 200)
                                        200 Rewrite
                                    @else
                                        {{ $redirect->status_code }}
                                    @endif
                                </span>
                            </td>

                            <!-- Destination Target -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 max-w-xs sm:max-w-md">
                                    <flux:icon.arrow-right class="size-3.5 text-zinc-400 shrink-0" />
                                    <span class="font-mono text-xs text-zinc-700 dark:text-zinc-300 truncate" title="{{ $redirect->to_url }}">
                                        {{ $redirect->to_url }}
                                    </span>
                                    @if(str_starts_with($redirect->to_url, 'http://') || str_starts_with($redirect->to_url, 'https://'))
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-accent/10 text-accent dark:text-accent-content border border-accent/20 shrink-0">
                                            External
                                        </span>
                                    @else
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 shrink-0">
                                            Internal
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Hits -->
                            <td class="py-3 px-4 text-center">
                                <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded-full {{ $redirect->hit_count > 0 ? 'bg-accent/10 text-accent dark:text-accent-content' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                    {{ number_format($redirect->hit_count) }}
                                </span>
                            </td>

                            <!-- Last Triggered -->
                            <td class="py-3 px-4 text-xs font-mono text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $redirect->last_accessed_at ? $redirect->last_accessed_at->diffForHumans() : 'Never' }}
                            </td>

                            <!-- Toggle Active -->
                            <td class="py-3 px-4 text-center">
                                <button
                                    type="button"
                                    wire:click="toggleActive('{{ $redirect->id }}')"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden {{ $redirect->is_active ? 'bg-accent' : 'bg-zinc-300 dark:bg-zinc-700' }}"
                                    title="{{ $redirect->is_active ? 'Click to pause' : 'Click to enable' }}"
                                >
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out {{ $redirect->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Test Redirect Live -->
                                    <a
                                        href="{{ url('/s/' . $site->slug . $redirect->from_path) }}"
                                        target="_blank"
                                        class="p-1.5 text-zinc-400 hover:text-accent dark:hover:text-accent-content rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                        title="Test redirect rule live"
                                    >
                                        <flux:icon.arrow-top-right-on-square class="size-4" />
                                    </a>

                                    <!-- Edit -->
                                    <button
                                        type="button"
                                        wire:click="editRedirect('{{ $redirect->id }}')"
                                        class="p-1.5 text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                        title="Edit rule"
                                    >
                                        <flux:icon.pencil-square class="size-4" />
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        type="button"
                                        wire:click="deleteRedirect('{{ $redirect->id }}')"
                                        wire:confirm="Are you sure you want to remove this redirect rule?"
                                        class="p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer"
                                        title="Delete rule"
                                    >
                                        <flux:icon.trash class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-zinc-400 text-xs">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon.arrows-right-left class="size-8 text-zinc-300 dark:text-zinc-700" />
                                    <p class="font-medium text-zinc-600 dark:text-zinc-300">No redirect rules found.</p>
                                    <p class="text-zinc-500">Create your first redirect URL or import existing rules from your code bundle.</p>
                                    <button
                                        type="button"
                                        wire:click="openCreateModal()"
                                        class="btn-primary mt-2 px-3.5 py-1.5 text-xs"
                                    >
                                        <flux:icon.plus class="size-3.5" />
                                        <span>Create First Redirect</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Redirect Modal -->
    <flux:modal wire:model="showCreateModal" class="space-y-6 max-w-lg">
        <div>
            <h3 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <flux:icon.arrows-right-left class="size-5 text-accent dark:text-accent-content" />
                Add Redirect Rule
            </h3>
            <p class="text-xs text-zinc-500 mt-1">Configure an instant HTTP redirect or internal rewrite for this site.</p>
        </div>

        <form wire:submit="createRedirect" class="space-y-4">
            <!-- Source Path -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Source Path (From)</label>
                <flux:input
                    wire:model="fromPath"
                    placeholder="/old-path or /blog/*"
                    icon="link"
                    required
                />
                <p class="text-[11px] text-zinc-500 mt-1">Leading slash is automatically added. Wildcards (<code class="font-mono text-zinc-700 dark:text-zinc-300">*</code>) and parameters (<code class="font-mono text-zinc-700 dark:text-zinc-300">:slug</code>) supported.</p>
                @error('fromPath') <span class="text-red-600 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Destination Target -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Destination Target (To)</label>
                <flux:input
                    wire:model="toUrl"
                    placeholder="/new-path, /posts/:splat, or https://example.com"
                    icon="arrow-top-right-on-square"
                    required
                />
                <p class="text-[11px] text-zinc-500 mt-1">Can be an internal relative path or an external URL (<code class="font-mono text-zinc-700 dark:text-zinc-300">https://...</code>).</p>
                @error('toUrl') <span class="text-red-600 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- HTTP Status Code -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Redirect Type (HTTP Status)</label>
                <flux:select wire:model="statusCode">
                    <flux:select.option :value="301">301 - Moved Permanently (Default, SEO Recommended)</flux:select.option>
                    <flux:select.option :value="302">302 - Found / Temporary</flux:select.option>
                    <flux:select.option :value="307">307 - Temporary Redirect (Preserves HTTP Method)</flux:select.option>
                    <flux:select.option :value="308">308 - Permanent Redirect (Preserves HTTP Method)</flux:select.option>
                    <flux:select.option :value="200">200 - Internal Rewrite (SPA / Proxy Fallback)</flux:select.option>
                </flux:select>
                @error('statusCode') <span class="text-red-600 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Description (Optional)</label>
                <flux:input
                    wire:model="description"
                    placeholder="e.g. Marketing promo link or renamed blog section"
                />
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="filled">Save Redirect Rule</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Edit Redirect Modal -->
    <flux:modal wire:model="showEditModal" class="space-y-6 max-w-lg">
        <div>
            <h3 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <flux:icon.pencil-square class="size-5 text-accent dark:text-accent-content" />
                Edit Redirect Rule
            </h3>
            <p class="text-xs text-zinc-500 mt-1">Modify target destination, status code, or rule status.</p>
        </div>

        <form wire:submit="updateRedirect" class="space-y-4">
            <!-- Source Path -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Source Path (From)</label>
                <flux:input
                    wire:model="fromPath"
                    placeholder="/old-path"
                    icon="link"
                    required
                />
                @error('fromPath') <span class="text-red-600 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Destination Target -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Destination Target (To)</label>
                <flux:input
                    wire:model="toUrl"
                    placeholder="/new-path or https://example.com"
                    icon="arrow-top-right-on-square"
                    required
                />
                @error('toUrl') <span class="text-red-600 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- HTTP Status Code -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Redirect Type (HTTP Status)</label>
                <flux:select wire:model="statusCode">
                    <flux:select.option :value="301">301 - Moved Permanently (Default, SEO Recommended)</flux:select.option>
                    <flux:select.option :value="302">302 - Found / Temporary</flux:select.option>
                    <flux:select.option :value="307">307 - Temporary Redirect</flux:select.option>
                    <flux:select.option :value="308">308 - Permanent Redirect</flux:select.option>
                    <flux:select.option :value="200">200 - Internal Rewrite</flux:select.option>
                </flux:select>
            </div>

            <!-- Status Toggle -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-200 dark:border-zinc-800">
                <div>
                    <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 block">Rule Enabled</span>
                    <span class="text-[11px] text-zinc-500">Temporarily pause or activate this redirect.</span>
                </div>
                <flux:switch wire:model="isActive" />
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Description (Optional)</label>
                <flux:input
                    wire:model="description"
                    placeholder="Notes or campaign details"
                />
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="filled">Save Changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
