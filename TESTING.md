# Testing

## PHP unit tests

```
composer install
composer test
```

These cover the standalone helpers in `includes/functions.php`. UCM has no autoloader and
most of it needs a database, so anything larger is covered by the browser tests below.

## Browser (end to end) tests

1. Start the Docker environment

```
docker-compose up -d
```

This spins up the PHP app on http://localhost:8090, MySQL, MailHog, and phpMyAdmin. You'll need the app to be installed/seeded with test data (admin credentials: admin@example.com / password).

2. Install Node dependencies (first time only)

```
npm install
npx playwright install
```

The npx playwright install step downloads the Chromium browser binary that Playwright needs.

3. Run the tests

```
npm run test:e2e
```

Or with the interactive UI:

```
npm run test:e2e:ui
```

You can also run a specific test file:

```
npx playwright test tests/e2e/login.spec.ts
```

4. Viewing results

```
npx playwright show-report
```

## Testing the installer

`tests/e2e/install.spec.ts` walks the setup wizard from start to finish against an empty
database. It needs a throwaway copy of the app, which is not started by
`docker compose up`:

```
./scripts/install-test.sh
npx playwright test tests/e2e/install.spec.ts
./scripts/install-test.sh --stop
```

The script resets the `ucm_install_test` database and starts a fresh container on
http://localhost:8092 that keeps its own `config.local.php` inside the container, so
installing there leaves your development app on :8090 alone. If that container is not
running, the installer tests skip instead of failing.

## Testing an update end to end

`tests/e2e/upgrade.spec.ts` checks that an installation can update itself from itself.
To test a real update - a built release installed fresh, a newer version published from a
local copy of the update server, licence codes, install order, migrations and help - follow
[docs/local-upgrade-test.md](docs/local-upgrade-test.md). It runs in its own containers on
:8094 and :8095 and leaves the development app alone.

## Pointing the app somewhere else

`includes/config.local.php` is the normal place for database settings and the installer
writes it for you. For containers and CI these environment variables override the
built-in defaults without editing any file:

| Variable            | Default                      |
| ------------------- | ---------------------------- |
| `UCM_DB_SERVER`     | `db` in Docker, else `localhost` |
| `UCM_DB_NAME`       | `ucm`                        |
| `UCM_DB_USER`       | `ucm` in Docker              |
| `UCM_DB_PASS`       | `ucm` in Docker              |
| `UCM_DB_PREFIX`     | `ucm_`                       |
| `UCM_CONFIG_LOCAL`  | `includes/config.local.php`  |
| `UCM_SECRET`        | the install path (set this)  |
| `UCM_DEBUG`         | off                          |

`includes/config.local.php` still wins over all of them when it exists.

`UCM_SECRET` seeds every signed link the app issues - download links, calendar feeds, the
cron URL. The installer writes a random one into `config.local.php`, so a normal install
never needs this variable. Set it only when running from the environment with no local
config, as the containers above do; otherwise it falls back to the install path, which is
guessable.
