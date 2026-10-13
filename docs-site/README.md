# Documentation

One content tree, four outputs. Everything else here exists to produce them.

```
src/content/docs/          the only place to write
  index.md                 the front page
  install/   running/      operational - also goes in the download
  using/                   the feature documentation
  developers/              plugins and customising
  reference/               troubleshooting, changelog, credits
```

## The four outputs

| Output | Where | Built by | Goes to |
|---|---|---|---|
| The website | `dist/web/` | `npm run build:web` | `ultimateclientmanager.com/docs/` |
| The app's API | `dist/web/api/v1/` | `npm run build:app` | same deploy; the help panel reads it |
| The shipped copy | `../includes/plugin_help/content/` | `npm run build:app` | inside the download |
| The screen map | `../includes/plugin_NAME/NAME.help.json` | `npm run build:app` | inside the download |
| The CodeCanyon archive | `dist/offline/` | `npm run build:offline` | copied over `../Documentation/` |

```
npm install
npm run build        # all of it
npm run upload       # build, then lay out one folder to put on the web server
npm run dev          # the website, with live reload
npm run check        # what CI runs: nothing has drifted, nothing points at nothing
```

**`npm run build:app` writes into the application tree.** That is deliberate: the shipped
copy of the documentation and the map of which screen gets which article are build output,
not hand maintained files. Twenty two hand maintained `*.help.json` files shipped to five
thousand installations for a decade, pointing at articles that had moved, read by nothing.
`npm run check` is what stops that happening again, and CI runs it.

## Writing an article

Frontmatter carries four fields beyond Starlight's own:

```yaml
---
title: Editing a customer
description: One sentence. It becomes the summary in the help panel.
help_keys:
  - customer-customer_admin_edit    # the screens this article explains
video: dQw4w9WgXcQ                  # optional, eleven characters
offline: false                      # true to include it in the CodeCanyon archive
featured: false                     # true to offer it when a screen has no article
---
```

### `help_keys`

A key is the page identity `index.php` already computes for every request: the plugin
folder and the page file, joined by a dash. It is the `<body id>` on every screen, so to
find the key for a screen, open it and read the body id — or use your browser's inspector.

`npm run check` fails if a key does not match a real page, which is the check that keeps
these honest as the PHP moves.

### Links

Write internal links as absolute site paths: `[Invoices](/using/invoices/)`. The builds
rewrite them per output — relative `.html` in the archive, absolute in the app panel. A
link to an article that does not exist fails `npm run check`.

### Callouts

`:::note`, `:::tip`, `:::caution`, `:::danger`, optionally with `:::danger[A title]`.
Starlight renders them as asides; the archive renders them as the boxes
`Documentation/assets/docs.css` has always drawn.

## Adding a video

1. Upload to YouTube. Visibility: **Unlisted**.
2. Copy the eleven character id out of the share URL — just the id, not the address.
3. Put it in the article's frontmatter: `video: dQw4w9WgXcQ`
4. `npm run posters` — downloads the still frame so the page loads nothing from Google
   until a reader presses play.
5. Commit.

That is all of it. The article page gets a player, `/videos/` lists it automatically, and
the help panel inside the application gets a "watch the video" link. The panel links and
never embeds: an embed would put a third party frame inside somebody's admin area.

For a video partway through an article, rename the file to `.mdx` and use the component:

```mdx
import Video from '../../../components/Video.astro';

<Video id="dQw4w9WgXcQ" title="Creating your first invoice" />
```

## Deploying

The site is static — HTML, CSS, JavaScript and JSON, with nothing to run. It needs no
Node, no database and no build step on the server, so ordinary Apache and PHP hosting
serves it as well as anything else would.

```
npm run upload
```

writes `dist/upload/`, which contains one folder to copy onto the web server and a
`UPLOAD-THIS.txt` saying where it goes. By default it goes to `public_html/docs/` and the
site is at `https://ultimateclientmanager.com/docs/`.

Replace the folder wholesale each time rather than merging into the old one, or files
deleted since the last build stay behind. Make sure the upload includes `.htaccess`: it
stops the site above the folder from rewriting these addresses, and points 404s at this
site's own 404 page. Many FTP clients hide dotfiles by default.

### Where the site lives

`site.mjs` is the only place that answers that, and everything reads it from there — the
Astro config, the JSON the application fetches, the offline archive and the deploy
script. To build for somewhere else, set the environment rather than editing anything:

```
UCM_DOCS_ORIGIN=https://docs.ultimateclientmanager.com UCM_DOCS_BASE= npm run upload
```

**The address has to agree with the application.** `DOCS_WEB` and `DOCS_API` in
`includes/plugin_help/docs_api.php` are where the help panel looks, and
`tests/unit/HelpDocsTest.php` pins them. Move one without the other and five thousand
installations quietly fall back to the copy inside their own download — which is the
designed behaviour, so nothing breaks loudly enough to notice.

### Why links get a second pass

Astro's `base` is applied to the addresses its own components build and to nothing
written inside an article, which is documented behaviour rather than a bug. So
`scripts/base-links.mjs` prefixes the base onto the ninety or so article links in the
built HTML, straight after `astro build`. The alternative was typing `/docs` into every
link in the content, which would make moving the site a ninety file change.

## Why the archive is built by a script rather than by Starlight

Envato requires documentation inside the download, working from a zip file with no
network. Starlight links from the site root, fetches a search index and assumes a server.
So `scripts/build-offline.mjs` renders the same Markdown into the same HTML shape and the
same stylesheet the hand written archive already used — around 150 lines, instead of
fighting a static site generator into a `file://` context it was not built for.

One source does not mean the same pages. The archive is the operational half; the feature
documentation belongs on the website and would only pad the archive out. `offline: true`
picks the subset.
