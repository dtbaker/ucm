---
title: 'Credits and licences'
description: 'Ultimate Client Manager stands on a lot of other people&rsquo;s work. This page credits it and tells you where to find the licence terms that come with each piece.'
sidebar:
  order: 3
offline: true
---

## Your licence

Your purchase from CodeCanyon grants you an Envato Market licence — the Regular Licence or the Extended Licence, depending on which you bought. The terms are published at [codecanyon.net/licenses/standard](https://codecanyon.net/licenses/standard).

That licence covers the application code, stylesheets, images and design created for this product. It does not cover the third-party components listed below, which stay under their own licences.

## The full manifest

The complete list — every component, its version, its licence, its copyright holder, where it came from and where it lives inside the download — is in the `Licensing` folder next to this documentation.

- [Third party libraries](../Licensing/third-party-libraries.html) — The manifest, as a table you can read in a browser.
- [Licensing notes](../Licensing/README.txt) — How the Envato licence and the component licences fit together.

The full text of every licence referenced there is in `Licensing/licenses/`.

:::note[If those links do not open]
They point one folder up from this documentation, which only works if you kept the download’s folder structure. If you moved this folder somewhere on its own, open the `Licensing` folder from the original download instead.
:::

## What is included, in brief

Around fifty third-party components ship inside the application. The ones you are most likely to notice:

<table><thead><tr><th>Component</th><th>Used for</th><th>Licence</th></tr></thead><tbody><tr><td><a href="https://github.com/dompdf/dompdf">dompdf</a></td><td>Turning invoices and quotes into PDFs.</td><td>LGPL&nbsp;2.1</td></tr><tr><td><a href="https://github.com/PHPMailer/PHPMailer">PHPMailer</a></td><td>Sending email, including over SMTP.</td><td>LGPL&nbsp;2.1</td></tr><tr><td><a href="http://htmlpurifier.org/">HTML Purifier</a></td><td>Cleaning HTML that people type into the editor.</td><td>LGPL&nbsp;2.1</td></tr><tr><td><a href="https://www.tiny.cloud/">TinyMCE</a></td><td>The rich text editor.</td><td>LGPL&nbsp;2.1</td></tr><tr><td><a href="https://jquery.com/">jQuery</a> and <a href="https://jqueryui.com/">jQuery UI</a></td><td>Most of the interactive behaviour in the interface.</td><td>MIT</td></tr><tr><td><a href="https://getbootstrap.com/">Bootstrap</a></td><td>Layout and components in several themes.</td><td>MIT</td></tr><tr><td><a href="https://fontawesome.com/v4/">Font Awesome</a></td><td>The icons.</td><td>OFL&nbsp;1.1 and MIT</td></tr><tr><td><a href="https://adminlte.io/">AdminLTE</a></td><td>The AdminLTE theme.</td><td>MIT</td></tr><tr><td><a href="https://www.plupload.com/">Plupload</a></td><td>Uploading files, including large ones in pieces.</td><td>GPL&nbsp;2.0</td></tr><tr><td><a href="https://phpseclib.com/">phpseclib</a></td><td>Encrypting sensitive stored values.</td><td>MIT</td></tr><tr><td><a href="https://morrisjs.github.io/morris.js/">Morris.js</a> and <a href="https://dmitrybaranovskiy.github.io/raphael/">Raphaël</a></td><td>Dashboard charts.</td><td>BSD and MIT</td></tr><tr><td><a href="https://github.com/thomasjbradley/signature-pad">SignaturePad</a></td><td>Drawn signatures on quotes and contracts.</td><td>BSD&nbsp;3-Clause</td></tr></tbody></table>

Payment gateway libraries are credited in the manifest alongside the rest.

## Components under the GPL

A small number of the included components are licensed under the GNU General Public Licence rather than a permissive licence. For those components the GPL applies, and the rights it gives you — including to the source, which is included — are yours. The full GPL text is at `Licensing/licenses/GPL-2.0.txt`.

This does not change the licence of the rest of the product, which remains the Envato licence you bought. A product made of parts under different licences is normal; what matters is that each part keeps its own terms, which is what the manifest records.

## Components that arrived without a licence file

We would rather tell you this than quietly leave it out. Eight of the bundled components arrived without a licence file or a licence header. Each one was looked at individually and the outcome is written down in `Licensing/licenses/REVIEWED.txt`.

Five of them are marked `NO LICENCE TEXT` in the manifest: the upstream project has an ordinary licence and our copy is simply missing the file. The other three are marked `AUTHOR CLEARED`: no licence text exists for them anywhere, and the author of this product has reviewed each one and confirmed it is cleared for inclusion here.

That clearance covers this product. If you are lifting one of those three components out of it to use somewhere else, read that file first and ask.

## Reporting a problem with attribution

If you are the author of something included here and the credit or licence is wrong or missing, please say so through the item’s comments page on CodeCanyon. It will be corrected in the next release.
