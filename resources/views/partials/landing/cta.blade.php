<section class="w-full lg:max-w-6xl px-6 py-24">
    <div class="bg-blue-600 rounded-3xl p-8 lg:p-16 text-center text-white shadow-2xl shadow-blue-500/40 relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-20 -mr-20 size-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 size-64 bg-blue-400/20 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 space-y-8">
            <h2 class="text-3xl lg:text-5xl font-black">Ready to launch your site?</h2>
            <p class="text-blue-100 text-lg lg:text-xl max-w-xl mx-auto leading-relaxed">
                Join thousands of developers who use DropHTML for rapid prototyping and instant hosting.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <flux:button size="base" variant="primary" class="bg-white text-blue-600 hover:bg-blue-50 border-none px-10 shadow-xl font-bold" :href="route('register')">
                    Create your 100% free account
                </flux:button>
                <flux:button size="base" variant="ghost" class="text-white hover:bg-white/10 border-white/20" :href="route('login')">
                    Existing user? Log in
                </flux:button>
            </div>
        </div>
    </div>
</section>