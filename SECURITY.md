# Security

## Implemented Fixes (PHP 8.5 Refactor)

- **Passwords**: MD5 replaced with `password_hash()`/`password_verify()` (bcrypt). Legacy MD5 hashes are verified and migrated on login.
- **Database**: Deprecated `mysql_*` removed; MySQLi only. SQL uses `db_escape()` for user input.
- **Session**: `httponly`, `secure` (when HTTPS), `samesite=Lax` on session cookies.
- **Login**: Password verified in PHP (not in SQL) to support bcrypt timing-safe comparison.

## Checklist for Deployment

- [ ] Use `includes/config.local.php` for DB credentials (gitignored)
- [ ] Enable HTTPS in production; set `secure` cookie flag
- [ ] Run the web installer or migrations to create/update database
- [ ] Change default admin password after first login
- [ ] Ensure `temp/` is not web-accessible or has restricted permissions

## Reporting Vulnerabilities

Please report security issues responsibly. For a commercial product, contact the vendor through CodeCanyon.
