<flux:dropdown position="bottom" align="start" {{ $attributes }}>
    <button type="button" class="dashboard-account-trigger" data-test="sidebar-menu-button" aria-label="Open account menu">
        <span class="dashboard-account-avatar">{{ auth()->user()->initials() }}</span>
        <span class="min-w-0 flex-1 truncate text-left">{{ auth()->user()->name }}</span>
        <flux:icon.chevrons-up-down class="size-4" />
    </button>

    <flux:menu class="w-64 p-1.5">
        <div class="flex items-center gap-3 p-2 text-start text-sm dashboard-surface dashboard-control-rounded mb-1 border dashboard-border">
            <flux:avatar
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                class="size-9 "
            />
            <div class="grid flex-1 text-start text-sm leading-tight min-w-0">
                <div class="flex items-center justify-between gap-1">
                    <flux:heading class="truncate font-medium dashboard-text">{{ auth()->user()->name }}</flux:heading>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-medium dashboard-accent-tint dashboard-accent border dashboard-accent-border">{{ auth()->user()->hasPaidPlan() ? 'Paid' : 'Free' }}</span>
                </div>
                <flux:text class="truncate text-xs dashboard-muted">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>

        <div class="px-2 py-1.5 mb-1 text-[11px] dashboard-muted flex items-center justify-between border-b dashboard-border">
            <span>Active Deployments</span>
            <span class="font-medium dashboard-text">{{ auth()->user()->sites()->count() }} sites</span>
        </div>

        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Account Settings') }}
            </flux:menu.item>
            <flux:menu.item :href="route('settings.storage')" icon="circle-stack" wire:navigate>
                {{ __('Storage & Usage') }}
            </flux:menu.item>
            <flux:menu.item :href="route('api-tokens.edit')" icon="key" wire:navigate>
                {{ __('API Tokens & CLI') }}
            </flux:menu.item>
            <flux:menu.item :href="route('appearance.edit')" icon="paint-brush" wire:navigate>
                {{ __('Appearance') }}
            </flux:menu.item>
        </flux:menu.radio.group>

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer dashboard-danger dashboard-danger-tint dashboard-control-rounded"
                data-test="logout-button"
            >
                {{ __('Log out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
