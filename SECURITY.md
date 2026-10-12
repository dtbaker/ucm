# Security

## Reporting a vulnerability

Report security issues privately, not in the item's public comments. Open a security
advisory on the GitHub repository, or contact the vendor through CodeCanyon.

Please include the version, the steps to reproduce, and what an attacker gets out of it.
You will get an acknowledgement; if the issue is confirmed, the fix is flagged as a
security fix in the changelog that ships with the release.

## What has been fixed

This release is largely a security release. The full list, in the wording buyers see, is
in `Documentation/changelog.html`. The structural changes:

- **Passwords.** MD5 replaced with `password_hash()` / `password_verify()` (bcrypt).
  Legacy hashes verify and are rehashed on login. A bypass that let a stored hash be used
  in place of the password has been closed.
- **Form tokens.** The CSRF check existed but its return value was discarded at the main
  dispatcher, so it blocked nothing. It now blocks, and the check has been applied across
  the save and delete handlers throughout the product.
- **Remote code execution.** The mPDF auto-installer (plaintext HTTP fetch, no checksum,
  extracted into an include path) is gone. `unserialize()` of request data is gone.
  Building a class name through `eval()` is gone.
- **Database.** `mysql_*` removed; MySQLi only. Identifiers go through `db_identifier()`.
  Database errors no longer reach the screen outside debug mode.
- **Uploads.** Upload, attachment and temp directories are no longer web-reachable.
  Uploads are checked before being saved, and file actions verify permission first.
- **Sessions.** `httponly`, `secure` when on HTTPS, and `samesite=Lax`.
- **Phoning home.** Purchase codes are no longer sent to a third-party site. Two scripts
  were being loaded from a CDN domain that no longer exists; both are removed.

## Known weaknesses

Recorded here so they are not rediscovered as surprises.

- **`module_security::sanatise_data()` is a no-op.** `security.php:486` returns
  immediately. Never rely on it.
- **`update_insert()` does not check ownership.** The check is commented out at
  `database.php:272-279`. Callers must enforce permission themselves.
- **Handlers dispatch on `$_REQUEST`.** Most state-changing actions are reachable by GET,
  so `SameSite=Lax` does not help. The form token is what protects them.
- **The default sample accounts use the password `password`.** Five of them, two
  privileged, created by the installer with no prompt to change them. Documented loudly
  in the install and upgrade guides, but it remains a live default.
- **No `session_regenerate_id()` on login**, so session fixation is possible.
- **Bundled libraries are vendored by hand**, so `composer audit` and Dependabot cannot
  see them. `Licensing/third-party-libraries.tsv` is the closest thing to an inventory —
  it lists every bundled component and its version, which is what a vulnerability triage
  needs to start from.

## Deployment checklist

- [ ] Database credentials in `includes/config.local.php`, not `includes/config.php`
- [ ] A real random `_UCM_SECRET`, not the install-path fallback
- [ ] HTTPS, so the `secure` cookie flag applies
- [ ] Web installer or Settings → Upgrade run, so the schema is current
- [ ] Default sample account passwords changed or the accounts deleted
- [ ] `temp/` and the upload directories not web-reachable — on nginx this needs rules
      written by hand, because the shipped `.htaccess` files do nothing there
- [ ] Debug mode off
