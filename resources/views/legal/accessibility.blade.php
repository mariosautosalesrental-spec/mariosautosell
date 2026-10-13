@php($store = \App\Models\Setting::current())

<x-legal-page title="Accessibility" description="Our accessibility goals for the Mario's Auto Sales & Rentals website and how to ask for help.">
    <p>Mario's Auto Sales &amp; Rentals wants everyone, including people with disabilities, to be able to browse our cars and contact us easily.</p>
    <h2>Our goal</h2>
    <p>We aim to meet the Web Content Accessibility Guidelines (WCAG) 2.1, level AA. That includes text descriptions for photos, readable color contrast, buttons large enough to tap, and a site that works with a keyboard and screen readers.</p>
    <h2>Known limits</h2>
    <p>Some content comes from other services, such as WhatsApp and Google Maps, and may not be fully accessible. We keep improving the site as we find issues.</p>
    <h2>Need help?</h2>
    <p>If any part of this website is hard to use, please let us know and we will help you directly, by phone or in person:</p>
    <ul>
        <li>Phone: {{ $store->phone }}</li>
        <li>Visit: {{ $store->fullAddress() }}</li>
    </ul>
    <p>Please tell us the page and the problem you ran into. We will reply as soon as we can.</p>
</x-legal-page>
