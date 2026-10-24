<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use App\Services\SiteDeploymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout, SiteDeploymentService $deploymentService): void
    {
        $user = Auth::user();

        if ($user->password_set_at) {
            $this->validate([
                'password' => $this->currentPasswordRules(),
            ]);
        }

        // Delete user's sites, deployments, analytics, domains, and site media
        foreach ($user->sites as $site) {
            $deploymentService->deleteSite($site);
        }

        // Delete standalone user cloud media assets
        Storage::disk('media')->deleteDirectory("users/{$user->id}");
        $user->mediaAssets()->forceDelete();

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="size-9 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center ring-1 ring-red-500/20 shrink-0">
                    <flux:icon.exclamation-triangle class="size-4.5" />
                </span>
                <div>
                    <flux:heading size="lg" class="tracking-tight">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

                    <flux:subheading class="mt-1">
                        {{ __('Once your account is deleted, all of your sites, domains, analytics, and data will be permanently deleted.') }}
                        @if(auth()->user()->password_set_at)
                            {{ __('Please enter your password to confirm.') }}
                        @endif
                    </flux:subheading>
                </div>
            </div>
        </div>

        @if(auth()->user()->password_set_at)
            <flux:input wire:model="password" :label="__('Password')" type="password" viewable required />
        @endif

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                {{ __('Delete account') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
