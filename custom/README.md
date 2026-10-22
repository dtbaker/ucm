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
| `seeded`   | The sample data was just made: dates it, repairs the logins, saves the snapshot |

**Deploy to demo** runs `ensure` after every deploy: repair, then the first snapshot if
there is none. After that it gives the demo its sample data if it has none (next section).
Each run finishes by signing in as all three logins the login page advertises
(`scripts/demo-login-check.sh`), and fails, saying why, if any is turned away.

**Nothing needs setting up by hand.** Repair makes sure that `admin@`, `user@` and
`staff@example.com` each exist, have the password `password` stored as a real hash, and,
for the customer and staff, have a role that grants "Can User Login". Any of those that is
missing is created, so the first deploy after this change repairs the demo and takes its
first snapshot, with all three logins in it.

**What the logins can see.** `demo/roles.php` lists, by the app's own permission names, what
the "Demo Staff" role may view: customers, vendors, jobs, quotes, invoices, tickets, timers,
the calendar, websites, files and contracts, the dashboard, and the staff member's own
profile. It is an allowlist of view permissions. Nothing under Settings, Security, Backups,
Finance, Newsletters or reports is in it, and `DemoRolesTest` fails if one is added.

"Demo Customer" is a portal contact: it sees its own customer's invoices, jobs, quotes,
tickets, files and websites, and nothing else. Every Data Access category in its list is the
option the app scopes to the customer ("Only customer I am assigned to as a contact",
"Invoices from customers I have access to", "Only tickets from my customer account", and so
on), never an "All ..." one, and the test fails if one is swapped, or if the role gains a
category a portal contact does not need. It has no notes (the agency's own), timers,
calendar, vendors or leads. It only has something to see because `user@example.com` is
linked to a sample customer, as that customer's primary contact (next section).

Repair only ever adds to a role, so a permission added by hand in the demo stays, and it adds
them again after every restore. A permission the database does not have yet is skipped and
listed in the log; the app registers a permission the first time a page checks it, which is
why the sample data's script opens every kind of page as each of the three logins before the
snapshot is taken.

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

## The sample data

Left alone the demo is the installer's two sample customers and nothing else, so the staff and
customer logins have nothing to look at. `scripts/demo-seed.mjs` gives it a small made-up
agency: six customers with contacts (and the installer's two), three vendors, four websites,
eight jobs with tasks and logged time, five recorded timers, four quotes, twelve invoices in
every state (draft, sent, part paid, overdue, paid), five support tickets with replies, files,
calendar entries and notes. Every business and person is invented and every address is on
`example.com`. What it creates is written down in `scripts/demo-seed/data.mjs`.

```
node scripts/demo-seed.mjs https://demo.ultimateclientmanager.com
```

**Why a script and not an SQL file.** It signs in as the public administrator login and posts the
forms the app's own pages post, so every total, status, tax line and relationship is the one the
app works out, and a release that changes how an invoice is stored does not leave the data
behind. A committed dump would have to carry ids that are different in every database: the
permission rows a role is built from have a different id on each install, the config table holds
`system_base_href` and the plugin versions of one site, and a dump taken from one site and loaded
into another quietly breaks both. The script touches none of them.

**Safe to run twice.** It does nothing, and says so, when any of the sample customers is already
there. It refuses to run against a site that is not showing the demo banner, so it cannot add a
dozen customers to somebody's real books, and it relies on demo mode blocking email: paying an
invoice in full sends the customer a receipt, and with `_BLOCK_EMAILS` off that would be mailed.
It needs Node 18 or later and nothing installed, and prints `SEEDED` or `PRESENT` last.

**When it runs.** *Deploy to demo* runs it after `ensure`, and *Reset demo* runs it after a
restore, both only when it has made new data (`seeded=true`), and then take the snapshot by
running `reset.php seeded`. The daily reset puts the snapshot back, so in the ordinary case the
script finds the data there and does nothing. The second run is a safety net for a snapshot taken
before there was any.

**The customer login.** The first customer, *Marlowe & Finch Bakery*, is where `user@example.com`
belongs. The script moves that existing account onto the customer, which is how the app attaches
an existing person to one, so it becomes the bakery's primary contact (shown as Ines Marlowe).
The installer's own customer 1 cannot be edited in demo mode, so it is not used.

**Dates stay current.** Nothing in the data is a calendar date. Every date is a number of days
from the day the script ran. The snapshot keeps them as they were, so the daily restore would
slowly age the demo, with invoices that were due next week overdue by Christmas. So `reset.php`
remembers the day the data was made (the `demo_data_date` config row, which travels with the
snapshot), and after every restore, and before every snapshot, moves each date forward by the
days since. The columns are listed in `demo/dates.php`, checked by `DemoDatesTest` against the
tables the app creates. Totals, statuses and relationships do not depend on the date, so
nothing else moves. This also dates the hours logged on job tasks. The app stamps a logged hour
with the moment it was logged, and has no field to say otherwise, so `seeded` dates those from
the day their task was done; it is the one thing the script cannot set through a form.

**Changing the data later.** The script does not update what is there, so editing
`data.mjs` changes only a demo that is made afresh. To rebuild the demo's data, start from a
database without it (the installer's, or a snapshot taken before it), run the script, and take
a snapshot. `node --test scripts/demo-seed/data.test.mjs` checks that the data holds together
(customers named exist, no invoice is paid twice over, a completed job has nothing left to do),
and runs in CI.

**What the customer login cannot do.** It is view-only, so it opens lists and records but not
a ticket: the app only opens a ticket for someone who may edit or create tickets, so the
customer sees its tickets listed (subject, status, who it is with) and not their replies.
Letting it reply would mean letting anyone who opens the demo write into a shared ticket. Its
dashboard is empty, because the dashboard's alerts and widgets are for staff.

**Known gaps, in the app and not here.** Signed in as the customer login, a few pages that
nothing links to do not check who is asking, and show other customers' records to anyone who
types the address: the email log (`email_admin`) and the timers list (`timer_admin_list`).
The invoice page for another customer's invoice ends in a PHP error instead of "access
denied", and the ticket list prints a warning about `$limit_time` when error display is on.
The menus, lists and every record page are scoped; these are the exceptions, and they exist
on any install.

### Testing it away from the demo

The script and the reset need a database that looks like the demo's, not the shared dev one.
On a throwaway container with its own database, installed from scratch (the installer's two
sample customers are what the real demo starts from), `UCM_DEMO_MODE=1`:

```
UCM_DEMO_SNAPSHOT=/tmp/snap.sql.gz php -q custom/demo/reset.php ensure   # logins, first snapshot
node scripts/demo-seed.mjs http://localhost:8093
UCM_DEMO_SNAPSHOT=/tmp/snap.sql.gz php -q custom/demo/reset.php seeded   # dates, repair, snapshot
./scripts/demo-login-check.sh http://localhost:8093
```

### Running it by hand on the server

```
cd <the demo's site folder>
php -q custom/demo/reset.php check      # what repair would change; changes nothing
php -q custom/demo/reset.php ensure
php -q custom/demo/reset.php seeded     # after scripts/demo-seed.mjs has made the sample data
```

The server's `/usr/local/php82/bin/php` is the CGI build of PHP, not the command-line one.
That is why the script cannot just test for the `cli` SAPI, why it needs `-q` (otherwise
HTTP headers are printed first), and why it also accepts the action as `UCM_DEMO_ACTION`
in the environment: that build does not set `$argv`. `custom/demo/shell-only.php` holds
the checks, with unit tests, because getting this wrong once made the workflows pass while
doing nothing.
