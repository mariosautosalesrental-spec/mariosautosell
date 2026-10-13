@props(['title'])

<x-layouts.app :title="$title.' | Mario\'s Auto Sales & Rentals'" :description="$description ?? ''">
    <div class="wrap legal-page">
        <a class="back" href="{{ route('home') }}"><x-ui.icon name="chevron-left" :size="18" /> Back to the lot</a>
        <article class="legal-body">
            <span class="eyebrow" style="color:var(--accent)">Mario's Auto Sales &amp; Rentals</span>
            <h1>{{ $title }}</h1>
            <p class="d-note">Last updated: October 12, 2026</p>
            {{ $slot }}
        </article>
    </div>
</x-layouts.app>
