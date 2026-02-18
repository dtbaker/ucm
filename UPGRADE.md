# UCM Upgrade Guide

## PHP 8.5 Migration

This document describes breaking changes when upgrading UCM to PHP 8.5.

### PHP Version Requirement

- **Previous**: PHP 5.3.0 or higher
- **Current**: PHP 8.4.0 or higher

Ensure your hosting provider supports PHP 8.4+. The deprecated `mysql_*` extension has been removed; only MySQLi is supported.

### Password Hashing

User passwords have been migrated from MD5 to `password_hash()` (bcrypt). On first login after upgrade:

- Existing users with MD5 passwords will be prompted to reset their password, or
- Run the password migration script (if provided) to re-hash all passwords in bulk

### File Paths and Custom Overrides

- All plugin paths remain unchanged: `includes/plugin_*/`
- The `custom/` folder override behavior is preserved
- URL structure (`m`, `p` query parameters and rewrite rules) is unchanged

### Docker Development

For local development, use Docker Compose:

1. Copy `includes/config.local.php.example` to `includes/config.local.php`
2. Run `docker-compose up -d`
3. Access the app at http://localhost:8090
4. Run the web installer if this is a fresh install

### Database

- MySQL 8.0 or MariaDB 10.5+ is recommended
- The `mysql_native_password` plugin may be required for older MySQL 8 configurations
