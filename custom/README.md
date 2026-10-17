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

