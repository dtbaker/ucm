# Demo chrome

The bar across the top of demo.ultimateclientmanager.com. It says the site is a demo,
switches between the bundled themes, and links to the documentation, GitHub and the
store. `module_theme::include_ucm()` picks `custom/<page>.php` ahead of the real one, so
`design_header.php` here renders the real header and slips the bar in after its `<body>`
tag, without the application knowing about it. `design_footer.php` does the same for the
footer and adds nothing.

`demo/` holds the theme menu's thumbnails: 280x176 screenshots of each theme's dashboard,
taken at 1280x800 with the bar closed. Retake them when a theme changes visibly.

## It only renders when demo mode is on

Both files return early unless `_DEMO_MODE`, so a checkout that has this folder is not
automatically a demo. Demo mode is the `UCM_DEMO_MODE` environment variable:

```
./scripts/demo-mode.sh on      # the demo bar, and the login form filled in
./scripts/demo-mode.sh off     # a plain install
./scripts/demo-mode.sh         # which is it currently
```

**Deployment needs `UCM_DEMO_MODE=1` in the environment.** Without it the demo deploys as
an ordinary install - no banner, and nothing stopping a visitor's changes from sticking.

Demo mode also turns `_BLOCK_EMAILS` on, which matters: anyone can put any address on a
customer and press "Email Invoice", and a demo that sends is a relay. `UCM_BLOCK_EMAILS`
overrides it either way.

## Not shipped to buyers

`.gitattributes` marks this folder `export-ignore`, so `git archive` - which is what
`scripts/build-release.sh` uses - leaves it out of the item archive. Committed so a deploy
carries it; excluded so a buyer does not get a header advertising the demo.


## Resetting the demo, and its logins

Visitors can add, change and delete records; demo mode only stops them changing settings.
`demo/reset.php` puts the data back, and the **Reset demo** GitHub workflow runs it every
day at 18:00 UTC. It can also be run by hand from the Actions tab:

| Action     | What it does                                                                    |
| ---------- | ------------------------------------------------------------------------------- |
| `restore`  | Puts the snapshot back (the daily run), then repairs the logins                 |
| `repair`   | Only repairs the logins                                                         |
| `snapshot` | Saves the demo exactly as it is now, as the copy every reset goes back to       |

**Deploy to demo** runs `ensure` after every deploy: repair, then the first snapshot if
there is none. Each run finishes by signing in as all three logins the login page
advertises (`scripts/demo-login-check.sh`), and fails, saying why, if any is turned away.

**Nothing needs setting up by hand.** Repair makes sure that `admin@`, `user@` and
`staff@example.com` each exist, have the password `password` stored as a real hash, and,
for the customer and staff, have a role that grants "Can User Login". Any of those that is
missing is created, so the first deploy after this change repairs the demo and takes its
first snapshot, with all three logins in it.

**What the logins can see.** `demo/roles.php` lists, by the app's own permission names, what
the "Demo Staff" role may view: customers, vendors, jobs, quotes, invoices, tickets, timers,
the calendar, websites, files and contracts, the dashboard, and the staff member's own
profile. It is an allowlist of view permissions. Nothing under Settings, Security, Backups,
Finance, Newsletters or reports is in it, and `DemoRolesTest` fails if one is added. "Demo
Customer" can sign in and nothing more, until it has a customer to be linked to and data to
see through it. Repair only ever adds to a role, so a permission added by hand in the demo
stays, and it adds them again after every restore. A permission the database does not have
yet is skipped and listed in the log; the app registers a permission the first time a page
checks it.

**The staff account is a staff member.** The installer's sample row makes `staff@example.com`
a contact of vendor 1, which the demo does not have. Its "Welcome" and "Edit Profile" links
then opened that vendor's contact page, which was refused, and it was missing from Settings >
Users. Repair clears the vendor link, so it is an ordinary staff user.

**The snapshot** is `demo-snapshot/ucm-demo.sql.gz`, beside the site's web root rather than
in it, so a deploy never deletes it and it is never served. A restore loads it into
staging tables and swaps them in at once, so a broken snapshot leaves the demo as it was.
`snapshot` repairs first and refuses to save while any login is still broken.

Take a new snapshot whenever the demo data should change, and after any release that
changes the database (Settings > Upgrade > Run Manual Upgrades first), or the next reset
puts the old tables back.

**Why the logins needed repairing.** The install data stores the literal word `password` for
the sample accounts. Since 4.0.0 that only works for the administrator, so the customer
login the demo advertises was refused. Those logins are printed on the demo's own login
page, so storing them hashed gives nothing away.

### Running it by hand on the server

```
cd <the demo's site folder>
php -q custom/demo/reset.php check      # what repair would change; changes nothing
php -q custom/demo/reset.php ensure
```

The server's `/usr/local/php82/bin/php` is the CGI build of PHP, not the command-line one.
That is why the script cannot just test for the `cli` SAPI, why it needs `-q` (otherwise
HTTP headers are printed first), and why it also accepts the action as `UCM_DEMO_ACTION`
in the environment: that build does not set `$argv`. `custom/demo/shell-only.php` holds
the checks, with unit tests, because getting this wrong once made the workflows pass while
doing nothing.
