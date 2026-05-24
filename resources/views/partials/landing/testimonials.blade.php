<section id="testimonials" class="py-24 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16" x-data="{ shown: false }" x-intersect.once="shown = true">
            <h2 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-3xl md:text-4xl font-bold mb-4 text-zinc-900 dark:text-white">Loved by developers</h2>
            <p :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-zinc-600 dark:text-zinc-400">See what others are saying about DropHTML.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8" x-data="{ shown: false }" x-intersect.once="shown = true">
            
            <!-- Testimonial 1 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 relative flex flex-col justify-between">
                <div class="mb-6">
                    <div class="flex items-center gap-1 mb-4 text-yellow-400">
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                    </div>
                    <p class="text-zinc-700 dark:text-zinc-300 italic">"DropHTML changed the way I share prototypes. I literally just drag a folder and send the link to my clients. It's ridiculously fast and the API makes automation a breeze."</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                        NM
                    </div>
                    <div>
                        <div class="font-bold text-zinc-900 dark:text-white text-sm">Norwin M.</div>
                        <div class="text-xs text-zinc-500">"Vibecoder"</div>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-200' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 relative flex flex-col justify-between">
                <div class="mb-6">
                    <div class="flex items-center gap-1 mb-4 text-yellow-400">
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                    </div>
                    <p class="text-zinc-700 dark:text-zinc-300 italic">"As a developer, I appreciate tools that just get out of my way. DropHTML does exactly that. The GitHub auto-deploy feature is seamless. A must-have for quick landing pages."</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400 font-bold text-sm">
                        RB
                    </div>
                    <div>
                        <div class="font-bold text-zinc-900 dark:text-white text-sm">Ramsay Brewer</div>
                        <div class="text-xs text-zinc-500">Developer, Affiliated with xpsystems.eu</div>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-300' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 relative flex flex-col justify-between">
                <div class="mb-6">
                    <div class="flex items-center gap-1 mb-4 text-yellow-400">
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                        <flux:icon.star class="w-4 h-4 fill-current" />
                    </div>
                    <p class="text-zinc-700 dark:text-zinc-300 italic">"I'm learning web development, and DropHTML makes it so easy to show my projects to my friends and teachers. No server setup, no confusing FTP clients. It just works!"</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold text-sm">
                        LH
                    </div>
                    <div>
                        <div class="font-bold text-zinc-900 dark:text-white text-sm">Louis H.</div>
                        <div class="text-xs text-zinc-500">Student</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>