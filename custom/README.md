# Demo chrome

The banner, theme switcher and demo login that wrap the app on
demo.ultimateclientmanager.com. `module_theme::include_ucm()` picks `custom/<page>.php`
ahead of the real one, so these two files wrap `design_header.php` and
`design_footer.php` without the application knowing about it.

## It only renders when demo mode is on

Both files return early unless `_DEMO_MODE`, so a checkout that has this folder is not
automatically a demo. Demo mode is the `UCM_DEMO_MODE` environment variable:

```
./scripts/demo-mode.sh on      # banner, theme switcher, demo login
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

## Known, deliberately left alone

`design_footer.php` injects Universal Analytics (`analytics.js`, a UA- property). UA
stopped processing data in July 2023, so it collects nothing and is a third-party script
on every demo page doing no work. Replace it with a GA4 tag or delete it - removing the
demo's analytics is an owner's decision, not a side effect of a refactor.
