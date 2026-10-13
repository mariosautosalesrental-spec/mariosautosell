@props(['car'])

@php
    $photo = $car->photos->first();
    $alt = trim($car->color.' '.$car->title);
@endphp

<article class="car">
    <a class="car-photo" href="{{ route('cars.show', $car->slug) }}"
       x-on:click.prevent="Livewire.dispatch('open-car', { id: {{ $car->id }} })">
        @if ($photo)
            <img src="{{ $photo->thumbUrl() }}" alt="{{ $alt }}" loading="lazy">
        @else
            <span class="no-photo">No photo yet</span>
            <span class="sr-only">{{ $car->fullName() }}</span>
        @endif
        @if ($car->price_drop)
            <x-badge>Price drop</x-badge>
        @endif
        <span class="peek">View details <x-ui.icon name="arrow-right" :size="16" :stroke="2.5" /></span>
    </a>
    <div class="car-body">
        <h3>{{ $car->title }}</h3>
        @if ($car->trim)
            <span class="meta">{{ $car->trim }}</span>
        @endif
        <ul class="chips">
            @if ($car->mileage !== null)
                <li>{{ \App\Support\Format::miles($car->mileage) }}</li>
            @endif
            <li>{{ $car->transmission->name }}</li>
            <li>{{ $car->drivetrain->name }}</li>
        </ul>
        <div class="price-row">
            <span class="price">{{ \App\Support\Format::price($car->price) }}</span>
            <span class="fees">incl. dealer fees</span>
        </div>
        <x-button variant="wa" :href="$car->whatsappUrl()" target="_blank" rel="noopener">
            <x-ui.icon name="whatsapp" /> Ask about this car
        </x-button>
    </div>
</article>
