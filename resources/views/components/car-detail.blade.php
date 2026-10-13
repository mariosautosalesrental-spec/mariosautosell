@props(['car', 'modal' => false])

@php
    $alt = trim($car->color.' '.$car->title);
    $photos = $car->photos->map(fn ($photo) => [
        'large' => $photo->largeUrl(),
        'thumb' => $photo->thumbUrl(),
        'alt' => $alt,
    ])->values();
    $warranty = $car->maWarranty();
    $specs = array_filter([
        'Engine' => $car->engine,
        'Transmission' => $car->transmission->name,
        'Drivetrain' => $car->drivetrain->name,
        'Body' => $car->bodyType->name,
        'Color' => $car->color,
    ], fn ($value) => filled($value));
@endphp

<div class="d-grid">
    <div class="d-photo" x-data="{ i: 0, photos: @js($photos) }"
         x-on:keydown.arrow-left="if (photos.length > 1) i = (i - 1 + photos.length) % photos.length"
         x-on:keydown.arrow-right="if (photos.length > 1) i = (i + 1) % photos.length">
        @if ($photos->isNotEmpty())
            <img id="dImg" src="{{ $photos[0]['large'] }}" alt="{{ $alt }}" x-bind:src="photos[i].large" x-bind:alt="photos[i].alt">
            @if ($photos->count() > 1)
                <button type="button" class="d-nav prev" aria-label="Previous photo" x-on:click="i = (i - 1 + photos.length) % photos.length">
                    <x-ui.icon name="chevron-left" />
                </button>
                <button type="button" class="d-nav next" aria-label="Next photo" x-on:click="i = (i + 1) % photos.length">
                    <x-ui.icon name="chevron-right" />
                </button>
                <div class="d-thumbs" role="group" aria-label="Photos">
                    <template x-for="(photo, n) in photos" x-bind:key="n">
                        <button type="button" x-bind:aria-label="'Show photo ' + (n + 1) + ' of ' + photos.length"
                                x-bind:aria-current="n === i ? 'true' : 'false'" x-on:click="i = n">
                            <img x-bind:src="photo.thumb" alt="">
                        </button>
                    </template>
                </div>
            @endif
        @else
            <span class="no-photo">No photo yet</span>
        @endif
    </div>

    <div class="d-body">
        {{-- The car's own page is the main heading of that page; inside the modal it is a section heading. --}}
        <{{ $modal ? 'h3' : 'h1' }} id="dTitle">{{ $car->title }}</{{ $modal ? 'h3' : 'h1' }}>
        <span class="meta" id="dMeta">{{ collect([$car->trim, \App\Support\Format::miles($car->mileage)])->filter()->implode(' · ') }}</span>
        <span class="price" id="dPrice">{{ \App\Support\Format::price($car->price) }}</span>
        <span class="d-note">Includes dealer fees. Plus tax, title &amp; registration.</span>

        <dl class="specs" id="dSpecs">
            @foreach ($specs as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>

        @if (filled($car->description))
            <p class="d-desc">{{ $car->description }}</p>
        @endif

        @if ($warranty)
            <div class="warranty" id="dWarranty">
                <strong>MA used-car warranty:</strong> with {{ number_format($car->mileage) }} miles, this car is covered for {{ $warranty }}.
            </div>
        @endif

        <div class="d-actions">
            <x-button variant="wa" id="dWa" :href="$car->whatsappUrl()" target="_blank" rel="noopener">
                <x-ui.icon name="whatsapp" /> WhatsApp about this car
            </x-button>
        </div>
    </div>
</div>
