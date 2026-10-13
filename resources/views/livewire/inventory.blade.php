<div>
    <div class="wrap search">
        <form id="searchForm" aria-label="Search inventory"
              x-on:submit.prevent="document.getElementById('inventory').scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })">
            <label for="fMake" class="only-desktop">Make
                <select id="fMake" wire:model.live="make">
                    <option value="">Any make</option>
                    @foreach ($makes as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label for="fBody">Body type
                <select id="fBody" wire:model.live="body">
                    <option value="">All types</option>
                    @foreach ($bodyTypes as $bodyType)
                        <option value="{{ $bodyType->slug }}">{{ $bodyType->name }}</option>
                    @endforeach
                </select>
            </label>
            <label for="fPrice">Max price
                <select id="fPrice" wire:model.live="max">
                    <option value="">No max</option>
                    @foreach ([10000, 15000, 20000, 25000, 30000] as $price)
                        <option value="{{ $price }}">{{ \App\Support\Format::price($price) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn btn-accent only-desktop" type="submit" id="searchBtn">Search</button>
        </form>
    </div>

    <section class="block wrap" aria-labelledby="makesH">
        <div class="section-head"><h2 id="makesH">Shop by make</h2></div>
        <div class="makes" id="makes">
            <button type="button" class="make" wire:click="selectMake('')" aria-pressed="{{ $activeMake === null ? 'true' : 'false' }}">
                All <small>{{ $totalActive }}</small>
            </button>
            @foreach ($makes as $category)
                <button type="button" class="make" wire:key="make-{{ $category->id }}" wire:click="selectMake('{{ $category->slug }}')"
                        aria-pressed="{{ $activeMake?->id === $category->id ? 'true' : 'false' }}">
                    {{ $category->name }} <small>{{ $category->active_cars_count }}</small>
                </button>
            @endforeach
        </div>
    </section>

    <section class="block wrap" id="inventory" aria-labelledby="invH">
        <div class="section-head">
            <h2 id="invH">On the lot</h2>
            <span class="meta" id="count">{{ $total }} {{ $total === 1 ? 'car' : 'cars' }}</span>
        </div>

        @if ($total === 0)
            <div class="empty">
                No cars match these filters yet.
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Message us on WhatsApp</a> and we will look for one.
            </div>
        @else
            <div class="grid" id="grid">
                @foreach ($cars as $car)
                    <x-car-card :car="$car" wire:key="car-{{ $car->id }}" />
                @endforeach
            </div>

            <div class="more" id="more">
                <span class="meta" id="moreText">Showing {{ $shown }} of {{ $total }} cars</span>
                @if ($shown < $total)
                    <button type="button" class="btn btn-ghost" id="moreBtn" wire:click="showMore">Show more cars</button>
                @endif
            </div>
        @endif
    </section>
</div>
