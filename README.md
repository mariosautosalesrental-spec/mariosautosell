# Mario's Auto Sales & Rentals

Site with the used-car lot of **Mario's Auto Sales & Rentals** (Dracut, MA) and an admin panel for the store to register cars, mark them as sold and follow monthly sales. The only goal of the public site is to show the cars and send the visitor to the store's WhatsApp.

Stack: Laravel 13 (PHP 8.3+), Livewire 4, Filament 5 (admin), Tailwind CSS 4, Intervention Image 4. No queues or jobs: photo conversions run on upload.

The product spec lives in `.spec/init/` (project description, user stories, database schema, project phases) and the approved mockups in `.spec/init/design/`.

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan make:filament-user      # admin user
php artisan storage:link            # car photos on the local disk
npm run dev                         # in one terminal
php artisan serve                   # in another
```

- Site: http://localhost:8000
- Admin: http://localhost:8000/admin

## Tests

```bash
php artisan test
```

Tests use SQLite in memory and the store timezone `America/New_York`.

## Configuration

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Public URL (used in the sitemap and links) |
| `APP_TIMEZONE` | Store timezone, used for "today" and the dashboard month (default `America/New_York`) |
| `PHOTOS_DISK` | Disk for car photos: `public` locally, `s3` in production |
| `AWS_*` | Credentials, bucket, endpoint and URL for S3 / Cloudflare R2 |
| `CARS_PER_PAGE` | Cars shown at once on the lot (default 12) |

Phone, WhatsApp, address and opening hours are **not** in `.env`: they are edited in the admin under **Store info**.

## Assets from the mockups

`scripts/extract-mockup-assets.py` extracts the logo, hero photo, map preview and demo car photos embedded in the site mockup:

```bash
python3 scripts/extract-mockup-assets.py ".spec/init/design/Mario's Car Dealer.html"
```

The demo photos in `database/seeders/demo-photos/` are samples for local development only. Real photos are uploaded through the admin.

## Accessibility checks

The public pages are audited with [axe-core](https://github.com/dequelabs/axe-core) (WCAG 2.0/2.1 A and AA plus best practices): home, home with the car modal open, a car page, Privacy, Terms, Accessibility and the 404 page, at desktop (1280px) and phone (390px) widths. The last run (October 2026) found no violations and no horizontal scroll on phones.

To repeat it: `npm i --no-save axe-core`, copy `node_modules/axe-core/axe.min.js` to `public/`, open a page in the browser, load that script and run `axe.run()`; delete the copy afterwards. Static design rules (light theme only, reduced motion, real buttons and links) are covered by `tests/Feature/Site/DesignRulesTest.php`.

## Legal texts

Privacy Policy, Terms of Use and Accessibility are drafts in English. The store should have a local attorney review them before the site goes live.
