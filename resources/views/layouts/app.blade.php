<x-layouts::app.sidebar :title="$title ?? null">
    @if(auth()->user() && !auth()->user()->password_set_at)
        <div class="border-b border-indigo-500/20 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-pink-500/10 dark:from-indigo-950/40 dark:via-purple-950/40 dark:to-pink-950/40 px-4 sm:px-6 py-2.5 flex items-center justify-between gap-4 backdrop-blur-md">
            <div class="flex items-center gap-2.5 text-xs sm:text-sm font-medium text-indigo-900 dark:text-indigo-200">
                <div class="size-6 rounded-lg bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <flux:icon.shield-exclamation class="size-4" />
                </div>
                <span>{{ __("You haven't set an account password yet. Setting a password enables email login alongside GitHub.") }}</span>
            </div>
            <a 
                href="{{ route('security.edit') }}" 
                wire:navigate
                class="shrink-0 px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-xs transition-colors"
            >
                {{ __('Set Password') }}
            </a>
        </div>
    @endif
    <flux:main class="p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
