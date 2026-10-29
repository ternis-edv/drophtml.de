@props(['site'])
<x-dashboard.context-menu :label="$site->original_name ?: $site->slug">
    <a role="menuitem" tabindex="-1" href="{{ route('dashboard.sites.edit', $site) }}"><flux:icon.code-bracket />Open Code Studio</a>
    <a role="menuitem" tabindex="-1" href="{{ url('/s/'.$site->slug) }}" target="_blank" rel="noopener"><flux:icon.arrow-top-right-on-square />View live site <span>↗</span></a>
    <button type="button" role="menuitem" data-context-restore tabindex="-1" @click="copyText({{ \Illuminate\Support\Js::from(url('/s/'.$site->slug)) }}, {{ \Illuminate\Support\Js::from($site->id) }})"><flux:icon.link />Copy site URL</button>
    <a role="menuitem" tabindex="-1" href="{{ route('dashboard.sites.media', $site) }}" wire:navigate><flux:icon.photo />Media library</a>
    <a role="menuitem" tabindex="-1" href="{{ route('dashboard.sites.settings', $site) }}" wire:navigate><flux:icon.cog-6-tooth />Site settings</a>
    <a role="menuitem" tabindex="-1" href="{{ route('dashboard.sites.download', $site) }}"><flux:icon.arrow-down-tray />Download ZIP</a>
    <hr role="separator">
    <button type="button" role="menuitem" tabindex="-1" wire:click="duplicateSite('{{ $site->id }}')"><flux:icon.document-duplicate />Duplicate site</button>
    <button type="button" role="menuitem" tabindex="-1" class="dashboard-context-danger" @click="askDelete({{ \Illuminate\Support\Js::from($site->id) }}, {{ \Illuminate\Support\Js::from($site->original_name ?: $site->slug) }})"><flux:icon.trash />Delete site</button>
</x-dashboard.context-menu>
