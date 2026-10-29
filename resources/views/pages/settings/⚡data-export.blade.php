<?php

use App\Services\UserDataExportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Data export')] class extends Component {
    public function download(UserDataExportService $exportService)
    {
        return $exportService->download(Auth::user());
    }

    public function render(UserDataExportService $exportService)
    {
        $user = Auth::user();

        return view('pages.settings.⚡data-export', [
            'summary' => $exportService->summary($user),
        ]);
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Data export') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Data export')" :subheading="__('Download a machine-readable copy of your personal data')" :max-width="'max-w-3xl'">
        <div class="space-y-6 my-4">
            <p class="text-sm leading-6 dashboard-muted">
                {{ __('Under the right to data portability (GDPR Art. 20 / DSGVO Art. 20) you can download all personal data we store about your account as a single JSON file.') }}
            </p>

            <!-- What will be exported -->
            <div class="dashboard-panel p-5 space-y-4">
                <div class="text-xs font-medium uppercase tracking-wider dashboard-muted">{{ __('Included in your export') }}</div>

                <ul class="space-y-2 text-sm dashboard-text">
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 dashboard-success shrink-0" />
                        <span>{{ __('Account profile (name, email, sign-in providers, account activity)') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 dashboard-success shrink-0" />
                        <span>{{ __('Sites including domains, deployments, and redirects') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 dashboard-success shrink-0" />
                        <span>{{ __('Uploaded media files') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 dashboard-success shrink-0" />
                        <span>{{ __('Activity log entries (including IP addresses)') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 dashboard-success shrink-0" />
                        <span>{{ __('API token metadata (names and abilities, never the token values)') }}</span>
                    </li>
                </ul>

                <div class="pt-3 border-t dashboard-border">
                    <div class="text-xs font-medium uppercase tracking-wider dashboard-muted">{{ __('Currently contains') }}</div>
                    <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1.5 text-sm">
                        <span class="dashboard-muted">{{ $summary['sites'] }} {{ __('sites') }}</span>
                        <span class="dashboard-muted">{{ $summary['media_assets'] }} {{ __('media files') }}</span>
                        <span class="dashboard-muted">{{ $summary['activity_logs'] }} {{ __('activity entries') }}</span>
                        <span class="dashboard-muted">{{ $summary['api_tokens'] }} {{ __('API tokens') }}</span>
                    </div>
                </div>
            </div>

            <!-- What is excluded -->
            <div class="dashboard-panel p-5 space-y-4">
                <div class="text-xs font-medium uppercase tracking-wider dashboard-muted">{{ __('Deliberately excluded') }}</div>

                <ul class="space-y-2 text-sm dashboard-text">
                    <li class="flex items-start gap-2.5">
                        <flux:icon.eye-slash class="size-4 mt-0.5 dashboard-muted shrink-0" />
                        <span>{{ __('Passwords, two-factor secrets, and recovery codes') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.eye-slash class="size-4 mt-0.5 dashboard-muted shrink-0" />
                        <span>{{ __('Sign-in provider tokens, API token values, site passwords, and webhook secrets') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.eye-slash class="size-4 mt-0.5 dashboard-muted shrink-0" />
                        <span>{{ __('Visitor logs of your sites — these contain personal data of third parties') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Download -->
            <flux:button data-dashboard-variant="primary"
                variant="primary"
                icon="document-arrow-down"
                wire:click="download"
                wire:loading.attr="disabled"
                class="dashboard-control-rounded font-medium"
            >
                {{ __('Download data export (JSON)') }}
            </flux:button>
        </div>
    </x-pages::settings.layout>
</section>
