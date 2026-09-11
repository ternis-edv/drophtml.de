<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="text-center space-y-1.5">
            <div class="inline-flex size-11 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 mb-2">
                <flux:icon.user-plus class="size-6" />
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Create an account') }}</h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ __('Start deploying static websites in seconds with DropHTML') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <!-- Social GitHub Register -->
        <div>
            <a 
                href="{{ route('social.redirect', 'github') }}" 
                class="w-full inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-medium text-sm transition-all border border-zinc-700/80 shadow-xs hover:shadow-md cursor-pointer"
            >
                <svg class="size-4.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                </svg>
                <span>{{ __('Sign up with GitHub') }}</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="relative flex items-center justify-center">
            <div class="border-t border-zinc-200 dark:border-zinc-800 w-full"></div>
            <span class="bg-white dark:bg-zinc-900 px-3 text-[11px] uppercase tracking-wider text-zinc-600 dark:text-zinc-400 shrink-0 font-medium">{{ __('Or with email') }}</span>
            <div class="border-t border-zinc-200 dark:border-zinc-800 w-full"></div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Name -->
            <div>
                <flux:input
                    name="name"
                    :label="__('Full Name')"
                    :value="old('name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Alex Morgan"
                />
            </div>

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="alex@domain.com"
                />
            </div>

            <!-- Password -->
            <div>
                <flux:input
                    name="password"
                    :label="__('Password')"
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
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm your password')"
                    viewable
                />
            </div>

            <button 
                type="submit" 
                data-test="register-user-button"
                class="w-full mt-2 py-2.5 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-semibold text-sm shadow-md shadow-indigo-500/25 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2"
            >
                <span>{{ __('Create account') }}</span>
                <flux:icon.arrow-right class="size-4" />
            </button>
        </form>

        <!-- Terms Note -->
        <p class="text-[11px] text-zinc-600 dark:text-zinc-400 text-center leading-relaxed">
            By signing up, you agree to our 
            <a href="{{ route('legal.show', 'terms') }}" class="underline hover:text-zinc-900 dark:hover:text-zinc-200">Terms of Service</a> 
            and 
            <a href="{{ route('legal.show', 'privacy') }}" class="underline hover:text-zinc-900 dark:hover:text-zinc-200">Privacy Policy</a>.
        </p>

        <!-- Login Switch Link -->
        <div class="text-center text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 pt-2 border-t border-zinc-200/60 dark:border-zinc-800/60">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline ml-1">
                {{ __('Log in') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
