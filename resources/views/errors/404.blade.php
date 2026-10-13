<x-layouts.app title="Page not found | Mario's Auto Sales & Rentals" description="The page may have moved, or the car may have been sold. Take a look at what's on the lot right now.">
    <div class="wrap error-page">
        <span class="eyebrow">Error 404</span>
        <h1>We can't find that page</h1>
        <p>The page may have moved, or the car may have been sold. Take a look at what's on the lot right now.</p>
        <x-button variant="accent" :href="route('home')">Back to the lot</x-button>
    </div>
</x-layouts.app>
