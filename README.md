# UCM - Ultimate Client Manager

Freelance project invoice CRM.

Please purchase a license from https://codecanyon.net/item/freelance-project-invoice-crm/2621629

## Development Setup with Docker

### Prerequisites

- Docker and Docker Compose
- Git

### Quick Start

1. **Clone and configure**

   ```bash
   git clone <repo-url> ucm && cd ucm
   cp includes/config.local.php.example includes/config.local.php
   ```

2. **Start services**

   ```bash
   docker-compose up -d
   ```

3. **Access the app**
   - Open http://localhost:8090
   - Run the web installer if this is a fresh install
   - Use the installer to create the database tables

4. **Mail (optional)**
   - MailHog runs on port 8025 for web UI: http://localhost:8025
   - Port 1025 for SMTP (configure in UCM email settings)

### Environment

- **App**: http://localhost:8090
- **MySQL**: localhost:3306 (user: ucm, password: ucm, database: ucm)
- **MailHog**: http://localhost:8025

### Debugging 500 Errors

**1. View the error in the response body** (often the fastest):

```bash
curl -s http://localhost:8090/
```

The 500 response body usually contains the PHP error message and stack trace.

**2. Debug endpoint** – hit http://localhost:8090/debug.php to check PHP, config, and DB connectivity without loading the full app.

**3. Docker logs**:

```bash
docker-compose logs -f app
```

`UCM_DEBUG=1` is set in docker-compose so PHP errors are shown. The image includes `docker/php-debug.ini` for early errors.

### Running Tests

```bash
# Install composer dependencies (on host)
composer install

# PHPUnit (if tests exist)
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
