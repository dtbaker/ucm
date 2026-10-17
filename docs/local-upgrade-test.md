# Local upgrade test

A repeatable test of the whole update path, run entirely on one machine: build a release,
install it in a fresh container, publish a newer version from a local update server, and
check that the installation finds the update, installs it in the right order, migrates its
database, shows the right messages, and handles licence codes the way it should.

It covers all three ways the product gets onto a server, and both ways it gets updated:

| | Method | Where |
| --- | --- | --- |
| Install | Unzip the CodeCanyon zip by hand, then run the setup wizard | Setup step 5 |
| Install | The old one-file `install.php`, which fetches everything from the update server as a zip | [S12](#s12-the-old-one-file-installer) |
| Update | The in-app updater, Settings, then Upgrade | S2 to S8 |
| Update | Copy one plugin folder by hand, then Run Manual Upgrades | [S9](#s9-the-manual-upgrade-path) |
| Update | Unzip a whole new release over the top, then Run Manual Upgrades | [S11](#s11-a-full-manual-unzip-over-the-top) |

Written for an agent or a person to follow from top to bottom. Every step says what to run
and what you should see. Record what you actually saw in the results log at the end.

> **Status.** Written 2026-10-17 and run from top to bottom once, in Mode A, the same day.
> The steps below are the corrected ones. Where the product misbehaves, the step says what
> you will see today and points at [Findings](#findings). If a step is wrong for you, fix it
> and add a line to the change log at the bottom.

## What this covers

| Area | Scenario | Section |
| --- | --- | --- |
| Baseline | A fresh install of the release, with nothing newer on offer | [S1](#s1-baseline-nothing-to-update) |
| Detection | One plugin bumped on the server is offered, with its changelog line | [S2](#s2-one-plugin-bumped) |
| Install | The files arrive byte for byte, the version is recorded, a re-check is clean | [S2](#s2-one-plugin-bumped) |
| Database | A plugin's upgrade SQL runs and changes the schema | [S3](#s3-a-database-migration) |
| New files | A file that is new on the server is created on the client | [S4](#s4-a-new-file-and-a-core-file) |
| Core files | Core files ride along with any plugin update, and only then | [S4](#s4-a-new-file-and-a-core-file) |
| Order | Offers and installs run in the documented order, including the "behind" rules | [S5](#s5-install-order) |
| Help | Updated bundled help arrives with the help plugin and shows on screen | [S6](#s6-help-documentation) |
| Messages | Server messages, the changelog text and progress text show, escaped | [S7](#s7-messages-on-the-upgrade-page) |
| Licences | No code, a short code, a wrong code, several codes, the right code | [S8](#s8-licence-codes) |
| Manual path | Files copied by hand, then Run Manual Upgrades | [S9](#s9-the-manual-upgrade-path) |
| Legacy | The old API endpoints still answer without errors | [S10](#s10-legacy-api-endpoints) |
| Manual upgrade | A whole release unzipped over the top keeps its settings and upgrades cleanly | [S11](#s11-a-full-manual-unzip-over-the-top) |
| Old installer | `install.php` fetches the product as a zip and the result starts | [S12](#s12-the-old-one-file-installer) |

**Known failures, as of 2026-10-17.** See [Findings](#findings) for the detail of each.

- S12 fails. The zip the update server builds for the old installer leaves out files the
  product needs and replaces `includes/config.php` with a pre-rewrite copy.
- Fixed by dtbaker/ucm#29: the in-app updater used to run a plugin's database step
  against the old copy of its code, so in S2, S3, S5 and S6 the new version was often not
  recorded and the upgrade SQL often did not run, while the page still said "Success!
  Everything worked." A release A built without that change still does this.
- A fresh unzip with no `config.local.php` returns a blank HTTP 500 instead of the setup
  wizard unless the fallback database details happen to work. Setup step 5 works round it
  the same way `docker-compose.yml` does.

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
  with the hash of a throwaway code. Nothing here needs the real one.
- **Leave the development stack alone.** Different container names, a different network,
  different ports. Nothing here touches `ucm-app`, `ucm-db` or port 8090. Worktree stacks
  on the same machine take ports in the 8090s too, so the setup checks its ports are free
  before it starts anything.
- **A licence failure is not a bug to fix with enforcement.** The product deliberately
  does no licence checking. A code is a thank-you. The gate in the API is a temporary
  switch for testing a release. Record what happens; do not add checks.

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
export UT_CODE=local-upgrade-test-code      # throwaway licence code, lower case
export UT_NET=172.31.250.0/24 UT_API_IP=172.31.250.10 UT_CLIENT_IP=172.31.250.20 UT_LEGACY_IP=172.31.250.30
export UT_CLIENT_PORT=8110 UT_API_PORT=8111 UT_LEGACY_PORT=8112
export SERVER="$UT/server-www/demo.ultimateclientmanager.com/public_html"
export API="$UT/server-www/api.ultimateclientmanager.com/public_html"
export CLIENT="$UT/client"
export LEGACY="$UT/legacy"
export RELEASE_ZIP="$UT/src/build/ucm-codecanyon.zip"   # not $ZIP: the zip command reads that as options
export TOOLS="$UCM/scripts/upgrade-test-tools.php"
db() { docker exec -i -e MYSQL_PWD=root ucm-ut-db mysql -uroot "$@"; }   # MYSQL_PWD: no password warning
# What a real host without config.local.php falls back to (includes/config.php outside Docker).
export REAL_HOST="-e UCM_CONFIG_LOCAL=/nonexistent -e UCM_DB_SERVER=localhost -e UCM_DB_USER=username -e UCM_DB_PASS=password -e UCM_DB_NAME=ucm"
# A page that fails here is usually a blank HTTP 500: the code turns error display off.
# This runs index.php in the container with a shutdown hook and prints the last PHP error.
ut_last_error() {   # ut_last_error <container> [-e NAME=value ...]
  local c=$1; shift
  docker exec "$c" sh -c 'printf "%s\n" "<?php register_shutdown_function(function(){ \$e = error_get_last(); if (\$e) fwrite(STDERR, \"LAST ERROR: \" . \$e[\"message\"] . \" at \" . \$e[\"file\"] . \":\" . \$e[\"line\"] . \"\\n\"); });" > /tmp/last-error.php'
  docker exec -w /var/www/html "$@" -e REQUEST_URI=/ -e HTTP_HOST=localhost -e SCRIPT_NAME=/index.php "$c" \
    php -d auto_prepend_file=/tmp/last-error.php index.php 2>&1 >/dev/null | grep -m1 'LAST ERROR' || echo 'no PHP error'
}
```

The commands below assume one shell session. If your tool starts a fresh shell for each
command, put the block above (and the helpers from step 8, and `DOCROOT` and `UPGRADE_URL`
from steps 6 and 7) in a file and source it every time.

Never export a variable called `ZIP` or `UNZIP` here. Info-ZIP's `zip` reads `$ZIP` as
default options, so with `ZIP` set to the release, `zip -qr new.zip ...` quietly adds files
to the release zip instead and exits 0.

Check nothing is in the way before starting:

```bash
for p in $UT_CLIENT_PORT $UT_API_PORT $UT_LEGACY_PORT; do
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

Expect `OK: ... shaped the way CodeCanyon wants.` (4,353 entries on 2026-10-17). The file a
buyer downloads is `$RELEASE_ZIP`.

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

Mode A: copy the API and point its licence gate at the throwaway code.

```bash
rsync -a --delete --exclude php_errorlog --exclude 'latest_updates*.html' "$UCM_WEB/api/" "$API/"
if grep -q 'ucm_testing_code_hash' "$API/upgrade.php"; then
  hash=$(printf %s "$UT_CODE" | shasum -a 256 | cut -d' ' -f1)
  perl -pi -e "s/(\\\$ucm_testing_code_hash\s*=\s*')[0-9a-f]+(')/\${1}$hash\${2}/" "$API/upgrade.php"
  grep -q "$hash" "$API/upgrade.php" && echo "gate now accepts the local code"
else
  echo "no licence gate in this copy of upgrade.php - every code is served"
fi
```

Write down which of those two lines you got. It decides what S8 expects. The
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

If the Dockerfile has an `ARG PHP_VERSION` line, test the lowest supported PHP as well:
add `--build-arg PHP_VERSION=8.2` to the first build. Run the whole procedure once per
version you care about.

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

### 5. Start the client and install release A

```bash
docker run -d --name ucm-ut-client --network ucm-ut --ip "$UT_CLIENT_IP" -p "$UT_CLIENT_PORT:80" \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT=/var/www/html -e UCM_SECRET=local-upgrade-test-client \
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

Why `UCM_DB_NAME`. Before the wizard has written `config.local.php`, every page connects
with the fallback details in `includes/config.php` (in Docker: host `db`, user `ucm`,
database `ucm`). There is no `ucm` database here, and on PHP 8.1 and later a failed
connection throws, so without the variable the client answers a blank HTTP 500 instead of
step 1 of the wizard. `docker-compose.yml` sets the same variable for its install-test
app. A real host has the same problem; see [Findings](#findings). To see what a real host
gets, ask for the first page with a real host's fallback values and no `config.local.php`:

```bash
ut_last_error ucm-ut-client $REAL_HOST     # in zsh: ${=REAL_HOST}
```

Today: `Uncaught mysqli_sql_exception: No such file or directory in .../includes/plugin_db/db.php:257`.
It works before or after the wizard, because `UCM_CONFIG_LOCAL` points the code away from
the real `config.local.php`.

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
  ('_installation_code','$UT_CODE'),
  ('help_allow_remote','0');"
```

`help_allow_remote` is off so the help panel shows the bundled article rather than trying
the live site first. Configuration is read from the database on every request, so the
change applies straight away. Confirm on the client: Settings, then Upgrade. The licence
box should hold `local-upgrade-test-code` and there should be no "ucm_upgrade_url setting
is incorrect" warning.

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
ut_offer() {   # what the server offers the client right now, in install order
  php "$TOOLS" plugins-json "$CLIENT" > "$UT/client-plugins.json"
  curl -s -X POST "http://localhost:$UT_API_PORT${UPGRADE_URL#http://$UT_API_IP}" \
    --data-urlencode "installation_code=${1-$UT_CODE}" \
    --data-urlencode "client_protocol=2" \
    --data-urlencode "libraries=$(shasum -a 256 "$CLIENT/vendor/composer/installed.json" 2>/dev/null | cut -d' ' -f1)" \
    --data-urlencode "current_plugins@$UT/client-plugins.json" | tee "$UT/offer.json" | php "$TOOLS" summarise
}
ut_version() { db ucm_install_test -N -e "SELECT val FROM ucm_config WHERE \`key\`='_plugin_version_$1'"; }
```

`ut_offer` asks the server the same question the client asks, from the host, so you can
see the offer without the browser. The upgrade page is still the real test: always check
the page as well. When the server has nothing to offer it sends an empty answer, not an
empty list (`upgrade_server.php` does this on purpose, as the old server did), and
`ut_offer` prints that as `offers: 0`.

To drive the client in a browser, log in at <http://localhost:8110> (`$UT_CLIENT_PORT`) as
`admin@example.com` / `password` (the installer's seeded account). Logging in with that
default password lands on the user's own page, which asks for a new password; ignore it.
The upgrade screen is `/?m[]=config&p[]=config_admin&m[]=config&p[]=config_upgrade`
(Settings, then Upgrade), then **Check for Upgrades**.

`ut_reset` puts back `temp/`, where the sessions live, so **every `ut_reset` logs the
browser out**. Log in again before each scenario's page checks.

Driving the page from a script: the rows of the offer are `tr[data-plugin]`, the button is
`#upgrade_start`, and the run has finished when `ucm.upgrade.all_done` is true.

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
   line reads `2.150 -> 2.151` (or whatever the current version is, plus 0.001), with the
   description `Update to plugin: faq ( local upgrade test marker (on <today>))`.
2. On the client, Check for Upgrades. The table shows the same three rows, all ticked. The
   description column shows the changelog text. The installed version shows as `2.15`
   rather than `2.150`: versions are numbers, so trailing zeros go.
3. Start the Upgrade, and wait until every row has its result: a list of files, then for
   `faq` the line `Processing update: faq - Current Version: ... all good` and
   "Success! Everything worked." The button only changes to "Completed, click here to
   continue" when the whole run takes longer than 4 seconds; a short run puts it back to
   "Start the Upgrade" (see [Findings](#findings)).

4. Check the files arrived exactly:

   ```bash
   diff -r "$SERVER/includes/plugin_faq" "$CLIENT/includes/plugin_faq" && echo identical
   ```

5. Check the version was recorded: `ut_version faq` should print the new version, and the
   `Processing update` line from step 3 should read `Current Version: 2.151` followed by
   `Upgrading plugin from version 2.15 to 2.151`.
   If it says `Current Version: 2.15`, the database step ran against the old copy of
   `faq.php` (the stale code finding, see [Findings](#findings)). On a build with
   dtbaker/ucm#29 the line then ends `not finished: ... still running the old copy` and
   "Some things failed", not "Success!". On a build without it, the page says "Success!"
   anyway and records nothing. Either way, record it, then run **Run Manual Upgrades**,
   which reads `Upgrading plugin from version 2.15 to 2.151` and records it.
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
   `Processing update: faq - Current Version: 2.151.... all good`, then
   `Upgrading plugin from version 2.15 to 2.151`, ending in "Success! Everything worked."

   Before dtbaker/ucm#29 this usually failed without saying so: the line read
   `Current Version: 2.15`, there was no `Upgrading plugin` line, the column was not
   there, and the page still said "Success! Everything worked." That is the stale code
   finding. With the fix it passes with the default opcache settings (4 runs out of 4 on
   2026-10-17). If you see it fail, record it.

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
   `Upgrading plugin from version 2.15 to 2.151`, which is the migration running now.
   Run it a second time: `all good` again, because the migration checks before it alters.
   The manual page's lines start `Checking plugin:`, not `Processing update:`.

### S4. A new file and a core file

**Core file alone.** Change a core file, bump nothing:

```bash
printf '\n/* local upgrade test: core file marker */\n' >> "$SERVER/css/styles.css"
```

1. `ut_offer`. Expect `offers: 0` (confirmed 2026-10-17), and Check for Upgrades on the
   page says "No upgrade available at this time." The server only adds the `corefiles` row
   when some plugin is being offered (`upgrade_server.php`, the `$do_upgrade` check before
   `corefiles`), so a change to a core file alone never reaches anyone. A release that
   changes a core file must bump a plugin too.

**Then with a plugin bump and a new file:**

```bash
php "$TOOLS" bump "$SERVER" faq "new file and core file"
printf 'local upgrade test\n' > "$SERVER/includes/plugin_faq/ut-new-file.txt"
```

2. `ut_offer`. `corefiles` is offered. In `$UT/offer.json`, check that its `files` list
   includes `css/styles.css`, and that the `faq` file list includes `ut-new-file.txt`.
3. Upgrade on the client.
4. Expect both on the client:

   ```bash
   cat "$CLIENT/includes/plugin_faq/ut-new-file.txt"
   tail -2 "$CLIENT/css/styles.css"
   ```

5. The progress text lists `File: includes/plugin_faq/ut-new-file.txt` with "this file has
   been installed successfully". The client prints those words for every file it writes,
   new or not, so step 4 is the real proof.

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
   `theme` were right and `help` was not); with it, 6 of 6 were right over two runs.

**A client behind on db.** Make the client look old, then bump something else:

```bash
php "$TOOLS" set-version "$CLIENT" db 2.12
php "$TOOLS" bump "$SERVER" faq "behind on db"
```

3. `ut_offer`. Expect `corefiles`, `database`, then `db`, then `faq`, and `check_again: yes`.
   (Checked against the real server code on 2026-10-17: exactly this.)
4. Upgrade on the client. `db` installs first. The page does **not** ask you to check
   again: the client never reads `check_again` (see [Findings](#findings)). Check for
   Upgrades yourself; it offers nothing more.

**A client behind on config.**

```bash
ut_reset
php "$TOOLS" set-version "$CLIENT" config 2.384
for p in config faq; do php "$TOOLS" bump "$SERVER" $p "behind on config"; done
```

5. `ut_offer`. Expect `config` ticked (`recommended=y`) and `faq` unticked
   (`recommended=n force=n`), with `check_again: yes`.
6. On the page, only `config`, `corefiles` and `database` are ticked. Upgrade with the
   defaults, then Check for Upgrades again. Now `faq` is offered ticked.

### S6. Help documentation

Help articles ship inside the help plugin, in `includes/plugin_help/content/`. An edited
article only reaches an install when the help plugin's version goes up.

**Edit without a bump.**

```bash
php "$TOOLS" help-marker "$SERVER" using/faq "LOCAL-UPGRADE-TEST help marker"
```

1. `ut_offer`. Expect `offers: 0`. The edit alone is not offered.

**Edit with a bump.**

```bash
php "$TOOLS" bump "$SERVER" help "bundled help update"
```

2. `ut_offer`. `help` is offered.
3. Before upgrading, open the FAQ screen on the client (`/?m[]=faq&p[]=faq_questions`) and
   open the help panel with the **?** button at the top right (`#header_help`). It opens a
   "Help" pop-up headed "FAQ", without the marker.
4. Upgrade on the client.
5. Open the same screen's help again. The marker paragraph,
   "LOCAL-UPGRADE-TEST help marker", is now the first paragraph of the article body,
   under the "FAQ" heading and the one-line summary (those two come from separate fields).
   Also check the file:
   `grep -c LOCAL-UPGRADE-TEST "$CLIENT/includes/plugin_help/content/using--faq.json"` prints `1`.
6. Open the help on the Upgrade screen itself and record which article it shows. Today it
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
3. **A server message, escaped.** Mode A only. In the local API copy, make the paused
   message contain markup:

   ```bash
   perl -pi -e "s/'Updates are paused for a short while/'<b>UT-ESCAPE<\/b> & <i>check<\/i> Updates are paused for a short while/" "$API/upgrade.php"
   ```

   On the client (Settings, Upgrade), replace the code in the box with `wrong-code-123`
   and press Check for Upgrades. There is no separate save button: that button saves the
   box and then checks. The message shows with the tags as visible text, not as bold and
   italic, followed by "No upgrade available at this time." Put the line back afterwards
   by re-running setup step 2's API copy and gate commands (they print
   "gate now accepts the local code" again).
4. **Rate limiting.** The server allows 600 requests per address per 10 minutes.

   ```bash
   ut_reset
   for i in $(seq 1 601); do ut_offer >/dev/null; done; ut_offer | sed -n 2p
   ```

   The loop takes about three minutes. Expect
   `message: Too many update requests from this address. Please try again shortly.`
   Then log in and Check for Upgrades on the client: it is a different address (the
   client's private IP), so it is still served, and `docker logs ucm-ut-api` shows a `200`
   for `172.31.250.20`. Clear the counter with `ut_reset`.

### S8. Licence codes

Each check is a code typed in the box on Settings, then Upgrade, then Check for Upgrades
(which saves the box first; there is no separate save). Do the rows in order without a
`ut_reset` between them, so you stay logged in. Bump something first so there is an
update to withhold:
`php "$TOOLS" bump "$SERVER" faq "licence check"`. Before each check, take a fingerprint
of the client so you can prove nothing was written:

```bash
fingerprint() { ( cd "$CLIENT" && find . -path ./temp -prune -o -type f -print0 | sort -z | xargs -0 shasum ) | shasum; }
before=$(fingerprint)
```

After each check that should not install anything, `[ "$(fingerprint)" = "$before" ] && echo unchanged`.

| Code saved | Mode | Expect |
| --- | --- | --- |
| (empty) | both | "Please enter your license code before doing an upgrade." on a bare page with no menu (go back with the browser). Nothing offered. |
| `abc` | both | Not saved: codes of five characters or fewer are dropped (only `free` is kept). Same as empty. |
| `wrong-code-123` | A, gate present | "Updates are paused for a short while as a new release is prepared. Please check again later. No upgrade available at this time." Nothing offered, client unchanged. |
| `wrong-code-123` | A without gate, or B | Offered as normal. No code is checked. |
| `wrong-code-123` and `local-upgrade-test-code` | A | Served. Add the second box with **+**. The codes are sent joined with `\|` and the gate accepts any one. |
| `LOCAL-UPGRADE-TEST-CODE` | A | Served. The gate lower-cases codes before comparing. |
| `local-upgrade-test-code` | both | Served. |

Also record whether "The above license code is for ... thanks for purchasing!" appears
under the box. It only appears when the server confirms a code, and the server currently
always answers with an empty `licence_codes` list, so expect it not to appear. `ut_offer`
prints that list as `licences: []`.

The same checks from the command line, Mode A: `ut_offer wrong-code-123` prints the paused
message and `offers: 0`; `ut_offer ''` the same.

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
   `Checking plugin: faq - Current Version: 2.151....`, then
   `Upgrading plugin from version 2.15 to 2.151`, then `all good`, and the page ends
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
   is behind the files before you run the upgrade. On 2026-10-17 it showed none.
4. Settings, then Upgrade, then **Run Manual Upgrades**. The faq line reads
   `Upgrading plugin from version 2.15 to 2.151`, then `all good`. The help line is only
   `Checking plugin: help - Current Version: 2.141.... all good`: help has no database
   tables, so it records the new version without printing an `Upgrading` line. The page
   ends "Success! Everything worked." with no `failed`.
5. `ut_version faq` and `ut_version help` print the new versions. The `ut_unzip` column
   exists: `db ucm_install_test -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_unzip'"`.
6. The FAQ screen's help panel starts with "LOCAL-UPGRADE-TEST unzip marker".
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
   host gets: `Uncaught mysqli_sql_exception: No such file or directory in
   .../includes/plugin_db/db.php:257`, which the browser shows as a blank HTTP 500 with no
   message (see [Findings](#findings)). Record the exact text. With the Docker fallback
   instead (`ut_last_error ucm-ut-client -e UCM_CONFIG_LOCAL=/nonexistent -e UCM_DB_NAME=ucm`)
   it reads `Access denied for user 'ucm'@'%' to database 'ucm'`.
3. Follow the guide's fix: put the details in `config.local.php`.

   ```bash
   { echo '<?php'; cat "$UT/creds.txt"; } > "$CLIENT/includes/config.local.php"; sleep 3
   curl -s -o /dev/null -w 'after: %{http_code}\n' "http://localhost:$UT_CLIENT_PORT/"
   ut_last_error ucm-ut-client -e UCM_DB_SERVER=localhost -e UCM_DB_USER=username -e UCM_DB_PASS=password -e UCM_DB_NAME=ucm
   ```

   Expect `after: 200` and `no PHP error`: with `config.local.php` back, even a real host's
   fallback values are never used. Then log in and run S11a steps 4 to 7.

### S12. The old one-file installer

Before the CodeCanyon zip carried the whole product, a buyer could upload one
`install.php` to an empty folder and open it. It asked the update server for everything as
a zip (`return_as_zip` and `return_base_system_in_zip`), unpacked it beside itself, and
sent the buyer to the setup wizard. The original script is no longer in either repository,
so `scripts/upgrade-test-legacy-install.php` stands in for it. It makes the same request
that `ucm-web/api/download_zip.php` still makes. If you have a copy of the original, use
it instead and say so in the results.

**Expected today: this fails.** See [Findings](#findings). Run it anyway; the point is to
see it go green once the zip is fixed.

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
   2026-10-17 it read `Unpacked 3315 entries` (files and folders): 2,844 files, against
   3,640 in the release.
2. **A wrong code.** Mode A with the gate present. Ask for the zip with a code the gate
   does not know:

   ```bash
   curl -s -X POST "http://localhost:$UT_API_PORT${UPGRADE_URL#http://$UT_API_IP}" \
     -d installation_code=wrong-code-123 -d current_version=0 -d get_file_contents=1 \
     -d return_as_zip=1 -d return_base_system_in_zip=1 | head -c 300; echo
   ```

   Expect the JSON "Updates are paused" answer instead of a zip. The stand-in reports the
   same thing as "The server did not send a zip", and unpacks nothing.
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
   Today, expect the list in [Findings](#findings).
4. **Does it start?**

   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:$UT_LEGACY_PORT/"
   ```

   Passing is `200` with step 1 of the setup wizard. Today it is `500`. The zip's
   `config.php` switches error reporting off at runtime, so use the shutdown hook helper
   from Setup to see why:

   ```bash
   ut_last_error ucm-ut-legacy
   ```

   Today: `Uncaught Error: Failed opening required 'includes/class.libraries.php' ... in /var/www/html/init.php:130`.
5. **Once it starts, walk the wizard.** Skip this while step 4 fails; there is nothing to
   walk. This reuses the client's database, so it wipes it and puts it back afterwards.
   The legacy container has no `UCM_DB_NAME`, which is the point: the zip's own
   `config.php` must get it to the wizard.

   ```bash
   db -e "DROP DATABASE ucm_install_test; CREATE DATABASE ucm_install_test; GRANT ALL ON ucm_install_test.* TO 'ucm'@'%';"
   CI=1 UCM_INSTALL_BASE_URL="http://localhost:$UT_LEGACY_PORT" npx playwright test tests/e2e/install.spec.ts \
     --workers=1 --retries=0 --reporter=list > "$UT/legacy-install.log" 2>&1; echo "exit $?"
   ut_reset
   ```

   Expect `exit 0` and `2 passed`. The second test logs in, which proves the database
   details the wizard saved actually reach the app.

## Findings

Product behaviour this runbook turned up. None of it is fixed here: the runbook records
it, and each step that meets it says what you will see today.

### Found in the first full run (2026-10-17)

| What | Where | Effect |
| --- | --- | --- |
| **Fixed by dtbaker/ucm#29. The in-app updater runs each plugin's database step on stale code.** The page writes a plugin's files in one request and runs its database step in the next. PHP's opcache only re-reads a changed file every `opcache.revalidate_freq` seconds (2 by default, often more on shared hosts), and nothing calls `opcache_invalidate()` after a write, so the second request usually still has the old class. | `class.upgrade.php`, `complete_plugin_installation()` after `install_plugin_update_files()` | The new version is not recorded and the plugin's upgrade SQL does not run, while the page says `all good` and "Success! Everything worked." Seen in 7 of 9 plugin steps on 2026-10-17 (S2, S3, S5, S6). Proven by S3 passing with `opcache.revalidate_freq=0`. Run Manual Upgrades repairs it afterwards. The fix invalidates opcache for every file the updater writes; where the host refuses that (`opcache.restrict_api`), the database step now says `not finished` and changes nothing instead of reporting success. |
| **A fresh install with no `config.local.php` is a blank HTTP 500, not the setup wizard,** unless the fallback database details in `includes/config.php` happen to connect. On PHP 8.1 and later `mysqli_connect()` throws on failure, and nothing catches it or calls `mysqli_report()`, so the "Connect failed" message in `db_connect()` is never reached. | `init.php:206` `is_installed()` → `includes/plugin_db/db.php:257` | The main install route (unzip and open the site) does not reach the wizard on a normal host, whose fallback is `localhost` / `username` / `ucm`. The same blank 500 is what a site that loses its `config.local.php` shows (S11b). The dev stack and CI never see it, because `docker-compose.yml` sets `UCM_DB_NAME`. |
| **Fixed by the 3.x upgrade fix.** The `corefiles` list names three files the server never sends | `upgrade_server.php`, the `corefiles` list | Every update ended the core row with three `REFUSED: the server sent no contents for this file` lines. `js/jquery-1.8.3.min.js` and `js/jquery-ui-1.9.2.custom.min.js` are not in the release; `temp/.htaccess` is skipped by `is_ignored_path()` (`/temp/`), so an updated install never gets a newer `temp/.htaccess` either. |
| A core file changed with no plugin bump is never offered | `upgrade_server.php`, the `$do_upgrade` check before `corefiles` | Answers the S4 question: no. A release that changes only core files reaches nobody through the updater. |
| The Upgrade screen's help opens "The plugin system" | `includes/plugin_config/config.help.json`, generated from `ucm-web` `site/src/content/docs/developers/plugins.md` frontmatter | Answers the S6 question: it shows `developers/plugins`; it should show `install/upgrading` ("Upgrading from 2.x"). |
| The client ignores `check_again` | no reference to it outside `upgrade_server.php` | A client that is behind gets part of its updates and is never told there are more. It has to think of pressing Check for Upgrades again. |
| "Completed, click here to continue" is hidden on a short run | `includes/plugin_form/js/form.js`, `dtbaker_loading_button()` restores the button text after 4 seconds | A run under 4 seconds ends with the button reading "Start the Upgrade" again, as if nothing happened. |
| An empty licence code ends on a bare page | `config_upgrade.php`, `exit` after "Please enter your license code before doing an upgrade." | No menu or link back. |

### The old installer's zip (S12)

Confirmed on 2026-10-17 by building the old installer's zip from the real update server
code and comparing it with a release built from `2026-revive-2`, and confirmed again in the
full run. Each is a reason S12 fails. The zip is assembled in
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

Copy this into the pull request, or a note, for each run.

```
Local upgrade test - <date> - <who>
Release A: <REF> @ <commit>        Mode: A | B        Image PHP: <version>
Licence gate in the API copy: present | absent

S1 baseline                     pass | fail | n/a   <notes>
S2 one plugin bumped            ...
S3 database migration           ...
S4 core file alone / new file   ...  (record what the core-file-alone offer was)
S5 order: name / db / config    ...
S6 help: no bump / bump / upgrade-screen article  ...
S7 messages: changelog / progress / escaping / rate limit  ...
S8 licences (one line per row of the table)  ...
S9 manual path                  ...
S10 legacy endpoints            ...
S11a unzip over a current install: settings / uploads / manual upgrade / help  ...
S11b unzip over a pre-rewrite install: error text / fix  ...
S12 old installer: download / wrong code / file diff / starts / wizard  ...
Install by manual unzip (setup step 2 and 5)  ...  (and the REAL_HOST line: blank 500 or wizard?)
Stale code in the in-app updater: <n> of <m> "Processing update" lines showed the old version
UpgradeIntegrityTest            ./vendor/bin/phpunit --filter UpgradeIntegrity  -> <result>

Anything surprising:
```

## Teardown

```bash
docker rm -f ucm-ut-client ucm-ut-api ucm-ut-legacy ucm-ut-db
docker network rm ucm-ut
docker rmi ucm-ut-php ucm-ut-php-base
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
