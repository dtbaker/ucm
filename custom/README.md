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

| Action     | What it does                                                               |
| ---------- | -------------------------------------------------------------------------- |
| `restore`  | Puts the snapshot back (the daily run), then repairs the logins            |
| `repair`   | Only repairs the logins. **Deploy to demo** runs this after every deploy   |
| `snapshot` | Saves the demo exactly as it is now, as the copy every reset goes back to  |

Each run finishes by signing in as all three logins the login page advertises
(`scripts/demo-login-check.sh`), and fails if any is turned away.

**The snapshot** is `demo-snapshot/ucm-demo.sql.gz`, beside the site's web root rather than
in it, so a deploy never deletes it and it is never served. A restore loads it into
staging tables and swaps them in at once, so a broken snapshot leaves the demo as it was.
Until a snapshot exists, `restore` only repairs.

**Repair** stores the password `password` as a real hash for admin, user and staff. Since
4.0.0 the literal word `password` from the install data only works for the administrator,
which is why the customer login stopped working. A non-admin login also needs a role that
grants "Can User Login". Repair cannot invent one, so it fails and says which account is
missing it.

### One-off setup

1. Merge, and let the deploy run. Its repair step reports any login that is still missing a role.
2. In the demo, give `user@example.com` a customer role and `staff@example.com` a staff role,
   each with "Can User Login" (Settings > Security Roles, then each user's page). Sign in as
   each to check what they see.
3. Tidy the demo data so it is what every visitor should start from.
4. Run **Reset demo** with `snapshot`. It refuses to save a snapshot whose logins do not work.

Take a new snapshot whenever the demo data should change, and after any release that
changes the database (Settings > Upgrade > Run Manual Upgrades first), or the next reset
puts the old tables back.
