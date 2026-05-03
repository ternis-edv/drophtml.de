<?php

use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function render()
    {
        $logs = auth()->user()->activityLogs()->latest()->paginate(20);

        return view('components.dashboard.⚡activity-log', [
            'logs' => $logs,
        ]);
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')">Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Activity Log</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold mt-2">Activity Log</h1>
        </div>
    </div>

    <flux:card>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Action</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                    <flux:table.column>IP Address</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($logs as $log)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $log->action }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $log->description }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm font-mono text-zinc-500">
                                {{ $log->ip_address ?: 'Unknown' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500 whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-8 text-zinc-500">
                                No activity recorded yet.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </flux:card>
</div>