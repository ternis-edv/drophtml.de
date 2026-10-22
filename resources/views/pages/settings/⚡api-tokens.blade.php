<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

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

        <form wire:submit="createToken" class="space-y-6">
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
            <div class="mt-6 p-4 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 rounded-2xl border border-emerald-200/80 dark:border-emerald-800/60 shadow-xs">
                <div class="flex items-center gap-2">
                    <flux:icon.check-circle class="size-4 text-emerald-600 dark:text-emerald-400" />
                    <p class="text-xs font-semibold">{{ __('Please copy your new API token now. For your security, it won\'t be shown again.') }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between bg-white dark:bg-zinc-900 px-3 py-2 rounded-xl border border-emerald-200/80 dark:border-emerald-800/60 font-mono text-xs">
                    <code class="break-all text-emerald-900 dark:text-emerald-200">{{ $plainTextToken }}</code>
                    <button 
                        type="button" 
                        @click="navigator.clipboard.writeText('{{ $plainTextToken }}'); Flux.toast('API token copied to clipboard!')" 
                        class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 p-1 rounded-md hover:bg-emerald-50 dark:hover:bg-emerald-950 transition-colors ml-2 shrink-0 cursor-pointer"
                        title="Copy Token"
                    >
                        <flux:icon.clipboard class="size-4" />
                    </button>
                </div>
            </div>
        @endif

        @if ($tokens->isNotEmpty())
            <div class="mt-10 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                <flux:heading size="sm">{{ __('Active Tokens') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    @foreach ($tokens as $token)
                        <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-accent/10 text-accent dark:text-accent-content flex items-center justify-center ring-1 ring-accent/20">
                                    <flux:icon.key class="size-4" />
                                </div>
                                <div>
                                    <div class="font-semibold text-xs tracking-tight text-zinc-900 dark:text-zinc-100">{{ $token->name }}</div>
                                    <div class="text-[11px] font-mono text-zinc-400 dark:text-zinc-500 mt-0.5">
                                        @if ($token->last_used_at)
                                            {{ __('Last used') }} {{ $token->last_used_at->diffForHumans() }}
                                        @else
                                            {{ __('Never used') }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <flux:button variant="danger" wire:click="revokeToken({{ $token->id }})" size="xs">
                                {{ __('Revoke') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </x-pages::settings.layout>
</section>
