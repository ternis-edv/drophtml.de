<x-layouts::app :title="__('Deploy Site')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Deploy Site</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1">Deploy Static Site</h1>
            <p class="text-xs sm:text-sm text-zinc-500">Upload a standalone HTML document or complete ZIP package with automatic SSL and edge distribution.</p>
        </div>

        <!-- Uploader Card -->
        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs">
            <livewire:file-uploader />
        </div>

        <!-- Deployment Guidance Feature Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs space-y-1.5">
                <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-semibold text-xs">
                    <div class="size-6 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                        <flux:icon.document-text class="size-3.5" />
                    </div>
                    <span>Single HTML File</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">Drop a standalone <code class="font-mono text-zinc-700 dark:text-zinc-300">.html</code> file for instant landing pages, portfolios, or design prototypes.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs space-y-1.5">
                <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
                    <div class="size-6 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <flux:icon.archive-box class="size-3.5" />
                    </div>
                    <span>Full ZIP Archive</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">Package your <code class="font-mono text-zinc-700 dark:text-zinc-300">dist/</code> or build folder with an <code class="font-mono text-zinc-700 dark:text-zinc-300">index.html</code> at root.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs space-y-1.5">
                <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400 font-semibold text-xs">
                    <div class="size-6 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <flux:icon.shield-check class="size-3.5" />
                    </div>
                    <span>Zero-Config SSL</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">Every site is automatically provisioned with free Let's Encrypt certificates.</p>
            </div>
        </div>

        @php
            $curlCreateSnippet = 'curl -X POST ' . url('/api/sites') . ' \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -F "original_name=My Project" \\
  -F "file=@dist.zip"';

            $curlRedeploySnippet = 'curl -X POST ' . url('/api/sites/{site_id}/deploy') . ' \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -F "file=@dist.zip" \\
  -F "commit_message=Release v1.2.0"';

            $githubSnippet = 'name: Deploy to DropHTML
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
          curl -f -X POST "' . url('/api/sites') . '/${{ secrets.DROPHTML_SITE_ID }}/deploy" \\
            -H "Authorization: Bearer ${{ secrets.DROPHTML_TOKEN }}" \\
            -F "file=@dist.zip" \\
            -F "commit_message=Deploy ${{ github.sha }}"';
        @endphp

        <!-- Developer CLI & CI/CD Hub -->
        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 p-6 space-y-4 shadow-xs" x-data="{ tab: 'curl', copied: false, copyText(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="size-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                        <flux:icon.command-line class="size-4.5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Deploy via Terminal & CI/CD</h3>
                        <p class="text-xs text-zinc-500">Automate static deployments with cURL or GitHub Actions pipeline.</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200/80 dark:border-zinc-700/80 text-xs">
                    <button type="button" @click="tab = 'curl'" :class="tab === 'curl' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer">cURL</button>
                    <button type="button" @click="tab = 'redeploy'" :class="tab === 'redeploy' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer">Redeploy Existing</button>
                    <button type="button" @click="tab = 'github'" :class="tab === 'github' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer">GitHub Actions</button>
                </div>
            </div>

            <!-- Snippet 1: cURL Create -->
            <div x-show="tab === 'curl'" class="space-y-2">
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $curlCreateSnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($curlCreateSnippet))" 
                        class="absolute top-2.5 right-2.5 px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors cursor-pointer border border-zinc-700"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    Need an API token? Generate one in <a href="{{ route('profile.edit') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 underline font-medium">Account Settings</a>.
                </p>
            </div>

            <!-- Snippet 2: cURL Redeploy -->
            <div x-show="tab === 'redeploy'" class="space-y-2" x-cloak>
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $curlRedeploySnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($curlRedeploySnippet))" 
                        class="absolute top-2.5 right-2.5 px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors cursor-pointer border border-zinc-700"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    Replaces all site files and automatically captures a rollback snapshot.
                </p>
            </div>

            <!-- Snippet 3: GitHub Actions -->
            <div x-show="tab === 'github'" class="space-y-2" x-cloak>
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $githubSnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($githubSnippet))" 
                        class="absolute top-2.5 right-2.5 px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors cursor-pointer border border-zinc-700"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    Add <code class="font-mono text-zinc-700 dark:text-zinc-300">DROPHTML_TOKEN</code> and <code class="font-mono text-zinc-700 dark:text-zinc-300">DROPHTML_SITE_ID</code> as repository secrets in GitHub.
                </p>
            </div>
        </div>
    </div>
</x-layouts::app>
