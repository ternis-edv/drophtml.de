@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-sm font-medium flex items-center justify-center gap-2 shadow-xs']) }}>
        <flux:icon.check-circle class="size-4 shrink-0" />
        <span>{{ $status }}</span>
    </div>
@endif
