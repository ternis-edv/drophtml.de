<section id="demo" class="py-24 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-4 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                <flux:icon.bolt class="w-3.5 h-3.5 text-indigo-500" /> Live Interactive Showcase
            </div>
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4 text-zinc-900 dark:text-white tracking-tight">
                Experience the Speed Before You Sign Up
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto text-base md:text-lg">
                No mockups, no video recordings. Interact directly with our deploy simulator, real-time code editor, and developer terminal.
            </p>
        </div>

        <!-- Master Interactive Container -->
        <div 
            x-data="{
                activeTab: 'simulator',
                // Simulator State
                simState: 'idle',
                simProgress: 0,
                simLogs: [],
                simInterval: null,
                startSimulation() {
                    this.simState = 'uploading';
                    this.simProgress = 0;
                    this.simLogs = ['[1/4] Preparing site assets (index.html, styles.css, app.js)...'];
                    
                    let interval = setInterval(() => {
                        this.simProgress += 10;
                        if (this.simProgress === 40) {
                            this.simState = 'building';
                            this.simLogs.push('[2/4] Optimizing HTML structure and asset links...');
                        } else if (this.simProgress === 70) {
                            this.simLogs.push('[3/4] Distributing to 24 Edge CDN points of presence...');
                        } else if (this.simProgress >= 100) {
                            clearInterval(interval);
                            this.simProgress = 100;
                            this.simLogs.push('[4/4] SSL certificate provisioned. Site is LIVE!');
                            this.simState = 'success';
                        }
                    }, 140);
                },
                resetSimulation() {
                    this.simState = 'idle';
                    this.simProgress = 0;
                    this.simLogs = [];
                },
                
                // Code Editor State
                editorTemplate: 'saas',
                templates: {
                    saas: `<!-- DropHTML Hero Component -->
<div class='hero-box'>
  <span class='badge'>🚀 v2.0 Released</span>
  <h1>Build & Ship Faster</h1>
  <p>Static hosting with zero configuration, automatic SSL, and instant preview URLs.</p>
  <div class='actions'>
    <button class='btn-primary'>Get Started Free</button>
    <button class='btn-outline'>Documentation</button>
  </div>
</div>

<style>
  .hero-box {
    font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    text-align: center;
    padding: 32px 20px;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
    color: white;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
  }
  .badge {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 12px;
    letter-spacing: 0.5px;
  }
  h1 { font-size: 26px; font-weight: 800; margin: 0 0 10px; }
  p { font-size: 14px; opacity: 0.85; max-width: 360px; margin: 0 auto 20px; line-height: 1.5; }
  .actions { display: flex; gap: 10px; justify-content: center; }
  .btn-primary {
    background: white;
    color: #312e81;
    font-weight: 700;
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    transition: transform 0.15s;
  }
  .btn-primary:hover { transform: translateY(-1px); }
  .btn-outline {
    background: transparent;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.4);
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
  }
</style>`,
                    portfolio: `<!-- Minimal Developer Portfolio -->
<div class='card'>
  <div class='avatar'>FT</div>
  <h2>Fabian Ternis</h2>
  <div class='role'>Fullstack Software Engineer</div>
  <p>Crafting high performance web systems, developer tooling, and modern cloud infrastructure.</p>
  <div class='tags'>
    <span>Laravel</span>
    <span>TypeScript</span>
    <span>Tailwind</span>
    <span>PostgreSQL</span>
  </div>
</div>

<style>
  .card {
    font-family: system-ui, sans-serif;
    padding: 30px;
    background: #09090b;
    color: #fafafa;
    border-radius: 16px;
    border: 1px solid #27272a;
    text-align: center;
  }
  .avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-weight: 800;
    font-size: 20px;
    color: white;
  }
  h2 { margin: 0; font-size: 22px; font-weight: 700; }
  .role { color: #818cf8; font-size: 13px; font-weight: 600; margin: 4px 0 12px; }
  p { font-size: 13px; color: #a1a1aa; line-height: 1.5; margin: 0 0 18px; }
  .tags { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; }
  .tags span {
    background: #18181b;
    border: 1px solid #27272a;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    color: #e4e4e7;
  }
</style>`,
                    badge: `<!-- Project Status Badge -->
<div class='badge-card'>
  <div class='status-dot'></div>
  <div class='info'>
    <div class='title'>All Systems Operational</div>
    <div class='desc'>Global Edge latency: 12ms &bull; 99.99% Uptime</div>
  </div>
  <div class='uptime-bar'>
    <div class='pip' style='background: #22c55e;'></div>
    <div class='pip' style='background: #22c55e;'></div>
    <div class='pip' style='background: #22c55e;'></div>
    <div class='pip' style='background: #22c55e;'></div>
    <div class='pip' style='background: #22c55e;'></div>
    <div class='pip' style='background: #22c55e;'></div>
    <div class='pip' style='background: #22c55e;'></div>
  </div>
</div>

<style>
  .badge-card {
    font-family: system-ui, sans-serif;
    display: flex;
    align-items: center;
    gap: 16px;
    background: #18181b;
    padding: 16px 24px;
    border-radius: 12px;
    border: 1px solid #27272a;
    color: white;
  }
  .status-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 12px #22c55e;
    flex-shrink: 0;
  }
  .title { font-weight: 700; font-size: 14px; margin-bottom: 2px; }
  .desc { font-size: 11px; color: #a1a1aa; }
  .uptime-bar { display: flex; gap: 4px; margin-left: auto; }
  .pip { width: 6px; height: 24px; border-radius: 3px; }
</style>`
                },
                editorCode: '',
                deviceMode: 'desktop',
                init() {
                    this.editorCode = this.templates.saas;
                    this.$watch('editorTemplate', (val) => {
                        if (this.templates[val]) {
                            this.editorCode = this.templates[val];
                        }
                    });
                },

                // Terminal State
                termTab: 'curl',
                termCopied: false,
                copyCommand(text) {
                    navigator.clipboard.writeText(text);
                    this.termCopied = true;
                    setTimeout(() => this.termCopied = false, 2000);
                }
            }"
            class="glass-panel rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-2xl overflow-hidden"
        >
            <!-- Top Segment Tabs Bar -->
            <div class="px-6 py-4 bg-zinc-100/90 dark:bg-zinc-900/90 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="flex gap-2 mr-2">
                        <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                    </div>
                    <span class="text-xs font-mono text-zinc-500 dark:text-zinc-400 hidden sm:inline">preview.drophtml.de/interactive</span>
                </div>

                <!-- Tab Buttons -->
                <div class="flex bg-zinc-200 dark:bg-zinc-800/80 p-1 rounded-xl gap-1 text-xs font-semibold">
                    <button 
                        @click="activeTab = 'simulator'"
                        :class="activeTab === 'simulator' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <flux:icon.rocket-launch class="w-3.5 h-3.5 text-indigo-500" />
                        <span>Deploy Simulator</span>
                    </button>
                    <button 
                        @click="activeTab = 'editor'"
                        :class="activeTab === 'editor' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <flux:icon.code-bracket class="w-3.5 h-3.5 text-pink-500" />
                        <span>Live Code Editor</span>
                    </button>
                    <button 
                        @click="activeTab = 'terminal'"
                        :class="activeTab === 'terminal' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <flux:icon.command-line class="w-3.5 h-3.5 text-emerald-500" />
                        <span>REST API & CLI</span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: Instant Deploy Simulator -->
            <div x-show="activeTab === 'simulator'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="p-6 md:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <!-- Left: Control & Trigger Box -->
                    <div class="space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 text-xs font-semibold">
                            <span>Step-by-step pipeline simulation</span>
                        </div>
                        <h3 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white leading-tight">
                            Drop a file. We handle the infrastructure.
                        </h3>
                        <p class="text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Watch our automated build engine instantly inspect static files, sanitize paths, generate edge certificates, and route through global CDNs in under 2 seconds.
                        </p>

                        <!-- Interactive Drop Box Trigger -->
                        <div 
                            @click="if (simState === 'idle') startSimulation()"
                            :class="{
                                'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 cursor-default': simState !== 'idle',
                                'border-zinc-300 dark:border-zinc-700 hover:border-indigo-500 dark:hover:border-indigo-400 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 cursor-pointer': simState === 'idle'
                            }"
                            class="border-2 border-dashed rounded-2xl p-8 text-center transition-all group relative overflow-hidden"
                        >
                            <template x-if="simState === 'idle'">
                                <div>
                                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <flux:icon.cloud-arrow-up class="w-7 h-7" />
                                    </div>
                                    <div class="font-bold text-zinc-900 dark:text-white text-base mb-1">Click to Simulate Instant Upload</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Simulates dropping a <code>portfolio-v2.zip</code> static site archive</div>
                                </div>
                            </template>

                            <template x-if="simState === 'uploading' || simState === 'building'">
                                <div>
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center animate-spin">
                                        <flux:icon.arrow-path class="w-6 h-6" />
                                    </div>
                                    <div class="font-bold text-zinc-900 dark:text-white text-sm mb-2" x-text="simState === 'uploading' ? 'Uploading & validating archive...' : 'Extracting & deploying to Edge...'"></div>
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-2.5 overflow-hidden max-w-xs mx-auto">
                                        <div class="bg-indigo-600 h-2.5 transition-all duration-150 ease-out" :style="`width: ${simProgress}%`"></div>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-2 font-mono" x-text="`${simProgress}% complete`"></div>
                                </div>
                            </template>

                            <template x-if="simState === 'success'">
                                <div class="space-y-3">
                                    <div class="w-12 h-12 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center">
                                        <flux:icon.check-circle class="w-7 h-7" />
                                    </div>
                                    <div class="font-bold text-green-600 dark:text-green-400 text-sm">Deployment Completed in 840ms!</div>
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click.stop="resetSimulation()" class="px-4 py-1.5 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-lg text-xs font-semibold transition-colors">
                                            Run Another Simulation
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Right: Mock Terminal & Live Result View -->
                    <div class="bg-zinc-900 dark:bg-[#070709] rounded-2xl p-6 border border-zinc-800 shadow-xl flex flex-col h-[380px] justify-between font-mono text-xs">
                        <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                            <span class="text-zinc-400 text-xs flex items-center gap-2">
                                <flux:icon.command-line class="w-3.5 h-3.5 text-indigo-400" />
                                build-pipeline-worker-04
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="simState === 'success' ? 'bg-green-900/60 text-green-300' : (simState === 'idle' ? 'bg-zinc-800 text-zinc-400' : 'bg-indigo-900/60 text-indigo-300')">
                                <span x-text="simState.toUpperCase()"></span>
                            </span>
                        </div>

                        <!-- Logs list -->
                        <div class="flex-1 py-4 overflow-y-auto space-y-2 text-zinc-300">
                            <template x-if="simLogs.length === 0">
                                <div class="text-zinc-600 italic py-12 text-center font-sans text-xs">
                                    Click "Simulate Instant Upload" on the left to watch live execution logs.
                                </div>
                            </template>

                            <template x-for="(log, idx) in simLogs" :key="idx">
                                <div class="flex items-start gap-2">
                                    <span class="text-indigo-400 select-none">&rarr;</span>
                                    <span x-text="log" :class="idx === simLogs.length - 1 && simState === 'success' ? 'text-green-400 font-bold' : 'text-zinc-300'"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Live Output Box -->
                        <div class="pt-3 border-t border-zinc-800 flex items-center justify-between">
                            <div class="text-zinc-400 truncate">
                                Live URL: <span class="text-indigo-400 underline cursor-pointer" x-text="simState === 'success' ? 'https://stellar-portfolio.drophtml.de' : 'https://...' "></span>
                            </div>
                            <button 
                                x-show="simState === 'success'"
                                @click="navigator.clipboard.writeText('https://stellar-portfolio.drophtml.de'); Flux.toast('Demo URL copied!')"
                                class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[11px] font-sans font-medium transition-colors"
                            >
                                Copy URL
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Live Code Editor -->
            <div x-show="activeTab === 'editor'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="p-6 md:p-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Template:</label>
                        <select 
                            x-model="editorTemplate" 
                            class="bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white text-xs rounded-lg px-3 py-1.5 outline-none font-medium"
                        >
                            <option value="saas">SaaS Hero Section</option>
                            <option value="portfolio">Developer Portfolio</option>
                            <option value="badge">System Status Badge</option>
                        </select>
                    </div>

                    <!-- Viewport toggles -->
                    <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg">
                        <button 
                            @click="deviceMode = 'desktop'" 
                            :class="deviceMode === 'desktop' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white'" 
                            class="px-2.5 py-1 rounded text-xs font-medium transition-colors flex items-center gap-1 cursor-pointer"
                        >
                            <flux:icon.computer-desktop class="w-3.5 h-3.5" /> Desktop
                        </button>
                        <button 
                            @click="deviceMode = 'mobile'" 
                            :class="deviceMode === 'mobile' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white'" 
                            class="px-2.5 py-1 rounded text-xs font-medium transition-colors flex items-center gap-1 cursor-pointer"
                        >
                            <flux:icon.device-phone-mobile class="w-3.5 h-3.5" /> Mobile
                        </button>
                    </div>
                </div>

                <!-- Split Pane -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 min-h-[460px]">
                    <!-- Left: Code Area -->
                    <div class="flex flex-col bg-zinc-900 dark:bg-[#070709] rounded-2xl border border-zinc-800 overflow-hidden shadow-lg">
                        <div class="bg-zinc-800/80 px-4 py-2 border-b border-zinc-800 flex justify-between items-center text-xs text-zinc-400 font-mono">
                            <span class="flex items-center gap-1.5">
                                <flux:icon.document-text class="w-3.5 h-3.5 text-indigo-400" /> index.html
                            </span>
                            <span class="text-[11px] text-zinc-500">Live HTML/CSS preview</span>
                        </div>
                        <textarea 
                            x-model="editorCode" 
                            class="flex-1 w-full p-4 font-mono text-xs md:text-sm bg-transparent text-indigo-200 border-none outline-none resize-none leading-relaxed" 
                            spellcheck="false"
                        ></textarea>
                    </div>

                    <!-- Right: Live Preview Frame -->
                    <div class="flex flex-col bg-zinc-100 dark:bg-zinc-900/60 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-lg">
                        <div class="bg-zinc-200/80 dark:bg-zinc-800/80 px-4 py-2 border-b border-zinc-300 dark:border-zinc-700/60 flex items-center justify-between text-xs text-zinc-500">
                            <div class="flex items-center gap-2">
                                <flux:icon.eye class="w-3.5 h-3.5 text-indigo-500" />
                                <span class="font-mono">Live Rendering</span>
                            </div>
                            <span class="text-[11px] font-sans text-green-600 dark:text-green-400 font-medium">&bull; Instant reactive update</span>
                        </div>
                        <div class="flex-1 p-6 flex items-center justify-center overflow-auto">
                            <div 
                                :class="deviceMode === 'mobile' ? 'w-[320px] shadow-2xl rounded-2xl border-4 border-zinc-800 p-2 bg-white dark:bg-black' : 'w-full max-w-md'"
                                class="transition-all duration-300"
                            >
                                <div x-html="editorCode"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: REST API & CLI Terminal -->
            <div x-show="activeTab === 'terminal'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="p-6 md:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    <div class="lg:col-span-1 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                            <span>Developer First</span>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Full Automation with REST & CLI</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Integrate DropHTML directly into your CI/CD pipelines (GitHub Actions, GitLab CI, Bitbucket) or deploy programmatically via shell scripts and OpenAPI endpoints.
                        </p>
                        
                        <div class="space-y-2 pt-2">
                            <button 
                                @click="termTab = 'curl'"
                                :class="termTab === 'curl' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'"
                                class="w-full text-left p-3 rounded-xl border text-xs flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span>cURL Deploy Request</span>
                                <flux:icon.arrow-right class="w-3.5 h-3.5" />
                            </button>
                            <button 
                                @click="termTab = 'github'"
                                :class="termTab === 'github' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'"
                                class="w-full text-left p-3 rounded-xl border text-xs flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span>GitHub Actions CI/CD</span>
                                <flux:icon.arrow-right class="w-3.5 h-3.5" />
                            </button>
                            <button 
                                @click="termTab = 'domains'"
                                :class="termTab === 'domains' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'"
                                class="w-full text-left p-3 rounded-xl border text-xs flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span>Custom Domain API</span>
                                <flux:icon.arrow-right class="w-3.5 h-3.5" />
                            </button>
                            <button 
                                @click="termTab = 'media'"
                                :class="termTab === 'media' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'"
                                class="w-full text-left p-3 rounded-xl border text-xs flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span>Cloud Media API</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 font-bold">NEW</span>
                                </span>
                                <flux:icon.arrow-right class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Right: Terminal Snippet -->
                    <div class="lg:col-span-2 bg-zinc-950 rounded-2xl border border-zinc-800 p-6 font-mono text-xs text-zinc-300 relative shadow-xl overflow-hidden">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-zinc-800">
                            <span class="text-zinc-500 text-xs font-sans font-medium">Interactive Terminal Output</span>
                            <button 
                                @click="
                                    let content = $refs.termContent.innerText;
                                    copyCommand(content);
                                "
                                class="flex items-center gap-1 text-[11px] px-2.5 py-1 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors cursor-pointer"
                            >
                                <flux:icon.clipboard class="w-3 h-3" />
                                <span x-text="termCopied ? 'Copied!' : 'Copy Code'"></span>
                            </button>
                        </div>

                        <div x-ref="termContent" class="overflow-x-auto">
                            <!-- cURL -->
                            <template x-if="termTab === 'curl'">
                                <pre class="leading-relaxed"><code><span class="text-zinc-500"># 1. Deploy a static HTML site or ZIP archive via API</span>
<span class="text-emerald-400">curl</span> -X POST https://drophtml.de/api/sites \
  -H <span class="text-yellow-300">"Authorization: Bearer dhtml_pat_98fbc102..."</span> \
  -H <span class="text-yellow-300">"Accept: application/json"</span> \
  -F <span class="text-yellow-300">"original_name=landing-production.zip"</span> \
  -F <span class="text-yellow-300">"file=@./dist/build.zip"</span> \
  -F <span class="text-yellow-300">"is_permanent=true"</span>

<span class="text-zinc-500 mt-4 block"># 2. Response (HTTP 201 Created):</span>
{
  <span class="text-indigo-400">"id"</span>: <span class="text-yellow-300">"019183ab-41c9-7ef2-a7cb-29f10928a01f"</span>,
  <span class="text-indigo-400">"slug"</span>: <span class="text-yellow-300">"landing-production-k7x2q"</span>,
  <span class="text-indigo-400">"status"</span>: <span class="text-yellow-300">"active"</span>,
  <span class="text-indigo-400">"url"</span>: <span class="text-yellow-300">"https://drophtml.de/s/landing-production-k7x2q"</span>,
  <span class="text-indigo-400">"is_permanent"</span>: <span class="text-emerald-400">true</span>
}</code></pre>
                            </template>

                            <!-- GitHub Actions -->
                            <template x-if="termTab === 'github'">
                                <pre class="leading-relaxed"><code><span class="text-zinc-500"># .github/workflows/deploy.yml</span>
<span class="text-pink-400">name</span>: Deploy Static Site
<span class="text-pink-400">on</span>:
  <span class="text-pink-400">push</span>:
    <span class="text-pink-400">branches</span>: [ main ]

<span class="text-pink-400">jobs</span>:
  <span class="text-pink-400">deploy</span>:
    <span class="text-pink-400">runs-on</span>: ubuntu-latest
    <span class="text-pink-400">steps</span>:
      - <span class="text-indigo-400">uses</span>: actions/checkout@v4
      - <span class="text-indigo-400">name</span>: Zip static files
        <span class="text-indigo-400">run</span>: zip -r site.zip ./dist
      - <span class="text-indigo-400">name</span>: Push to DropHTML
        <span class="text-indigo-400">run</span>: |
          curl -X POST https://drophtml.de/api/sites \
            -H "Authorization: Bearer @{{ secrets.DROPHTML_TOKEN }}" \
            -F "original_name=production-build" \
            -F "file=@site.zip"</code></pre>
                            </template>

                            <!-- Custom Domain -->
                            <template x-if="termTab === 'domains'">
                                <pre class="leading-relaxed"><code><span class="text-zinc-500"># Link a verified custom domain programmatically</span>
<span class="text-emerald-400">curl</span> -X POST https://drophtml.de/api/sites/{site_id}/domains \
  -H <span class="text-yellow-300">"Authorization: Bearer $TOKEN"</span> \
  -H <span class="text-yellow-300">"Content-Type: application/json"</span> \
  -d <span class="text-yellow-300">'{"domain": "docs.mycompany.com"}'</span>

<span class="text-zinc-500 mt-4 block"># Response:</span>
{
  <span class="text-indigo-400">"id"</span>: <span class="text-yellow-300">"019183ac-50a1-7ab2-9b2f-8172901a"</span>,
  <span class="text-indigo-400">"domain"</span>: <span class="text-yellow-300">"docs.mycompany.com"</span>,
  <span class="text-indigo-400">"verified_at"</span>: <span class="text-yellow-300">"2026-09-10T18:24:00Z"</span>,
  <span class="text-indigo-400">"dns_target"</span>: <span class="text-yellow-300">"sites.drophtml.de"</span>
}</code></pre>
                            </template>

                            <!-- Cloud Media API -->
                            <template x-if="termTab === 'media'">
                                <pre class="leading-relaxed"><code><span class="text-zinc-500"># Direct upload static media to high-speed cloud asset storage</span>
<span class="text-emerald-400">curl</span> -X POST https://drophtml.de/api/media \
  -H <span class="text-yellow-300">"Authorization: Bearer $TOKEN"</span> \
  -H <span class="text-yellow-300">"Accept: application/json"</span> \
  -F <span class="text-yellow-300">"file=@hero-video-4k.mp4"</span> \
  -F <span class="text-yellow-300">"storage_driver=asset_storage"</span> \
  -F <span class="text-yellow-300">"site_id=019183ab-41c9-7ef2-a7cb-29f10928a01f"</span>

<span class="text-zinc-500 mt-4 block"># Response (HTTP 201 Created):</span>
{
  <span class="text-indigo-400">"id"</span>: <span class="text-yellow-300">"019183b2-9a02-7c31-9f33-10928a01f"</span>,
  <span class="text-indigo-400">"filename"</span>: <span class="text-yellow-300">"hero-video-4k.mp4"</span>,
  <span class="text-indigo-400">"storage_driver"</span>: <span class="text-yellow-300">"asset_storage"</span>,
  <span class="text-indigo-400">"url"</span>: <span class="text-yellow-300">"https://assets.drophtml.de/media/sites/.../hero-video-4k.mp4"</span>,
  <span class="text-indigo-400">"html_snippet"</span>: <span class="text-yellow-300">"&lt;video controls src=\"...\"&gt;&lt;/video&gt;"</span>
}</code></pre>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>