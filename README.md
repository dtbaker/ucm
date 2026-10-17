# UCM - Ultimate Client Manager

Freelance project invoice CRM.

Open source under the [MIT licence](LICENSE). Bundled third-party components keep their own
licences; see [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md). A packaged release with
support is also sold on
[CodeCanyon](https://codecanyon.net/item/freelance-project-invoice-crm/2621629).

## Development Setup with Docker

### Prerequisites

- Docker and Docker Compose
- Git

### Quick Start

1. **Clone and start**

   ```bash
   git clone <repo-url> ucm && cd ucm
   docker-compose up -d
   ```

   No config file to copy - the app picks up the Docker database settings on its own.

2. **Access the app**
   - Open http://localhost:8090
   - Run the web installer if this is a fresh install. It creates the database tables
     and writes `includes/config.local.php` for you.

3. **Mail (optional)**
   - MailHog runs on port 8025 for web UI: http://localhost:8025
   - Port 1025 for SMTP (configure in UCM email settings)

### Environment

- **App**: http://localhost:8090
- **MySQL**: localhost:8096 (user: ucm, password: ucm, database: ucm)
- **MailHog**: http://localhost:8025
- **phpMyAdmin**: http://localhost:8091

To point the app at a different database without editing any file, set `UCM_DB_SERVER`,
`UCM_DB_NAME`, `UCM_DB_USER`, `UCM_DB_PASS`, `UCM_DB_PREFIX` or `UCM_CONFIG_LOCAL` - see
[TESTING.md](TESTING.md).

### Debugging 500 Errors

**1. View the error in the response body** (often the fastest):

```bash
curl -s http://localhost:8090/
```

The 500 response body usually contains the PHP error message and stack trace.

**2. Docker logs**:

```bash
docker-compose logs -f app
```

`UCM_DEBUG=1` is set in docker-compose so PHP errors are shown. The image includes `docker/php-debug.ini` for early errors.

### Running Tests

```bash
# Install composer dependencies (on host)
composer install

# PHPUnit
composer test

# PHPStan static analysis
composer phpstan

# E2E tests (requires Docker app running)
docker-compose up -d
npm install && npm run test:e2e
```

## Documentation

The full documentation is online at <https://ultimateclientmanager.com/docs/>. Its source
is in the `ucm-web` repository, not this one.

`Documentation/` and `Licensing/` are the buyer-facing halves of the release archive. The
build puts them beside `Main Files` in the zip rather than inside it.

- `Documentation/readme.html` - one short getting-started page: requirements, installing,
  upgrading, and links to the live documentation for everything else. Keep it short; the
  release build fails if anything else appears in that folder.
- `Licensing/` - assembled at build time from `LICENSE`, `THIRD_PARTY_NOTICES.md` and
  `LICENSES/` at the repository root. Add a row to the notices when you bundle a new
  library, and its licence text to `LICENSES/` if it is not there yet.

## Building the release archive

```bash
./scripts/build-release.sh --verify
```

Writes `build/ucm-codecanyon.zip` in the three-folder layout CodeCanyon requires, then
checks it: nothing but those three folders at the root, no development files inside
`Main Files`, the getting-started page, and third-party notices whose licence texts are
all present. CI runs the same command.

`Main Files` comes from `git archive`, so only committed files ship and
[.gitattributes](.gitattributes) decides what stays out. Nothing in the working copy can
leak in by accident.

## Releasing

The release version is `_SCRIPT_VERSION` in `init.php`. It is the number in the footer and
on Settings > Upgrade (`module_config::product_version()`), and the one the changelog
quotes. Every release:

1. **Bump `_SCRIPT_VERSION`** and add a line under it: `// <version> - <date> - <summary>`.
   It is also the cache key on every stylesheet and script, so skipping it leaves browsers
   on the old CSS and JavaScript.
2. **Bump the `$version` of every plugin whose files changed**, with a changelog comment
   in that plugin's `init()`: `//<version> - <date> - <what changed>`. The in-app updater
   compares plugin versions and nothing else, so a changed file in a plugin that was not
   bumped never reaches an existing install. The comment is what the update screen shows
   as the description.
3. **Core files only travel with a plugin update.** `init.php`, `design_header.php`,
   `includes/functions.php` and the rest of the core list in
   `includes/plugin_config/upgrade_server.php` are sent whenever any plugin is offered. A
   release that changes only core files has to bump at least one plugin, or it is never
   offered.
4. **Images, `css/` and `js/` outside that core list do not travel at all**, except in a
   fresh zip install. Put new assets inside a plugin, as `includes/plugin_theme/images/`
   does, so they arrive with it.
5. Update the changelog, build with `./scripts/build-release.sh --verify`, and run
   [docs/local-upgrade-test.md](docs/local-upgrade-test.md) against the build.

## Upgrading

See the [upgrade guide](https://ultimateclientmanager.com/docs/install/upgrading/) for
breaking changes and migration notes.

## File Structure

- `includes/` - Core application and plugins
- `includes/plugin_*/` - Plugin modules (each extends `module_base`)
- `custom/` - User overrides (mirrors `includes/` structure)
- `pages/` - Root-level pages
- `temp/` - Temporary files, cache, sessions
