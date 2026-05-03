<section class="w-full lg:max-w-6xl px-6 py-24">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div class="space-y-6">
            <h2 class="text-3xl lg:text-4xl font-black tracking-tight">Know exactly who's visiting.</h2>
            <p class="text-lg text-zinc-500 dark:text-zinc-400 leading-relaxed">
                Our dashboard gives you real-time insights into your traffic. See views over time, top referrers, and device types without installing any scripts.
            </p>
            <ul class="space-y-4">
                <li class="flex items-center gap-3 font-medium">
                    <div class="size-6 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
                        <flux:icon.check class="size-4" />
                    </div>
                    <span>No cookies required</span>
                </li>
                <li class="flex items-center gap-3 font-medium">
                    <div class="size-6 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
                        <flux:icon.check class="size-4" />
                    </div>
                    <span>Privacy-focused tracking</span>
                </li>
                <li class="flex items-center gap-3 font-medium">
                    <div class="size-6 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
                        <flux:icon.check class="size-4" />
                    </div>
                    <span>Instant real-time updates</span>
                </li>
            </ul>
        </div>

        <div class="relative group">
            <div class="absolute -inset-4 bg-gradient-to-tr from-purple-500 to-blue-500 rounded-[2rem] blur opacity-10 group-hover:opacity-20 transition duration-1000"></div>
            <flux:card class="relative overflow-hidden border-zinc-200 dark:border-zinc-800 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex gap-2">
                        <div class="size-3 rounded-full bg-red-400"></div>
                        <div class="size-3 rounded-full bg-amber-400"></div>
                        <div class="size-3 rounded-full bg-green-400"></div>
                    </div>
                    <div class="text-xs font-mono text-zinc-400">drophtml.de/analytics</div>
                </div>
                
                <div class="space-y-6 animate-pulse-slow">
                    <div class="flex justify-between items-end gap-2 h-32">
                        @foreach([30, 45, 25, 60, 75, 40, 90, 55, 80] as $h)
                            <div class="flex-1 bg-blue-500/20 dark:bg-blue-500/40 rounded-t-md" style="height: {{ $h }}%"></div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-100 dark:border-zinc-800">
                            <div class="text-xs text-zinc-400 uppercase tracking-wider font-bold mb-1">Live Views</div>
                            <div class="text-2xl font-black text-blue-600 tracking-tighter">1,284</div>
                        </div>
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-100 dark:border-zinc-800">
                            <div class="text-xs text-zinc-400 uppercase tracking-wider font-bold mb-1">Avg. Duration</div>
                            <div class="text-2xl font-black text-indigo-600 tracking-tighter">2m 45s</div>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</section>