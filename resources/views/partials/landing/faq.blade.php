<section id="faq" class="py-24 relative z-10 reveal" x-data="{ openFaq: null }" x-intersect.once="$el.classList.add('is-shown')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-4 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                Frequently Asked Questions
            </div>
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4 text-zinc-900 dark:text-white tracking-tight">
                Got Questions? We Have Answers.
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto text-base">
                Everything you need to know about DropHTML deployment, custom domains, media hosting, and automation.
            </p>
        </div>

        <!-- Accordion List -->
        <div class="space-y-4">
            
            <!-- Item 1 -->
            <div class="glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-colors">
                <button 
                    @click="openFaq = (openFaq === 1 ? null : 1)"
                    type="button"
                    class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-zinc-900 dark:text-white text-base md:text-lg cursor-pointer select-none"
                >
                    <span>How does static site hosting on DropHTML work?</span>
                    <flux:icon.chevron-down 
                        class="w-5 h-5 text-zinc-400 transition-transform duration-200 shrink-0" 
                        ::class="openFaq === 1 ? 'rotate-180 text-indigo-500' : ''"
                    />
                </button>
                <div x-show="openFaq === 1" x-collapse class="px-6 pb-6 text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60 pt-4">
                    Simply drag and drop an <code>index.html</code> file or a zip archive containing your static files. Our build pipeline unpacks your files, sanitizes file paths, generates a live URL like <code>stellar-portfolio.drophtml.de</code>, and distributes it to our global CDN nodes in under two seconds.
                </div>
            </div>

            <!-- Item 2 -->
            <div class="glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-colors">
                <button 
                    @click="openFaq = (openFaq === 2 ? null : 2)"
                    type="button"
                    class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-zinc-900 dark:text-white text-base md:text-lg cursor-pointer select-none"
                >
                    <span>How does Cloud Media Storage work?</span>
                    <flux:icon.chevron-down 
                        class="w-5 h-5 text-zinc-400 transition-transform duration-200 shrink-0" 
                        ::class="openFaq === 2 ? 'rotate-180 text-indigo-500' : ''"
                    />
                </button>
                <div x-show="openFaq === 2" x-collapse class="px-6 pb-6 text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60 pt-4">
                    Heavy files like 4K videos, high-resolution graphics, and PDF documents can bloat your site archive. With DropHTML Cloud Media, you can upload them to dedicated high-speed cloud asset storage with global edge caching and get 1-click embed tags (<code>&lt;img&gt;</code>, <code>&lt;video&gt;</code>, Markdown, or raw URLs). You can also migrate files between "Code" and "Asset Storage" at any time with a single click.
                </div>
            </div>

            <!-- Item 3 -->
            <div class="glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-colors">
                <button 
                    @click="openFaq = (openFaq === 3 ? null : 3)"
                    type="button"
                    class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-zinc-900 dark:text-white text-base md:text-lg cursor-pointer select-none"
                >
                    <span>Can I use my own custom domain with free automatic SSL?</span>
                    <flux:icon.chevron-down 
                        class="w-5 h-5 text-zinc-400 transition-transform duration-200 shrink-0" 
                        ::class="openFaq === 3 ? 'rotate-180 text-indigo-500' : ''"
                    />
                </button>
                <div x-show="openFaq === 3" x-collapse class="px-6 pb-6 text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60 pt-4">
                    Yes. Point your domain or subdomain CNAME record to <code>sites.drophtml.de</code>. Once DNS propagates, our system verifies ownership and provisions an auto-renewing Let's Encrypt TLS certificate for HTTPS security with zero manual configuration.
                </div>
            </div>

            <!-- Item 4 -->
            <div class="glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-colors">
                <button 
                    @click="openFaq = (openFaq === 4 ? null : 4)"
                    type="button"
                    class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-zinc-900 dark:text-white text-base md:text-lg cursor-pointer select-none"
                >
                    <span>How does GitHub Auto-Deploy work?</span>
                    <flux:icon.chevron-down 
                        class="w-5 h-5 text-zinc-400 transition-transform duration-200 shrink-0" 
                        ::class="openFaq === 4 ? 'rotate-180 text-indigo-500' : ''"
                    />
                </button>
                <div x-show="openFaq === 4" x-collapse class="px-6 pb-6 text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60 pt-4">
                    Connect your GitHub account and select any repository. We automatically configure a webhook. Every time you push to your default branch, GitHub notifies DropHTML, and we instantly sync your latest static files with atomic zero-downtime updates.
                </div>
            </div>

            <!-- Item 5 -->
            <div class="glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-colors">
                <button 
                    @click="openFaq = (openFaq === 5 ? null : 5)"
                    type="button"
                    class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-zinc-900 dark:text-white text-base md:text-lg cursor-pointer select-none"
                >
                    <span>Can I lock client previews behind a password?</span>
                    <flux:icon.chevron-down 
                        class="w-5 h-5 text-zinc-400 transition-transform duration-200 shrink-0" 
                        ::class="openFaq === 5 ? 'rotate-180 text-indigo-500' : ''"
                    />
                </button>
                <div x-show="openFaq === 5" x-collapse class="px-6 pb-6 text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60 pt-4">
                    Yes. In your site dashboard, toggle "Password Protection" and set any secret passcode. When clients visit the site or subdomain, they will be greeted with a branded unlock screen. Once unlocked, the session is preserved.
                </div>
            </div>

            <!-- Item 6 -->
            <div class="glass-panel rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-colors">
                <button 
                    @click="openFaq = (openFaq === 6 ? null : 6)"
                    type="button"
                    class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-zinc-900 dark:text-white text-base md:text-lg cursor-pointer select-none"
                >
                    <span>Is there a full REST API for automated CI/CD pipelines?</span>
                    <flux:icon.chevron-down 
                        class="w-5 h-5 text-zinc-400 transition-transform duration-200 shrink-0" 
                        ::class="openFaq === 6 ? 'rotate-180 text-indigo-500' : ''"
                    />
                </button>
                <div x-show="openFaq === 6" x-collapse class="px-6 pb-6 text-sm md:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60 pt-4">
                    Yes. You can generate Personal Access Tokens (PAT) and integrate DropHTML with GitHub Actions, GitLab CI, Bitbucket, shell scripts, or custom services using our standard REST API endpoints (documented interactively at <code>/docs/api</code>).
                </div>
            </div>

        </div>

    </div>
</section>
