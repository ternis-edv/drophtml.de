<x-layouts::site :title="$title ?? null">
        @if(auth()->user() && !auth()->user()->password_set_at)
            <div class="mb-6 dashboard-rounded border dashboard-accent-border dashboard-accent-tint p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" role="status">
                <div class="flex items-center gap-3 text-xs sm:text-sm font-medium dashboard-text">
                    <div class="size-7 dashboard-control-rounded dashboard-accent-tint dashboard-accent flex items-center justify-center shrink-0 ">
                        <flux:icon.shield-exclamation class="size-4" />
                    </div>
                    <div>
                        <span class="font-medium dashboard-text">Security Recommendation:</span>
                        <span>{{ __(" You haven't set an account password yet. Setting a password enables email login alongside GitHub.") }}</span>
                    </div>
                </div>
                <a
                    href="{{ route('security.edit') }}"
                    wire:navigate
                    class="shrink-0 dashboard-button dashboard-button-primary px-3.5 py-1.5 dashboard-control-rounded text-xs"
                >
                    {{ __('Set Password Now') }} &rarr;
                </a>
            </div>
        @endif

        {{ $slot }}
</x-layouts::site>
