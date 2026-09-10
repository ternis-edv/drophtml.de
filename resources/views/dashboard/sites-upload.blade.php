<x-layouts::app :title="__('Deploy Site')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl max-w-4xl mx-auto">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Deploy Site</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1.5">Deploy Static Site</h1>
            <p class="text-xs text-zinc-500 mt-0.5">Upload a standalone HTML document or a complete ZIP package containing your build artifacts.</p>
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

        <!-- Developer CLI & CI/CD Hub -->
        <flux:card class="p-6 space-y-4" x-data="{ tab: 'curl', copied: false, copyText(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <flux:icon.command-line class="size-5 text-indigo-600 dark:text-indigo-400" />
                    <div>
                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Deploy via Terminal & CI/CD</h3>
                        <p class="text-xs text-zinc-500">Automate deployments from your terminal or continuous integration pipeline.</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="inline-flex rounded-lg p-1 bg-zinc-100 dark:bg-zinc-800 text-xs">
                    <button type="button" @click="tab = 'curl'" :class="tab === 'curl' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'" class="px-2.5 py-1 font-medium rounded-md transition-colors cursor-pointer">cURL</button>
                    <button type="button" @click="tab = 'redeploy'" :class="tab === 'redeploy' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'" class="px-2.5 py-1 font-medium rounded-md transition-colors cursor-pointer">Redeploy Existing</button>
                    <button type="button" @click="tab = 'github'" :class="tab === 'github' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'" class="px-2.5 py-1 font-medium rounded-md transition-colors cursor-pointer">GitHub Actions</button>
                </div>
            </div>

            <!-- Snippet 1: cURL Create -->
            <div x-show="tab === 'curl'" class="space-y-2">
                <div class="relative">
                    <pre class="bg-zinc-900 text-zinc-100 p-3.5 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed"><code>curl -X POST {{ url('/api/sites') }} \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "original_name=My Project" \
  -F "file=@dist.zip"</code></pre>
                    <button 
                        type="button" 
                        @click="copyText('curl -X POST {{ url('/api/sites') }} \\\n  -H &quot;Authorization: Bearer YOUR_API_TOKEN&quot; \\\n  -F &quot;original_name=My Project&quot; \\\n  -F &quot;file=@dist.zip&quot;')" 
                        class="absolute top-2.5 right-2.5 px-2 py-1 text-[11px] font-medium rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors cursor-pointer"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400">Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    Need an API token? Generate one in <a href="{{ route('profile.edit') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 underline font-medium">Account Settings</a>.
                </p>
            </div>

            <!-- Snippet 2: cURL Redeploy -->
            <div x-show="tab === 'redeploy'" class="space-y-2" x-cloak>
                <div class="relative">
                    <pre class="bg-zinc-900 text-zinc-100 p-3.5 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed"><code>curl -X POST {{ url('/api/sites/{site_id}/deploy') }} \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "file=@dist.zip" \
  -F "commit_message=Release v1.2.0"</code></pre>
                    <button 
                        type="button" 
                        @click="copyText('curl -X POST {{ url('/api/sites/{site_id}/deploy') }} \\\n  -H &quot;Authorization: Bearer YOUR_API_TOKEN&quot; \\\n  -F &quot;file=@dist.zip&quot; \\\n  -F &quot;commit_message=Release v1.2.0&quot;')" 
                        class="absolute top-2.5 right-2.5 px-2 py-1 text-[11px] font-medium rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors cursor-pointer"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400">Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    Replaces all site files and automatically captures a rollback snapshot.
                </p>
            </div>

            <!-- Snippet 3: GitHub Actions -->
            <div x-show="tab === 'github'" class="space-y-2" x-cloak>
                <div class="relative">
                    <pre class="bg-zinc-900 text-zinc-100 p-3.5 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed"><code>name: Deploy to DropHTML
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build static site
        run: npm ci && npm run build && cd dist && zip -r ../dist.zip .
      - name: Push to DropHTML
        run: |
          curl -f -X POST "{{ url('/api/sites') }}/${{ '${{ secrets.DROPHTML_SITE_ID }}' }}/deploy" \
            -H "Authorization: Bearer ${{ '${{ secrets.DROPHTML_TOKEN }}' }}" \
            -F "file=@dist.zip" \
            -F "commit_message=Deploy ${{ '${{ github.sha }}' }}"</code></pre>
                    <button 
                        type="button" 
                        @click="copyText('name: Deploy to DropHTML\non:\n  push:\n    branches: [main]\njobs:\n  deploy:\n    runs-on: ubuntu-latest\n    steps:\n      - uses: actions/checkout@v4\n      - name: Build static site\n        run: npm ci && npm run build && cd dist && zip -r ../dist.zip .\n      - name: Push to DropHTML\n        run: |\n          curl -f -X POST &quot;{{ url('/api/sites') }}/${{ '${{ secrets.DROPHTML_SITE_ID }}' }}/deploy&quot; \\\n            -H &quot;Authorization: Bearer ${{ '${{ secrets.DROPHTML_TOKEN }}' }}&quot; \\\n            -F &quot;file=@dist.zip&quot; \\\n            -F &quot;commit_message=Deploy ${{ '${{ github.sha }}' }}&quot;')" 
                        class="absolute top-2.5 right-2.5 px-2 py-1 text-[11px] font-medium rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors cursor-pointer"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400">Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    Add <code>DROPHTML_TOKEN</code> and <code>DROPHTML_SITE_ID</code> as repository secrets in GitHub.
                </p>
            </div>
        </flux:card>
    </div>
</x-layouts::app>
