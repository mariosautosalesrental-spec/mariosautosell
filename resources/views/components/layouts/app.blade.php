@props([
    'title' => "Used Cars in Dracut, MA | Mario's Auto Sales & Rentals",
    'description' => "Quality used cars at straight prices from a local, family-run lot in Dracut, MA. See what's on the lot and message Mario on WhatsApp.",
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Mario's Auto Sales &amp; Rentals">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:image" content="{{ $image ?? asset('images/logo.png') }}">
    <meta name="twitter:card" content="summary">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <x-site.header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />

    @livewireScripts
</body>
</html>
