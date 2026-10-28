@extends('layouts.landing')

@section('content')
<div class="pt-32 pb-24 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-10 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-sky-500/30 bg-sky-500/10 text-sky-400 text-xs font-medium tracking-wide uppercase mb-4">
            <svg class="w-4 h-4 text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Agent Skills Standard &bull; AI First
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
            DropHTML for AI Agents
        </h1>
        <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
            Give Claude Code, Antigravity, Cursor, and custom LLM agents the superpower to publish static sites, share prototypes, and host CDN media in seconds.
        </p>
    </div>

    <!-- Quick action links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
        <a href="{{ route('skills.drophtml') }}" target="_blank" class="flex items-center justify-between p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 hover:border-sky-500/50 hover:bg-zinc-900/80 transition-all group">
            <div>
                <div class="text-sm font-semibold text-white group-hover:text-sky-400 transition-colors">Raw SKILL.md</div>
                <div class="text-xs text-zinc-400 mt-1">Direct specification file for LLMs</div>
            </div>
            <svg class="w-5 h-5 text-zinc-500 group-hover:text-sky-400 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>

        <a href="{{ url('/.well-known/agent-skills/drophtml/SKILL.md') }}" target="_blank" class="flex items-center justify-between p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 hover:border-emerald-500/50 hover:bg-zinc-900/80 transition-all group">
            <div>
                <div class="text-sm font-semibold text-white group-hover:text-emerald-400 transition-colors">Well-Known Discovery</div>
                <div class="text-xs text-zinc-400 mt-1">.well-known/agent-skills discovery endpoint</div>
            </div>
            <svg class="w-5 h-5 text-zinc-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>

        <a href="{{ url('/docs/api') }}" target="_blank" class="flex items-center justify-between p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 hover:border-indigo-500/50 hover:bg-zinc-900/80 transition-all group">
            <div>
                <div class="text-sm font-semibold text-white group-hover:text-indigo-400 transition-colors">REST & OpenAPI Docs</div>
                <div class="text-xs text-zinc-400 mt-1">Complete API spec & interactive explorer</div>
            </div>
            <svg class="w-5 h-5 text-zinc-500 group-hover:text-indigo-400 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>
    </div>

    <!-- Main documentation container -->
    <div class="glass-panel rounded-3xl p-8 md:p-12 border border-zinc-200 dark:border-zinc-800 space-y-12">
        <!-- Section: How to equip your agent -->
        <section>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 text-sm font-mono">1</span>
                Equip your Agent in seconds
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6 leading-relaxed">
                Add DropHTML as a skill in your project or agent environment by pointing to the standard skill definition or copying it into your workspace:
            </p>

            <div class="bg-zinc-950 rounded-2xl p-6 border border-zinc-800 font-mono text-sm text-zinc-300 space-y-3">
                <div class="text-zinc-500"># Install DropHTML skill into your local workspace</div>
                <div>mkdir -p .agents/skills/drophtml</div>
                <div>curl -sSL {{ route('skills.drophtml') }} &gt; .agents/skills/drophtml/SKILL.md</div>
                <div class="pt-2 text-zinc-500"># Set your DropHTML API token (from dashboard settings)</div>
                <div>export DROPHTML_API_TOKEN="your_token_here"</div>
            </div>
        </section>

        <!-- Section: Agent Capabilities -->
        <section>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 text-sm font-mono">2</span>
                What AI Agents Can Do
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80">
                    <h3 class="font-semibold text-white mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Instant HTML Deployments
                    </h3>
                    <p class="text-sm text-zinc-400">Agents can deploy raw HTML strings, directories, or ZIP archives directly without needing build tools or server setup.</p>
                </div>

                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80">
                    <h3 class="font-semibold text-white mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Global Media Hosting & CDN
                    </h3>
                    <p class="text-sm text-zinc-400">Upload images, diagrams, audio, and video via multipart or base64 data. Receive CDN URLs, markdown, and HTML tags.</p>
                </div>

                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80">
                    <h3 class="font-semibold text-white mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Continuous Updates & Redeploy
                    </h3>
                    <p class="text-sm text-zinc-400">Agents can update an existing site by ID or slug with automatic deployment snapshots and rollback support.</p>
                </div>

                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80">
                    <h3 class="font-semibold text-white mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Password Protection & Custom Domains
                    </h3>
                    <p class="text-sm text-zinc-400">Configure client passwords, custom domains, and permanent hosting with zero friction.</p>
                </div>
            </div>
        </section>

        <!-- Section: CLI & Direct Curl Workflows -->
        <section>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 text-sm font-mono">3</span>
                CLI &amp; API Workflows
            </h2>

            <div class="space-y-6">
                <div>
                    <h3 class="text-base font-semibold text-zinc-200 mb-2">Publish raw HTML snippet directly</h3>
                    <div class="bg-zinc-950 rounded-xl p-4 border border-zinc-800 font-mono text-xs sm:text-sm text-zinc-300 overflow-x-auto">
                        ./scripts/drophtml deploy --name "Interactive Demo" --html "&lt;h1&gt;Hello from Agent&lt;/h1&gt;" --permanent
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-zinc-200 mb-2">Deploy a static frontend directory</h3>
                    <div class="bg-zinc-950 rounded-xl p-4 border border-zinc-800 font-mono text-xs sm:text-sm text-zinc-300 overflow-x-auto">
                        ./scripts/drophtml deploy ./dist --name "React SPA" --permanent
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-zinc-200 mb-2">Upload media to Cloud CDN</h3>
                    <div class="bg-zinc-950 rounded-xl p-4 border border-zinc-800 font-mono text-xs sm:text-sm text-zinc-300 overflow-x-auto">
                        ./scripts/drophtml media upload ./diagram.png
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: SKILL.md Raw Specification -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center gap-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 text-sm font-mono">4</span>
                    SKILL.md Specification
                </h2>
                <a href="{{ route('skills.drophtml') }}" download="SKILL.md" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-zinc-800 text-zinc-200 hover:bg-zinc-700 transition-colors">
                    Download SKILL.md
                </a>
            </div>

            <div class="relative bg-zinc-950 rounded-2xl p-6 border border-zinc-800 font-mono text-xs text-zinc-400 max-h-96 overflow-y-auto leading-relaxed">
                <pre class="whitespace-pre-wrap">{{ $rawMarkdown }}</pre>
            </div>
        </section>
    </div>
</div>
@endsection
