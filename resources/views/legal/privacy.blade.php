@php($store = \App\Models\Setting::current())

<x-legal-page title="Privacy Policy" description="How Mario's Auto Sales & Rentals collects and uses information when you use this website.">
    <p>This policy explains what information Mario's Auto Sales &amp; Rentals ("we", "us") collects when you use this website, and how we use it.</p>
    <h2>Information we collect</h2>
    <ul>
        <li><strong>Messages you send us.</strong> When you tap a WhatsApp button, you leave this site and talk to us on WhatsApp. We see your name, phone number and whatever you write, such as the car you are asking about.</li>
        <li><strong>Basic usage data.</strong> Like most websites, our hosting and analytics tools may record your browser type, device, pages visited and approximate location (city level). This helps us see which cars people look at.</li>
    </ul>
    <h2>How we use it</h2>
    <ul>
        <li>To answer your questions about a car, a trade-in or a visit.</li>
        <li>To improve the website and the cars we show.</li>
    </ul>
    <p>We do not sell your personal information. We only share it with service providers that help us run the site (such as hosting and analytics), or when the law requires it.</p>
    <h2>WhatsApp</h2>
    <p>Conversations on WhatsApp are also covered by WhatsApp's own privacy policy. We only use your number to reply to you, unless you ask us to keep you posted about new cars.</p>
    <h2>Your choices</h2>
    <p>You can ask us to delete your messages or contact details at any time by writing to us on WhatsApp or calling {{ $store->phone }}.</p>
    <h2>Contact</h2>
    <p>Mario's Auto Sales &amp; Rentals, {{ $store->fullAddress() }} · {{ $store->phone }}</p>
</x-legal-page>
