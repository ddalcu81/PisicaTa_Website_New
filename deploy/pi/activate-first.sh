#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

if [[ $# -ne 4 || ${EUID} -ne 0 ]]; then
    printf 'Usage as root: %s RELEASE_TAR EDITORIAL_JSON GIT_SHA SHA256SUMS\n' "$0" >&2
    exit 2
fi

archive=$(realpath "$1")
editorial=$(realpath "$2")
revision=$3
checksums=$(realpath "$4")
root=/srv/pisicata
release="$root/releases/$revision"
caddyfile=/etc/caddy/Caddyfile
site_config=/etc/caddy/pisicata.caddy
pool=/etc/php/8.4/fpm/pool.d/pisicata.conf
ca=/var/lib/caddy/.local/share/caddy/pki/authorities/local/root.crt
url=https://192.168.1.100:9443

[[ $revision =~ ^[0-9a-f]{40}$ ]]
[[ $(hostname -s) == RasPi && $(uname -m) == aarch64 ]]
[[ -f $archive && -f $editorial && -f $checksums && -f $ca ]]
[[ $(dirname "$archive") == "$(dirname "$editorial")" && $(dirname "$archive") == "$(dirname "$checksums")" ]]
(
    cd "$(dirname "$archive")"
    sha256sum --strict --check "$(basename "$checksums")"
)

db_collisions=$(mariadb --batch --skip-column-names --execute="SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'pisicata_staging'; SELECT User FROM mysql.user WHERE User = 'pisicata';")
if [[ -e $root || -e $site_config || -e $pool || -S /run/php/pisicata.sock ]] ||
    getent passwd pisicata >/dev/null || getent group pisicata >/dev/null ||
    [[ -n $(ss -Hltn 'sport = :9443') || -n $db_collisions ]]; then
    printf 'A Pisica Ta resource or staging port already exists. Review it before continuing.\n' >&2
    exit 1
fi

pre_existing_status=$(curl --silent --show-error --fail --max-time 10 --cacert "$ca" \
    --resolve rental.test:8443:192.168.1.100 --output /dev/null --write-out '%{http_code}' https://rental.test:8443/login)
useradd --system --user-group --home-dir "$root" --shell /usr/sbin/nologin pisicata
install -d -m 0755 "$root" "$root/releases"
install -d -m 0755 -o pisicata -g pisicata "$release"
# The archive is read by root and extracted by the project identity.
runuser -u pisicata -- tar --extract --gzip --no-same-owner --no-same-permissions --directory "$release" < "$archive"
[[ $(< "$release/REVISION") == "$revision" ]]
chown -R root:root "$release"
chmod -R a+rX "$release"
chown pisicata:pisicata "$release/bootstrap/cache"

install -d -m 0750 -o pisicata -g pisicata "$root/shared" "$root/shared/storage"
install -d -m 0750 -o pisicata -g pisicata \
    "$root/shared/storage/app/private" "$root/shared/storage/framework/cache/data" \
    "$root/shared/storage/framework/sessions" "$root/shared/storage/framework/views" "$root/shared/storage/logs"
mv "$release/storage" "$release/storage-layout"
ln -s "$root/shared/storage" "$release/storage"
install -m 0640 -o root -g pisicata /dev/null "$root/shared/.env"
{
    printf 'APP_KEY=base64:%s\n' "$(php -r 'echo base64_encode(random_bytes(32));')"
    cat <<CONFIG
APP_NAME="Pisica Ta"
APP_ENV=staging
APP_DEBUG=false
APP_URL=$url
DB_CONNECTION=mysql
DB_SOCKET=/run/mysqld/mysqld.sock
DB_DATABASE=pisicata_staging
DB_USERNAME=pisicata
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
SESSION_DRIVER=file
SESSION_COOKIE=pisicata_session
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=daily
SITE_INDEXABLE=false
ADSENSE_ENABLED=false
CONFIG
} > "$root/shared/.env"
ln -s "$root/shared/.env" "$release/.env"
install -m 0640 -o root -g pisicata "$editorial" "$root/shared/editorial.json"

mariadb <<'SQL'
CREATE DATABASE pisicata_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pisicata'@'localhost' IDENTIFIED VIA unix_socket;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON pisicata_staging.* TO 'pisicata'@'localhost';
SQL

cd "$release"
runuser -u pisicata -- php artisan migrate --force
runuser -u pisicata -- php artisan editorial:import "$root/shared/editorial.json"
runuser -u pisicata -- php artisan config:cache
runuser -u pisicata -- php artisan route:cache
runuser -u pisicata -- php artisan view:cache
mariadb --execute="REVOKE CREATE, ALTER, INDEX, REFERENCES ON pisicata_staging.* FROM 'pisicata'@'localhost';"

backup="$root/backups/first-activation"
install -d -m 0700 "$backup"
cp -a "$caddyfile" "$backup/Caddyfile"
candidate=$(mktemp /etc/caddy/.pisicata.XXXXXX)
cp -a "$caddyfile" "$candidate"
selected=false
reload_started=false
rollback() {
    local result=$?
    if [[ $result -ne 0 ]]; then
        if [[ $selected == true ]]; then
            cp -a "$backup/Caddyfile" "$caddyfile"
        fi
        mv -fT "$pool" "$backup/pisicata.pool.disabled" 2>/dev/null || true
        mv -fT "$site_config" "$backup/pisicata.caddy.disabled" 2>/dev/null || true
        if [[ $reload_started == true ]]; then
            systemctl reload php8.4-fpm || true
            systemctl reload caddy || true
        fi
        printf 'Pi activation failed. Pisica Ta files and database are retained for inspection.\n' >&2
    fi
    unlink "$candidate" 2>/dev/null || true
    exit "$result"
}
trap rollback EXIT
install -m 0644 "$release/deploy/pi/pisicata.fpm.conf" "$pool"
install -m 0644 "$release/deploy/pi/pisicata.Caddyfile" "$site_config"
printf '\nimport %s\n' "$site_config" >> "$candidate"
/usr/sbin/php-fpm8.4 -t
caddy validate --config "$candidate" --adapter caddyfile
ln -s "$release" "$root/.current-new"
mv -T "$root/.current-new" "$root/current"
selected=true
cp -a "$candidate" "$caddyfile"
reload_started=true
systemctl reload php8.4-fpm
systemctl reload caddy

check_url() {
    curl --silent --show-error --fail --max-time 8 --cacert "$ca" "$url$1"
}
healthy=false
for attempt in {1..8}; do
    if check_url /up >/dev/null 2>&1; then
        healthy=true
        break
    fi
    sleep 1
done
[[ $healthy == true ]]
home_html=$(check_url /)
article_html=$(check_url /motan-sau-pisica-cum-determin-sexul-pisicii-mele)
login_html=$(check_url /login)
built_css=$(php -r 'echo json_decode(file_get_contents("public/build/manifest.json"), true)["resources/css/site.css"]["file"];')
[[ $home_html == *'Totul despre'* && $article_html == *'application/ld+json'* && $login_html == *'Login'* ]]
check_url "/build/$built_css" >/dev/null
[[ $(check_url /robots.txt) == *'Disallow: /'* ]]
legacy_status=$(curl --silent --show-error --fail --max-time 8 --cacert "$ca" --output /dev/null --write-out '%{http_code}' "$url/articol.php?artid=188")
[[ $legacy_status == 301 ]]
post_existing_status=$(curl --silent --show-error --fail --max-time 10 --cacert "$ca" \
    --resolve rental.test:8443:192.168.1.100 --output /dev/null --write-out '%{http_code}' https://rental.test:8443/login)
[[ $pre_existing_status == "$post_existing_status" ]]
printf 'Pisica Ta staging active at %s (%s)\n' "$url" "$revision"
