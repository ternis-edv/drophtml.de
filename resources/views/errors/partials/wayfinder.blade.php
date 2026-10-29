<div class="wayfinder" aria-hidden="true">
                <div class="map-grid"></div>
                <span class="map-coordinate">YOU ARE HERE</span>
                <div class="map-number">{{ $status }}</div>
                <svg class="map-route" viewBox="0 0 460 440" fill="none">
                    <path class="route-shadow" d="M-20 345H100Q125 345 125 320V267Q125 242 150 242H295Q320 242 320 217V135Q320 110 345 110H480"/>
                    <path class="route-line" d="M-20 345H100Q125 345 125 320V267Q125 242 150 242H295Q320 242 320 217V135Q320 110 345 110H480"/>
                    <circle cx="125" cy="295" r="18" class="map-pin-halo"/><circle cx="125" cy="295" r="6" class="map-pin"/>
                    <rect x="300" y="90" width="40" height="40" rx="12" class="destination"/>
                    <path d="m313 110 5 5 9-10" class="destination-check"/>
                </svg>
                <span class="map-tag">{{ $status === 404 ? 'Destination not found' : 'Route interrupted' }}<span>There’s another way forward.</span></span>
                <span class="map-caption">RECALCULATING THE ROUTE @include('errors.partials.icon', ['name' => 'arrow'])</span>
            </div>
