<x-layouts::app :title="__('Upload Site')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl max-w-5xl mx-auto">
        <flux:card>
            <div class="mb-6">
                <h1 class="text-2xl font-bold">Upload New Site</h1>
                <p class="text-zinc-500">Drag and drop your HTML or ZIP file below. Your site will be published instantly.</p>
            </div>
            
            <livewire:file-uploader />
        </flux:card>
    </div>
</x-layouts::app>