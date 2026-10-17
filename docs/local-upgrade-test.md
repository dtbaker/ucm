# Local upgrade test

A repeatable test of the whole update path, run entirely on one machine: build a release,
install it in a fresh container, publish a newer version from a local update server, and
check that the installation finds the update, installs it in the right order, migrates its
database, shows the right messages, and handles licence codes the way it should.

Written for an agent or a person to follow from top to bottom. Every step says what to run
and what you should see. Record what you actually saw in the results log at the end.

> **Status.** Written 2026-10-17. The helper script, the version and ordering rules, the
> licence gate and the server's answers were each checked on their own against the real
> code. The full run from top to bottom has not been done yet. The first person to run it
> should fix any step that is wrong and add a line to the change log at the bottom.

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
  different ports. Nothing here touches `ucm-app`, `ucm-db` or port 8090.
- **A licence failure is not a bug to fix with enforcement.** The product deliberately
  does no licence checking. A code is a thank-you. The gate in the API is a temporary
  switch for testing a release. Record what happens; do not add checks.

## Prerequisites

- Docker, with the daemon running.
- PHP 8.2 or newer on the host, plus `composer`, `git`, `rsync`, `curl`, `shasum`, `perl`.
- Node and the Playwright browsers, for the installer walk (`npm ci && npx playwright install chromium`).
- This repository. For Mode A, a checkout of `ucm-web` beside it.

## Setup

Run every block from the root of this repository. The variables below are used throughout.

```bash
export UCM=$(git rev-parse --show-toplevel)
export UCM_WEB="$UCM/../ucm-web"            # Mode A only
export UT="$HOME/ucm-upgrade-test"          # scratch space; deleted at the end
export REF=2026-revive-2                    # the branch or tag to build release A from
export UT_CODE=local-upgrade-test-code      # throwaway licence code, lower case
export UT_NET=172.31.250.0/24 UT_API_IP=172.31.250.10 UT_CLIENT_IP=172.31.250.20
export SERVER="$UT/server-www/demo.ultimateclientmanager.com/public_html"
export API="$UT/server-www/api.ultimateclientmanager.com/public_html"
export CLIENT="$UT/client"
export TOOLS="$UCM/scripts/upgrade-test-tools.php"
db() { docker exec -i ucm-ut-db mysql -uroot -proot "$@"; }
```

### 1. Build release A

The build uses `git archive`, so it contains only committed files, exactly as a buyer gets
them. Build in a throwaway clone so the working copy is never touched.

```bash
rm -rf "$UT" && mkdir -p "$UT"
git clone --quiet --shared --branch "$REF" "$UCM" "$UT/src"
( cd "$UT/src" && ./scripts/build-release.sh --verify )
```

Expect `OK: ... shaped the way CodeCanyon wants.` The product is in
`$UT/src/build/Main Files/`.

### 2. Lay out the two trees

```bash
mkdir -p "$CLIENT" "$SERVER" "$API"
rsync -a "$UT/src/build/Main Files/" "$CLIENT/"
rsync -a "$UT/src/build/Main Files/" "$SERVER/"
```

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

```bash
docker build -t ucm-ut-php "$UCM"
```

If the Dockerfile has an `ARG PHP_VERSION` line, test the lowest supported PHP as well:
`docker build --build-arg PHP_VERSION=8.2 -t ucm-ut-php "$UCM"`. Run the whole procedure
once per version you care about.

### 4. Start the database

```bash
docker network create --subnet "$UT_NET" ucm-ut
docker run -d --name ucm-ut-db --network ucm-ut --network-alias db \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=ucm_install_test \
  -e MYSQL_USER=ucm -e MYSQL_PASSWORD=ucm \
  mysql:8.0 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
for i in $(seq 1 60); do db -e 'SELECT 1' >/dev/null 2>&1 && break; sleep 2; done; db -e 'SELECT VERSION()'
```

The alias `db` and the database `ucm_install_test` match what `tests/e2e/install.spec.ts`
types into the installer, so that spec can walk the wizard unchanged. Retry the query
rather than trusting the first answer: MySQL restarts once on a new volume.

### 5. Start the client and install release A

```bash
docker run -d --name ucm-ut-client --network ucm-ut --ip "$UT_CLIENT_IP" -p 8094:80 \
  --add-host ultimateclientmanager.com:127.0.0.1 \
  --add-host www.ultimateclientmanager.com:127.0.0.1 \
  --add-host api.ultimateclientmanager.com:127.0.0.1 \
  -e APACHE_DOCUMENT_ROOT=/var/www/html -e UCM_SECRET=local-upgrade-test-client \
  -v "$CLIENT:/var/www/html" ucm-ut-php
for i in $(seq 1 30); do curl -fsS -o /dev/null http://localhost:8094/ && break; sleep 1; done
UCM_INSTALL_BASE_URL=http://localhost:8094 npx playwright test tests/e2e/install.spec.ts --workers=1 > "$UT/install.log" 2>&1; echo "exit $?"
```

Expect `exit 0` and `2 passed` in `$UT/install.log`. If it says `skipped`, the wizard was
already walked; restart from step 4 with a fresh database. The install writes
`$CLIENT/includes/config.local.php`.

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
docker run -d --name ucm-ut-api --network ucm-ut --ip "$UT_API_IP" -p 8095:80 \
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
  rsync -a --delete "$UT/src/build/Main Files/" "$SERVER/"
  docker exec ucm-ut-api rm -rf /tmp/ucm-upgrade-rate
  sleep 3   # opcache revalidates every 2 seconds
}
ut_offer() {   # what the server offers the client right now, in install order
  php "$TOOLS" plugins-json "$CLIENT" > "$UT/client-plugins.json"
  curl -s -X POST "http://localhost:8095${UPGRADE_URL#http://$UT_API_IP}" \
    --data-urlencode "installation_code=${1-$UT_CODE}" \
    --data-urlencode "client_protocol=2" \
    --data-urlencode "current_plugins@$UT/client-plugins.json" | tee "$UT/offer.json" | php "$TOOLS" summarise
}
ut_version() { db ucm_install_test -N -e "SELECT val FROM ucm_config WHERE \`key\`='_plugin_version_$1'"; }
```

`ut_offer` asks the server the same question the client asks, from the host, so you can
see the offer without the browser. The upgrade page is still the real test: always check
the page as well.

To drive the client in a browser, log in at <http://localhost:8094> as
`admin@example.com` / `password` (the installer's seeded account). The upgrade screen is
Settings, then Upgrade, then **Check for Upgrades**.

## Scenarios

Run `ut_reset` before each one. Each scenario lists what to change on the server, what to
do, and what you should see.

### S1. Baseline: nothing to update

No changes.

1. `ut_offer`
2. On the client, Check for Upgrades.

Expect `offers: 0`, and the page shows no table of updates. A client identical to its
server has nothing to install.

### S2. One plugin bumped

```bash
php "$TOOLS" bump "$SERVER" faq "local upgrade test marker"
```

1. `ut_offer`. Expect, in this order: `faq`, then `corefiles`, then `database`. The `faq`
   line reads `2.150 -> 2.151` (or whatever the current version is, plus 0.001), with the
   description `Update to plugin: faq ( local upgrade test marker (on <today>))`.
2. On the client, Check for Upgrades. The table shows the same three rows, all ticked. The
   description column shows the changelog text.
3. Start the Upgrade, and wait for "Completed, click here to continue".
4. Check the files arrived exactly:

   ```bash
   diff -r "$SERVER/includes/plugin_faq" "$CLIENT/includes/plugin_faq" && echo identical
   ```

5. Check the version was recorded: `ut_version faq` prints the new version.
6. `ut_offer` again. Expect `offers: 0`. Check for Upgrades on the page shows nothing.

### S3. A database migration

```bash
php "$TOOLS" bump "$SERVER" faq "adds a column"
php "$TOOLS" add-column "$SERVER" faq faq ut_marker
```

`add-column` adds an `ALTER TABLE` to what `module_faq::get_upgrade_sql()` returns, so the
column goes in through the real upgrade path.

1. Check the column is not there yet: `db ucm_install_test -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_marker'"` prints nothing.
2. Upgrade on the client, as in S2.
3. The same query now prints the `ut_marker` row. The progress text shows
   `Processing update: faq - Current Version: ... all good`, ending in
   "Success! Everything worked."
4. Run Settings, then Upgrade, then **Run Manual Upgrades**. It must report `all good`
   again with no error: the migration checks before it alters, so it is safe to repeat.

### S4. A new file and a core file

**Core file alone.** Change a core file, bump nothing:

```bash
printf '\n/* local upgrade test: core file marker */\n' >> "$SERVER/css/styles.css"
```

1. `ut_offer`. Record what you see. Reading the code, a core file is only sent alongside a
   plugin update, so this should be `offers: 0`. If it is, a change to a core file alone
   never reaches anyone, which is worth knowing when planning a release.

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

5. The progress text says the new file "has been **installed** successfully".

### S5. Install order

The rules, from `upgrade_server.php`: offers are sorted by plugin name. If the client is
behind on `db` (below 2.13) or `language` (below 2.179), then `db`, `language` and `session`
are moved to the front and `check_again` is set. If it is behind on `backup` (below 2.111)
or `config` (below 2.385), only `config`, `backup`, `core`, `theme`, `form` and `db` stay
ticked; everything else is offered unticked. `corefiles` and `database` always come last.
The page installs ticked rows top to bottom, then runs each plugin's database step in the
same order.

**Name order.**

```bash
for p in theme help faq; do php "$TOOLS" bump "$SERVER" $p "order check"; done
```

1. `ut_offer`. Expect `faq`, `help`, `theme`, `corefiles`, `database`.
2. Upgrade on the client. The "Processing update" lines appear in that same order.

**A client behind on db.** Make the client look old, then bump something else:

```bash
php "$TOOLS" set-version "$CLIENT" db 2.12
php "$TOOLS" bump "$SERVER" faq "behind on db"
```

3. `ut_offer`. Expect `db` first, then `faq`, `corefiles`, `database`, and `check_again: yes`.
   (Checked against the real server code on 2026-10-17: exactly this.)
4. Upgrade on the client. `db` installs first. Afterwards the page asks you to check again;
   doing so offers nothing more.

**A client behind on config.**

```bash
ut_reset
php "$TOOLS" set-version "$CLIENT" config 2.384
for p in config faq; do php "$TOOLS" bump "$SERVER" $p "behind on config"; done
```

5. `ut_offer`. Expect `config` ticked (`recommended=y`) and `faq` unticked
   (`recommended=n force=n`), with `check_again: yes`.
6. On the page, only `config` (and the core rows) are ticked. Upgrade with the defaults,
   then check again. Now `faq` is offered ticked.

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
   open the help panel with the **?** button. It does not show the marker.
4. Upgrade on the client.
5. Open the same screen's help again. The article now starts with
   "LOCAL-UPGRADE-TEST help marker". Also check the file:
   `grep -c LOCAL-UPGRADE-TEST "$CLIENT/includes/plugin_help/content/using--faq.json"`.
6. Open the help on the Upgrade screen itself and record which article it shows. Today
   `config.help.json` maps `config-config_upgrade` to `developers/plugins`, not
   `install/upgrading`. That mapping is generated from the documentation site.

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

   Save a wrong code on the client (Settings, Upgrade, replace the code with
   `wrong-code-123`, save), then Check for Upgrades. The message shows with the tags as
   visible text, not as bold and italic. Put the line back afterwards by re-running setup
   step 2's API copy and gate commands.
4. **Rate limiting.** The server allows 600 requests per address per 10 minutes.

   ```bash
   for i in $(seq 1 601); do ut_offer >/dev/null; done; ut_offer | sed -n 2p
   ```

   Expect `message: Too many update requests from this address. Please try again shortly.`
   Then Check for Upgrades on the client: it is a different address (the client's private
   IP), so it is still served. Clear the counter with `ut_reset`.

### S8. Licence codes

Each check is a code saved in the box on Settings, then Upgrade, then Check for Upgrades.
Bump something first so there is an update to withhold:
`php "$TOOLS" bump "$SERVER" faq "licence check"`. Before each check, take a fingerprint
of the client so you can prove nothing was written:

```bash
fingerprint() { ( cd "$CLIENT" && find . -path ./temp -prune -o -type f -print0 | sort -z | xargs -0 shasum ) | shasum; }
before=$(fingerprint)
```

After each check that should not install anything, `[ "$(fingerprint)" = "$before" ] && echo unchanged`.

| Code saved | Mode | Expect |
| --- | --- | --- |
| (empty) | both | "Please enter your license code before doing an upgrade." Nothing offered. |
| `abc` | both | Not saved: codes of five characters or fewer are dropped (only `free` is kept). Same as empty. |
| `wrong-code-123` | A, gate present | "Updates are paused for a short while as a new release is prepared." Nothing offered, client unchanged. |
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
   `Upgrading plugin from version <old> to <new>`, then `all good`, then
   "Success! Everything worked."
3. `ut_version faq` prints the new version, and
   `db ucm_install_test -e "SHOW COLUMNS FROM ucm_faq LIKE 'ut_manual'"` prints a row.

### S10. Legacy API endpoints

Mode A only. The current client never calls these, but they are still deployed, so they
should answer without a PHP error.

```bash
php "$TOOLS" bump "$SERVER" faq "legacy endpoint check"
for u in "/index.php?curl_check=1" "/latest_updates.php" "/info.php" "/help.php?modules=faq&pages=faq_questions"; do
  printf '%-45s ' "$u"; curl -s -o "$UT/legacy.html" -w '%{http_code} ' "http://localhost:8095$u"
  grep -ciE 'fatal error|warning:|parse error' "$UT/legacy.html"
done
curl -s http://localhost:8095/latest_updates.php | grep -c 'legacy endpoint check'
```

Expect `200 0` on every line: an answer, and no PHP error in it. `curl_check` answers
`success`. `latest_updates.php` lists the bumped faq line under today's date. Pages that
pull styles from the old WordPress site will look unstyled locally; that is expected.

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
UpgradeIntegrityTest            ./vendor/bin/phpunit --filter UpgradeIntegrity  -> <result>

Anything surprising:
```

## Teardown

```bash
docker rm -f ucm-ut-client ucm-ut-api ucm-ut-db
docker network rm ucm-ut
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

## Change log

- 2026-10-17: written. Helper, ordering rules, licence gate and server answers verified
  individually; full run not yet done.
