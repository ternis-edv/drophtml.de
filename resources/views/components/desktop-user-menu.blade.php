<flux:dropdown position="bottom" align="start">
    <div class="px-2 py-1">
        <flux:sidebar.profile
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
            icon:trailing="chevrons-up-down"
            data-test="sidebar-menu-button"
            class="hover:bg-zinc-100 dark:hover:bg-zinc-800/80 rounded-xl transition-colors cursor-pointer"
        />
    </div>

    <flux:menu class="w-60">
        <div class="flex items-center gap-2.5 px-2 py-2 text-start text-sm">
            <flux:avatar
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                class="size-9 ring-2 ring-indigo-500/20"
            />
            <div class="grid flex-1 text-start text-sm leading-tight min-w-0">
                <flux:heading class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate text-xs text-zinc-400">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Account Settings') }}
            </flux:menu.item>
            <flux:menu.item :href="route('api-tokens.edit')" icon="key" wire:navigate>
                {{ __('API Tokens') }}
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
                class="w-full cursor-pointer text-red-600 dark:text-red-400 hover:!bg-red-500/10"
                data-test="logout-button"
            >
                {{ __('Log out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
