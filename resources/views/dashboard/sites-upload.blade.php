<x-layouts::app :title="__('Deploy Site')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Deploy Site</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1">Deploy Static Site</h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">Upload standalone HTML or complete ZIP archives with automated Let's Encrypt SSL and global Edge CDN distribution in seconds.</p>
        </div>

        <!-- Uploader Card -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 shadow-xs p-6 sm:p-8">
            <livewire:file-uploader />
        </div>

        <!-- Deployment Guidance Feature Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 shadow-xs space-y-2">
                <div class="flex items-center gap-2.5 text-zinc-900 dark:text-zinc-100 font-bold text-xs">
                    <div class="size-7 rounded-xl bg-accent/[0.08] text-accent flex items-center justify-center border border-accent/15">
                        <flux:icon.document-text class="size-4" />
                    </div>
                    <span>Single HTML File</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Drop a standalone <code class="font-mono text-zinc-800 dark:text-zinc-200">.html</code> file for instant portfolios, landing pages, or prototype review links.</p>
            </div>

            <div class="p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 shadow-xs space-y-2">
                <div class="flex items-center gap-2.5 text-zinc-900 dark:text-zinc-100 font-bold text-xs">
                    <div class="size-7 rounded-xl bg-accent/[0.08] text-accent flex items-center justify-center border border-accent/15">
                        <flux:icon.archive-box class="size-4" />
                    </div>
                    <span>Full ZIP Archive</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Package your static build folder (<code class="font-mono text-zinc-800 dark:text-zinc-200">dist/</code> or <code class="font-mono text-zinc-800 dark:text-zinc-200">out/</code>) with an <code class="font-mono text-zinc-800 dark:text-zinc-200">index.html</code> at root.</p>
            </div>

            <div class="p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 shadow-xs space-y-2">
                <div class="flex items-center gap-2.5 text-zinc-900 dark:text-zinc-100 font-bold text-xs">
                    <div class="size-7 rounded-xl bg-accent/[0.08] text-accent flex items-center justify-center border border-accent/15">
                        <flux:icon.shield-check class="size-4" />
                    </div>
                    <span>Automated SSL &amp; CDN</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Every site is automatically provisioned with auto-renewing TLS certificates across 300+ Edge nodes.</p>
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
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 shadow-xs p-6 sm:p-7 space-y-5" x-data="{ tab: 'curl', copied: false, copyText(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="size-9 rounded-xl bg-accent/[0.08] text-accent flex items-center justify-center border border-accent/15">
                        <flux:icon.command-line class="size-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-zinc-100">Deploy via Terminal &amp; CI/CD Pipelines</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Automate static deployments with cURL or GitHub Actions CI/CD.</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-950/60 border border-zinc-200 dark:border-zinc-800 text-xs">
                    <button type="button" @click="tab = 'curl'" :class="tab === 'curl' ? 'bg-white dark:bg-zinc-800 text-accent shadow-xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 font-medium'" class="px-3 py-1 rounded-lg transition-colors duration-150 cursor-pointer">cURL Deploy</button>
                    <button type="button" @click="tab = 'redeploy'" :class="tab === 'redeploy' ? 'bg-white dark:bg-zinc-800 text-accent shadow-xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 font-medium'" class="px-3 py-1 rounded-lg transition-colors duration-150 cursor-pointer">Redeploy Existing</button>
                    <button type="button" @click="tab = 'github'" :class="tab === 'github' ? 'bg-white dark:bg-zinc-800 text-accent shadow-xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 font-medium'" class="px-3 py-1 rounded-lg transition-colors duration-150 cursor-pointer">GitHub Actions</button>
                </div>
            </div>

            <!-- Snippet 1: cURL Create -->
            <div x-show="tab === 'curl'" class="space-y-2">
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 sm:p-5 rounded-2xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $curlCreateSnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($curlCreateSnippet))" 
                        class="absolute top-3 right-3 px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors cursor-pointer border border-zinc-700 flex items-center gap-1.5"
                    >
                        <flux:icon.clipboard class="size-3" />
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Need an API token? Generate personal access tokens in <a href="{{ route('api-tokens.edit') }}" wire:navigate class="text-accent underline underline-offset-2 font-semibold hover:opacity-80 transition-opacity duration-150">API Tokens</a>.
                </p>
            </div>

            <!-- Snippet 2: cURL Redeploy -->
            <div x-show="tab === 'redeploy'" class="space-y-2" x-cloak>
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 sm:p-5 rounded-2xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $curlRedeploySnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($curlRedeploySnippet))" 
                        class="absolute top-3 right-3 px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors cursor-pointer border border-zinc-700 flex items-center gap-1.5"
                    >
                        <flux:icon.clipboard class="size-3" />
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Replaces all site files and automatically captures an immutable rollback snapshot.
                </p>
            </div>

            <!-- Snippet 3: GitHub Actions -->
            <div x-show="tab === 'github'" class="space-y-2" x-cloak>
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 sm:p-5 rounded-2xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $githubSnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($githubSnippet))" 
                        class="absolute top-3 right-3 px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors cursor-pointer border border-zinc-700 flex items-center gap-1.5"
                    >
                        <flux:icon.clipboard class="size-3" />
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Add <code class="font-mono text-zinc-800 dark:text-zinc-200">DROPHTML_TOKEN</code> and <code class="font-mono text-zinc-800 dark:text-zinc-200">DROPHTML_SITE_ID</code> as repository secrets in your GitHub project settings.
                </p>
            </div>
        </div>
    </div>
</x-layouts::app>
