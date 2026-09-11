<div class="flex items-start max-md:flex-col gap-8">
    <div class="w-full md:w-56 shrink-0">
        <div class="glass-card rounded-2xl p-2.5">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item icon="user" :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
                <flux:navlist.item icon="shield-check" :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
                <flux:navlist.item icon="key" :href="route('api-tokens.edit')" wire:navigate>{{ __('API Tokens') }}</flux:navlist.item>
                <flux:navlist.item icon="paint-brush" :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            </flux:navlist>
        </div>
    </div>

    <div class="flex-1 self-stretch min-w-0">
        <div class="glass-card rounded-2xl p-6 sm:p-8 max-w-2xl">
            <flux:heading size="lg">{{ $heading ?? '' }}</flux:heading>
            <flux:subheading class="mt-1">{{ $subheading ?? '' }}</flux:subheading>

            <div class="mt-6 w-full">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
