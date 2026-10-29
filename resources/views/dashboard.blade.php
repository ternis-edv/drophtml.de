<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 dashboard-control-rounded">
        <livewire:dashboard.sites-overview lazy="on-load" />
    </div>
</x-layouts::app>
