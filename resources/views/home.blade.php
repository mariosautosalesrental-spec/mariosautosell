<x-layouts.app>
    <section class="hero" id="top" aria-label="Welcome">
        <div class="hero-inner">
            <div class="hero-copy">
                <span class="eyebrow">Used cars · Dracut, MA</span>
                <h1>Quality used cars. Prices that make sense.</h1>
                <p>A local, family-run lot where you get a good car and a straight answer.</p>
                <div><a class="btn btn-accent" id="hCta" href="#inventory">Browse our cars</a></div>
            </div>
            <div class="hero-photo">
                <img id="hImg" src="{{ asset('images/hero.jpg') }}" alt="Aerial view of a used car lot with cars parked around a small shop">
            </div>
        </div>
    </section>

    <livewire:inventory />

    <x-site.trust />
    <x-site.trade-in />
    <x-site.visit />

    <livewire:car-detail />
</x-layouts.app>
