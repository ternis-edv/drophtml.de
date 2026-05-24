<section id="upload" class="py-24 relative z-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12" x-data="{ shown: false }" x-intersect.once="shown = true">
            <h2 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-3xl md:text-4xl font-bold mb-4 text-white">Deploy Right Now</h2>
            <p :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-zinc-400">Drag and drop your HTML or ZIP file below. It's completely free and works instantly.</p>
        </div>

        <div 
            x-data="{ shown: false }" 
            x-intersect.once="shown = true"
            :class="shown ? 'opacity-100 translate-y-0 delay-200' : 'opacity-0 translate-y-10'" 
            class="transition-all duration-700 ease-out glass-panel rounded-3xl p-2 md:p-4 border border-zinc-800 shadow-[0_20px_50px_-12px_rgba(79,70,229,0.3)]"
        >
            <div class="bg-[#09090b] rounded-2xl p-6 border border-zinc-800">
                <livewire:file-uploader />
            </div>
            @guest
                <div class="mt-4 text-center text-sm text-zinc-500">
                    Sites uploaded without an account expire in 24 hours. <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300">Create a free account</a> to keep them longer.
                </div>
            @endguest
        </div>
    </div>
</section>