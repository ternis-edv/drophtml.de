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
            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                {{ __('Under the right to data portability (GDPR Art. 20 / DSGVO Art. 20) you can download all personal data we store about your account as a single JSON file.') }}
            </p>

            <!-- What will be exported -->
            <div class="panel p-5 space-y-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Included in your export') }}</div>

                <ul class="space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 text-green-600 dark:text-green-400 shrink-0" />
                        <span>{{ __('Account profile (name, email, sign-in providers, account activity)') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 text-green-600 dark:text-green-400 shrink-0" />
                        <span>{{ __('Sites including domains, deployments, and redirects') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 text-green-600 dark:text-green-400 shrink-0" />
                        <span>{{ __('Uploaded media files') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 text-green-600 dark:text-green-400 shrink-0" />
                        <span>{{ __('Activity log entries (including IP addresses)') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.check class="size-4 mt-0.5 text-green-600 dark:text-green-400 shrink-0" />
                        <span>{{ __('API token metadata (names and abilities, never the token values)') }}</span>
                    </li>
                </ul>

                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Currently contains') }}</div>
                    <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1.5 text-sm">
                        <span class="text-zinc-500">{{ $summary['sites'] }} {{ __('sites') }}</span>
                        <span class="text-zinc-500">{{ $summary['media_assets'] }} {{ __('media files') }}</span>
                        <span class="text-zinc-500">{{ $summary['activity_logs'] }} {{ __('activity entries') }}</span>
                        <span class="text-zinc-500">{{ $summary['api_tokens'] }} {{ __('API tokens') }}</span>
                    </div>
                </div>
            </div>

            <!-- What is excluded -->
            <div class="panel p-5 space-y-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Deliberately excluded') }}</div>

                <ul class="space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <li class="flex items-start gap-2.5">
                        <flux:icon.eye-slash class="size-4 mt-0.5 text-zinc-400 shrink-0" />
                        <span>{{ __('Passwords, two-factor secrets, and recovery codes') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.eye-slash class="size-4 mt-0.5 text-zinc-400 shrink-0" />
                        <span>{{ __('Sign-in provider tokens, API token values, site passwords, and webhook secrets') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <flux:icon.eye-slash class="size-4 mt-0.5 text-zinc-400 shrink-0" />
                        <span>{{ __('Visitor logs of your sites — these contain personal data of third parties') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Download -->
            <flux:button
                variant="primary"
                icon="document-arrow-down"
                wire:click="download"
                wire:loading.attr="disabled"
                class="rounded-xl font-bold"
            >
                {{ __('Download data export (JSON)') }}
            </flux:button>
        </div>
    </x-pages::settings.layout>
</section>
