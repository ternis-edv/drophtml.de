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
                $query->whereIn('action', ['site_created', 'site_deployed', 'deployment_created']);
            } elseif ($this->actionFilter === 'files') {
                $query->whereIn('action', ['file_created', 'file_edited', 'file_deleted', 'file_renamed', 'file_uploaded_to_manager']);
            } elseif ($this->actionFilter === 'domains') {
                $query->whereIn('action', ['domain_created', 'domain_deleted', 'domain_verified']);
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
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1.5">Audit & Activity Log</h1>
        </div>

        <!-- Filter Pills -->
        <div class="inline-flex rounded-lg p-1 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-xs">
            <button wire:click="setFilter('all')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $actionFilter === 'all' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">All</button>
            <button wire:click="setFilter('deploys')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $actionFilter === 'deploys' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Deploys</button>
            <button wire:click="setFilter('files')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $actionFilter === 'files' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Files</button>
            <button wire:click="setFilter('domains')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $actionFilter === 'domains' ? 'bg-white dark:bg-zinc-900 text-purple-600 dark:text-purple-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Domains</button>
            <button wire:click="setFilter('lifecycle')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $actionFilter === 'lifecycle' ? 'bg-white dark:bg-zinc-900 text-amber-600 dark:text-amber-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Lifecycle</button>
        </div>
    </div>

    <!-- Activity Log List Card -->
    <flux:card>
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
                        <flux:table.row>
                            <flux:table.cell>
                                @php
                                    $action = $log->action;
                                    $color = 'zinc';
                                    if (str_contains($action, 'create') || str_contains($action, 'deploy')) $color = 'emerald';
                                    elseif (str_contains($action, 'delete')) $color = 'red';
                                    elseif (str_contains($action, 'domain')) $color = 'purple';
                                    elseif (str_contains($action, 'expiry') || str_contains($action, 'update')) $color = 'amber';
                                    elseif (str_contains($action, 'file')) $color = 'indigo';
                                @endphp
                                <flux:badge size="sm" :color="$color" class="font-mono text-[11px]">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($log->site)
                                    <a href="/s/{{ $log->site->slug }}" target="_blank" class="font-mono text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $log->site->slug }}
                                    </a>
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-700 dark:text-zinc-300 max-w-md">
                                {{ $log->description }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs font-mono text-zinc-500 whitespace-nowrap">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500 text-right whitespace-nowrap" title="{{ $log->created_at->toIso8601String() }}">
                                {{ $log->created_at->diffForHumans() }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-12 text-zinc-400 text-xs">
                                No activity recorded for this filter.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        
        @if($logs->hasPages())
            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                {{ $logs->links() }}
            </div>
        @endif
    </flux:card>
</div>