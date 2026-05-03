<x-layouts::app.sidebar :title="$title ?? null">
    @if(auth()->user() && !auth()->user()->password_set_at)
        <div class="bg-blue-600 text-white px-4 py-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <flux:icon.information-circle class="size-4" />
                <span>You haven't set a password yet. Setting a password allows you to log in without GitHub.</span>
            </div>
            <flux:button size="xs" variant="primary" class="bg-white text-blue-600 border-none hover:bg-blue-50" :href="route('profile.edit')" wire:navigate>
                Set Password
            </flux:button>
        </div>
    @endif
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
