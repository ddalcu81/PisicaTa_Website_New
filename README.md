# Pisica Ta

Romanian cat-care publication built with Laravel 13 and PHP 8.4, MariaDB 11.8, Vue 3, Inertia 3, TypeScript, and Vite. Public pages use Blade and work with JavaScript disabled. The editorial interface is at `/admin`.

## Run locally on Ubuntu

Install PHP 8.4 with PDO MySQL, DOM, mbstring, fileinfo and the standard Laravel extensions, Composer, Node 24, npm, and MariaDB. The test suite also uses PDO SQLite and GD. Create an empty MariaDB database and account dedicated to this workspace before migrating.

```sh
composer install
npm ci
npm run build
cp .env.example .env
php artisan key:generate
```

Set the database credentials in the local `.env`, then run:

```sh
php artisan migrate
php artisan editorial:import OldVersion/localhost_andreida_pisicata.sql
php artisan editorial:create-admin editor@example.com --name=Editor
php artisan serve
```

Open the displayed URL and sign in at `/admin` with the account created at the prompt. Editors can create and edit articles and categories, change publication status and dates, and upload a cover. A future publication date keeps an article private until that date in Bucharest. Returning a published article to draft removes it from the public site and sitemap.

The importer reads only the article and category rows. It preserves titles, article text, category hierarchy, dates, and slugs, removes invalid NUL characters, and strips inline images, unsafe markup, and unsupported embeds. It keeps the content of existing editorial records across repeated imports. Each imported article has a sourced cover with an attribution. An editor can upload a replacement through the admin. New articles use a generic cover until an editor uploads one. The source archive stays local and is excluded from Git.

## URLs and search

- `/articole.php` is the article index. Article and category pages keep their original slug paths. `/index.php` redirects to `/`.
- `/articol.php?artid=…`, `/articol.php?catart=…`, and known stale article paths redirect permanently to their stored URLs. Where an original slug contains an encoding error, it remains the canonical URL to preserve bookmarks, and its corrected variant redirects to it.
- Published articles have a canonical URL, social metadata, Article and BreadcrumbList structured data, and a generated sitemap. Search at `/cauta` filters published articles across title, summary, and body. Search and editorial pages carry a noindex directive.
- Staging defaults to `SITE_INDEXABLE=false` and `ADSENSE_ENABLED=false`. For a public release, set the correct HTTPS `APP_URL` and enable indexing. Configure publisher and responsive unit IDs only after the site and required consent messaging are ready in the AdSense account. The application then provides `ads.txt` and at most two responsive placements on substantial articles, with the side placement visible only on wider screens. The ads load near the viewport. See [Google's placement guidance](https://support.google.com/adsense/answer/1282097) and [consent management requirements](https://support.google.com/adsense/answer/13554116).

## Checks

```sh
npm run typecheck
npm run build
php vendor/bin/phpunit
```

Tests use a disposable SQLite database. For the isolated Pi target and rollback steps, see [Pi staging](docs/pi-staging.md).
