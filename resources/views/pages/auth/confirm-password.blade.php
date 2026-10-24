<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="text-center space-y-1.5">
            <div class="inline-flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 mb-2">
                <flux:icon.shield-check class="size-6" />
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Confirm your password') }}</h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ __('This is a secure area. Please confirm your password before continuing.') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <!-- Form -->
        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-4.5">
            @csrf

            <div>
                <flux:input
                    name="password"
                    :label="__('Current Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                />
            </div>

            <button 
                type="submit" 
                data-test="confirm-password-button"
                class="w-full mt-2 py-2.5 px-4 rounded-xl bg-accent hover:bg-accent/90 text-white font-semibold text-sm shadow-md shadow-accent/25 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2"
            >
                <flux:icon.lock-closed class="size-4" />
                <span>{{ __('Confirm Password') }}</span>
            </button>
        </form>
    </div>
</x-layouts::auth>
