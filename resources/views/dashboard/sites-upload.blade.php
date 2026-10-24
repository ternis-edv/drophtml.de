<x-layouts::app :title="__('Deploy Site')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col gap-1.5 rise-in" style="--i: 0">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Deploy Site</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="page-title mt-1">Deploy Static Site</h1>
            <p class="page-lede">Upload standalone HTML or complete ZIP archives with automated Let's Encrypt SSL and global Edge CDN distribution in seconds.</p>
        </div>

        <!-- Uploader Card -->
        <div class="panel p-6 sm:p-8 rise-in" style="--i: 1">
            <livewire:file-uploader />
        </div>

        <!-- Deployment Guidance Feature Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="panel p-5 space-y-2 rise-in" style="--i: 2">
                <div class="flex items-center gap-2.5 text-zinc-900 dark:text-zinc-100 font-bold text-xs">
                    <span class="icon-tile" aria-hidden="true">
                        <flux:icon.document-text class="size-4" />
                    </span>
                    <span>Single HTML File</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Drop a standalone <code class="font-mono text-zinc-800 dark:text-zinc-200">.html</code> file for instant portfolios, landing pages, or prototype review links.</p>
            </div>

            <div class="panel p-5 space-y-2 rise-in" style="--i: 3">
                <div class="flex items-center gap-2.5 text-zinc-900 dark:text-zinc-100 font-bold text-xs">
                    <span class="icon-tile" aria-hidden="true">
                        <flux:icon.archive-box class="size-4" />
                    </span>
                    <span>Full ZIP Archive</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Package your static build folder (<code class="font-mono text-zinc-800 dark:text-zinc-200">dist/</code> or <code class="font-mono text-zinc-800 dark:text-zinc-200">out/</code>) with an <code class="font-mono text-zinc-800 dark:text-zinc-200">index.html</code> at root.</p>
            </div>

            <div class="panel p-5 space-y-2 rise-in" style="--i: 4">
                <div class="flex items-center gap-2.5 text-zinc-900 dark:text-zinc-100 font-bold text-xs">
                    <span class="icon-tile" aria-hidden="true">
                        <flux:icon.shield-check class="size-4" />
                    </span>
                    <span>Automated SSL &amp; CDN</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Every site is automatically provisioned with auto-renewing TLS certificates across 300+ Edge nodes.</p>
            </div>
        </div>

        @php
            $cliDeploySnippet = '# 1. Authenticate with your personal API token' . "\n" .
'export DROPHTML_TOKEN="YOUR_API_TOKEN"' . "\n\n" .
'# 2. Deploy directory, build folder or standalone file' . "\n" .
'./scripts/drophtml deploy ./dist --name "My Project" --open';

            $curlCreateSnippet = 'curl -X POST ' . url('/api/sites') . ' \\' . "\n" .
'  -H "Authorization: Bearer YOUR_API_TOKEN" \\' . "\n" .
'  -F "original_name=My Project" \\' . "\n" .
'  -F "file=@dist.zip"';

            $curlRedeploySnippet = 'curl -X POST ' . url('/api/sites/{site_id}/deploy') . ' \\' . "\n" .
'  -H "Authorization: Bearer YOUR_API_TOKEN" \\' . "\n" .
'  -F "file=@dist.zip" \\' . "\n" .
'  -F "commit_message=Release v1.2.0"';

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
        <div class="panel p-6 sm:p-7 space-y-5 rise-in" style="--i: 5" x-data="{ tab: 'cli', copied: false, copyText(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="icon-tile" aria-hidden="true">
                        <flux:icon.command-line class="size-5" />
                    </span>
                    <div>
                        <h3 class="section-title">Deploy via CLI, Agents &amp; CI/CD Pipelines</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Automate static deployments with our developer CLI, coding agents, or GitHub Actions.</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="pill-group" role="tablist" aria-label="Deployment methods">
                    <button type="button" role="tab" @click="tab = 'cli'" :aria-selected="tab === 'cli'" :class="tab === 'cli' ? 'is-active' : ''" class="pill">Agent &amp; CLI</button>
                    <button type="button" role="tab" @click="tab = 'curl'" :aria-selected="tab === 'curl'" :class="tab === 'curl' ? 'is-active' : ''" class="pill">cURL Deploy</button>
                    <button type="button" role="tab" @click="tab = 'redeploy'" :aria-selected="tab === 'redeploy'" :class="tab === 'redeploy' ? 'is-active' : ''" class="pill">Redeploy</button>
                    <button type="button" role="tab" @click="tab = 'github'" :aria-selected="tab === 'github'" :class="tab === 'github' ? 'is-active' : ''" class="pill">GitHub Actions</button>
                </div>
            </div>

            <!-- Snippet 0: Agent & CLI -->
            <div x-show="tab === 'cli'" class="space-y-2">
                <div class="relative">
                    <pre class="bg-zinc-950 text-zinc-100 p-4 sm:p-5 rounded-2xl text-xs font-mono overflow-x-auto leading-relaxed border border-zinc-800"><code>{!! $cliDeploySnippet !!}</code></pre>
                    <button 
                        type="button" 
                        @click="copyText(@js($cliDeploySnippet))" 
                        class="absolute top-3 right-3 px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors cursor-pointer border border-zinc-700 flex items-center gap-1.5"
                    >
                        <flux:icon.clipboard class="size-3" />
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-emerald-400" x-cloak>Copied!</span>
                    </button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Works seamlessly with local terminals and AI coding agents (Claude Code, Antigravity, Cursor). Learn more in <a href="{{ route('docs.agents') }}" wire:navigate class="text-accent underline underline-offset-2 font-semibold hover:opacity-80 transition-opacity duration-150">DropHTML for Agents</a>.
                </p>
            </div>

            <!-- Snippet 1: cURL Create -->
            <div x-show="tab === 'curl'" class="space-y-2" x-cloak>
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
