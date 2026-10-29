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
                <flux:button data-dashboard-variant="primary" variant="primary" type="submit">
                    {{ __('Create Token') }}
                </flux:button>
            </div>
        </form>

        @if ($plainTextToken)
            <div class="mt-6 p-4 dashboard-success-tint dashboard-success dashboard-rounded border dashboard-success-border">
                <div class="flex items-center gap-2">
                    <flux:icon.check-circle class="size-4 dashboard-success" />
                    <p class="text-xs font-medium">{{ __('Please copy your new API token now. For your security, it won\'t be shown again.') }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between dashboard-surface px-3 py-2 dashboard-control-rounded border dashboard-success-border font-mono text-xs">
                    <code class="break-all dashboard-success">{{ $plainTextToken }}</code>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $plainTextToken }}'); Flux.toast('API token copied to clipboard!')"
                        class="dashboard-success p-1 rounded-md dashboard-success-tint transition-colors ml-2 shrink-0 cursor-pointer"
                        title="Copy Token"
                    >
                        <flux:icon.clipboard class="size-4" />
                    </button>
                </div>
            </div>
        @endif

        @if ($tokens->isNotEmpty())
            <div class="mt-10 pt-6 border-t dashboard-border">
                <flux:heading size="sm">{{ __('Active Tokens') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    @foreach ($tokens as $token)
                        <div class="dashboard-panel p-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="dashboard-icon" aria-hidden="true">
                                    <flux:icon.key class="size-4" />
                                </span>
                                <div>
                                    <div class="font-medium text-xs tracking-tight dashboard-text">{{ $token->name }}</div>
                                    <div class="text-[11px] font-mono dashboard-muted mt-0.5">
                                        @if ($token->last_used_at)
                                            {{ __('Last used') }} {{ $token->last_used_at->diffForHumans() }}
                                        @else
                                            {{ __('Never used') }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <flux:button data-dashboard-variant="danger" variant="danger" wire:click="revokeToken({{ $token->id }})" size="xs">
                                {{ __('Revoke') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10 pt-6 border-t dashboard-border">
            <div class="dashboard-panel p-5    border dashboard-border dashboard-rounded">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="dashboard-icon bg-sky-500/10 text-sky-400 border border-sky-500/20" aria-hidden="true">
                            <flux:icon.cpu-chip class="size-4" />
                        </span>
                        <div>
                            <flux:heading size="sm">{{ __('Use with AI Agents & CLI') }}</flux:heading>
                            <p class="text-xs dashboard-muted mt-0.5">{{ __('Equip Claude Code, Antigravity, Cursor, and custom agents with DropHTML capabilities.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('docs.agents') }}" target="_blank" class="text-xs text-sky-400 hover:text-sky-300 font-medium inline-flex items-center gap-1">
                        {{ __('Agent Docs') }} &rarr;
                    </a>
                </div>

                <div class="mt-4 space-y-2 font-mono text-xs dashboard-inset p-3.5 dashboard-control-rounded border dashboard-border dashboard-text">
                    <div class="dashboard-muted"># 1. Export your token in your terminal or agent environment</div>
                    <div>export DROPHTML_API_TOKEN="your_token_here"</div>
                    <div class="pt-2 dashboard-muted"># 2. Initialize the skill in any project workspace</div>
                    <div>./scripts/drophtml init</div>
                </div>
            </div>
        </div>
    </x-pages::settings.layout>
</section>
