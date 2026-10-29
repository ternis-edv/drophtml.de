<svg class="error-icon {{ $class ?? '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('sun')
            <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>
            @break
        @case('moon')
            <path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"/>
            @break
        @case('retry')
            <path d="M3 10a9 9 0 1 1 2.6 8.4M3 4v6h6"/>
            @break
        @case('turn')
            <path d="M9 14H5a2 2 0 0 1-2-2V3m12 7 4 4-4 4m4-4H9"/>
            @break
        @case('grid')
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            @break
        @case('upload')
            <path d="M12 16V3m-5 5 5-5 5 5M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>
            @break
        @case('code')
            <path d="m8 7-5 5 5 5m8-10 5 5-5 5m-3-14-2 18"/>
            @break
        @default
            <path d="M7 17 17 7M7 7h10v10"/>
    @endswitch
</svg>
