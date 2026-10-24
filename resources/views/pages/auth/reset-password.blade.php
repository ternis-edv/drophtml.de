<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="text-center space-y-1.5">
            <div class="inline-flex size-11 items-center justify-center rounded-xl bg-accent/10 text-accent dark:text-brand-300 border border-accent/20 mb-2">
                <flux:icon.lock-closed class="size-6" />
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Set new password') }}</h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ __('Please enter your new password to complete the reset') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    value="{{ request('email') }}"
                    :label="__('Email address')"
                    type="email"
                    required
                    autocomplete="email"
                />
            </div>

            <!-- Password -->
            <div>
                <flux:input
                    name="password"
                    :label="__('New Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Create a strong password')"
                    viewable
                />
            </div>

            <!-- Confirm Password -->
            <div>
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Re-enter your new password')"
                    viewable
                />
            </div>

            <button 
                type="submit" 
                data-test="reset-password-button"
                class="w-full mt-2 py-2.5 px-4 rounded-xl bg-accent hover:bg-accent/90 text-white font-semibold text-sm shadow-md shadow-accent/25 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2"
            >
                <flux:icon.check class="size-4" />
                <span>{{ __('Update password') }}</span>
            </button>
        </form>
    </div>
</x-layouts::auth>
