<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;
                    this.code = '';
                    this.recovery_code = '';

                    this.$nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            <!-- Header for OTP -->
            <div x-show="!showRecoveryInput" class="text-center space-y-1.5 mb-6">
                <div class="inline-flex size-11 items-center justify-center rounded-xl bg-accent/10 text-accent dark:text-brand-300 border border-accent/20 mb-2">
                    <flux:icon.device-phone-mobile class="size-6" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Two-factor challenge') }}</h1>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter the 6-digit code from your authenticator app') }}</p>
            </div>

            <!-- Header for Recovery Code -->
            <div x-show="showRecoveryInput" class="text-center space-y-1.5 mb-6">
                <div class="inline-flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 mb-2">
                    <flux:icon.shield-exclamation class="size-6" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Recovery Code') }}</h1>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter one of your emergency recovery codes to authenticate') }}</p>
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-6">
                @csrf

                <!-- OTP Input Mode -->
                <div x-show="!showRecoveryInput" class="space-y-4">
                    <div class="flex items-center justify-center py-2" x-ref="otp">
                        <flux:otp
                            x-model="code"
                            length="6"
                            name="code"
                            label="OTP Code"
                            label:sr-only
                            class="mx-auto"
                        />
                    </div>
                </div>

                <!-- Recovery Code Input Mode -->
                <div x-show="showRecoveryInput" class="space-y-4">
                    <div>
                        <flux:input
                            type="text"
                            name="recovery_code"
                            x-ref="recovery_code"
                            x-bind:required="showRecoveryInput"
                            autocomplete="one-time-code"
                            x-model="recovery_code"
                            :label="__('Emergency Recovery Code')"
                            placeholder="abcdef-123456"
                        />
                    </div>

                    @error('recovery_code')
                        <div class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-500 text-xs text-center">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-2.5 px-4 rounded-xl bg-accent hover:bg-accent/90 text-white font-semibold text-sm shadow-md shadow-accent/25 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2"
                >
                    <flux:icon.check class="size-4" />
                    <span>{{ __('Continue to Dashboard') }}</span>
                </button>

                <!-- Recovery / OTP Switch -->
                <div class="text-center pt-2 border-t border-zinc-200/60 dark:border-zinc-800/60">
                    <button 
                        type="button" 
                        @click="toggleInput()" 
                        class="text-xs sm:text-sm text-accent dark:text-brand-300 hover:underline font-medium cursor-pointer"
                    >
                        <span x-show="!showRecoveryInput">{{ __('Use an emergency recovery code instead') }}</span>
                        <span x-show="showRecoveryInput">{{ __('Use an authentication code instead') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
