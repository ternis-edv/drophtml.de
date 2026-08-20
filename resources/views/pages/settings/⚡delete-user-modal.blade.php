<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $user = Auth::user();

        if ($user->password_set_at) {
            $this->validate([
                'password' => $this->currentPasswordRules(),
            ]);
        }

        // Delete user's sites storage directories
        foreach ($user->sites as $site) {
            if ($site->path) {
                Storage::disk('public')->deleteDirectory($site->path);
            }
        }

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

            <flux:subheading>
                {{ __('Once your account is deleted, all of your sites, domains, analytics, and data will be permanently deleted.') }}
                @if(auth()->user()->password_set_at)
                    {{ __('Please enter your password to confirm.') }}
                @endif
            </flux:subheading>
        </div>

        @if(auth()->user()->password_set_at)
            <flux:input wire:model="password" :label="__('Password')" type="password" viewable required />
        @endif

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                {{ __('Delete account') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
