<x-layouts::site :title="__('Custom Domains')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:dashboard.site-domains :site-id="request()->route('site')" />
    </div>
</x-layouts::site>