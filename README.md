# UCM - Ultimate Client Manager

Freelance project invoice CRM.

Please purchase a license from https://codecanyon.net/item/freelance-project-invoice-crm/2621629

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

## Upgrading

See [UPGRADE.md](UPGRADE.md) for breaking changes and migration notes.

## File Structure

- `includes/` - Core application and plugins
- `includes/plugin_*/` - Plugin modules (each extends `module_base`)
- `custom/` - User overrides (mirrors `includes/` structure)
- `pages/` - Root-level pages
- `temp/` - Temporary files, cache, sessions
