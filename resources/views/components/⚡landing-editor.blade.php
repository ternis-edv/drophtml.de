<?php

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    public $code = <<<HTML
<!-- DropHTML Preview -->
<div class='app'>
  <h1>Hello, DropHTML! 🚀</h1>
  <p>Edit this code and see the preview update instantly.
  Perfect for rapid prototyping.</p>
  <button>Deploy Me</button>
</div>

<style>
  .app {
    font-family: system-ui, sans-serif;
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
    border-radius: 16px;
    border: 1px solid #c7d2fe;
  }
  h1 { color: #4338ca; margin-bottom: 10px; }
  p { color: #6b7280; line-height: 1.6; }
  button {
    margin-top: 20px;
    padding: 10px 24px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
  }
  button:hover { background: #4f46e5; transform: translateY(-2px); }
</style>
HTML;

    public $uploading = false;
    public $deployedUrl = null;

    public function deploy()
    {
        $this->uploading = true;

        try {
            $slug = Str::random(8);
            $path = "sites/{$slug}";

            Storage::disk('public')->makeDirectory($path);
            Storage::disk('public')->put("{$path}/index.html", $this->code);

            $site = Site::create([
                'slug' => $slug,
                'original_name' => 'index.html',
                'path' => $path,
                'user_id' => auth()->id(), // null if guest
                'expires_at' => auth()->check() ? now()->addDays(30) : now()->addHours(24),
            ]);

            if (auth()->check()) {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'site_id' => $site->id,
                    'action' => 'site_uploaded',
                    'description' => 'Deployed site from interactive editor',
                    'ip_address' => request()->ip(),
                ]);
            }

            $host = parse_url(config('app.url'), PHP_URL_HOST);
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http';

            if ($host && $host !== 'localhost') {
                $this->deployedUrl = "{$scheme}://{$slug}.{$host}";
            } else {
                $this->deployedUrl = url("/s/{$slug}");
            }
        } catch (\Exception $e) {
            // Handle error quietly or add an error property
        }

        $this->uploading = false;
    }
    
    public function resetDeploy()
    {
        $this->deployedUrl = null;
    }
};
?>

<section id="demo" class="py-24 relative relative z-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16" x-data="{ shown: false }" x-intersect.once="shown = true">
            <h2 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-3xl md:text-4xl font-bold mb-4 text-zinc-900 dark:text-white">Experience the Magic</h2>
            <p :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-zinc-600 dark:text-zinc-400">Try the mock editor below. It's exactly how the real dashboard feels.</p>
        </div>

        <div 
            x-data="{ shown: false }" 
            x-intersect.once="shown = true"
            :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'" 
            class="transition-all duration-1000 ease-out glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden"
        >
            <!-- Mac Window Header -->
            <div class="bg-zinc-100 dark:bg-zinc-900/80 px-4 py-3 flex items-center border-b border-zinc-200 dark:border-zinc-800">
                <div class="flex gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                </div>
                <div class="mx-auto text-xs text-zinc-500 font-mono">dashboard.drophtml.de</div>
            </div>

            <!-- Editor Body -->
            <div class="grid grid-cols-1 md:grid-cols-2 min-h-[450px]">
                
                <!-- Left Pane: Code -->
                <div class="p-6 bg-white dark:bg-[#0d0d12] flex flex-col relative border-r border-zinc-200 dark:border-zinc-800/50">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-mono text-zinc-500 uppercase tracking-wider flex items-center gap-2">
                            <flux:icon.code-bracket class="w-4 h-4" /> index.html
                        </span>
                        <button wire:click="deploy" wire:loading.attr="disabled" class="px-4 py-1.5 bg-indigo-500 hover:bg-indigo-600 disabled:bg-indigo-500/50 text-white text-xs font-medium rounded-md transition-colors flex items-center gap-2">
                            <span wire:loading.remove wire:target="deploy">
                                <span class="flex items-center gap-1"><flux:icon.cloud-arrow-up class="w-3 h-3"/> Deploy Now</span>
                            </span>
                            <span wire:loading wire:target="deploy">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Deploying...
                                </span>
                            </span>
                        </button>
                    </div>
                    
                    <textarea wire:model.live.debounce.500ms="code" class="w-full flex-grow bg-transparent border-none text-zinc-800 dark:text-indigo-200/90 font-mono text-sm leading-relaxed resize-none focus:ring-0 p-0 outline-none" spellcheck="false"></textarea>

                    <!-- Deployment Overlay -->
                    @if($deployedUrl)
                    <div class="absolute inset-0 bg-white/90 dark:bg-[#0d0d12]/90 backdrop-blur-md flex flex-col items-center justify-center p-6 z-10">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mb-6 border border-green-200 dark:border-green-500/30">
                                <flux:icon.check class="w-8 h-8" />
                            </div>
                            <h3 class="text-zinc-900 dark:text-white text-xl font-bold mb-2">Your site is live!</h3>
                            <p class="text-zinc-500 dark:text-zinc-400 text-sm mb-6">Deployed successfully.</p>
                            
                            <div class="flex items-center gap-3 bg-zinc-100 dark:bg-black/50 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 w-full max-w-sm justify-between">
                                <span class="text-sm text-indigo-600 dark:text-indigo-300 font-mono truncate"><a href="{{ $deployedUrl }}" target="_blank" class="hover:underline">{{ $deployedUrl }}</a></span>
                            </div>
                            
                            <button wire:click="resetDeploy" class="mt-8 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-white transition-colors flex items-center gap-1">
                                <flux:icon.arrow-path class="w-4 h-4" /> Edit again
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Pane: Preview -->
                <div class="bg-zinc-50 dark:bg-[#f8fafc] relative overflow-hidden flex flex-col">
                    <div class="w-full p-2 bg-zinc-200 border-b border-zinc-300 text-xs text-zinc-500 text-center font-mono flex items-center justify-center gap-2 shadow-sm">
                        <flux:icon.eye class="w-3 h-3" />
                        Live Preview
                    </div>
                    <div class="flex-grow w-full h-full flex items-center justify-center p-4">
                        <!-- Live preview container -->
                        <div class="w-full max-w-md" x-data="{ code: @entangle('code') }" x-html="code"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>