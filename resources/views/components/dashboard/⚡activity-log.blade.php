<?php

use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $actionFilter = 'all';

    public function setFilter($filter)
    {
        $this->actionFilter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        $query = auth()->user()->activityLogs()->with('site')->latest();

        if ($this->actionFilter !== 'all') {
            if ($this->actionFilter === 'deploys') {
                $query->whereIn('action', ['site_uploaded', 'site_redeployed', 'site_rollback', 'site_duplicated', 'github_deployed', 'github_auto_deployed']);
            } elseif ($this->actionFilter === 'files') {
                $query->whereIn('action', ['file_created', 'file_edited', 'file_deleted', 'file_renamed', 'file_uploaded_to_manager']);
            } elseif ($this->actionFilter === 'domains') {
                $query->whereIn('action', ['domain_added', 'domain_removed']);
            } elseif ($this->actionFilter === 'lifecycle') {
                $query->whereIn('action', ['expiry_extended', 'site_updated', 'site_settings_updated']);
            }
        }

        $logs = $query->paginate(20);

        return view('components.dashboard.⚡activity-log', [
            'logs' => $logs,
        ]);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Activity Log</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="page-title mt-1.5 flex items-center gap-3">
                <span class="icon-tile" aria-hidden="true">
                    <flux:icon.clipboard-document-list class="size-4.5" />
                </span>
                <span>Audit &amp; Activity Log</span>
            </h1>
        </div>

        <!-- Filter Pills -->
        <div class="pill-group shrink-0" role="group" aria-label="Filter activity by type">
            @foreach (['all' => 'All', 'deploys' => 'Deploys', 'files' => 'Files', 'domains' => 'Domains', 'lifecycle' => 'Lifecycle'] as $filterKey => $filterLabel)
                <button
                    type="button"
                    wire:click="setFilter('{{ $filterKey }}')"
                    aria-pressed="{{ $actionFilter === $filterKey ? 'true' : 'false' }}"
                    @class(['pill', 'is-active' => $actionFilter === $filterKey])
                >{{ $filterLabel }}</button>
            @endforeach
        </div>
    </div>

    <!-- Activity Log List Card -->
    <div class="panel p-6 rise-in" style="--i: 1">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Event / Action</flux:table.column>
                    <flux:table.column>Target Site</flux:table.column>
                    <flux:table.column>Details & Description</flux:table.column>
                    <flux:table.column>IP Address</flux:table.column>
                    <flux:table.column class="text-right">Timestamp</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($logs as $log)
                        <flux:table.row class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors duration-150">
                            <flux:table.cell>
                                @php
                                    $action = $log->action;
                                    $color = 'zinc';
                                    if (str_contains($action, 'create') || str_contains($action, 'deploy')) $color = 'emerald';
                                    elseif (str_contains($action, 'delete')) $color = 'red';
                                    elseif (str_contains($action, 'domain')) $color = 'indigo';
                                    elseif (str_contains($action, 'expiry')) $color = 'amber';
                                    elseif (str_contains($action, 'update')) $color = 'zinc';
                                    elseif (str_contains($action, 'file')) $color = 'indigo';
                                @endphp
                                <flux:badge size="sm" :color="$color" class="font-mono text-[11px]">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($log->site)
                                    <a href="/s/{{ $log->site->slug }}" target="_blank" class="font-mono text-xs text-accent dark:text-accent-content hover:underline">
                                        {{ $log->site->slug }}
                                    </a>
                                @else
                                    <span class="text-xs text-zinc-400 font-mono">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-700 dark:text-zinc-300 max-w-md">
                                {{ $log->description }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs font-mono text-zinc-500 whitespace-nowrap">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500 text-right whitespace-nowrap font-mono" title="{{ $log->created_at->toIso8601String() }}">
                                {{ $log->created_at->diffForHumans() }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-16 text-zinc-400 text-xs">
                                <flux:icon.magnifying-glass class="size-8 text-zinc-300 dark:text-zinc-700 mx-auto mb-2" />
                                <div class="font-semibold">No activity recorded for this filter.</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        
        @if($logs->hasPages())
            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
