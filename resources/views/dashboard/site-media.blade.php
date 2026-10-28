<x-layouts::site :title="__('Site Media Assets')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl max-w-7xl mx-auto">
        <livewire:dashboard.media-manager :site-id="request()->route('site')" />
    </div>
</x-layouts::site>
