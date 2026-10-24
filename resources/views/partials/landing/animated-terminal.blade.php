<section id="terminal" class="lp-section lp-reveal" aria-labelledby="terminal-heading">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="4 17 10 11 4 5" />
                    <line x1="12" y1="19" x2="20" y2="19" />
                </svg>
                <span>Automated &amp; Agent-Native</span>
            </span>
            <h2 id="terminal-heading">Deploy from Terminal or AI Agents</h2>
            <p>Ship static sites, test previews, and upload CDN media via the DropHTML CLI, official <code>SKILL.md</code>, or REST API.</p>
        </div>

        <div 
            x-data="{
                activeTab: 'agent',
                copied: false,
                copyCurrent() {
                    const snippets = {
                        agent: './scripts/drophtml deploy ./dist --name \"Marketing Site\" --permanent',
                        html: './scripts/drophtml deploy --name \"Agent Prototype\" --html \"<!DOCTYPE html><html><body><h1>Instant Preview</h1></body></html>\"',
                        media: './scripts/drophtml media upload ./assets/banner.webp --driver asset_storage',
                        curl: 'curl -X POST https://drophtml.de/api/sites \\\n  -H \"Authorization: Bearer $DROPHTML_API_TOKEN\" \\\n  -H \"Content-Type: application/json\" \\\n  -d \'{\"original_name\": \"Demo\", \"html\": \"<h1>Hello</h1>\", \"is_permanent\": true}\''
                    };
                    navigator.clipboard.writeText(snippets[this.activeTab] || '');
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                }
            }"
            class="max-w-4xl mx-auto rounded-2xl bg-zinc-950 border border-zinc-800 shadow-2xl overflow-hidden text-left"
        >
            <!-- Terminal Title Bar -->
            <div class="flex items-center justify-between px-4 py-3 bg-zinc-900/90 border-b border-zinc-800 select-none">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                    <span class="ml-2 text-xs font-mono text-zinc-400">drophtml-terminal</span>
                </div>

                <!-- Tabs -->
                <div class="flex items-center gap-1 bg-zinc-950/60 p-1 rounded-xl border border-zinc-800/80 text-xs font-mono">
                    <button 
                        type="button"
                        @click="activeTab = 'agent'"
                        :class="activeTab === 'agent' ? 'bg-zinc-800 text-sky-400 font-semibold shadow-xs' : 'text-zinc-400 hover:text-zinc-200'"
                        class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5"
                    >
                        <span>🤖 Agent Deploy</span>
                    </button>
                    <button 
                        type="button"
                        @click="activeTab = 'html'"
                        :class="activeTab === 'html' ? 'bg-zinc-800 text-purple-400 font-semibold shadow-xs' : 'text-zinc-400 hover:text-zinc-200'"
                        class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                    >
                        <span>Raw HTML</span>
                    </button>
                    <button 
                        type="button"
                        @click="activeTab = 'media'"
                        :class="activeTab === 'media' ? 'bg-zinc-800 text-emerald-400 font-semibold shadow-xs' : 'text-zinc-400 hover:text-zinc-200'"
                        class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                    >
                        <span>Media CDN</span>
                    </button>
                    <button 
                        type="button"
                        @click="activeTab = 'curl'"
                        :class="activeTab === 'curl' ? 'bg-zinc-800 text-indigo-400 font-semibold shadow-xs' : 'text-zinc-400 hover:text-zinc-200'"
                        class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                    >
                        <span>curl API</span>
                    </button>
                </div>

                <!-- Copy button -->
                <button 
                    type="button"
                    @click="copyCurrent()"
                    class="text-xs font-mono px-2.5 py-1 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition-all flex items-center gap-1.5 cursor-pointer"
                    :title="copied ? 'Copied to clipboard!' : 'Copy command'"
                >
                    <span x-text="copied ? '✓ Copied' : 'Copy'"></span>
                </button>
            </div>

            <!-- Terminal Screen Body -->
            <div class="p-6 font-mono text-xs sm:text-sm leading-relaxed overflow-x-auto min-h-[260px] bg-zinc-950/95">
                <!-- Tab 1: Agent CLI -->
                <div x-show="activeTab === 'agent'" class="space-y-3">
                    <div class="text-zinc-500"># Deploy static build output autonomously via DropHTML CLI</div>
                    <div class="text-zinc-200 flex items-start gap-2">
                        <span class="text-sky-400 select-none">$</span>
                        <span>./scripts/drophtml deploy ./dist --name "Marketing Site" --permanent</span>
                    </div>
                    <div class="text-zinc-400 pt-1">
                        📦 Packaging ./dist (24 files, 1.2 MB)...<br />
                        🚀 Uploading bundle to DropHTML...<br />
                        ✨ Unpacking assets and calculating checksums...
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80 space-y-1">
                        <div class="text-emerald-400 font-semibold">✔ Site published successfully!</div>
                        <div class="text-zinc-300">Live URL:     <span class="text-sky-400 underline">https://drophtml.de/s/marketing-site-7f9a</span></div>
                        <div class="text-zinc-400">Site ID:      site_8f2ad619</div>
                        <div class="text-zinc-400">Retention:    Permanent (No expiration)</div>
                    </div>
                </div>

                <!-- Tab 2: Raw HTML -->
                <div x-show="activeTab === 'html'" class="space-y-3">
                    <div class="text-zinc-500"># AI Agents deploy HTML mockups directly from memory without writing to disk</div>
                    <div class="text-zinc-200 flex items-start gap-2">
                        <span class="text-purple-400 select-none">$</span>
                        <span>./scripts/drophtml deploy --name "Agent Prototype" --html "&lt;!DOCTYPE html&gt;&lt;html&gt;&lt;body&gt;&lt;h1&gt;Instant Preview&lt;/h1&gt;&lt;/body&gt;&lt;/html&gt;"</span>
                    </div>
                    <div class="text-zinc-400 pt-1">
                        ⚡ Streaming HTML payload (184 bytes)...<br />
                        🔒 Generating secure URL and session preview...
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80 space-y-1">
                        <div class="text-emerald-400 font-semibold">✔ Prototype deployed in 210ms!</div>
                        <div class="text-zinc-300">Live URL:     <span class="text-purple-400 underline">https://drophtml.de/s/agent-prototype-3e4b</span></div>
                        <div class="text-zinc-400">Entry:        index.html</div>
                    </div>
                </div>

                <!-- Tab 3: Media CDN -->
                <div x-show="activeTab === 'media'" class="space-y-3">
                    <div class="text-zinc-500"># Host images, diagrams, audio, and video on Global CDN (S3/R2)</div>
                    <div class="text-zinc-200 flex items-start gap-2">
                        <span class="text-emerald-400 select-none">$</span>
                        <span>./scripts/drophtml media upload ./assets/banner.webp --driver asset_storage</span>
                    </div>
                    <div class="text-zinc-400 pt-1">
                        ☁ Offloading banner.webp (420 KB) to Cloudflare R2 Edge Storage...
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80 space-y-1">
                        <div class="text-emerald-400 font-semibold">✔ Media asset deployed to Edge CDN!</div>
                        <div class="text-zinc-300">CDN URL:      <span class="text-emerald-400">https://assets.drophtml.de/sites/site_8f2a/media/banner-b92c.webp</span></div>
                        <div class="text-zinc-400">Markdown:     <span class="text-zinc-300">![banner.webp](https://assets.drophtml.de/sites/site_8f2a/media/banner-b92c.webp)</span></div>
                        <div class="text-zinc-400">HTML Tag:     <span class="text-zinc-300">&lt;img src="https://assets.drophtml.de/sites/site_8f2a/media/banner-b92c.webp" alt="banner.webp" /&gt;</span></div>
                    </div>
                </div>

                <!-- Tab 4: curl API -->
                <div x-show="activeTab === 'curl'" class="space-y-3">
                    <div class="text-zinc-500"># REST API with Bearer token authentication</div>
                    <div class="text-zinc-200">
                        <span class="text-indigo-400 select-none">$</span> curl -X POST https://drophtml.de/api/sites \<br />
                        &nbsp;&nbsp;-H "Authorization: Bearer $DROPHTML_API_TOKEN" \<br />
                        &nbsp;&nbsp;-H "Content-Type: application/json" \<br />
                        &nbsp;&nbsp;-d '{"original_name": "Demo", "html": "&lt;h1&gt;Hello&lt;/h1&gt;", "is_permanent": true}'
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80 font-mono text-zinc-300">
                        {<br />
                        &nbsp;&nbsp;<span class="text-zinc-500">"data"</span>: {<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-indigo-300">"id"</span>: <span class="text-emerald-400">"site_4901ba72"</span>,<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-indigo-300">"slug"</span>: <span class="text-emerald-400">"demo-6x91"</span>,<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-indigo-300">"url"</span>: <span class="text-sky-400">"https://drophtml.de/s/demo-6x91"</span>,<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-indigo-300">"status"</span>: <span class="text-emerald-400">"active"</span>,<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-indigo-300">"is_permanent"</span>: <span class="text-purple-400">true</span><br />
                        &nbsp;&nbsp;}<br />
                        }
                    </div>
                </div>
            </div>

            <!-- Terminal Footer link -->
            <div class="px-6 py-3 bg-zinc-900/60 border-t border-zinc-800/80 flex items-center justify-between text-xs text-zinc-400">
                <span>Looking for the full specification?</span>
                <div class="flex items-center gap-4">
                    <a href="{{ route('docs.agents') }}" class="text-sky-400 hover:text-sky-300 font-medium">Agent Skills Guide &rarr;</a>
                    <a href="{{ url('/docs/api') }}" class="text-indigo-400 hover:text-indigo-300 font-medium">OpenAPI Docs &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>
