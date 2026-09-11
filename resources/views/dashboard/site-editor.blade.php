<x-layouts::app :title="__('Code Studio')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:dashboard.site-editor :site-id="request()->route('site')" />
    </div>
</x-layouts::app>