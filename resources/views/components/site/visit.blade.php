@php($store = \App\Models\Setting::current())

<section class="wrap visit" id="visit" aria-labelledby="visitH">
    <div>
        <h2 id="visitH">Visit the lot</h2>
        <p style="font-size:18px;margin:14px 0 6px">{{ $store->street }}<br>{{ $store->cityLine() }}</p>
        <div class="phone">{{ $store->phone }}</div>
        <dl>
            <dt>Mon–Fri</dt><dd>{{ $store->hours_mon_fri }}</dd>
            <dt>Saturday</dt><dd>{{ $store->hours_sat }}</dd>
            <dt>Sunday</dt><dd>{{ $store->hours_sun }}</dd>
        </dl>
    </div>
    <div class="map">
        <iframe src="{{ $store->mapsEmbedUrl() }}" title="Map showing {{ $store->fullAddress() }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        <a class="map-btn" href="{{ $store->mapsUrl() }}" target="_blank" rel="noopener" aria-label="Open in Google Maps: {{ $store->fullAddress() }}">
            <x-ui.icon name="pin" :size="18" :stroke="2.2" />
            Open in Google Maps
        </a>
    </div>
</section>
