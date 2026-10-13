<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Home, every car on the site and the legal pages, with absolute URLs
     * built from APP_URL. Hidden, sold and deleted cars are not listed.
     */
    public function sitemap(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $cars = Car::active()->latestFirst()->get(['slug', 'updated_at']);

        $xml = view('sitemap', [
            'base' => $base,
            'cars' => $cars,
            'pages' => ['privacy', 'terms', 'accessibility'],
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $body = "User-agent: *\nAllow: /\nDisallow: /admin\n\nSitemap: {$base}/sitemap.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
