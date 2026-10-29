<x-layouts::site :title="$title ?? null">
        @if(auth()->user() && !auth()->user()->password_set_at)
            <div class="rise-in mb-6 rounded-2xl border border-accent/20 bg-accent/5 dark:bg-accent/10 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 backdrop-blur-md" role="status">
                <div class="flex items-center gap-3 text-xs sm:text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <div class="size-7 rounded-lg bg-accent/15 text-accent flex items-center justify-center shrink-0 ring-1 ring-accent/30">
                        <flux:icon.shield-exclamation class="size-4" />
                    </div>
                    <div>
                        <span class="font-bold text-zinc-900 dark:text-white">Security Recommendation:</span>
                        <span>{{ __(" You haven't set an account password yet. Setting a password enables email login alongside GitHub.") }}</span>
                    </div>
                </div>
                <a 
                    href="{{ route('security.edit') }}" 
                    wire:navigate
                    class="shrink-0 btn-primary px-3.5 py-1.5 rounded-xl text-xs"
                >
                    {{ __('Set Password Now') }} &rarr;
                </a>
            </div>
        @endif

        {{ $slot }}
</x-layouts::site>
