# The marketing website — a proof, not a deployment

**Nothing here is live and nothing here should be uploaded yet.** This is the working
proof attached to `docs/proposals/website.md`: enough of a site to judge whether replacing
the WordPress install at ultimateclientmanager.com is worth doing, built the same way the
documentation is so that the two can become one thing.

```
npm install
npm run dev      # http://localhost:4321
npm run build    # static files in dist/
```

## What it is

Six pages of plain static HTML, built by Astro, with one small stylesheet. No CMS, no
database, no plugins, no logins, no monthly bill, and nothing to keep patched. The same
shape as `docs-site/`, which is already built and proven.

```
src/pages/index.astro         the landing page
src/pages/features.astro      what the software does, in detail
src/pages/self-hosted.astro   the argument against renting a CRM
src/pages/pricing.astro       what it costs and what the licence allows
src/pages/requirements.astro  will it run on my hosting
src/pages/support.astro       where to get help, and what is promised
```

## Every claim on these pages comes from the repository

The copy was written from `docs-site/src/content/docs/`, `Documentation/` and the code
itself. Nothing is invented: no customer names, no testimonials, no performance claims, no
numbers that are not in the repository.

**Where a fact was needed and could not be established, there is a visible placeholder**
rather than a plausible guess. They render as a marked box and are listed in
`docs/proposals/owner-todo.md`. Search for `<Placeholder` to find them all. Do not publish
this site with any of them still in it.

## How it becomes one site with the documentation

Today this is a second Astro project because the documentation site is deployed and this
one is not, and keeping them apart means a proposal cannot break something that works.

Merging them is small, and the route was checked rather than assumed: Starlight claims the
`[...slug]` catch-all route, but explicit pages under `src/pages/` still win — which is
already how the documentation's `/videos/` page works — and `docsLoader({ generateId })`
moves every article under a `docs/` prefix. So the merged project is this project's pages
at the root and the documentation at `/docs/`, which is **exactly the address the
documentation already publishes to**. Nothing moves, nothing redirects.

See `docs/proposals/website.md` §5.
