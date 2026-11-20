# Pisica Ta on Raspberry Pi

The proposed test site is `https://192.168.1.100:9443`. It uses the Pi LAN address so it needs no public DNS or Pi-hole edits. The new Caddy site uses the Pi's existing internal certificate authority. Clients need to trust the public root certificate of that authority. Verify the address, free port, SSH host key, and active Caddy and PHP-FPM configuration before activation.

| Resource | Pisica Ta staging |
| --- | --- |
| Releases | `/srv/pisicata/releases/<git-sha>` |
| Active release | `/srv/pisicata/current` |
| Configuration and writable data | `/srv/pisicata/shared/.env` and `/srv/pisicata/shared/storage` |
| Runtime identity and FPM socket | `pisicata`, `/run/php/pisicata.sock` |
| Database | `pisicata_staging`, database identity `pisicata@localhost` |
| Database transport | `/run/mysqld/mysqld.sock` |
| Caddy configuration | Dedicated block supplied in `deploy/pi/pisicata.Caddyfile` |

Confirm the above resources and exact Git revision with the owner. A privileged Pi operator is needed to register the runtime user, MariaDB identity, pool, and Caddy site. Review the existing Pi config and back it up privately before making changes. The only shared service operations in this scope are validated graceful reloads of Caddy and PHP-FPM. Keep the existing LAN sites, pools, and DNS and firewall settings in place.

## Prepare a release on Ubuntu

Use a clean checkout of the approved revision, locked dependencies, and a local build. Install production PHP dependencies in the assembled release directory using `composer install --no-dev --classmap-authoritative`. The release contains the tracked application, PHP dependencies, and the built `public/build` directory. Package only the reviewed release contents and record the SHA-256 checksum. Verify it after transfer.

Export public editorial data separately as a first-release input. The command is read-only with respect to the database and refuses to overwrite its output.

```sh
php artisan editorial:import OldVersion/localhost_andreida_pisicata.sql --export=/tmp/pisicata-editorial.json
```

Review the destination and archive inventory. Keep the original SQL, legacy site files, local QA databases, and environment files outside the release. The temporary JSON contains article and category data and redirects. Put it outside the document root and use it for the initial import.

The first-activation helper takes the release archive, editorial JSON, exact Git SHA, and a checksum file covering the two inputs. Its copy from the selected revision can be run by an authorized Pi operator. It checks for resource collisions, prepares the new project, validates the service configuration, and rolls back the service configuration if its health checks fail.

```sh
sudo bash activate-first.sh pisicata-release.tar.gz pisicata-editorial.json <git-sha> SHA256SUMS
```

After activation, create the editor account from a terminal on the Pi. The command prompts for the password without displaying it.

```sh
cd /srv/pisicata/current
sudo -u pisicata php artisan editorial:create-admin editor@example.com --name=Editor
```

## First activation

1. Create the dedicated unprivileged runtime identity and its project directories. The Caddy account needs read access to the release's `public` directory and connect access to the FPM socket. Only the runtime needs read access to `.env` and write access to shared storage.
2. Create a new MariaDB schema with `utf8mb4_unicode_ci`. With MariaDB's `unix_socket` plugin active, `CREATE USER 'pisicata'@'localhost' IDENTIFIED VIA unix_socket` ties the credential to the dedicated operating-system identity. Give the account grants scoped to `pisicata_staging.*`. Migration privileges are needed for first setup. Runtime operations need only select, insert, update, and delete. Run project Artisan commands as `pisicata`.
3. Extract the verified release into its SHA directory. Link its `.env` to the private shared file and its `storage` to the shared directory after creating the Laravel directory structure there. Configure `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://192.168.1.100:9443`, `DB_CONNECTION=mysql`, `DB_SOCKET=/run/mysqld/mysqld.sock`, `DB_DATABASE=pisicata_staging`, `DB_USERNAME=pisicata`, `SESSION_SECURE_COOKIE=true`, `SITE_INDEXABLE=false`, and `ADSENSE_ENABLED=false`. Generate the staging app key on the Pi. Use the example environment's file-based sessions and cache.
4. Apply the migration, then run `php artisan editorial:import /path/to/pisicata-editorial.json`. Check that the initial import contains 229 articles and 21 categories. Cache the application configuration, routes, and views as that identity. Keep `bootstrap/cache` writable by the runtime.
5. Add the FPM pool and Caddy block. Validate the full PHP-FPM config with `php-fpm8.4 -t` and the complete Caddy config with `caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile`. Select the exact release with an atomic symlink switch at `/srv/pisicata/current`. Gracefully reload the required services. Verify the new certificate with the internal public CA, `/up`, the homepage, article HTML, a legacy redirect, a build asset, and `/admin`. Verify the existing App Efemer staging URL after the reload.

Subsequent releases keep the shared key, storage, and database. Back up the site's private state before changes and verify restoration to a disposable location. Review any new migration separately. If a code release fails, switch `current` back to the last compatible SHA. For the first activation, remove the Pisica Ta site block and pool from the active configurations, validate both configurations, and gracefully reload. Retain the release and project data for diagnosis. A code rollback does not reverse database migrations.

Keep `resolve_root_symlink` enabled in the Pisica Ta FastCGI block. It gives PHP the specific release directory for each request so an atomic switch also changes the path used by PHP's caches.

## SEO activation — 20 November 2026

The active application revision is `33c067091e3db623a0073f9fd95b0660efa3c8b6` at the existing `https://192.168.1.100:9443` target. The initial content release was `79a778f8512f90d8674ca26d54bd2a3926d1ba05`; the final refinement preserves earlier dates for 158 mechanical/copy repairs and gives new dates only to the six rewritten guides. The pre-SEO release `147822f82c185f9a1e12e543918157aaa3f80d51` is retained. No database migration, shared-service reload, DNS, Caddy or PHP-FPM configuration change was needed.

Private pre-change database dumps and shared-state archives are in `/srv/pisicata/backups/seo-79a778f` and `/srv/pisicata/backups/seo-33c0670`. Both database backups were restored into disposable MariaDB databases. The respective editorial batch was applied and rolled back there, with exact comparison of all article fields and timestamps. The disposable databases and temporary grants were removed before activation. Execution scripts and artifact checksums are retained in `deploy/pi/releases/2026-11-20-seo/` as records of these specific releases; their preconditions intentionally prevent re-running them against the current release.

The private manifest `/srv/pisicata/shared/storage/app/private/seo-79a778f-rollback.json` restores the original pre-SEO article text and dates through `editorial:revise`. Preview it first and use a new private `--backup` destination when applying. The command refuses intervening content changes. The `seo-33c0670-rollback.json` file in the same private directory reverses only the date refinement. Restoring a code symlink does not itself restore content. All three release directories remain available for a compatible atomic code rollback.

Verification: nine tests/90 assertions, TypeScript and Vite build passed locally; every article rendered from an isolated repaired SQLite database; the final trusted-HTTPS crawl passed 229 article URLs, 229 cover requests, 21 category pages and 33 permanent redirects. Article HTML, structured data, canonical URLs, staging noindex, the six-card homepage, search, admin sign-in page, sitemap and health endpoints passed. A fresh editorial-only database export matched all expected content, original slugs, publication dates, states, categories and aliases. The admin count remains one. App Efemer's existing `/up` endpoint returned 200. Browser visual QA was unavailable because the browser access host was unavailable; no mobile performance measurement is claimed.
