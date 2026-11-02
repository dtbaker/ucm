# Local upgrade test

A repeatable test of the whole update path, run entirely on one machine: build a release,
install it in a fresh container, publish a newer version from a local update server, and
check that the installation finds the update, installs it in the right order, migrates its
database, shows the right messages, treats the licence code as optional and never sends it,
swaps the old bundled libraries for the Composer ones, and keeps working when retired plugin
folders are left behind.

It covers all three ways the product gets onto a server, and both ways it gets updated:

| | Method | Where |
| --- | --- | --- |
| Install | Unzip the CodeCanyon zip by hand, then run the setup wizard | Setup step 5 |
| Install | The old one-file `install.php`, which fetches everything from the update server as a zip | [S12](#s12-the-old-one-file-installer) |
| Update | The in-app updater, Settings, then Upgrade | S2 to S8, S13 |
| Update | Copy one plugin folder by hand, then Run Manual Upgrades | [S9](#s9-the-manual-upgrade-path) |
| Update | Unzip a whole new release over the top, then Run Manual Upgrades | [S11](#s11-a-full-manual-unzip-over-the-top), [S13](#s13-upgrading-from-400-the-old-libraries-go-and-vendor-arrives) |

Written for an agent or a person to follow from top to bottom. Every step says what to run
and what you should see. Record what you actually saw in the results log at the end.

> **Status.** Written 2026-10-17 and run from top to bottom in Mode A the same day. Run again
> on 2026-11-02 against the 4.0.1 release zip (PHP 8.4, and the install plus S1 and S2 on
> PHP 8.2), when the licence code became optional, dompdf and PHPMailer moved to Composer and
> four payment gateways were retired. The steps below are the corrected ones, and the
> results of that run are in the [results log](#results-log). Where the product misbehaves,
> the step says what you will see today and points at [Findings](#findings). If a step is
> wrong for you, fix it and add a line to the change log at the bottom.

The scenarios are driven from the host. `scripts/upgrade-test-drive.mjs` clicks through the
client's Upgrade screen with Playwright (`check`, `upgrade`, `manual`, `codes`, `notice`,
`help`, `page`, `crawl`, `pdf`, `mail`), so a run does not need a browser or a person. Its
header lists every command. Set `UT_BASE` to the client it should drive.

## What this covers

| Area | Scenario | Section |
| --- | --- | --- |
| Baseline | A fresh install of the release, with nothing newer on offer | [S1](#s1-baseline-nothing-to-update) |
| Detection | One plugin bumped on the server is offered, with its changelog line | [S2](#s2-one-plugin-bumped) |
| Install | The files arrive byte for byte, the version is recorded, a re-check is clean | [S2](#s2-one-plugin-bumped) |
| Database | A plugin's upgrade SQL runs and changes the schema | [S3](#s3-a-database-migration) |
| New files | A file that is new on the server is created on the client | [S4](#s4-a-new-file-and-a-core-file) |
| Core files | Core files are offered for a newer release with no plugin bump, and ride along with any plugin update | [S4](#s4-a-new-file-and-a-core-file) |
| Order | Offers and installs run in the documented order, including the "behind" rules | [S5](#s5-install-order) |
| Help | Updated bundled help arrives with the help plugin and shows on screen | [S6](#s6-help-documentation) |
| Messages | Server messages, the changelog text and progress text show, escaped | [S7](#s7-messages-on-the-upgrade-page) |
| Licences | The code is optional and never sent: no code, a short code, a wrong code, several codes and the right code all give the same offers | [S8](#s8-licence-codes) |
| Manual path | Files copied by hand, then Run Manual Upgrades | [S9](#s9-the-manual-upgrade-path) |
| Legacy | The old API endpoints still answer without errors | [S10](#s10-legacy-api-endpoints) |
| Manual upgrade | A whole release unzipped over the top keeps its settings and upgrades cleanly | [S11](#s11-a-full-manual-unzip-over-the-top) |
| Old installer | `install.php` fetches the product as a zip and the result starts | [S12](#s12-the-old-one-file-installer) |
| Old libraries | A 4.0.0 install loses its bundled dompdf, PHPMailer and stripe-php, gets `vendor/`, and still makes PDFs and sends email, by the in-app updater and by unzip | [S13](#s13-upgrading-from-400-the-old-libraries-go-and-vendor-arrives) |
| Secret warning | The "Set a private secret" warning shows on an upgraded install, not on a fresh one | [S14](#s14-the-secret-warning) |
| Retired gateways | An upgraded install that keeps the four retired gateway folders still loads every page | [S15](#s15-retired-gateways-left-behind) |

**Known failures, as of 2026-11-02.** See [Findings](#findings) for the detail of each.

- The in-app updater never replaces `includes/config.php`, so an install updated in-app
  keeps the old one, and the secret warning (S14) never shows on it. It shows after a
  full unzip.
- `includes/plugin_paymethod_stripe/pages/stripe_form.php` is left behind by both update
  paths and answers a blank HTTP 500 if its address is opened (S13).
- The `ucm-web` copy of `upgrade.php` still carries its temporary gate, which answers
  "Updates are paused" to every request without the testing code. A 4.0.1 client sends no
  code, so with the gate in place no 4.0.1 client can update (setup step 2, S7, S8).

Fixed since 2026-10-17, and now passing: S12 (the old installer's zip is built from the
release file list and starts), the stale-code problem in the in-app updater (dtbaker/ucm#29),
the three `REFUSED` lines on the core row, a core-only change never being offered, and the
blank HTTP 500 on a fresh unzip with no `config.local.php`.

Out of scope: the live documentation site. The help system's network address is a
constant, so a local run can only test the help that ships inside the download. The
tamper and checksum rules are covered by `tests/unit/UpgradeIntegrityTest.php`, so run
that rather than re-creating it by hand.

## How the pieces fit

In production, every installation posts to `https://api.ultimateclientmanager.com/upgrade.php`.
That file lives in the private `ucm-web` repository, in `api/`. It is a thin wrapper: it
changes directory to `../../demo.ultimateclientmanager.com/public_html/`, boots the UCM
installation it finds there, and runs that installation's own
`includes/plugin_config/upgrade_server.php`. So **the update server is just another UCM
install**, and the version it offers for each plugin is read from that install's files.

The local stack copies that layout exactly:

| Production | Local container | Local path |
| --- | --- | --- |
| `api.ultimateclientmanager.com/public_html` | `ucm-ut-api`, document root | `$UT/server-www/api.ultimateclientmanager.com/public_html` |
| `demo.ultimateclientmanager.com/public_html` | `ucm-ut-api`, beside it | `$UT/server-www/demo.ultimateclientmanager.com/public_html` |
| A customer's installation | `ucm-ut-client` | `$UT/client` |
| A customer using the old installer | `ucm-ut-legacy` (S12 only) | `$UT/legacy` |
| Their database server | `ucm-ut-db` | |

The client is the release as built ("release A"). The server holds the same build with
deliberate edits ("release B"): bumped versions, new files, help changes.

**Two modes.** Mode A uses the real `ucm-web/api` wrapper, including its licence gate.
Mode B is for anyone without access to `ucm-web`: the client posts straight to the server
install's `upgrade_server.php`. Mode B skips the wrapper and the gate, so the licence
scenarios marked "Mode A" do not apply to it. Everything else is the same.

## Rules

- **Never edit the repositories.** Release A and B are copies. The helper refuses a git
  checkout. The local copy of `ucm-web/api` is a copy too.
- **Never talk to production.** Both test containers resolve `ultimateclientmanager.com`,
  `www.` and `api.` to `127.0.0.1`. Check that in setup step 6 before anything else.
- **Never write a real licence code down.** `ucm-web/api/upgrade.php` may carry a gate that
  only serves one testing code, stored as a hash. The local copy has that hash replaced
  with the hash of a throwaway code, and the gate itself is switched off in the copy,
  because a 4.0.1 client sends no code at all. Nothing here needs the real one.
- **Leave the development stack alone.** Different container names, a different network,
  different ports. Nothing here touches `ucm-app`, `ucm-db` or port 8090. Worktree stacks
  on the same machine take ports in the 8090s too, so the setup checks its ports are free
  before it starts anything.
- **A licence failure is not a bug to fix with enforcement.** The product deliberately
  does no licence checking. A code is a thank-you, kept on the site, never sent, and not
  needed for an update. The gate in the API is a temporary switch for testing a release.
  Record what happens; do not add checks.

## Prerequisites

- Docker, with the daemon running.
- PHP 8.2 or newer on the host, plus `composer`, `git`, `rsync`, `curl`, `shasum`, `perl`,
  `zip` and `unzip`.
- Node and the Playwright browsers, for the installer walk (`npm ci && npx playwright install chromium`).
- This repository. For Mode A, a checkout of `ucm-web` beside it.

## Setup

Run every block from the root of this repository. The variables below are used throughout.

```bash
export UCM=$(git rev-parse --show-toplevel)
# Mode A only. Beside the main checkout, which is not $UCM/.. when this is a git worktree.
export UCM_WEB="$(dirname "$(git rev-parse --path-format=absolute --git-common-dir)")/../ucm-web"
export UT="$HOME/ucm-upgrade-test"          # scratch space; deleted at the end
export REF=master                           # the branch or tag to build release A from
export UT_CODE=local-upgrade-test-code      # throwaway licence code, lower case. Only S8 and the 4.0.0 client in S13 use it
export UT_NET=172.31.250.0/24 UT_API_IP=172.31.250.10 UT_CLIENT_IP=172.31.250.20 UT_LEGACY_IP=172.31.250.30
export UT_CLIENT_PORT=8110 UT_API_PORT=8111 UT_LEGACY_PORT=8112 UT_OLD_PORT=8113 UT_82_PORT=8114 UT_MAIL_PORT=8026
export SERVER="$UT/server-www/demo.ultimateclientmanager.com/public_html"
export API="$UT/server-www/api.ultimateclientmanager.com/public_html"
export CLIENT="$UT/client"
export LEGACY="$UT/legacy"
export OLD="$UT/old"                        # S13: a 4.0.0 install
export DOCROOT=/var/www/api.ultimateclientmanager.com/public_html   # Mode A. Mode B: see step 6
export UPGRADE_URL="http://$UT_API_IP/upgrade.php"                    # Mode A. Mode B: see step 7
export RELEASE_ZIP="$UT/src/build/ucm-codecanyon.zip"   # not $ZIP: the zip command reads that as options.
                                                       # To test a zip that is already built, point this at it and skip step 1
export TOOLS="$UCM/scripts/upgrade-test-tools.php"
db() { docker exec -i -e MYSQL_PWD=root ucm-ut-db mysql -uroot "$@"; }   # MYSQL_PWD: no password warning
# What a real host without config.local.php falls back to (includes/config.php outside Docker).
export REAL_HOST="-e UCM_CONFIG_LOCAL=/nonexistent -e UCM_DB_SERVER=localhost -e UCM_DB_USER=username -e UCM_DB_PASS=password -e UCM_DB_NAME=ucm"
# A page that fails here is usually a blank HTTP 500: the code turns error display off.
# This runs index.php in the container with a shutdown hook and prints the last fatal error.
# Only fatal kinds count: error_get_last() also returns warnings that code silenced with @
# (the session file read on a first request is one), which are not why a page failed.
ut_last_error() {   # ut_last_error <container> [-e NAME=value ...]
  local c=$1; shift
  docker exec "$c" sh -c 'printf "%s\n" "<?php register_shutdown_function(function(){ \$e = error_get_last(); if (\$e && in_array(\$e[\"type\"], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR), true)) fwrite(STDERR, \"LAST ERROR: \" . \$e[\"message\"] . \" at \" . \$e[\"file\"] . \":\" . \$e[\"line\"] . \"\\n\"); });" > /tmp/last-error.php'
  docker exec -w /var/www/html "$@" -e REQUEST_URI=/ -e HTTP_HOST=localhost -e SCRIPT_NAME=/index.php "$c" \
    php -d auto_prepend_file=/tmp/last-error.php index.php 2>&1 >/dev/null | grep -m1 'LAST ERROR' || echo 'no PHP error'
}
```

The commands below assume one shell session. If your tool starts a fresh shell for each
command, put the block above (and the helpers from step 8) in a file and source it every
time. The helpers are zsh and bash compatible. Do not set `UT_BASE` globally: it is how
`scripts/upgrade-test-drive.mjs` is told which client to drive, so set it per command.

Never export a variable called `ZIP` or `UNZIP` here. Info-ZIP's `zip` reads `$ZIP` as
default options, so with `ZIP` set to the release, `zip -qr new.zip ...` quietly adds files
to the release zip instead and exits 0.

Check nothing is in the way before starting:

```bash
for p in $UT_CLIENT_PORT $UT_API_PORT $UT_LEGACY_PORT $UT_OLD_PORT $UT_82_PORT $UT_MAIL_PORT; do
  lsof -iTCP:$p -sTCP:LISTEN -n -P >/dev/null 2>&1 && echo "port $p is taken - pick another"
done
docker ps -a --format '{{.Names}}' | grep '^ucm-ut-' && echo "old ucm-ut containers exist - run Teardown first"
docker network inspect ucm-ut >/dev/null 2>&1 && echo "the ucm-ut network exists - run Teardown first"
```

No output means you can go on.

### 1. Build release A

The build uses `git archive`, so it contains only committed files, exactly as a buyer gets
them. Build in a throwaway clone so the working copy is never touched.

```bash
rm -rf "$UT" && mkdir -p "$UT"
git clone --quiet --shared --branch "$REF" "$UCM" "$UT/src"
( cd "$UT/src" && ./scripts/build-release.sh --verify )
```

Expect `OK: ... shaped the way CodeCanyon wants.` (4,353 entries on 2026-10-17, 4,815 on
2026-11-02). The file a buyer downloads is `$RELEASE_ZIP`. The release carries the
Composer libraries in `vendor/` (dompdf, phpmailer, stripe, htmlpurifier) and
`includes/release-files.txt`, the list of every file in it. The update server offers files
from that list, which matters in S4.

### 2. Lay out the two trees, by unzipping the release

This is the full manual install a buyer does: unzip the CodeCanyon download, and upload
the contents of `Main Files` to their web root. Use the zip itself, not the build folder,
so the thing tested is the thing that ships.

```bash
mkdir -p "$UT/unzipped" "$CLIENT" "$SERVER" "$API"
unzip -q "$RELEASE_ZIP" -d "$UT/unzipped"
ls "$UT/unzipped"                                   # Documentation  Licensing  Main Files
rsync -a "$UT/unzipped/Main Files/" "$CLIENT/"
rsync -a "$UT/unzipped/Main Files/" "$SERVER/"
ls "$CLIENT/index.php" "$CLIENT/vendor/autoload.php" "$CLIENT/temp/.htaccess" "$CLIENT/includes/plugin_file/upload/.htaccess"
[ ! -e "$CLIENT/Documentation" ] && [ ! -e "$CLIENT/includes/config.local.php" ] && echo "web root is clean"
```

Expect the three folders at the top of the zip, all four files listed, and "web root is
clean". `Documentation` and `Licensing` are for the buyer to read and stay out of the web
root. The release ships no `config.local.php`; the wizard writes it.

Mode A: copy the API, point its licence gate at the throwaway code, and switch the gate
off.

```bash
rsync -a --delete --exclude php_errorlog --exclude 'latest_updates*.html' "$UCM_WEB/api/" "$API/"
if grep -q 'ucm_testing_code_hash' "$API/upgrade.php"; then
  hash=$(printf %s "$UT_CODE" | shasum -a 256 | cut -d' ' -f1)
  perl -pi -e "s/(\\\$ucm_testing_code_hash\s*=\s*')[0-9a-f]+(')/\${1}$hash\${2}/" "$API/upgrade.php"
  grep -q "$hash" "$API/upgrade.php" && echo "gate now accepts the local code"
  cp "$API/upgrade.php" "$UT/upgrade.php.gated"      # S7 needs the gated copy back for one check
  perl -pi -e 's/if \( ! \$ucm_testing_allowed \) \{/if ( false ) { \/\/ UT: gate disabled in local copy/' "$API/upgrade.php"
  grep -c 'UT: gate disabled' "$API/upgrade.php"    # 1
else
  echo "no licence gate in this copy of upgrade.php - every request is served"
fi
```

Write down which of those you got. With the gate present, a 4.0.1 client is paused whatever
you do, because it sends no code (see [Findings](#findings)); a copy of the gated file is
kept as `$UT/upgrade.php.gated` for the one check in S7 that wants it. The
`latest_updates*.html` files are a six-hour cache, so they are left out to force a fresh
render in S10.

Mode B: skip the block above. `$API` stays empty.

### 3. Build the image

The repository's image has no `zip` extension, so it cannot build the zip the old
installer asks for, or unpack one. Shared hosts almost always have it, so add it:

```bash
docker build -t ucm-ut-php-base "$UCM"
docker build -t ucm-ut-php --build-arg BASE=ucm-ut-php-base - <<'EOF'
ARG BASE
FROM ${BASE}
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev \
 && rm -rf /var/lib/apt/lists/* && docker-php-ext-install zip
EOF
docker run --rm ucm-ut-php php -r 'echo PHP_VERSION, class_exists("ZipArchive") ? " zip ok\n" : " NO ZIP\n";'
```

The Dockerfile has an `ARG PHP_VERSION` line (default 8.4), so the lowest supported PHP
builds the same way. Give the images other tags so both can exist:

```bash
docker build -t ucm-ut-php-base82 --build-arg PHP_VERSION=8.2 "$UCM"
docker build -t ucm-ut-php82 --build-arg BASE=ucm-ut-php-base82 - <<'EOF'
ARG BASE
FROM ${BASE}
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev \
 && rm -rf /var/lib/apt/lists/* && docker-php-ext-install zip
EOF
docker run --rm ucm-ut-php82 php -r 'echo PHP_VERSION, "\n";'      # 8.2.x
```

Run the whole procedure on the highest PHP, and the install and S1 to S2 on 8.2 (S16
below does the second).

### 4. Start the database

```bash
docker network create --subnet "$UT_NET" ucm-ut
docker run -d --name ucm-ut-db --network ucm-ut --network-alias db \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=ucm_install_test \
  -e MYSQL_USER=ucm -e MYSQL_PASSWORD=ucm \
  mysql:8.0 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
for i in $(seq 1 60); do db -h127.0.0.1 -e 'SELECT 1' >/dev/null 2>&1 && break; sleep 2; done; db -h127.0.0.1 -e 'SELECT VERSION()'
```

The alias `db` and the database `ucm_install_test` match what `tests/e2e/install.spec.ts`
types into the installer, so that spec can walk the wizard unchanged. The wait goes over
TCP on purpose: on a new volume the image first runs a temporary server that only listens
on the socket, then restarts, so a socket query can succeed too early.

Start a mailhog beside it. Nothing here sends mail anywhere else, and the S13 checks read
what the client sent back from its API. Port 8026 on the host, because the development
stack's own mailhog is on 8025:

```bash
docker run -d --name ucm-ut-mail --network ucm-ut -p "$UT_MAIL_PORT:8025" mailhog/mailhog
curl -s "http://localhost:$UT_MAIL_PORT/api/v2/messages" | head -c 60      # {"total":0,...
```

The container has no `--add-host` lines and nothing outside the test network is told to
use it. The client reaches it as `ucm-ut-mail:1025`.

### 5. Start the client and install release A

```bash
docker run -d --name ucm-ut-client --network ucm-ut --ip "$UT_CLIENT_IP" -p "$UT_CLIENT_PORT:80" \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT=/var/www/html \
  -e UCM_DB_NAME=ucm_install_test \
  -v "$CLIENT:/var/www/html" ucm-ut-php
for i in $(seq 1 30); do curl -fsS -o /dev/null "http://localhost:$UT_CLIENT_PORT/" 2>/dev/null && break; sleep 1; done
curl -sL "http://localhost:$UT_CLIENT_PORT/" | grep -o 'Step #1[^<]*'
CI=1 UCM_INSTALL_BASE_URL="http://localhost:$UT_CLIENT_PORT" npx playwright test tests/e2e/install.spec.ts \
  --workers=1 --retries=0 --reporter=list > "$UT/install.log" 2>&1; echo "exit $?"; tail -3 "$UT/install.log"
```

Expect `Step #1: Database Connection Details`, then `exit 0` **and** `2 passed`. `exit 0`
alone proves nothing: when the spec cannot see an un-installed app it skips both tests and
still exits 0. If it says `skipped`, the wizard was already walked or the page is not
answering; restart from step 4 with a fresh database. The install writes
`$CLIENT/includes/config.local.php`.

Why the flags. `CI=1` drops the Playwright config's `webServer` check, which otherwise
waits for the development app on :8090 and fails if it is not running. `--retries=0`
undoes the two retries `CI` turns on, because the wizard can only be walked once.
`--reporter=list` replaces the HTML reporter, which on a failure starts a web server and
waits for Ctrl+C.

There is no `UCM_SECRET` on the container on purpose. The wizard writes a random
`_UCM_SECRET` into `includes/config.local.php`, and S14 checks that a fresh install made
this way shows no secret warning. An environment secret would hide that.

Why `UCM_DB_NAME`. Before the wizard has written `config.local.php`, every page connects
with the fallback details in `includes/config.php` (in Docker: host `db`, user `ucm`,
database `ucm`). There is no `ucm` database here, so the variable points the client at the
one that exists. `docker-compose.yml` sets the same variable for its install-test app. A
real host has no such variable, so ask for the first page with a real host's fallback
values and no `config.local.php`:

```bash
ut_last_error ucm-ut-client ${=REAL_HOST}     # zsh. In bash: $REAL_HOST, unquoted
docker exec -w /var/www/html ${=REAL_HOST} -e REQUEST_URI=/ -e HTTP_HOST=localhost -e SCRIPT_NAME=/index.php \
  ucm-ut-client php index.php 2>&1 | sed 's/<[^>]*>//g' | grep -v '^\s*$' | head -3
```

Expect `no PHP error` and `Hello, Welcome to the setup wizard. You are currently on step 0 of
5.` On 2026-10-17 this was `Uncaught mysqli_sql_exception: No such file or directory in
.../includes/plugin_db/db.php:257`, a blank HTTP 500 (fixed). It works before or after the
wizard, because `UCM_CONFIG_LOCAL` points the code away from the real `config.local.php`.
In zsh a bare `$REAL_HOST` is passed as one argument and the check quietly tests nothing,
so use `${=REAL_HOST}` there.

### 6. Start the update server

The server install needs a database with the schema in it. A copy of the client's is
enough, because it only reads configuration; the versions it offers come from its files.

```bash
db -e "CREATE DATABASE ucm_ut_server; GRANT ALL ON ucm_ut_server.* TO 'ucm'@'%';"
docker exec ucm-ut-db sh -c 'mysqldump -uroot -proot ucm_install_test | mysql -uroot -proot ucm_ut_server'
```

Mode A, document root at the API:

```bash
DOCROOT=/var/www/api.ultimateclientmanager.com/public_html
```

Mode B, document root at the server install, with the server switched on:

```bash
DOCROOT=/var/www/demo.ultimateclientmanager.com/public_html
db ucm_ut_server -e "REPLACE INTO ucm_config (\`key\`,\`val\`) VALUES ('upgrade_server_enabled','1');"
```

Then, either mode:

```bash
docker run -d --name ucm-ut-api --network ucm-ut --ip "$UT_API_IP" -p "$UT_API_PORT:80" \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT="$DOCROOT" \
  -e UCM_DB_SERVER=db -e UCM_DB_NAME=ucm_ut_server -e UCM_DB_USER=ucm -e UCM_DB_PASS=ucm \
  -e UCM_SECRET=local-upgrade-test-server \
  -v "$UT/server-www:/var/www" ucm-ut-php
for c in ucm-ut-client ucm-ut-api; do docker exec "$c" getent hosts api.ultimateclientmanager.com; done
```

**Stop if either `getent` line shows anything but `127.0.0.1`.** That is the guard that
keeps this run away from production.

The server is reached by its private IP on purpose. The client only accepts an update URL
on the official hosts, a loopback address, a `*.localhost` name or a private IP
(`module_config::upgrade_url_is_allowed()`). A Docker container name such as `ucm-ut-api`
is refused with "Incorrect API url".

### 7. Point the client at the local server

```bash
case "$DOCROOT" in
  *api.ultimateclientmanager.com*) UPGRADE_URL="http://$UT_API_IP/upgrade.php" ;;
  *) UPGRADE_URL="http://$UT_API_IP/includes/plugin_config/upgrade_server.php" ;;
esac
db ucm_install_test -e "REPLACE INTO ucm_config (\`key\`,\`val\`) VALUES
  ('ucm_upgrade_url','$UPGRADE_URL'),
  ('help_allow_remote','0');"
```

`help_allow_remote` is off so the help panel shows the bundled article rather than trying
the live site first. Configuration is read from the database on every request, so the
change applies straight away. The licence code is not set: it is optional, and S8 checks
that nothing changes when one is typed. Confirm on the client: Settings, then Upgrade. The
licence box is empty, the page says the code is optional, is not sent anywhere and is not
needed for updates, and there is no "ucm_upgrade_url setting is incorrect" warning.

### 8. Snapshot the clean state

Every scenario starts from here, so they do not depend on each other.

```bash
mkdir -p "$UT/baseline"
rsync -a --delete "$CLIENT/" "$UT/baseline/client/"
docker exec ucm-ut-db mysqldump -uroot -proot ucm_install_test > "$UT/baseline/client.sql"
```

Define the helpers used below:

```bash
ut_reset() {   # client back to the snapshot, server back to release A
  rsync -a --delete "$UT/baseline/client/" "$CLIENT/"
  db ucm_install_test < "$UT/baseline/client.sql"
  rsync -a --delete "$UT/unzipped/Main Files/" "$SERVER/"
  docker exec ucm-ut-api rm -rf /tmp/ucm-upgrade-rate
  sleep 3   # opcache revalidates every 2 seconds
}
ut_offer() {   # what the server offers the client right now, in install order. Sends no licence code, as the client does; ut_offer <code> sends that one
  php "$TOOLS" plugins-json "$CLIENT" > "$UT/client-plugins.json"
  curl -s -X POST "http://localhost:$UT_API_PORT${UPGRADE_URL#http://$UT_API_IP}" \
    ${1:+--data-urlencode "installation_code=$1"} \
    --data-urlencode "release_version=${UT_RELEASE-$(php "$TOOLS" release "$CLIENT")}" \
    --data-urlencode "client_protocol=2" \
    --data-urlencode "libraries=$(shasum -a 256 "$CLIENT/vendor/composer/installed.json" 2>/dev/null | cut -d' ' -f1)" \
    --data-urlencode "current_plugins@$UT/client-plugins.json" | tee "$UT/offer.json" | php "$TOOLS" summarise
}
ut_version() { db ucm_install_test -N -e "SELECT val FROM ucm_config WHERE \`key\`='_plugin_version_$1'"; }
```

`ut_offer` asks the server the same question the client asks, from the host, so you can
see the offer without the browser. It sends no licence code, because the client does not.
**After you edit anything in `$SERVER`, wait 3 seconds before `ut_offer` or a page check**:
the server's opcache revalidates every 2 seconds, and an offer asked for too soon is the old
one (it showed `offers: 0` right after `bump-release` on 2026-11-02). It sends the client's release version as the client
does; `UT_RELEASE= ut_offer` sends none, which is how a 3.x client asks. The upgrade page is still the real test: always check
the page as well. When the server has nothing to offer it sends an empty answer, not an
empty list (`upgrade_server.php` does this on purpose, as the old server did), and
`ut_offer` prints that as `offers: 0`.

To drive the client without a browser use `node scripts/upgrade-test-drive.mjs <command>`
from the repository root (`UT_BASE` defaults to the client on :8110; every command logs in
as the seeded admin first). `check` is Check for Upgrades, `upgrade` is check plus Start the
Upgrade and waits for every row (it prints each row's progress text), `manual` is Run Manual
Upgrades. The steps below say "Upgrade on the client" for either a person or that script.

To drive the client in a browser, log in at <http://localhost:8110> (`$UT_CLIENT_PORT`) as
`admin@example.com` / `password` (the installer's seeded account). Logging in with that
default password lands on the user's own page, which asks for a new password; ignore it.
The upgrade screen is `/?m[]=config&p[]=config_admin&m[]=config&p[]=config_upgrade`
(Settings, then Upgrade), then **Check for Upgrades**.

`ut_reset` puts back `temp/`, where the sessions live, so **every `ut_reset` logs the
browser out**. Log in again before each scenario's page checks. (The script logs in every
time, so it does not care.)

Driving the page from your own script: the rows of the offer are `tr[data-plugin]`, the
button is `#upgrade_start`, and the run has finished when `ucm.upgrade.all_done` is true.
That is all `upgrade-test-drive.mjs` does.

## Scenarios

Run `ut_reset` before each one. Each scenario lists what to change on the server, what to
do, and what you should see.

### S1. Baseline: nothing to update

No changes.

1. `ut_offer`
2. On the client, Check for Upgrades.

Expect `offers: 0`, and the page says "No upgrade available at this time." with no table.
A client identical to its server has nothing to install.

### S2. One plugin bumped

```bash
php "$TOOLS" bump "$SERVER" faq "local upgrade test marker"
```

1. `ut_offer`. Expect, in this order: `corefiles`, then `database`, then `faq`. The `faq`
   line reads `2.151 -> 2.152` (or whatever the current version is, plus 0.001), with the
   description `Update to plugin: faq ( local upgrade test marker (on <today>))`. The
   `corefiles` row comes with every plugin update, showing `4.0.1 -> 4.0.1` (23 files); the
   `database` row has 1 file when the client's `vendor/` matches the server's.
2. On the client, Check for Upgrades. The table shows the same three rows, all ticked. The
   description column shows the changelog text. Versions are numbers, so trailing zeros go:
   a plugin at `2.150` would show as `2.15`.
3. Start the Upgrade, and wait until every row has its result: a list of files, then for
   `faq` the line `Processing update: faq - Current Version: ... all good` and
   "Success! Everything worked." There are no `REFUSED` lines on the core row any more (on
   2026-10-17 there were three). The script reported the button as "Completed, click here to
   continue" after a 0.3 second run on 2026-11-02; the 4 second rule in
   [Findings](#findings) may still apply to a person watching a slower page.

4. Check the files arrived exactly:

   ```bash
   diff -r "$SERVER/includes/plugin_faq" "$CLIENT/includes/plugin_faq" && echo identical
   ```

5. Check the version was recorded: `ut_version faq` should print the new version, and the
   `Processing update` line from step 3 should read `Current Version: 2.152` followed by
   `Upgrading plugin from version 2.151 to 2.152`.
   If it shows the old version, the database step ran against the old copy of `faq.php`
   (the stale code finding, see [Findings](#findings), fixed by dtbaker/ucm#29). On a build
   with the fix the line then ends `not finished: ... still running the old copy` and
   "Some things failed", not "Success!". On a build without it, the page says "Success!"
   anyway and records nothing. Either way, record it, then run **Run Manual Upgrades**,
   which reads `Upgrading plugin from version 2.151 to 2.152` and records it.
6. `ut_offer` again. Expect `offers: 0`. Check for Upgrades on the page shows nothing.
   (Both are read from the files, so they are clean even when step 5 was not.)

### S3. A database migration

```bash
php "$TOOLS" bump "$SERVER" faq "adds a column"
php "$TOOLS" add-column "$SERVER" faq faq ut_marker
```

`add-column` adds an `ALTER TABLE` to what `module_faq::get_upgrade_sql()` returns, so the
column goes in through the real upgrade path.

1. Check the column is not there yet: `db ucm_install_test -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_marker'"` prints nothing.
2. Upgrade on the client, as in S2.
3. Passing: the same query now prints the `ut_marker` row, and the progress text shows
   `Processing update: faq - Current Version: 2.152.... all good`, then
   `Upgrading plugin from version 2.151 to 2.152`, ending in "Success! Everything worked."

   Before dtbaker/ucm#29 this usually failed without saying so: the line read the old
   version, there was no `Upgrading plugin` line, the column was not there, and the page
   still said "Success! Everything worked." That is the stale code finding. With the fix it
   passes with the default opcache settings (4 runs out of 4 on 2026-10-17, and again on
   2026-11-02). If you see it fail, record it.

   To prove the cause of a failure, run the scenario once more with PHP's opcache checking
   files on every request, then put the setting back:

   ```bash
   docker exec ucm-ut-client sh -c 'echo opcache.revalidate_freq=0 > /usr/local/etc/php/conf.d/zz-ut.ini && apache2ctl graceful'
   # ut_reset, the two commands above, log in, upgrade: now it passes
   docker exec ucm-ut-client sh -c 'rm /usr/local/etc/php/conf.d/zz-ut.ini && apache2ctl graceful'
   ```

4. Run Settings, then Upgrade, then **Run Manual Upgrades**. It must report `all good`
   for every plugin with no `failed`, ending in "Success! Everything worked.", and the
   column must now exist. If step 3 failed, the faq line also says
   `Upgrading plugin from version 2.151 to 2.152`, which is the migration running now.
   Run it a second time: `all good` again, because the migration checks before it alters.
   The manual page's lines start `Checking plugin:`, not `Processing update:`. A re-run
   also prints `Upgrading plugin from version 2.188 to 2.188` for `data`, `invoice` and
   `paymethod_stripe`, whose upgrade step has work to do every time, and the `note` line
   carries a stray `Array ( )` (both cosmetic, both seen on 2026-11-02).

### S4. A new file and a core file

**Core file alone.** Change a core file, bump nothing:

```bash
printf '\n/* local upgrade test: core file marker */\n' >> "$SERVER/css/styles.css"
```

1. `ut_offer`. Expect `offers: 0`, and Check for Upgrades on the page says "No upgrade
   available at this time." Nothing compares file contents: with the same release and the
   same plugin versions there is nothing to offer.

**Then with a release bump:**

```bash
php "$TOOLS" bump-release "$SERVER" "core file alone"; sleep 3
```

2. `ut_offer`. Expect `corefiles`, then `database`, and no plugin. The `corefiles` line
   reads `4.0.1 -> 4.0.2` (the client's release, then the server's) with the description
   `Core application files ( 4.0.2: core file alone (on <today>))`. The server offers the
   core rows whenever its `_SCRIPT_VERSION` is newer than the release the client sends
   (`ucm_upgrade_server::offers_core_files()`).
3. `UT_RELEASE= ut_offer`. Expect `offers: 0`: a 3.x client sends no release, and gets
   exactly the answer it always got, core files only with a plugin update.
4. Upgrade on the client. Expect the marker at the end of `"$CLIENT/css/styles.css"`, and
   `php "$TOOLS" release "$CLIENT"` prints `4.0.2`. The footer shows the new release.
5. `ut_offer` again. Expect `offers: 0`: the client is on the server's release now.

Run `ut_reset` before the next part.

**A plugin bump and a new file, same release:**

```bash
printf '\n/* local upgrade test: core file marker */\n' >> "$SERVER/css/styles.css"
php "$TOOLS" bump "$SERVER" faq "new file and core file"
printf 'local upgrade test\n' > "$SERVER/includes/plugin_faq/ut-new-file.txt"
echo includes/plugin_faq/ut-new-file.txt >> "$SERVER/includes/release-files.txt"; sleep 3
```

The last `echo` is needed since 2026-11-02: the server offers files from the release's own
file list, `includes/release-files.txt`, when the tree has one (which a built release
always does), and a file that is not on it is never offered. Without that line step 8 finds
no `ut-new-file.txt` and the page lists nine faq files instead of ten.

6. `ut_offer`. `corefiles` is offered. In `$UT/offer.json`, check that its `files` list
   includes `css/styles.css`, and that the `faq` file list includes `ut-new-file.txt`.
7. Upgrade on the client.
8. Expect both on the client:

   ```bash
   cat "$CLIENT/includes/plugin_faq/ut-new-file.txt"
   tail -2 "$CLIENT/css/styles.css"
   ```

9. The progress text lists `File: includes/plugin_faq/ut-new-file.txt` with "this file has
   been installed successfully". The client prints those words for every file it writes,
   new or not, so step 8 is the real proof.

### S5. Install order

The rules, from `upgrade_server.php`: offers are sorted by plugin name. If the client is
behind on `db` (below 2.13) or `language` (below 2.179), then `db`, `language` and `session`
are moved to the front and `check_again` is set. If it is behind on `backup` (below 2.111)
or `config` (below 2.385), only `config`, `backup`, `core`, `theme`, `form` and `db` stay
ticked; everything else is offered unticked. `corefiles` and `database` always come first
and stay ticked, and `config`, which holds the updater, always comes last
(`ucm_upgrade_server::install_order()`). The page works through the ticked rows top to bottom; for each one it
writes the files and then runs that plugin's database step, before moving to the next row.

**Name order.**

```bash
for p in theme help faq; do php "$TOOLS" bump "$SERVER" $p "order check"; done
```

1. `ut_offer`. Expect `corefiles`, `database`, `faq`, `help`, `theme`.
2. Upgrade on the client. The "Processing update" lines appear in that same order. Record
   the `Current Version` each one shows: each should be the new version. Before
   dtbaker/ucm#29 some were the old one (the stale code finding; on 2026-10-17 `faq` and
   `theme` were right and `help` was not); with it, 6 of 6 were right over two runs, and 3
   of 3 on 2026-11-02. (`theme` is 945 files and takes about three seconds.)

**A client behind on db.** Make the client look old, then bump something else:

```bash
php "$TOOLS" set-version "$CLIENT" db 2.12
php "$TOOLS" bump "$SERVER" faq "behind on db"
```

3. `ut_offer`. Expect `corefiles`, `database`, then `db`, then `faq`, and `check_again: yes`.
   (Checked against the real server code on 2026-10-17 and again on 2026-11-02: exactly this.)
4. Upgrade on the client. `db` installs first. The page does **not** ask you to check
   again: the client never reads `check_again` (see [Findings](#findings)). Check for
   Upgrades yourself; it offers nothing more.

**A client behind on config.**

```bash
ut_reset
php "$TOOLS" set-version "$CLIENT" config 2.384
for p in config faq; do php "$TOOLS" bump "$SERVER" $p "behind on config"; done
```

5. `ut_offer`. Expect `corefiles`, `database`, `faq` unticked (`recommended=n force=n`),
   then `config` ticked (`recommended=y`) last, with `check_again: yes`.
6. On the page, only `config`, `corefiles` and `database` are ticked. Upgrade with the
   defaults, then Check for Upgrades again. Now `faq` is offered ticked (with `corefiles` and
   `database` again, as they ride along with any plugin).

### S6. Help documentation

The help panel reads each article from the documentation site. What ships with the app is
the index of articles (`includes/plugin_help/content/_index.json`, in the help plugin) and
one screen map per plugin (`includes/plugin_<name>/<name>.help.json`), which says which
article each screen opens. Each reaches an install only with its own plugin's update.

In these containers the documentation site is unreachable (setup step 6) and remote help is
off (setup step 7), so the panel shows what an install without internet sees: the title and
summary from the index, and a link to read the article online.

**An index edit without a bump.**

```bash
php "$TOOLS" help-marker "$SERVER" using/faq "LOCAL-UPGRADE-TEST summary marker"
```

1. `ut_offer`. Expect `offers: 0`. The edit alone is not offered.

**With a bump.**

```bash
php "$TOOLS" bump "$SERVER" help "help index update"
```

2. `ut_offer`. `help` is offered.
3. Before upgrading, open the FAQ screen on the client (`/?m[]=faq&p[]=faq_questions`) and
   open the help panel with the **?** button at the top right (`#header_help`). It shows the
   heading "FAQ", its one-line summary without the marker, and "Read this on the
   documentation site". (`node scripts/upgrade-test-drive.mjs help '/?m[]=faq&p[]=faq_questions'`
   prints the heading and the summary.)
4. Upgrade on the client.
5. Open the same screen's help again. The summary now starts with
   "LOCAL-UPGRADE-TEST summary marker". Also check the file:
   `grep -c LOCAL-UPGRADE-TEST "$CLIENT/includes/plugin_help/content/_index.json"` prints `1`.

**A screen map change.** `ut_reset` first.

```bash
php "$TOOLS" help-map "$SERVER" faq faq-faq_questions install/installing
php "$TOOLS" bump "$SERVER" faq "screen map update"
```

6. `ut_offer`. `faq` is offered, `help` is not.
7. Upgrade on the client. The FAQ screen's help now opens "Installing" instead of "FAQ".
8. Open the help on the Upgrade screen itself and record which article it shows. Today it
   is "The plugin system" (`developers/plugins`), not "Upgrading from 2.x"
   (`install/upgrading`). `config.help.json` is generated from the documentation site, and
   in `ucm-web` the frontmatter of `site/src/content/docs/developers/plugins.md` lists
   `config-config_upgrade`; moving that line to `install/upgrading.md` and regenerating
   should fix it.

### S7. Messages on the upgrade page

1. **The changelog text.** Seen in S2: the description column carries the text from the
   `//<version> - <date> - <text>` comment in the plugin file.
2. **Progress and completion.** Seen in S2 to S4: per-file "installed successfully",
   per-plugin "all good", and "Success! Everything worked."
3. **A server message, escaped.** Mode A only. The only message the local API can show is
   the gate's paused message, so this check puts the gated copy back for a moment and gives
   its message markup. The client sends no code, so it is paused whatever is in its box:

   ```bash
   cp "$UT/upgrade.php.gated" "$API/upgrade.php"
   perl -pi -e "s/'Updates are paused for a short while/'<b>UT-ESCAPE<\/b> & <i>check<\/i> Updates are paused for a short while/" "$API/upgrade.php"
   sleep 3
   php "$TOOLS" bump "$SERVER" faq "gated"; sleep 3
   node scripts/upgrade-test-drive.mjs check
   ```

   On the client (Settings, Upgrade), Check for Upgrades. The message shows with the tags as
   visible text (`<b>UT-ESCAPE</b> & <i>check</i> Updates are paused ...`), not as bold and
   italic, followed by "No upgrade available at this time." In the page source the tags are
   `&lt;b&gt;`, and no `<b>` or `<i>` element is inside the content area. Then put the
   ungated copy back by re-running setup step 2's API copy and gate commands.

   This also shows the problem with the gate in `ucm-web`: `ut_offer` with no code gets the
   same paused answer, because a 4.0.1 client has no code to send.
4. **Rate limiting.** The server allows 600 requests per address per 10 minutes.

   ```bash
   ut_reset
   for i in $(seq 1 1300); do out=$(ut_offer); case "$out" in *"Too many"*) echo "limited after $i"; echo "$out" | sed -n 2p; break;; esac; done
   ```

   The loop takes about three minutes. Expect
   `message: Too many update requests from this address. Please try again shortly.`
   Count in windows, not a straight 601: the counter is a file per address per fixed ten
   minute window, so a run that crosses a window boundary needs more requests (on
   2026-11-02 the first 601 did not trip it, because the count had split 580 and 22 over two
   windows, and it took 579 more).
   Then Check for Upgrades on the client: it is a different address (the client's private
   IP), so it is still served, and `docker logs ucm-ut-api` shows a `200` for
   `172.31.250.20` next to the `429`s for the host. Clear the counter with `ut_reset`.

### S8. Licence codes

Since 4.0.1 the licence code is optional. It is kept in the `_installation_code` setting as a
record of a purchase, it is not sent anywhere, and an update does not need it. So every row
below must give the **same offers**, and the request that reaches the server must carry no
code, no `client_ip` and no `installation_location`.

Turn on a request log in the local API copy first, so the last point is proved from the
server's side and not just from reading the client code:

```bash
perl -pi -e 's/^(ini_set\( .display_errors., false \);)/$1\nfile_put_contents( "\/tmp\/ut-post.log", json_encode( array( "keys" => array_keys( \$_REQUEST ), "code" => isset( \$_REQUEST["installation_code"] ) ? \$_REQUEST["installation_code"] : null ) ) . "\\n", FILE_APPEND ); \/\/ UT: request log/' "$API/upgrade.php"
php -l "$API/upgrade.php"; docker exec ucm-ut-api rm -f /tmp/ut-post.log
```

Bump something so there is an update to withhold, and take a fingerprint of the client so you
can prove nothing was written:

```bash
ut_reset; php "$TOOLS" bump "$SERVER" faq "licence check"; sleep 3
fingerprint() { ( cd "$CLIENT" && find . -path ./temp -prune -o -type f -print0 | sort -z | xargs -0 shasum ) | shasum; }
before=$(fingerprint)
```

Each check is `node scripts/upgrade-test-drive.mjs codes <code> [<code> ...]`, which types the
code or codes into the box on Settings, Upgrade and presses Check for Upgrades (that button
saves the box first; there is no separate save; `-` is an empty box; extra boxes are blanked).
After all of them, `[ "$(fingerprint)" = "$before" ] && echo unchanged`.

| Code typed | Expect |
| --- | --- |
| (empty, `-`) | No gate message and no bare page. The three rows `corefiles`, `database`, `faq` are offered, all ticked. The box stays empty. |
| `abc` | Not saved: codes of five characters or fewer are dropped (only `free` is kept). The box is empty again. Same offers. |
| `wrong-code-123` | Saved, shown in the box, and the **same three rows**. Nothing says the code is wrong. |
| `wrong-code-123` and `local-upgrade-test-code` | Both saved (`+` adds a box; stored joined with `\|`). Same three rows. |
| `LOCAL-UPGRADE-TEST-CODE` | Saved as typed. Same three rows. |
| `local-upgrade-test-code` | Same three rows. |

Then:

1. The box and the page. The Upgrade page says "Your license code is optional. It is kept on
   this site as a record of your purchase, is not sent anywhere, and updates do not need
   it." "Please enter your license code before doing an upgrade." never appears. The line
   "The above license code is for ... thanks for purchasing!" never appears either: it
   needs a confirmed code in `_licence_code_<code>`, which nothing writes any more.
2. What the server received. Every request in `docker exec ucm-ut-api sh -c 'sort /tmp/ut-post.log | uniq -c'`
   has the same keys, `application`, `current_version`, `release_version`, `current_plugins`,
   `current_files`, `requested_plugin`, `get_file_contents`, `client_protocol`, `libraries`,
   and `"code":null`. No `installation_code`, `client_ip` or `installation_location`. Keep
   a code saved, then run **Start the Upgrade**, and look again: the download requests carry
   none either (on 2026-11-02: 11 requests, none with a code).
3. `[ "$(fingerprint)" = "$before" ] && echo unchanged` after the checks, so a wrong code
   installed and removed nothing. Then Start the Upgrade with the right code saved: it
   installs the same way as S2.
4. From the command line: `ut_offer` (no code), `ut_offer wrong-code-123`, `ut_offer ''` and
   `ut_offer local-upgrade-test-code` all print the same offers. That shows the server
   side ignores codes, as long as the gate in the API copy is off. With the gate on, only
   a request carrying the testing code is answered, and the 4.0.1 client never carries it.
5. A client older than 4.0.1 (S13's 4.0.0 install) still posts its saved code and still
   shows "Please enter your license code before doing an upgrade." on a bare page when the
   box is empty. Type any throwaway code of six characters or more to get past it. This is
   the old client, not the release under test, and it goes away once the client is updated.

Remove the request log by re-running setup step 2's API copy and gate commands.

### S9. The manual upgrade path

What a buyer does when they upload files over FTP instead of using the updater.

```bash
php "$TOOLS" bump "$SERVER" faq "manual path"
php "$TOOLS" add-column "$SERVER" faq faq ut_manual
rsync -a "$SERVER/includes/plugin_faq/" "$CLIENT/includes/plugin_faq/"
sleep 3
```

1. The new code is on the client, but `ut_version faq` still prints the old version and the
   `ut_manual` column does not exist.
2. Settings, then Upgrade, then **Run Manual Upgrades**. The faq line reads
   `Checking plugin: faq - Current Version: 2.152....`, then
   `Upgrading plugin from version 2.151 to 2.152`, then `all good`, and the page ends
   "Success! Everything worked."
3. `ut_version faq` prints the new version, and
   `db ucm_install_test -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_manual'"` prints a row.

### S10. Legacy API endpoints

Mode A only. The current client never calls these, but they are still deployed, so they
should answer without a PHP error.

```bash
php "$TOOLS" bump "$SERVER" faq "legacy endpoint check"
for u in "/index.php?curl_check=1" "/latest_updates.php" "/info.php" "/help.php?modules=faq&pages=faq_questions"; do
  printf '%-45s ' "$u"; curl -s -o "$UT/legacy.html" -w '%{http_code} ' "http://localhost:$UT_API_PORT$u"
  grep -ciE 'fatal error|warning:|parse error' "$UT/legacy.html"
done
curl -s "http://localhost:$UT_API_PORT/latest_updates.php" | grep -c 'legacy endpoint check'
```

Expect `200 0` on every line: an answer, and no PHP error in it. `curl_check` answers
`success`. `latest_updates.php` lists the bumped faq line under today's date. Pages that
pull styles from the old WordPress site will look unstyled locally; that is expected.

`docker logs ucm-ut-api` does show three PHP warnings from this step, in `ucm-web` files
that display nothing: `Undefined array key "codes"` in `info.php` line 88, and `Undefined
array key "topics"` plus `foreach() argument must be of type array|object, null given` in
`help.php` line 53 (2026-11-02). They are in the private repository and out of scope.

### S11. A full manual unzip over the top

The documented way to update without the updater: download the new release, unzip it,
copy the contents of `Main Files` over the existing site, then run the manual upgrade.
Copying over the top never deletes anything, so settings and uploads must survive.

**Make release B into a zip, shaped like the CodeCanyon download.**

```bash
php "$TOOLS" bump "$SERVER" faq "full unzip upgrade"
php "$TOOLS" add-column "$SERVER" faq faq ut_unzip
php "$TOOLS" bump "$SERVER" help "full unzip upgrade"
php "$TOOLS" help-marker "$SERVER" using/faq "LOCAL-UPGRADE-TEST unzip marker"
rm -rf "$UT/release-b" "$UT/release-b-unzipped" && mkdir -p "$UT/release-b/Main Files"
rsync -a --exclude php_errorlog --exclude '/temp/cache-*' "$SERVER/" "$UT/release-b/Main Files/"
( cd "$UT/release-b" && zip -qr ../ucm-release-b.zip "Main Files" )
unzip -q "$UT/ucm-release-b.zip" -d "$UT/release-b-unzipped"
ls "$UT/release-b-unzipped/Main Files/index.php"
```

Expect the `index.php` path back. If `unzip` says it cannot find `ucm-release-b.zip`, a
`ZIP` variable is set in your shell (see Setup): `unset ZIP` and run the block again.

**S11a. A current install.**

```bash
cp "$CLIENT/includes/config.local.php" "$UT/config.local.before"
printf 'a customer upload\n' > "$CLIENT/includes/plugin_file/upload/ut-customer-upload.txt"
rsync -a "$UT/release-b-unzipped/Main Files/" "$CLIENT/"
sleep 3
```

1. Settings survive: `cmp "$UT/config.local.before" "$CLIENT/includes/config.local.php" && echo kept`.
2. Uploads survive, and so does the guard on their folder:
   `ls "$CLIENT/includes/plugin_file/upload/ut-customer-upload.txt" "$CLIENT/includes/plugin_file/upload/.htaccess"`.
3. The site loads and you can log in. Record whether it shows any notice that the database
   is behind the files before you run the upgrade. On 2026-10-17 and on 2026-11-02 it showed
   none.
4. Settings, then Upgrade, then **Run Manual Upgrades**. The faq line reads
   `Upgrading plugin from version 2.151 to 2.152`, then `all good`. The help line is only
   `Checking plugin: help - Current Version: 2.141.... all good`: help has no database
   tables, so it records the new version without printing an `Upgrading` line. The page
   ends "Success! Everything worked." with no `failed`.
5. `ut_version faq` and `ut_version help` print the new versions. The `ut_unzip` column
   exists: `db ucm_install_test -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_unzip'"`.
6. The FAQ screen's help panel summary starts with "LOCAL-UPGRADE-TEST unzip marker".
7. `ut_offer` prints `offers: 0`: the client now matches the server.

**S11b. An install from before the rewrite, with its database details in `includes/config.php`.**

Releases before this one wrote the database details into `includes/config.php`, which is a
product file and is replaced by the unzip. The upgrade guide says to move them into
`includes/config.local.php` first. This checks what happens when someone does not.

```bash
ut_reset
grep -E "define\( '(_DB_[A-Z]+|_UCM_SECRET)'" "$CLIENT/includes/config.local.php" > "$UT/creds.txt"
wc -l < "$UT/creds.txt"                                   # expect 6
php -r '$f = $argv[1]; file_put_contents( $f, preg_replace( "/^<\?php\n/", "<?php\n" . file_get_contents( getenv( "UT" ) . "/creds.txt" ), file_get_contents( $f ), 1 ) );' "$CLIENT/includes/config.php"
rm "$CLIENT/includes/config.local.php"; sleep 3
curl -s -o /dev/null -w 'before: %{http_code}\n' "http://localhost:$UT_CLIENT_PORT/"
rsync -a "$UT/release-b-unzipped/Main Files/" "$CLIENT/"; sleep 3
curl -s -o /dev/null -w 'after unzip, here: %{http_code}\n' "http://localhost:$UT_CLIENT_PORT/"
ut_last_error ucm-ut-client $REAL_HOST     # in zsh: ${=REAL_HOST}
```

1. `before: 200`. A site with its details in `config.php` works.
2. `after unzip, here: 200`. That is the test stack, not the product: the client container
   carries `UCM_DB_NAME=ucm_install_test` (setup step 5), so the fallback in the new
   `config.php` happens to find the right database. The `ut_last_error` line is what a real
   host gets. Since 2026-11-02 it prints `no PHP error`, and running `index.php` with those
   values (the second command of setup step 5) prints `Hello, Welcome to the setup wizard.
   You are currently on step 0 of 5.` instead of a blank HTTP 500. That is better than a
   blank page, but note what it means: a live site that lost its database details by this
   unzip shows the **setup wizard**. (On 2026-10-17 it was `Uncaught mysqli_sql_exception:
   No such file or directory in .../includes/plugin_db/db.php:257`, a blank 500.) Record
   what you see. In zsh pass `${=REAL_HOST}`, not `$REAL_HOST`.
3. Follow the guide's fix: put the details in `config.local.php`.

   ```bash
   { echo '<?php'; cat "$UT/creds.txt"; } > "$CLIENT/includes/config.local.php"; sleep 3
   curl -s -o /dev/null -w 'after: %{http_code}\n' "http://localhost:$UT_CLIENT_PORT/"
   ut_last_error ucm-ut-client -e UCM_DB_SERVER=localhost -e UCM_DB_USER=username -e UCM_DB_PASS=password -e UCM_DB_NAME=ucm
   ```

   Expect `after: 200` and `no PHP error` (4 of 4 on 2026-11-02): with `config.local.php` back, even a real host's
   fallback values are never used. Then log in and run S11a steps 4 to 7.

### S12. The old one-file installer

Before the CodeCanyon zip carried the whole product, a buyer could upload one
`install.php` to an empty folder and open it. It asked the update server for everything as
a zip (`return_as_zip` and `return_base_system_in_zip`), unpacked it beside itself, and
sent the buyer to the setup wizard. The original script is no longer in either repository,
so `scripts/upgrade-test-legacy-install.php` stands in for it. It makes the same request
that `ucm-web/api/download_zip.php` still makes. If you have a copy of the original, use
it instead and say so in the results.

**Expected today: this passes.** It failed on 2026-10-17 (see [Findings](#findings)); since
the zip is built from `includes/release-files.txt` it is complete and starts.

```bash
ut_reset
rm -rf "$LEGACY" && mkdir -p "$LEGACY"
cp "$UCM/scripts/upgrade-test-legacy-install.php" "$LEGACY/install.php"
docker run -d --name ucm-ut-legacy --network ucm-ut --ip "$UT_LEGACY_IP" -p "$UT_LEGACY_PORT:80" \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT=/var/www/html \
  -e UCM_UT_SERVER="$UPGRADE_URL" -e UCM_UT_CODE="$UT_CODE" \
  -v "$LEGACY:/var/www/html" ucm-ut-php
# Wait for Apache on a page that does not exist. Never poll install.php: one GET of it runs the install.
for i in $(seq 1 30); do [ "$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:$UT_LEGACY_PORT/ut-not-here")" = 404 ] && break; sleep 1; done
curl -s --max-time 300 "http://localhost:$UT_LEGACY_PORT/install.php" | sed 's/<[^>]*>//g' | grep -v '^\s*$'
```

1. **The download.** Expect `Unpacked <n> entries` and a link to the setup wizard. On
   2026-11-02 it read `Unpacked 4125 entries` (4,125 files, the same as the release's file
   list). On 2026-10-17 it read 3,315, with 2,844 files against 3,640 in the release.
2. **A wrong code.** Mode A with the gate present only. A 4.0.1 install sends no code, and
   with the gate off in the API copy (setup step 2) every request is served, including this
   one: you get the zip. With the gate on, ask for the zip with a code the gate does not know:

   ```bash
   curl -s -X POST "http://localhost:$UT_API_PORT${UPGRADE_URL#http://$UT_API_IP}" \
     -d installation_code=wrong-code-123 -d current_version=0 -d get_file_contents=1 \
     -d return_as_zip=1 -d return_base_system_in_zip=1 | head -c 300; echo
   ```

   With the gate on, expect the JSON "Updates are paused" answer instead of a zip. The
   stand-in reports the same thing as "The server did not send a zip", and unpacks nothing.
   With the gate off, the first bytes are `PK` (a zip).
3. **Compare it with the release.**

   ```bash
   ( cd "$UT/unzipped/Main Files" && find . -type f | sed 's#^\./##' | sort ) > "$UT/release-files.txt"
   ( cd "$LEGACY" && find . -type f ! -name install.php | sed 's#^\./##' | sort ) > "$UT/legacy-files.txt"
   echo "missing, outside vendor/:"; comm -23 "$UT/release-files.txt" "$UT/legacy-files.txt" | grep -v '^vendor/'
   echo "missing from vendor/: $(comm -23 "$UT/release-files.txt" "$UT/legacy-files.txt" | grep -c '^vendor/')"
   echo "only in the legacy install:"; comm -13 "$UT/release-files.txt" "$UT/legacy-files.txt"
   cmp -s "$UT/unzipped/Main Files/includes/config.php" "$LEGACY/includes/config.php" && echo "config.php matches the release" || echo "config.php DIFFERS from the release"
   ```

   Passing means nothing missing except buyer notes, and `config.php matches the release`.
   2026-11-02: nothing missing, nothing extra, 4,125 files each side, `config.php matches the
   release`. (On 2026-10-17 the list in [Findings](#findings).)
4. **Does it start?**

   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:$UT_LEGACY_PORT/"
   ```

   Passing is a redirect (`302`) to step 1 of the setup wizard; 2026-11-02 gave `302`. On
   2026-10-17 it was `500`. The zip's `config.php` switches error reporting off at runtime,
   so if it fails again use the shutdown hook helper from Setup to see why:

   ```bash
   ut_last_error ucm-ut-legacy
   ```

   On 2026-10-17: `Uncaught Error: Failed opening required 'includes/class.libraries.php' ... in /var/www/html/init.php:130`.
5. **Walk the wizard.** This reuses the client's database, so it wipes it and puts it back afterwards.
   The legacy container has no `UCM_DB_NAME`, which is the point: the zip's own
   `config.php` must get it to the wizard.

   ```bash
   db -e "DROP DATABASE ucm_install_test; CREATE DATABASE ucm_install_test; GRANT ALL ON ucm_install_test.* TO 'ucm'@'%';"
   CI=1 UCM_INSTALL_BASE_URL="http://localhost:$UT_LEGACY_PORT" npx playwright test tests/e2e/install.spec.ts \
     --workers=1 --retries=0 --reporter=list > "$UT/legacy-install.log" 2>&1; echo "exit $?"
   ut_reset
   ```

   Expect `exit 0` and `2 passed` (it did on 2026-11-02). The second test logs in, which
   proves the database details the wizard saved actually reach the app. Then
   `node scripts/upgrade-test-drive.mjs pdf` with `UT_BASE=http://localhost:$UT_LEGACY_PORT`
   (before `ut_reset`) makes an invoice PDF from this install too.

### S13. Upgrading from 4.0.0: the old libraries go and vendor/ arrives

4.0.1 swaps three bundled libraries for Composer ones. dompdf 1.0.2 (`includes/plugin_pdf/dompdf`)
becomes `vendor/dompdf` 3.1.6, PHPMailer 5.2.21 (`includes/plugin_email/class.phpmailer.php`,
`class.smtp.php`, `class.pop3.php`) becomes `vendor/phpmailer` 6.12, and the 2014 stripe-php
(`includes/plugin_paymethod_stripe/stripe-php`) is dropped for `vendor/stripe`. Updating never
deletes files, so the `pdf`, `email` and `paymethod_stripe` plugins delete the old copies in
their `install_upgrade()`. This scenario checks that that happens, and that `vendor/` gets
onto an install that did not have it, by both update paths, and that PDFs and email still work
afterwards.

**Why a 4.0.0 tree.** A real 3.x tree cannot run on PHP 8.4, so the starting point is the
last commit that still bundled everything, `e834210` (4.0.0, before the retired gateways
were removed). It has all of the old folders, the four retired gateway folders, and a
`vendor/` with only htmlpurifier and stripe. What a 3.x client sends (no release, no
libraries) is checked from the command line in step 8.

**Build it and install it.** A separate container, database and port, so the client from
setup is not disturbed:

```bash
mkdir -p "$OLD"
git -C "$UCM" archive e834210 | tar -x -C "$OLD"
git -C "$UCM" show e834210:composer.json > "$OLD/composer.json"
git -C "$UCM" show e834210:composer.lock > "$OLD/composer.lock"
( cd "$OLD" && composer install --no-dev --no-interaction --prefer-dist -q && rm composer.json composer.lock )
ls "$OLD/vendor"                                         # autoload.php composer ezyang stripe: no dompdf, no phpmailer
db -e "CREATE DATABASE ucm_ut_old; GRANT ALL ON ucm_ut_old.* TO 'ucm'@'%';"
docker run -d --name ucm-ut-old --network ucm-ut --ip 172.31.250.40 -p "$UT_OLD_PORT:80" \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT=/var/www/html -e UCM_DB_NAME=ucm_ut_old \
  -v "$OLD:/var/www/html" ucm-ut-php
for i in $(seq 1 30); do curl -fsS -o /dev/null "http://localhost:$UT_OLD_PORT/" 2>/dev/null && break; sleep 1; done
CI=1 UCM_INSTALL_DB_NAME=ucm_ut_old UCM_INSTALL_BASE_URL="http://localhost:$UT_OLD_PORT" npx playwright test tests/e2e/install.spec.ts \
  --workers=1 --retries=0 --reporter=list > "$UT/old-install.log" 2>&1; echo "exit $?"; tail -3 "$UT/old-install.log"
```

Expect `2 passed`. (`composer` needs the network, which is the host's, not a test container's.)

Make it look like a pre-4.0.1 install, then snapshot it:

```bash
perl -ni -e 'print unless /_UCM_SECRET/' "$OLD/includes/config.local.php"       # a 3.x install never had one
db ucm_ut_old -e "REPLACE INTO ucm_config (\`key\`,\`val\`) VALUES
  ('ucm_upgrade_url','$UPGRADE_URL'), ('help_allow_remote','0'), ('_installation_code','$UT_CODE');"
mkdir -p "$UT/old-baseline"
rsync -a --delete "$OLD/" "$UT/old-baseline/tree/"
docker exec ucm-ut-db mysqldump -uroot -proot ucm_ut_old 2>/dev/null > "$UT/old-baseline/old.sql"
old_reset() { rsync -a --delete "$UT/old-baseline/tree/" "$OLD/"; db ucm_ut_old < "$UT/old-baseline/old.sql"; sleep 3; }
old_offer() {   # what the server offers a 4.0.0 client, sending what a 4.0.0 client sends
  php "$TOOLS" plugins-json "$OLD" > "$UT/old-plugins.json"
  curl -s -X POST "http://localhost:$UT_API_PORT${UPGRADE_URL#http://$UT_API_IP}" \
    --data-urlencode "release_version=$(php "$TOOLS" release "$OLD")" \
    --data-urlencode "current_plugins@$UT/old-plugins.json" | tee "$UT/old-offer.json" | php "$TOOLS" summarise
}
ut_lib_check() {   # ut_lib_check <container> <install dir>: old paths gone, new libraries there and loadable
  local c=$1 d=$2 bad=0 f
  for f in includes/plugin_pdf/dompdf includes/plugin_email/class.phpmailer.php includes/plugin_email/class.smtp.php includes/plugin_email/class.pop3.php includes/plugin_paymethod_stripe/stripe-php; do
    [ -e "$d/$f" ] && { echo "STILL THERE: $f"; bad=1; }
  done
  for f in vendor/dompdf/dompdf/src/Dompdf.php vendor/phpmailer/phpmailer/src/PHPMailer.php vendor/stripe/stripe-php/init.php vendor/composer/installed.json; do
    [ -f "$d/$f" ] || { echo "MISSING: $f"; bad=1; }
  done
  docker exec -w /var/www/html "$c" php -r 'require "vendor/autoload.php"; echo class_exists("Dompdf\\Dompdf") && class_exists("PHPMailer\\PHPMailer\\PHPMailer") && class_exists("Stripe\\StripeClient") ? "loadable\n" : "NOT loadable\n";'
  [ $bad = 0 ] && echo "libraries ok"
}
export UT_BASE="http://localhost:$UT_OLD_PORT"        # for the drive script, in this section
```

The throwaway licence code is set because a 4.0.0 client still refuses to check for updates
without one (S8, last point). Any code of six characters or more does.

**Before.**

1. The old copies are there and the new ones are not: `ls "$OLD/includes/plugin_pdf/dompdf" "$OLD/includes/plugin_email/class.phpmailer.php" "$OLD/includes/plugin_paymethod_stripe/stripe-php"`
   lists them, and `ls "$OLD/vendor/dompdf"` fails. `ut_lib_check ucm-ut-old "$OLD"` reports the
   old paths `STILL THERE` and the new ones `MISSING`.
2. `node scripts/upgrade-test-drive.mjs pdf` makes an invoice and its PDF:
   `HTTP 200 application/pdf ... starts "%PDF-1.7", Producer: dompdf 1.0.2 + CPDF` (2026-11-02).
3. Take the snapshot above as the starting point every time: `old_reset`.

**S13a. The in-app updater.** `old_reset`, then:

4. `old_offer`. Expect `protocol: 2`, `corefiles` `4.0.0 -> 4.0.1` first, then `database`
   with about 1,700 files and the description "Database application update and third party
   libraries" (that is `vendor/`: the client sent no `libraries` fingerprint, so the server
   sends it), then 38 plugins in name order, `config` last: 40 rows on 2026-11-02. It must
   include `pdf`, `email` and `paymethod_stripe`, and none of `authorize`, `coinbase`,
   `multisafepay`, `paynl`.
5. `node scripts/upgrade-test-drive.mjs upgrade > "$UT/old-inapp.log" 2>&1; echo $?`. It takes
   about 16 seconds. Expect exit `0`, the button "Completed, click here to continue" and
   `grep -c 'Success! Everything worked' "$UT/old-inapp.log"` printing 38. The `pdf` line
   reads `Processing update: pdf - Current Version: 2.137.... all good`. Search the log with
   `grep -E 'Some things failed|REFUSED|not finished' "$UT/old-inapp.log"` (file names
   containing "Failed" and "Error" in `vendor/stripe` are not failures: do not grep for
   those words). With the request log from S8 still on in the API copy, `docker exec ucm-ut-api sh -c
   'sort /tmp/ut-post.log | uniq -c'` shows the requests of this 4.0.0 client, which carry
   `installation_code` and `client_ip` (41 on 2026-11-02): a 4.0.0 client sends them, only a
   4.0.1 client does not (S8).
6. The libraries: `ut_lib_check ucm-ut-old "$OLD"` prints `loadable` and `libraries ok`: all
   five old paths are gone, `vendor/dompdf`, `vendor/phpmailer`, `vendor/stripe` and
   `vendor/composer/installed.json` are there. Every file of the release's `vendor/`
   arrived with the same bytes: `diff -rq "$UT/unzipped/Main Files/vendor" "$OLD/vendor"`
   prints nothing but four extra files under `vendor/stripe/stripe-php` (`API_VERSION`,
   `lib/ThinEvent.php`, `lib/V2/Event.php`, `lib/V2/EventDestination.php`). Those are in
   the 4.0.0 `composer install` this scenario builds and not in the release's, and the
   updater never deletes; they are not a product problem.
7. It still works: `node scripts/upgrade-test-drive.mjs pdf` prints
   `HTTP 200 application/pdf ... Producer: dompdf 3.1.6 + CPDF`. `node scripts/upgrade-test-drive.mjs mail`
   prints, for the settings test email, `sent=true; mailhog has it=true;
   multipart/alternative=true; X-Mailer/generator: PHPMailer 6.12.0`, and for the invoice
   email `mailhog has it=true; PDF listed on screen=true; has application/pdf part=true;
   multipart/mixed=true`. It saves the SMTP settings to `ucm-ut-mail:1025` first, deletes
   its own two messages afterwards, and reads them back from the mailhog on `$UT_MAIL_PORT`.
8. A 3.x client, from the command line: ask with no release and no libraries,
   `curl -s -X POST "http://localhost:$UT_API_PORT${UPGRADE_URL#http://$UT_API_IP}" --data-urlencode "current_plugins@$UT/old-plugins.json" | php "$TOOLS" summarise | head -12`.
   The `database` row also carries the libraries. (A 3.x client has no `vendor/`, so it
   needs them; a client whose `libraries` fingerprint matches the server's is sent one file.)

**S13b. Unzip over the top.** `old_reset`, then do what the upgrade guide says:

```bash
rsync -a "$UT/unzipped/Main Files/" "$OLD/"; sleep 3
```

9. Before running anything: the site loads and you can log in (`node scripts/upgrade-test-drive.mjs page / 3`),
   and the old folders **are still there**, because copying never deletes:
   `ut_lib_check ucm-ut-old "$OLD"` lists five `STILL THERE`. The new code already makes a
   PDF: `node scripts/upgrade-test-drive.mjs pdf` prints `Producer: dompdf 3.1.6 + CPDF`.
10. Settings, Upgrade, **Run Manual Upgrades** (`node scripts/upgrade-test-drive.mjs manual > "$UT/old-manual.log"`).
    It ends "Success! Everything worked." with no `failed`.
11. `ut_lib_check ucm-ut-old "$OLD"` now prints `loadable` and `libraries ok`: the old paths
    are gone. Then `pdf` and `mail` pass as in step 7 (PHPMailer 6.12.0, dompdf 3.1.6).

**S13c. Leftovers.** Neither path removes `includes/plugin_paymethod_stripe/pages/stripe_form.php`,
the old Charges form. It requires the deleted `stripe-php/lib/Stripe.php`, so
`UT_BASE=http://localhost:$UT_OLD_PORT node scripts/upgrade-test-drive.mjs page '/?m[]=paymethod_stripe&p[]=stripe_form' 4`
prints `HTTP 500` (blank, since errors are off). Nothing links to the page; see
[Findings](#findings). The release's own list of files does not have it, so a fresh install is clean.

### S14. The secret warning

Settings and Settings, Upgrade show a "Set a private secret for this site" box when
`_UCM_SECRET` is just the install folder (`_UCM_SECRET_IS_INSTALL_PATH`). The script prints
whether it is shown on three pages (the box is not meant to be on the advanced settings
page):

```bash
UT_BASE=http://localhost:$UT_CLIENT_PORT node scripts/upgrade-test-drive.mjs notice
```

| Install | `config.local.php` holds `_UCM_SECRET` | Expect |
| --- | --- | --- |
| Fresh, made by the wizard (the client from setup step 5) | yes, 64 random characters | Not shown on Settings or Upgrade. |
| 4.0.0 install with no secret, unzipped over (S13b, after Run Manual Upgrades) | no | **Shown** on Settings and on Upgrade. |
| The same install, updated in-app (S13a) | no | **Should be shown. It is not** (2026-11-02): see [Findings](#findings). |
| Any install with `UCM_SECRET` in the container environment | n/a | Not shown. |

Why the in-app row differs. The warning is switched on by `includes/config.php`, which
defines `_UCM_SECRET_IS_INSTALL_PATH`, and the in-app updater never replaces
`includes/config.php` (it holds database details on installs from before `config.local.php`).
An install updated in-app keeps its 4.0.0 `config.php`, where the constant does not exist, so
the flag is never defined. Check it with `grep -c IS_INSTALL_PATH "$OLD/includes/config.php"`:
`0` after S13a, `2` after S13b.

### S15. Retired gateways left behind

`plugin_paymethod_authorize`, `coinbase`, `multisafepay` and `paynl` are not in the release.
An install that had them keeps the folders, because updating never deletes, and keeps their
settings in the database.

1. After S13a and again after S13b, the folders are still in `$OLD/includes` and
   `ls "$OLD/includes" | grep -c 'paymethod_\(authorize\|coinbase\|multisafepay\|paynl\)'`
   prints `4`.
2. `node scripts/upgrade-test-drive.mjs crawl 1500` (with `UT_BASE` on the old install)
   follows every link from the dashboard and the payment settings page, skipping logout and
   anything that deletes, and prints `crawled 526 pages, 0 with an error` (525 after S13b):
   no HTTP error, no PHP error text on any page. It also reports pages that name a retired
   gateway on a payment page: none.
3. Nothing offers to fetch them: Settings, Upgrade says "No upgrade available at this time."
   after the update, `old_offer` lists none of the four, and the Upgrade and Payment settings
   pages never say "download" or "install". The advanced settings page still lists their
   saved settings (`payment_method_authorize_label` and so on, and `plugin_enabled_paymethod_authorize`
   = 1), which is old data, not an offer.
4. `docker logs ucm-ut-old 2>&1 | grep -ciE 'fatal|uncaught|PHP (Warning|Notice|Deprecated)'` prints `0`.

### S16. PHP 8.2, the lowest supported

The same release on `ucm-ut-php82` (setup step 3): install it by the wizard, check for
updates, install one, and make a PDF and an email. It shares the update server, the
database server and the mailhog.

```bash
C82="$UT/client82"; mkdir -p "$C82"; rsync -a "$UT/unzipped/Main Files/" "$C82/"
db -e "CREATE DATABASE ucm_ut_82; GRANT ALL ON ucm_ut_82.* TO 'ucm'@'%';"
docker run -d --name ucm-ut-client82 --network ucm-ut --ip 172.31.250.60 -p "$UT_82_PORT:80" \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT=/var/www/html -e UCM_DB_NAME=ucm_ut_82 -v "$C82:/var/www/html" ucm-ut-php82
for i in $(seq 1 30); do curl -fsS -o /dev/null "http://localhost:$UT_82_PORT/" 2>/dev/null && break; sleep 1; done
CI=1 UCM_INSTALL_DB_NAME=ucm_ut_82 UCM_INSTALL_BASE_URL="http://localhost:$UT_82_PORT" npx playwright test tests/e2e/install.spec.ts \
  --workers=1 --retries=0 --reporter=list > "$UT/install82.log" 2>&1; echo "exit $?"; tail -3 "$UT/install82.log"
db ucm_ut_82 -e "REPLACE INTO ucm_config (\`key\`,\`val\`) VALUES ('ucm_upgrade_url','$UPGRADE_URL'),('help_allow_remote','0');"
export UT_BASE="http://localhost:$UT_82_PORT"
node scripts/upgrade-test-drive.mjs check              # No upgrade available at this time.
rsync -a --delete "$UT/unzipped/Main Files/" "$SERVER/"; sleep 3
php "$TOOLS" bump "$SERVER" faq "local upgrade test marker"; php "$TOOLS" add-column "$SERVER" faq faq ut_marker82; sleep 3
node scripts/upgrade-test-drive.mjs upgrade            # corefiles, database, faq; Success! Everything worked.
db ucm_ut_82 -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_marker82'"
node scripts/upgrade-test-drive.mjs pdf; node scripts/upgrade-test-drive.mjs mail
node scripts/upgrade-test-drive.mjs crawl 400
docker logs ucm-ut-client82 2>&1 | grep -ciE 'fatal|uncaught|PHP (Warning|Notice|Deprecated)'    # 0
```

Expect `2 passed`, the same offer as S2, the column present, the same PDF and email results
as S13 step 7, `crawled 400 pages, 0 with an error`, and `0`. Run `ut_reset` afterwards so the
server goes back to release A.

## Findings

Product behaviour this runbook turned up. None of it is fixed here: the runbook records
it, and each step that meets it says what you will see today.

### Found in the run of 2026-11-02 (release 4.0.1)

| What | Where | Effect |
| --- | --- | --- |
| **The secret warning never shows on an install updated in-app.** The flag that switches it on, `_UCM_SECRET_IS_INSTALL_PATH`, is defined only in `includes/config.php`, and the in-app updater never replaces that file. An install updated from 4.0.0 in-app keeps its 4.0.0 `config.php`, which has no such constant, and `init.php:91` defines `_UCM_SECRET` as the install path without defining the flag. | `includes/config.php:83-94`, `init.php:91-95`, `includes/functions.php:157` (`ucm_secret_notice_html()` reads `defined(...) && ...`) | Settings and Settings, Upgrade show nothing on exactly the installs the warning is for. It does show after a full unzip. Suggested fix: derive it where it is read (`_UCM_SECRET === _UCM_FOLDER`), or define the flag in the `init.php` fallback as well. |
| **`includes/plugin_paymethod_stripe/pages/stripe_form.php` is left behind** by both update paths, because the stripe plugin deletes only the `stripe-php` folder ("and nothing else"). It requires the deleted `stripe-php/lib/Stripe.php`. | `includes/plugin_paymethod_stripe/cleanup.php`; the orphan page | Opening `/?m[]=paymethod_stripe&p[]=stripe_form` on an upgraded install is a blank HTTP 500. Nothing links to it, so it is dead weight, but the cleanup could remove it too. |
| **The temporary gate in `ucm-web/api/upgrade.php` pauses every 4.0.1 client.** It answers "Updates are paused ..." to any request without the testing code, and a 4.0.1 client sends no code. | `../ucm-web/api/upgrade.php` lines 29-58 | If it is still deployed when 4.0.1 is released, no 4.0.1 install can update. Remove the block before release (its own comment says so). The local copy switches it off. |
| A 4.0.0 client still stops with "Please enter your license code before doing an upgrade." when its box is empty, and still sends the code, `client_ip` and `installation_location`. | the 4.0.0 client, not this release | A buyer on 4.0.0 with no code saved must type any six characters once before the in-app update to 4.0.1 will check. After it, the 4.0.1 client sends nothing. |
| A site that loses its database details now shows the **setup wizard** rather than a blank 500. | `init.php`, `is_installed()` | Better than a blank page, but a live site whose `config.local.php` was not moved before an unzip (S11b) shows a first-run wizard to visitors. |
| Three PHP warnings in the update server's own endpoints | `ucm-web/api/info.php:88`, `help.php:53` | In the private repository. Display is off, so nothing reaches a page (S10). |

### Found in the first full run (2026-10-17)

| What | Where | Effect |
| --- | --- | --- |
| **Fixed by dtbaker/ucm#29. The in-app updater runs each plugin's database step on stale code.** The page writes a plugin's files in one request and runs its database step in the next. PHP's opcache only re-reads a changed file every `opcache.revalidate_freq` seconds (2 by default, often more on shared hosts), and nothing calls `opcache_invalidate()` after a write, so the second request usually still has the old class. | `class.upgrade.php`, `complete_plugin_installation()` after `install_plugin_update_files()` | The new version is not recorded and the plugin's upgrade SQL does not run, while the page says `all good` and "Success! Everything worked." Seen in 7 of 9 plugin steps on 2026-10-17 (S2, S3, S5, S6). Proven by S3 passing with `opcache.revalidate_freq=0`. Run Manual Upgrades repairs it afterwards. The fix invalidates opcache for every file the updater writes; where the host refuses that (`opcache.restrict_api`), the database step now says `not finished` and changes nothing instead of reporting success. |
| **Fixed (checked 2026-11-02). A fresh install with no `config.local.php` is a blank HTTP 500, not the setup wizard,** unless the fallback database details in `includes/config.php` happen to connect. On PHP 8.1 and later `mysqli_connect()` throws on failure, and nothing catches it or calls `mysqli_report()`, so the "Connect failed" message in `db_connect()` is never reached. | `init.php:206` `is_installed()` → `includes/plugin_db/db.php:257` | The main install route (unzip and open the site) does not reach the wizard on a normal host, whose fallback is `localhost` / `username` / `ucm`. The same blank 500 is what a site that loses its `config.local.php` shows (S11b). The dev stack and CI never see it, because `docker-compose.yml` sets `UCM_DB_NAME`. |
| **Fixed by the 3.x upgrade fix (no `REFUSED` lines on 2026-11-02).** The `corefiles` list names three files the server never sends | `upgrade_server.php`, the `corefiles` list | Every update ended the core row with three `REFUSED: the server sent no contents for this file` lines. `js/jquery-1.8.3.min.js` and `js/jquery-ui-1.9.2.custom.min.js` are not in the release; `temp/.htaccess` is skipped by `is_ignored_path()` (`/temp/`), so an updated install never gets a newer `temp/.htaccess` either. |
| **Fixed by the release version check (checked again 2026-11-02, S4).** A core file changed with no plugin bump is never offered | `upgrade_server.php`, the `$do_upgrade` check before `corefiles` | Answered the S4 question: no. A release that changed only core files reached nobody through the updater. The client now sends `release_version` and the server offers the core rows when its own is newer; a 3.x client still gets them only with a plugin update. |
| **Still true 2026-11-02.** The Upgrade screen's help opens "The plugin system" | `includes/plugin_config/config.help.json`, generated from `ucm-web` `site/src/content/docs/developers/plugins.md` frontmatter | Answers the S6 question: it shows `developers/plugins`; it should show `install/upgrading` ("Upgrading from 2.x"). |
| **Still true 2026-11-02.** The client ignores `check_again` | no reference to it outside `upgrade_server.php` | A client that is behind gets part of its updates and is never told there are more. It has to think of pressing Check for Upgrades again. |
| **Not re-checked 2026-11-02** (the script read the button before the 4 seconds were up and saw "Completed"). "Completed, click here to continue" is hidden on a short run | `includes/plugin_form/js/form.js`, `dtbaker_loading_button()` restores the button text after 4 seconds | A run under 4 seconds ends with the button reading "Start the Upgrade" again, as if nothing happened. |
| **Gone (2026-11-02): the licence gate was removed from the client.** An empty licence code ends on a bare page | `config_upgrade.php`, `exit` after "Please enter your license code before doing an upgrade." | No menu or link back. |

### The old installer's zip (S12)

**All of the rows below are fixed: S12 passes since 2026-11-02** (the zip is built from the
release's own file list, `includes/release-files.txt`, and matches the release file for file).
Confirmed on 2026-10-17 by building the old installer's zip from the real update server
code and comparing it with a release built from `2026-revive-2`, and confirmed again in the
full run. Each was a reason S12 failed. The zip is assembled in
`includes/plugin_config/upgrade_server.php`, in the `return_base_system_in_zip` block and
the per-plugin listings.

| What | Effect on an install made this way |
| --- | --- |
| `includes/class.libraries.php` is left out | Every page fails at `init.php:130`. Confirmed end to end. |
| `includes/config.php` is replaced with a pre-rewrite copy | It never loads `config.local.php`, so the wizard's saved database details are never read. It also turns error reporting off, sets `_REWRITE_LINKS` to false, and puts a new random secret into a product file on every download. |
| `vendor/` is left out (780 files) | No Composer libraries. Stripe cannot take a payment, because it has no bundled fallback. |
| `temp/.htaccess`, `temp/index.php` and the guard files in the upload and attachment folders are left out | Private folders are reachable from the web, which undoes one of this release's security fixes. The listing skips those folders entirely, including their guards. |
| `n/index.php` is left out | The newsletter link folder is missing. |
| The repository's Docker image has no `zip` extension | The zip route cannot run in the dev stack at all, so `tests/e2e/upgrade.spec.ts` cannot cover it. This runbook adds the extension to its own image. |

## Results log

Copy this into the pull request, or a note, for each run. The newest filled-in run is below
the template.

```
Local upgrade test - <date> - <who>
Release A: <zip or REF> @ <commit>   Mode: A | B        Image PHP: <version(s)>
Licence gate in the API copy: present (switched off) | absent

S1 baseline                     pass | fail | n/a   <notes>
S2 one plugin bumped            ...
S3 database migration           ...
S4 core file alone / release bump / old client / new file (and release-files.txt)   ...
S5 order: name / db / config    ...
S6 help: index no bump / index bump / screen map / upgrade-screen article  ...
S7 messages: changelog / progress / escaping / rate limit  ...
S8 licences: every row gives the same offers; request log keys; unchanged fingerprint  ...
S9 manual path                  ...
S10 legacy endpoints            ...
S11a unzip over a current install: settings / uploads / manual upgrade / help  ...
S11b unzip over a pre-rewrite install: what a real host gets / fix  ...
S12 old installer: download / file diff / starts / wizard  ...
S13a 4.0.0 in-app: rows / old paths gone / vendor arrived / PDF producer / email generator  ...
S13b 4.0.0 unzip + Run Manual Upgrades: same  ...
S13c leftovers (stripe_form.php)  ...
S14 secret warning: fresh / unzip / in-app  ...
S15 retired gateways: crawl pages and errors / any offer to fetch them  ...
S16 PHP 8.2: install / S1 / S2 / PDF / email  ...
Install by manual unzip (setup step 2 and 5)  ...  (and the REAL_HOST line: blank 500 or wizard?)
Stale code in the in-app updater: <n> of <m> "Processing update" lines showed the old version
UpgradeIntegrityTest            ./vendor/bin/phpunit --filter UpgradeIntegrity  -> <result>

Anything surprising:
```

### Run of 2026-11-02 (4.0.1 zip, by the verification agent)

```
Local upgrade test - 2026-11-02 - verification agent (Claude)
Release A: build/ucm-codecanyon.zip, 4.0.1, built from 8c654ac+ (not rebuilt)   Mode: A
Image PHP: 8.4.18 (everything), 8.2.35 (install, S1, S2, PDF, email, 400-page crawl)
Licence gate in the API copy: present (switched off; a gated copy kept for S7)

S1 baseline                     pass   offers: 0 by ut_offer, "No upgrade available at this time." on the page
S2 one plugin bumped            pass   corefiles (23 files), database (1), faq 2.151 -> 2.152; files identical; version recorded; re-check offers: 0
S3 database migration           pass   column added in the first run ("Upgrading plugin from version 2.151 to 2.152"); Run Manual Upgrades twice: all good, no failed
S4 core / bump / 3.x / new file pass   core alone: offers 0; with bump-release: corefiles 4.0.1 -> 4.0.2 + database; 3.x client (no release): offers 0; upgrade wrote marker, release 4.0.2; re-check 0.
                                       new file: only offered after adding it to includes/release-files.txt (runbook changed)
S5 order                        pass   name order corefiles,database,faq,help,theme; behind on db: db first, check_again yes; behind on config: config last and ticked, faq unticked, then faq offered ticked
S6 help                         pass   index edit alone: offers 0; with help bump: marker shows; screen map: faq help opens "Installing"; Upgrade screen still opens "The plugin system" (known)
S7 messages                     pass   markup in the server message shows as text; rate limit: 429 for the host after 1,180 requests, client at 172.31.250.20 still 200
S8 licences                     pass   rewritten. Empty, abc, wrong, wrong+right, upper case, right: identical three rows; 6 of 6 requests logged with no installation_code/client_ip/installation_location; upgrade requests (5) the same; client files unchanged; no "thanks for purchasing" line
S9 manual path                  pass
S10 legacy endpoints            pass   200 and no PHP error in the body x4; 3 warnings in ucm-ut-api's log from ucm-web info.php:88 and help.php:53
S11a unzip over a current install pass settings kept, upload and .htaccess kept, no notice before upgrade, faq and help versions recorded, ut_unzip column, help marker, offers 0
S11b                            pass   details in config.php: before 200; after unzip 200 (test stack); REAL_HOST: no PHP error, shows the setup wizard (was a blank 500); after config.local.php: 200, no PHP error
S12 old installer               pass   Unpacked 4125 entries; nothing missing, nothing extra, config.php matches; GET / is 302 to the wizard; wizard 2 passed; PDF made
S13a 4.0.0 in-app               pass   40 rows (38 plugins), database row carried 1,708 files (vendor/); 38 Success; old paths gone; vendor/dompdf, phpmailer, stripe, composer present and loadable; PDF producer dompdf 1.0.2 -> 3.1.6; email: PHPMailer 6.12.0, PDF attached.
S13b 4.0.0 unzip                pass   old folders still there after the copy; Run Manual Upgrades: Success, old paths gone, loadable, PDF dompdf 3.1.6, email PHPMailer 6.12.0
S13c leftovers                  FAIL   includes/plugin_paymethod_stripe/pages/stripe_form.php stays; page answers a blank HTTP 500
S14 secret warning              FAIL   fresh wizard install: not shown (correct). Unzip over 4.0.0: shown on Settings and Upgrade (correct). In-app update of 4.0.0: NOT shown (old config.php never defines the flag)
S15 retired gateways            pass   4 folders still present both ways; crawl 526 pages (525 after unzip), 0 errors; nothing offered; 0 PHP errors in the log
S16 PHP 8.2                     pass   install 2 passed, S1 clean, S2 as 8.4, column added, dompdf 3.1.6, PHPMailer 6.12.0, crawl 400 pages 0 errors, log clean
Install by manual unzip         pass   web root clean; wizard 2 passed on 8.4 and 8.2; config.local.php holds a random _UCM_SECRET; REAL_HOST gives the wizard, not a blank 500
Stale code in the in-app updater: none of the "Processing update" lines (about 20 in S2 to S8, 38 in S13a) showed the old version
UpgradeIntegrityTest            ./vendor/bin/phpunit --filter UpgradeIntegrity  -> OK (44 tests, 178 assertions)

Anything surprising:
- The ucm-web gate would pause every 4.0.1 client (setup step 2). Switched off in the local copy.
- In-app updated installs never get the secret warning (S14), because they keep the old includes/config.php.
- stripe_form.php is left behind and is a blank 500 if opened (S13c).
- The server's offer after a server edit needs 3 seconds (opcache) or it is the old one; S4 got offers: 0 once, wrongly.
- Rate limit counters are fixed windows, so 601 requests may not trip it.
- The runbook's REAL_HOST check silently tested nothing in zsh (one argument); ut_last_error reported a silenced session warning as the "last error".
- A re-run of Run Manual Upgrades prints "Upgrading plugin from version 2.188 to 2.188" for data, invoice and paymethod_stripe, and "Array ( )" on the note line.
- The old 4.0.0 install's settings test email said "sent=false" while the invoice email arrived; with 4.0.1 both arrive.
```

## Teardown

Name the containers, never prune: other stacks (the development app, its database, its mailhog)
are running on the same machine.

```bash
docker rm -f ucm-ut-client ucm-ut-client82 ucm-ut-old ucm-ut-api ucm-ut-legacy ucm-ut-mail ucm-ut-db
docker network rm ucm-ut
docker rmi ucm-ut-php ucm-ut-php-base ucm-ut-php82 ucm-ut-php-base82
rm -rf "$UT"
```

## Troubleshooting

- **A change is not picked up.** The image revalidates opcache every 2 seconds. Wait 3
  seconds after editing a file before the next request. On Docker Desktop, a file written
  on the host can also take a moment to show inside the container; check with
  `docker exec ucm-ut-api grep -c <marker> <path>` before blaming the code.
- **"Incorrect API url".** The update URL's host is not allowed. Use the private IP, not a
  container name.
- **The offer is empty or not JSON.** Look at `$UT/offer.json` and
  `docker logs ucm-ut-api`. The server install also writes to its own
  `php_errorlog` beside `index.php`.
- **"Too many update requests".** The rate limit counter is in the API container's temp
  folder. `ut_reset` clears it.
- **The installer spec skips.** The wizard can only be walked once per database. Reset
  from setup step 4.
- **The subnet is taken.** Pick another private range for `UT_NET`, `UT_API_IP` and
  `UT_CLIENT_IP`.
- **Mode A paused message for the right code.** The gate rewrite in setup step 2 did not
  match. Check it printed "gate now accepts the local code", and that the code is lower
  case.
- **A port is taken, or a request lands on a different app.** Other worktree stacks use
  ports in the 8090s. Run the check under Setup, and change `UT_CLIENT_PORT`,
  `UT_API_PORT` or `UT_LEGACY_PORT` if it reports anything.
- **S12 says "This folder already has UCM in it".** Something requested `install.php`
  already, which runs the install. That includes a readiness loop. Wait on a missing page
  instead, as the S12 block does, or add `?again=1`.
- **"Class ZipArchive not found" in the server's log.** The image was built without the
  zip extension. Redo setup step 3.
- **A page is a blank HTTP 500.** Error display is off in the code. Run
  `ut_last_error <container>` to see the error. If the client does it before the wizard,
  check it was started with `UCM_DB_NAME` (setup step 5).
- **`ut_offer` printed `offers: 0` with "the server sent an empty answer".** That is the
  server's normal "nothing to update" answer. An error looks different: "not an update
  server response" and the first bytes of what came back.
- **The installed version on the page or in `ut_version` did not move after an upgrade.**
  The stale code finding. Look at the `Current Version` in the "Processing update" line,
  then use Run Manual Upgrades. Release A should include dtbaker/ucm#29; if it does and
  this still happens, the host is refusing `opcache_invalidate()` and the faq row should
  say `not finished`.
- **`unzip` cannot find `ucm-release-b.zip`, but `zip` reported nothing.** A `ZIP` variable
  is set in the shell, and `zip` used it as options. `unset ZIP`.
- **Playwright never returns.** Its HTML reporter is serving a failure report. Use
  `--reporter=list`, as setup step 5 does.
- **The browser is suddenly logged out.** `ut_reset` puts back the sessions in `temp/`.
  Log in again.

- **`ut_offer` says `offers: 0` straight after you edited `$SERVER`.** The server's opcache
  has not revalidated yet. Wait 3 seconds and ask again.
- **The old installer's check `ut_last_error ... $REAL_HOST` prints `no PHP error` in zsh.**
  zsh does not split `$REAL_HOST`, so docker got one odd argument. Use `${=REAL_HOST}`.
- **A 4.0.0 client says "Please enter your license code before doing an upgrade."** It is the
  old client's gate. Put any code of six characters or more in the box (S13).
- **A new file in `$SERVER` is not offered (S4).** Add it to `$SERVER/includes/release-files.txt`:
  the server lists a built release from that file, not from the disk.
- **`composer install` in S13 cannot reach packagist.** It is the host's network; the test
  containers never need it. Retry, or copy a `vendor/` from an earlier run.
- **The rate limit loop never trips.** The counter is per fixed ten minute window; let the loop
  run past 1,200 requests, or run it again.

## Change log

- 2026-10-17: written. Helper, ordering rules, licence gate and server answers verified
  individually; full run not yet done.
- 2026-10-17: added the manual unzip install (setup step 2), a full unzip over the top
  (S11) and the old one-file installer (S12), with a stand-in for the lost `install.php`.
  S12 was run once in throwaway containers and fails as recorded under Findings. Moved the
  ports to 8110 to 8112 after 8094 and 8095 turned out to be in use by worktree stacks, and
  added the zip extension to the test image.
- 2026-10-17: first full run, top to bottom, Mode A with the gate present, PHP 8.4.18,
  release A from `docs/local-upgrade-test`. Fixes to the runbook: `UCM_WEB` found from the
  main checkout so it works from a git worktree; `$ZIP` renamed `$RELEASE_ZIP`, because
  `zip` read it as options and S11 silently wrote into the release zip; `db()` uses
  `MYSQL_PWD` and the MySQL wait goes over TCP, past the image's temporary socket-only
  server; the client gets `UCM_DB_NAME` so it reaches the wizard (product finding); the
  installer spec runs with `CI=1 --retries=0 --reporter=list` and must say `2 passed`,
  since it skips with exit 0; new `ut_last_error` helper; `ut_offer` (via
  `upgrade-test-tools.php summarise`) treats the server's empty "nothing to update" answer
  as `offers: 0` instead of an error; S2, S3 and S5 say what the stale code finding looks
  like and how to prove it; S2 expects the three `REFUSED` core lines and no reliable
  "Completed" button; S5 no longer expects a "check again" prompt; S6 says where the
  marker lands and which article the Upgrade screen opens; S7 and S8 say Check for
  Upgrades is the save button and the rate-limit loop takes three minutes; S11a's help
  line has no `Upgrading` text; S11b shows a real host's error with `ut_last_error`, since
  the test container's `UCM_DB_NAME` keeps the site up; S12 uses the helper. Every S12
  finding still holds. New product findings are under Findings.
- 2026-10-17: re-ran S2, S3 (four times) and the S5 order check, Mode B, release A from
  `fix/updater-opcache-invalidate` (dtbaker/ucm#29), default `opcache.revalidate_freq=2`.
  All passed, so the stale code notes in S2, S3, S5 and Findings now describe the fix and
  what a build without it looks like. Also checked the fix's backstop by setting
  `opcache.restrict_api=/nonexistent` on the client: the faq row says `not finished` and
  the database is left alone until Run Manual Upgrades.
- 2026-11-02: second full run, Mode A, against the built 4.0.1 zip (`build/ucm-codecanyon.zip`,
  not rebuilt; point `RELEASE_ZIP` at it and skip step 1), PHP 8.4.18, plus install and S1,
  S2 on PHP 8.2.35. Adjusted for the licence code being optional: the client no longer sends
  `installation_code`, `client_ip` or `installation_location`, an empty code no longer stops
  an upgrade, so S8 is rewritten (every row gives the same offers; a request log in the API
  copy proves what is sent), step 7 no longer sets a code, `ut_offer` sends none, and the
  local API copy has its gate switched off (a gated copy is kept for the S7 escaping check),
  since a client with no code is paused by the gate. Other fixes to the text: step 5 drops
  `UCM_SECRET` from the client container so the wizard's random secret is what S14 tests;
  `REAL_HOST` is shown as `${=REAL_HOST}` for zsh and the result changed from a blank 500
  to the setup wizard; `ut_last_error` counts only fatal kinds (it was reporting a silenced
  session warning); step 4 adds a `ucm-ut-mail` mailhog on :8026; step 3 shows the PHP 8.2
  images; every server edit is followed by `sleep 3`; S2 to S6 numbers updated (faq 2.151 ->
  2.152, `4.0.1 -> 4.0.2` for the release bump, corefiles ride along with every plugin
  update, no `REFUSED` lines, config last); S4's new file must also be added to
  `includes/release-files.txt`; S7's rate-limit loop runs until it trips instead of a fixed
  601 (fixed windows); S7's escaping check uses the gated copy; S10 notes three warnings in
  `ucm-web`'s endpoints; S11b and S12 expect the new behaviour (S12 now passes: 4,125 files,
  wizard walks). New: S13 (a 4.0.0 install updated in-app and by unzip: old bundled dompdf,
  PHPMailer and stripe-php gone, `vendor/` delivered, PDF and email work), S14 (secret
  warning), S15 (retired gateways, with a 500-page crawl), S16 (PHP 8.2), and
  `scripts/upgrade-test-drive.mjs`, which drives the Upgrade screen, makes PDFs and sends
  email without a browser. Findings updated: the secret warning never shows after an in-app
  update, `stripe_form.php` is left behind, and the `ucm-web` gate would pause every 4.0.1
  client.
