<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="text-center space-y-1.5">
            <div class="inline-flex size-11 items-center justify-center rounded-xl bg-accent/10 text-accent dark:text-brand-300 border border-accent/20 mb-2">
                <flux:icon.envelope-open class="size-6" />
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Verify your email') }}</h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                {{ __('Thanks for signing up! Please verify your email address by clicking on the link we just sent to you.') }}
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-medium flex items-center justify-center gap-2">
                <flux:icon.check-circle class="size-4 shrink-0" />
                <span>{{ __('A fresh verification link has been sent to your email address.') }}</span>
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button 
                    type="submit" 
                    class="w-full py-2.5 px-4 rounded-xl bg-accent hover:bg-accent/90 text-white font-semibold text-sm shadow-md shadow-accent/25 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2"
                >
                    <flux:icon.arrow-path class="size-4" />
                    <span>{{ __('Resend verification email') }}</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button 
                    type="submit" 
                    data-test="logout-button"
                    class="text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 underline cursor-pointer py-1"
                >
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>
