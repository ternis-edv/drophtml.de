<x-layouts::app :title="__('Site Settings')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl max-w-5xl mx-auto">
        <livewire:dashboard.site-settings :site-id="request()->route('site')" />
    </div>
</x-layouts::app>