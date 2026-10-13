<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $base }}/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach ($cars as $car)
    <url>
        <loc>{{ $base }}/cars/{{ $car->slug }}</loc>
        <lastmod>{{ $car->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach ($pages as $page)
    <url>
        <loc>{{ $base }}/{{ $page }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
@endforeach
</urlset>
