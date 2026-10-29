<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 pt-8 border-t dashboard-border space-y-4">
    <div class="flex items-start gap-3">
        <span class="size-9 dashboard-control-rounded dashboard-danger-tint dashboard-danger flex items-center justify-center  shrink-0">
            <flux:icon.exclamation-triangle class="size-4.5" />
        </span>
        <div>
            <flux:heading class="dashboard-danger">{{ __('Delete account') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Delete your account and all of its resources') }}</flux:subheading>
        </div>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button data-dashboard-variant="danger" variant="danger" data-test="delete-user-button">
            {{ __('Delete account') }}
        </flux:button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
</section>
