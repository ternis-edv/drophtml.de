<x-layouts::app :title="__('Site Analytics')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:dashboard.site-stats :site-id="request()->route('site')" />
    </div>
</x-layouts::app>