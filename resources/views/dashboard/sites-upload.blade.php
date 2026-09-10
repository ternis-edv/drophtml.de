<x-layouts::app :title="__('Deploy Site')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl max-w-4xl mx-auto">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Deploy Site</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1.5">Deploy New Static Site</h1>
            <p class="text-xs text-zinc-500 mt-0.5">Upload a single HTML document or a complete ZIP package containing your build artifacts.</p>
        </div>

        <flux:card>
            <livewire:file-uploader />
        </flux:card>

        <!-- Deployment Guidance -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <flux:card class="space-y-1.5 p-4">
                <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-semibold text-xs">
                    <flux:icon.document-text class="size-4" />
                    <span>Single HTML File</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">Drop a standalone .html file for an instant single-page site or presentation.</p>
            </flux:card>

            <flux:card class="space-y-1.5 p-4">
                <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
                    <flux:icon.archive-box class="size-4" />
                    <span>Full ZIP Archive</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">Package your <code>dist/</code> or <code>build/</code> directory with an <code>index.html</code> at the root.</p>
            </flux:card>

            <flux:card class="space-y-1.5 p-4">
                <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400 font-semibold text-xs">
                    <flux:icon.shield-check class="size-4" />
                    <span>Zero-Config SSL</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">Every site is automatically served over HTTPS with global edge distribution.</p>
            </flux:card>
        </div>
    </div>
</x-layouts::app>