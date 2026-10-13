<?php

namespace Tests\Feature\Site;

use Tests\TestCase;

/**
 * Static checks for the visual rules: light theme only and reduced motion.
 */
class DesignRulesTest extends TestCase
{
    public function test_site_css_respects_reduced_motion(): void
    {
        $css = file_get_contents(resource_path('css/site.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertMatchesRegularExpression('/prefers-reduced-motion: reduce\)\s*\{[^}]*transition: none !important/', $css);
        $this->assertMatchesRegularExpression('/prefers-reduced-motion: reduce\)\s*\{[^}]*animation: none !important/', $css);
    }

    public function test_there_is_no_dark_mode_in_the_public_site(): void
    {
        foreach ([resource_path('css/app.css'), resource_path('css/site.css')] as $file) {
            $this->assertStringNotContainsString('prefers-color-scheme: dark', file_get_contents($file), $file);
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php') && ! str_contains($file->getPathname(), '/filament/')) {
                $this->assertDoesNotMatchRegularExpression('/\bdark:/', file_get_contents($file->getPathname()), $file->getPathname());
            }
        }
    }

    public function test_site_declares_a_light_color_scheme(): void
    {
        $this->assertStringContainsString('color-scheme: light', file_get_contents(resource_path('css/app.css')));
    }

    public function test_every_site_view_uses_real_buttons_and_links_only(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getPathname(), '/filament/')) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression('/<(div|span)[^>]*\s(wire:click|x-on:click|@click)=/', file_get_contents($file->getPathname()), "Clickable non-button in {$file->getPathname()}");
        }
    }
}
