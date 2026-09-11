<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="relative p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
        <!-- Ambient subtle background glow for dashboard -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute bottom-10 left-1/3 w-80 h-80 bg-purple-500/5 dark:bg-purple-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

        @if(auth()->user() && !auth()->user()->password_set_at)
            <div class="mb-6 rounded-2xl border border-indigo-500/20 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-pink-500/10 dark:from-indigo-950/50 dark:via-purple-950/50 dark:to-pink-950/50 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 backdrop-blur-md">
                <div class="flex items-center gap-3 text-xs sm:text-sm font-medium text-indigo-900 dark:text-indigo-200">
                    <div class="size-7 rounded-lg bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 ring-1 ring-indigo-500/30">
                        <flux:icon.shield-exclamation class="size-4" />
                    </div>
                    <div>
                        <span class="font-bold text-indigo-950 dark:text-white">Security Recommendation:</span>
                        <span>{{ __(" You haven't set an account password yet. Setting a password enables email login alongside GitHub.") }}</span>
                    </div>
                </div>
                <a 
                    href="{{ route('security.edit') }}" 
                    wire:navigate
                    class="shrink-0 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs transition-transform hover:-translate-y-0.5 cursor-pointer"
                >
                    {{ __('Set Password Now') }} &rarr;
                </a>
            </div>
        @endif

        {{ $slot }}

        @auth
            <livewire:dashboard.command-palette />
        @endauth
    </flux:main>
</x-layouts::app.sidebar>
