<x-layouts.app title="Something went wrong | Mario's Auto Sales & Rentals" description="Please try again in a minute, or message Mario on WhatsApp and we will help you directly.">
    <div class="wrap error-page">
        <span class="eyebrow">Error 500</span>
        <h1>Something went wrong on our side</h1>
        <p>Please try again in a minute, or message Mario on WhatsApp and we will help you directly.</p>
        <x-button variant="accent" :href="route('home')">Back to the lot</x-button>
    </div>
</x-layouts.app>
