<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 pt-8 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
    <div class="flex items-start gap-3">
        <span class="size-9 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center ring-1 ring-red-500/20 shrink-0">
            <flux:icon.exclamation-triangle class="size-4.5" />
        </span>
        <div>
            <flux:heading class="text-red-700 dark:text-red-400">{{ __('Delete account') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Delete your account and all of its resources') }}</flux:subheading>
        </div>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" data-test="delete-user-button">
            {{ __('Delete account') }}
        </flux:button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
</section>
