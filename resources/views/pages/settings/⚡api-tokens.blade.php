<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('API Tokens')] class extends Component {
    public string $createTokenName = '';
    public string $plainTextToken = '';

    public function createToken(): void
    {
        $this->validate([
            'createTokenName' => ['required', 'string', 'max:255'],
        ]);

        $token = Auth::user()->createToken($this->createTokenName);

        $this->plainTextToken = $token->plainTextToken;

        $this->reset('createTokenName');

        Flux::toast(variant: 'success', text: __('API token created.'));
    }

    public function revokeToken(int $tokenId): void
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();

        // Clear plain text token if it was the one deleted
        $this->plainTextToken = '';

        Flux::toast(variant: 'success', text: __('API token revoked.'));
    }

    public function with(): array
    {
        return [
            'tokens' => Auth::user()->tokens()->get(),
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('API Tokens') }}</flux:heading>

    <x-pages::settings.layout :heading="__('API Tokens')" :subheading="__('Manage your personal API tokens')">

        <form wire:submit="createToken" class="mt-6 space-y-6">
            <flux:input
                wire:model="createTokenName"
                :label="__('Token Name')"
                type="text"
                required
                placeholder="{{ __('e.g. CLI, Github Actions') }}"
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Create Token') }}
                </flux:button>
            </div>
        </form>

        @if ($plainTextToken)
            <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded-md border border-green-200 dark:border-green-800">
                <p class="text-sm font-medium">{{ __('Please copy your new API token. For your security, it won\'t be shown again.') }}</p>
                <div class="mt-2 flex items-center justify-between bg-white dark:bg-zinc-800 p-2 rounded border border-green-200 dark:border-green-800">
                    <code class="text-xs break-all">{{ $plainTextToken }}</code>
                </div>
            </div>
        @endif

        @if ($tokens->isNotEmpty())
            <div class="mt-10">
                <flux:heading>{{ __('Manage Tokens') }}</flux:heading>
                <div class="mt-4 space-y-4">
                    @foreach ($tokens as $token)
                        <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-md">
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $token->name }}</div>
                                <div class="text-xs text-zinc-500 mt-1">
                                    @if ($token->last_used_at)
                                        {{ __('Last used') }} {{ $token->last_used_at->diffForHumans() }}
                                    @else
                                        {{ __('Never used') }}
                                    @endif
                                </div>
                            </div>

                            <flux:button variant="danger" wire:click="revokeToken({{ $token->id }})" size="sm">
                                {{ __('Revoke') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </x-pages::settings.layout>
</section>
