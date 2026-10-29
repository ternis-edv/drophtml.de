<x-layouts::site :title="__('Site Analytics')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 dashboard-control-rounded">
        <livewire:dashboard.site-stats :site-id="request()->route('site')" />
    </div>
</x-layouts::site>
