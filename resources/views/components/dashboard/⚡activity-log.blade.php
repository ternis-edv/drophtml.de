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
            } elseif ($this->actionFilter === 'media') {
                $query->where(function ($q) {
                    $q->where('action', 'like', 'media_%')
                        ->orWhere('action', 'like', '%asset%');
                });
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
        <x-dashboard.page-heading title="Activity log" description="A timeline of deployments, file changes, and account activity." eyebrow="YOUR WORKSPACE" class="!mb-0" />

        <!-- Filter Pills -->
        <div class="dashboard-tabs shrink-0" role="group" aria-label="Filter activity by type">
            @foreach (['all' => 'All', 'deploys' => 'Deploys', 'files' => 'Files', 'media' => 'Media', 'domains' => 'Domains', 'lifecycle' => 'Lifecycle'] as $filterKey => $filterLabel)
                <button
                    type="button"
                    wire:click="setFilter('{{ $filterKey }}')"
                    aria-pressed="{{ $actionFilter === $filterKey ? 'true' : 'false' }}"
                    @class(['dashboard-tab', 'is-active' => $actionFilter === $filterKey])
                >{{ $filterLabel }}</button>
            @endforeach
        </div>
    </div>

    <!-- Activity Log List Card -->
    <div class="dashboard-panel p-6">
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
                        <flux:table.row class="dashboard-hover-surface transition-colors duration-150">
                            <flux:table.cell>
                                @php
                                    $action = $log->action;
                                    $color = 'zinc';
                                    if (str_contains($action, 'create') || str_contains($action, 'deploy')) $color = 'emerald';
                                    elseif (str_contains($action, 'delete')) $color = 'red';
                                    elseif (str_contains($action, 'media')) $color = 'sky';
                                    elseif (str_contains($action, 'domain')) $color = 'indigo';
                                    elseif (str_contains($action, 'expiry')) $color = 'amber';
                                    elseif (str_contains($action, 'update')) $color = 'zinc';
                                    elseif (str_contains($action, 'file')) $color = 'indigo';
                                @endphp
                                <x-dashboard.badge size="sm" :color="$color" class="font-mono text-[11px]">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </x-dashboard.badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($log->site)
                                    <a href="/s/{{ $log->site->slug }}" target="_blank" class="font-mono text-xs dashboard-accent  hover:underline">
                                        {{ $log->site->slug }}
                                    </a>
                                @else
                                    <span class="text-xs dashboard-muted font-mono">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-xs dashboard-text max-w-md">
                                {{ $log->description }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs font-mono dashboard-muted whitespace-nowrap">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs dashboard-muted text-right whitespace-nowrap font-mono" title="{{ $log->created_at->toIso8601String() }}">
                                {{ $log->created_at->diffForHumans() }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-16 dashboard-muted text-xs">
                                <flux:icon.magnifying-glass class="size-8 dashboard-text mx-auto mb-2" />
                                <div class="font-medium">No activity recorded for this filter.</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if($logs->hasPages())
            <div class="mt-4 pt-4 border-t dashboard-border">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
