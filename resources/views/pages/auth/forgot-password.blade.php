<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="text-center space-y-1.5">
            <div class="inline-flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 mb-2">
                <flux:icon.key class="size-6" />
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Reset your password') }}</h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter your verified email address and we will send you a password reset link') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <!-- Form -->
        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4.5">
            @csrf

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    placeholder="you@domain.com"
                />
            </div>

            <button 
                type="submit" 
                data-test="email-password-reset-link-button"
                class="w-full mt-2 py-2.5 px-4 rounded-xl bg-accent hover:bg-accent/90 text-white font-semibold text-sm shadow-md shadow-accent/25 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2"
            >
                <flux:icon.paper-airplane class="size-4" />
                <span>{{ __('Email password reset link') }}</span>
            </button>
        </form>

        <!-- Return to Login -->
        <div class="text-center text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 pt-2 border-t border-zinc-200/60 dark:border-zinc-800/60">
            <span>{{ __('Remember your password?') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="text-accent dark:text-brand-300 font-semibold hover:underline ml-1">
                {{ __('Return to log in') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
