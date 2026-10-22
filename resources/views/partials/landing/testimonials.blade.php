<section id="testimonials" class="py-24 relative z-10 reveal" x-intersect.once="$el.classList.add('is-shown')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <span class="eyebrow mb-4">Community Feedback</span>
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-zinc-900 dark:text-white mb-4">
                Built for Developers Who Value Speed
            </h2>
            <p class="text-zinc-500 dark:text-zinc-400 max-w-xl mx-auto text-base">
                Discover why indie hackers, agencies, and fullstack engineers use DropHTML to deliver fast, hassle-free web experiences.
            </p>
        </div>

        <!-- 3 Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Testimonial 1 -->
            <div class="rounded-3xl p-8 bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1 text-amber-400">
                            @for ($i = 0; $i < 5; $i++)
                                <flux:icon.star class="w-4 h-4 fill-current" />
                            @endfor
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content">Verified User</span>
                    </div>
                    <p class="text-zinc-700 dark:text-zinc-300 text-sm md:text-base leading-relaxed italic">
                        &ldquo;DropHTML completely eliminated my deployment overhead for client landing pages. Dragging a zip and instantly having a password-protected staging URL ready is unmatched.&rdquo;
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-6 border-t border-zinc-200/60 dark:border-zinc-800/80 mt-6">
                    <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold text-sm">
                        NM
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white text-sm">Norwin M.</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Frontend Specialist & Freelancer</div>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="rounded-3xl p-8 bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1 text-amber-400">
                            @for ($i = 0; $i < 5; $i++)
                                <flux:icon.star class="w-4 h-4 fill-current" />
                            @endfor
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content">CI/CD Power User</span>
                    </div>
                    <p class="text-zinc-700 dark:text-zinc-300 text-sm md:text-base leading-relaxed italic">
                        &ldquo;The GitHub webhook integration and REST API are flawless. We hooked it up to our build pipelines, and documentation deploys in under two seconds. The new cloud asset storage is the cherry on top.&rdquo;
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-6 border-t border-zinc-200/60 dark:border-zinc-800/80 mt-6">
                    <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold text-sm">
                        RB
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white text-sm">Ramsay Brewer</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Software Engineer, XP Systems</div>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="rounded-3xl p-8 bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1 text-amber-400">
                            @for ($i = 0; $i < 5; $i++)
                                <flux:icon.star class="w-4 h-4 fill-current" />
                            @endfor
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content">Creator</span>
                    </div>
                    <p class="text-zinc-700 dark:text-zinc-300 text-sm md:text-base leading-relaxed italic">
                        &ldquo;Finally, static hosting that doesn&rsquo;t require terminal gymnastics or heavy framework configurations. Perfect for teaching, sharing prototypes, and shipping fast landing pages.&rdquo;
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-6 border-t border-zinc-200/60 dark:border-zinc-800/80 mt-6">
                    <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold text-sm">
                        LH
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white text-sm">Louis H.</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Web Developer & Educator</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
