# UCM Upgrade Guide

Developer notes on the breaking changes in this release.

> The guide written for buyers is `Documentation/upgrading.html`, which ships in the
> release archive. It is the one to send people to. This file is the short version for
> anyone working on the code.

## PHP version

- **Previous**: PHP 5.3.0 or higher
- **Current**: **PHP 8.4.0 or higher**

8.4 is the floor and is enforced at `index.php:16`, which stops the request before
anything else loads. CI runs the suite on 8.4 and 8.5, so both are supported.

Some older strings in the tree still say "PHP 8.5". They are message text, not a check —
nothing enforces 8.5. `composer.json` and `phpcs.xml.dist` both say 8.4, which is correct.

The deprecated `mysql_*` extension has been removed; only MySQLi is supported.

## Database credentials move out of `includes/config.php`

This is the change most likely to take an existing installation down.

The 2.x installer wrote the five `_DB_*` constants into `includes/config.php` itself. That
file is part of the product and is replaced on update, so an in-place upgrade destroys the
credentials and the site comes up with `Connect failed`.

This release writes `includes/config.local.php` instead, which is gitignored, never
shipped, and loaded before the defaults. An upgrading user has to copy their five
`define()` lines across by hand once; after that, updates are a straight file copy.

The five database constants are defined inside a single `!defined('_DB_SERVER')` guard, so
a `config.local.php` that sets some but not all five leaves the rest undefined. It is all
five or none.

## `_UCM_SECRET`

Older installations had no secret and fell back to `_UCM_FOLDER`, the install path. Every
capability hash in the app derives from this value, so setting a real one invalidates
links already emailed to customers. The buyer guide spells out the trade-off; the short
version is to set it during the upgrade, not afterwards.

It can also come from the `UCM_SECRET` environment variable.

## Password hashing

Passwords moved from MD5 to `password_hash()` (bcrypt). No migration step and no forced
reset: existing MD5 hashes still verify, and each one is rehashed on the next successful
login.

Note that `functions.php:32-35` still accepts the literal string `password` against the
literal stored value `password`, which is how the five seeded sample accounts work. Any
installation that never changed them still has five live accounts with that password, two
of them privileged.

## File paths and custom overrides

- Plugin paths are unchanged: `includes/plugin_*/`
- The `custom/` folder override behaviour is preserved
- URL structure (`m`, `p` parameters and the rewrite rules) is unchanged

## Database

- MySQL 8.0 or MariaDB 10.5+ is what this release is developed and tested against
- Nothing in the code checks the server version
- `db_connect()` sets `@@SESSION.sql_mode = ''` on every connect, so strict mode is off
  regardless of server configuration
- The schema is InnoDB with foreign key constraints, so the DB user needs `ALTER` and
  `REFERENCES`, not just `CREATE` and `INSERT`

## Running the upgrade

Settings → Upgrade → **Run Manual Upgrades**. It walks every plugin, compares the version
recorded in the `config` table against `$this->version` in the files, and applies
`get_upgrade_sql()` where they differ. Idempotent, so it is safe to re-run.

No purchase code is needed for this. The code only gates the separate *Check for Upgrades*
button, which talks to the vendor's update server.

## Docker development

1. `docker-compose up -d`
2. Open http://localhost:8090
3. Run the web installer if this is a fresh database

No config file to copy — the app picks up the Docker database settings on its own.
