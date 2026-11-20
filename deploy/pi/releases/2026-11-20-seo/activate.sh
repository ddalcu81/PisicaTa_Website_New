#!/usr/bin/env bash
set -euo pipefail
umask 077
sha=79a778f8512f90d8674ca26d54bd2a3926d1ba05
old=147822f82c185f9a1e12e543918157aaa3f80d51
incoming=/tmp/pisicata-seo-79a778f
release=/srv/pisicata/releases/$sha
backup=/srv/pisicata/backups/seo-79a778f
restore_db=pisicata_seo_restore_79a778f
[[ $(hostname) == RasPi ]]
[[ $(readlink -f /srv/pisicata/current) == /srv/pisicata/releases/$old ]]
[[ ! -e "$release" && ! -e "$backup" ]]
cd "$incoming"
sha256sum --check SHA256SUMS
mkdir -m 700 "$backup"
readlink -f /srv/pisicata/current > "$backup/previous-release"
mariadb-dump --single-transaction --routines --triggers --events pisicata_staging > "$backup/database.sql"
tar -czf "$backup/private-state.tar.gz" -C /srv/pisicata/shared .env storage
sha256sum "$backup/database.sql" "$backup/private-state.tar.gz" > "$backup/SHA256SUMS"
mkdir -m 755 "$release"
tar --no-same-owner --no-same-permissions -xzf release.tar.gz -C "$release"
chown -R root:root "$release"
find "$release" -type d -exec chmod 755 {} +
find "$release" -type f -exec chmod 644 {} +
[[ $(cat "$release/REVISION") == "$sha" ]]
rm -rf "$release/storage"
ln -s /srv/pisicata/shared/storage "$release/storage"
ln -s /srv/pisicata/shared/.env "$release/.env"
chown -R pisicata:pisicata "$release/bootstrap/cache"
sudo -u pisicata php "$release/artisan" config:cache --no-ansi
sudo -u pisicata php "$release/artisan" route:cache --no-ansi
[[ $(mariadb --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$restore_db'") == 0 ]]
mariadb -e "CREATE DATABASE $restore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT SELECT, INSERT, UPDATE, DELETE ON $restore_db.* TO 'pisicata'@'localhost';"
cleanup_restore() {
 mariadb -e "REVOKE SELECT, INSERT, UPDATE, DELETE ON $restore_db.* FROM 'pisicata'@'localhost'; DROP DATABASE $restore_db;"
}
trap cleanup_restore EXIT
mariadb "$restore_db" < "$backup/database.sql"
sudo -u pisicata php /dev/stdin "$release" < verify-mariadb.php
cleanup_restore
trap - EXIT
sudo -u pisicata php "$release/artisan" editorial:revise "$release/resources/editorial/seo-revisions-2026-11.json"
rollback=/srv/pisicata/shared/storage/app/private/seo-79a778f-rollback.json
[[ ! -e "$rollback" ]]
sudo -u pisicata php "$release/artisan" editorial:revise "$release/resources/editorial/seo-revisions-2026-11.json" --apply --backup="$rollback"
rollback_code() {
 ln -s "/srv/pisicata/releases/$old" /srv/pisicata/rollback-79a778f
 mv -Tf /srv/pisicata/rollback-79a778f /srv/pisicata/current
 echo "Activation failed. Code restored to $old; editorial rollback available at $rollback." >&2
}
trap rollback_code ERR
ln -s "$release" /srv/pisicata/next-79a778f
mv -Tf /srv/pisicata/next-79a778f /srv/pisicata/current
ca=/var/lib/caddy/.local/share/caddy/pki/authorities/local/root.crt
curl --fail --silent --show-error --cacert "$ca" https://192.168.1.100:9443/up > /dev/null
curl --fail --silent --show-error --cacert "$ca" https://192.168.1.100:9443/19-locuri-unde-poti-pune-pisica > "$backup/activated-article.html"
grep -q 'dateModified' "$backup/activated-article.html"
trap - ERR
printf 'Activated %s\nBackup: %s\nEditorial rollback: %s\n' "$sha" "$backup" "$rollback"
