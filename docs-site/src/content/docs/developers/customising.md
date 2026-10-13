---
title: 'Customising'
description: 'You can change almost any screen, template or stylesheet in the product without editing a single file you were shipped. Do it this way and your changes survive every future update untouched.'
sidebar:
  order: 2
offline: true
---

## The golden rule

:::danger[Never edit a file inside includes/ or at the top level]
Every one of them is replaced when you update. Any change you make there is gone, with no warning and no way to recover it short of your backup.

Instead, copy the file into a folder called `custom/` and edit the copy. The application looks there first.
:::

## The `custom/` folder

Create a folder called `custom` at the top level of your installation, next to `index.php`. It does not exist to begin with, and it is never part of an update.

Inside it, recreate the path of whatever you want to change. If you want to change

```
includes/plugin_invoice/template/invoice_print.php
```

then copy it to

```
custom/includes/plugin_invoice/template/invoice_print.php
```

and edit that. From then on the application uses your copy and ignores the original. Delete your copy to go back to the standard behaviour.

### What can be overridden

Anything the application includes as a file, which is most of it:

<table><thead><tr><th>To change</th><th>Copy to</th></tr></thead><tbody><tr><td>The printed and emailed invoice</td><td><code>custom/includes/plugin_invoice/template/invoice_print.php</code></td></tr><tr><td>The page header</td><td><code>custom/design_header.php</code></td></tr><tr><td>The page footer</td><td><code>custom/design_footer.php</code></td></tr><tr><td>The main menu</td><td><code>custom/design_menu.php</code></td></tr><tr><td>The login screen</td><td><code>custom/pages/login.php</code></td></tr><tr><td>The dashboard</td><td><code>custom/pages/home.php</code></td></tr><tr><td>Any plugin screen</td><td><code>custom/includes/plugin_&lt;name&gt;/pages/&lt;page&gt;.php</code></td></tr><tr><td>The whole behaviour of a plugin</td><td><code>custom/includes/plugin_&lt;name&gt;/&lt;name&gt;.php</code></td></tr><tr><td>The customer portal</td><td><code>custom/includes/plugin_customer/portal/index.php</code></td></tr></tbody></table>

### Adding a page that does not exist

The same mechanism adds new screens. Create

```
custom/includes/plugin_customer/pages/my_report.php
```

and it becomes reachable in the application as a page called `my_report` in the customer area, even though no such file exists in the product. This is the tidiest way to add a report of your own.

### The order things are searched

When the application needs a file it looks in this order and takes the first it finds:

1.  Your `custom/` copy, mobile version
2.  Your `custom/` copy
3.  The standard file, mobile version
4.  The active theme’s copy
5.  The standard file

So `custom/` beats a theme, and a theme beats the standard file. Switching themes never disturbs your own overrides.

:::caution[An override is a copy, frozen in time]
Once you override a file, you stop receiving updates to it — including fixes. After a major update it is worth comparing your copy against the new standard version and bringing across anything that changed. Override the smallest file that does the job, and keep a note of which files you have copied.
:::

:::note[Protect the folder if you can]
`custom/` sits inside your web space and the download does not include a rule to stop it being fetched directly. If your server lets you, deny direct web access to it the same way the upload folders are protected.
:::

## Themes

Eight themes are included. Choose one under _Settings → Theme_.

<table><thead><tr><th>Theme</th><th>Notes</th></tr></thead><tbody><tr><td><strong>Metis</strong></td><td>The default on a new installation.</td></tr><tr><td><strong>AdminLTE</strong></td><td>A wide, dense administrative layout.</td></tr><tr><td><strong>Blocks</strong></td><td>A card-based layout.</td></tr><tr><td><strong>White Label</strong></td><td>A plain layout intended for rebranding.</td></tr><tr><td><strong>Blue</strong>, <strong>Left</strong>, <strong>Pro</strong></td><td>Older variations on the original design.</td></tr><tr><td><strong>Default</strong></td><td>No theme. The underlying unstyled layout.</td></tr></tbody></table>

A theme can also be set per user, if you turn that option on, so staff can each pick their own.

### Small visual changes

For colours, logos, spacing and the like, use the custom CSS box on _Settings → Theme_. Whatever you put there is appended to the stylesheet on every page, so you do not need a file override at all. This is the right tool for the great majority of appearance changes.

```
.main-bar h3 { color: #1b4f82; }
#logo img    { max-height: 48px; }
```

:::note[If a style change does not appear]
Stylesheets and scripts are combined and cached for speed. Reload the page bypassing the browser cache — Ctrl or Cmd with Shift and R. If it still does not show, clear the files in `temp/`, which is where the combined copy is kept.
:::

### Building a theme

A theme is a folder under `includes/plugin_theme/themes/` containing at minimum an `init.php` that registers its stylesheet, and a `style.php`. It is picked up automatically once the folder is there.

To change a screen only for your theme, mirror the path inside the theme folder — `includes/plugin_theme/themes/yourtheme/includes/plugin_customer/pages/customer_admin_edit.php` overrides that one screen for that theme only. This is the same mechanism as `custom/`, one level further down the search order.

The simplest way to start is to copy an existing theme folder, rename it, and work from there. **Copy rather than edit**, so that updates to the original theme do not overwrite your work.

## Changing wording

Before overriding a file to change some text, check whether you can change it from the interface instead:

-   **Emails and documents** — _Settings → Templates_ holds the wording of everything the system sends.
-   **Interface labels** — _Settings → Language_ lets you replace any text in the interface, including in your own language. It is the right way to change “Customer” to “Client” throughout.

Both survive updates with no effort on your part, which makes them better than an override wherever they are enough.

## Putting it together

A good order to reach for things, from least to most work:

1.  A setting under _Settings_.
2.  A template or a language replacement.
3.  Custom CSS on the theme screen.
4.  A file override in `custom/`.
5.  A plugin of your own.

Each step up costs you more to maintain at update time. Most things people want are available in the first three.
