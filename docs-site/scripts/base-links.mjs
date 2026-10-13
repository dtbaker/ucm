/**
 * Put the base path in front of the links that Astro leaves alone.
 *
 *   node scripts/base-links.mjs [--check]
 *
 * Astro's `base` is applied to the addresses its own components build - the sidebar, the
 * stylesheets, the search bundle - and to nothing written inside an article. That is
 * documented behaviour, not a bug: Markdown is content and Astro does not rewrite it.
 * The result is that `[the invoices page](/using/invoices/)` is published exactly as
 * typed, and is a 404 the moment the site lives in a folder rather than at the root of
 * a host.
 *
 * There are about ninety such links. The alternatives were to type the base into every
 * one of them, which bakes the address into the content and means moving the site is a
 * ninety file change, or to add a Markdown plugin, which in Astro 7 means installing a
 * second Markdown processor for the whole site. Rewriting the built HTML is smaller than
 * either, runs on exactly the files that get uploaded, and leaves the content portable.
 *
 * --check exits non-zero instead of writing, so a test can assert the output is clean.
 */

import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { siteDir } from './lib/content.mjs';
import { base } from '../site.mjs';

const check = process.argv.includes( '--check' );
const root = join( siteDir, 'dist', 'web' );

/**
 * href="/x" and src="/x", but not "//host/x" (protocol relative, an external address)
 * and not anything already under the base.
 */
const link = /\b(href|src)="\/(?!\/)([^"]*)"/g;

function rewrite( html ) {
	return html.replace( link, ( match, attribute, path ) => {
		const href = `/${ path }`;
		if ( base && ( href === base || href.startsWith( `${ base }/` ) ) ) {
			return match;
		}
		return `${ attribute }="${ base }${ href }"`;
	} );
}

function htmlFiles( dir ) {
	const found = [];
	for ( const entry of readdirSync( dir ) ) {
		const full = join( dir, entry );
		if ( statSync( full ).isDirectory() ) {
			found.push( ...htmlFiles( full ) );
		} else if ( entry.endsWith( '.html' ) ) {
			found.push( full );
		}
	}
	return found;
}

if ( ! base ) {
	console.log( 'The site is at the root of its host; there is nothing to prefix.' );
	process.exit( 0 );
}

const changed = [];
for ( const file of htmlFiles( root ) ) {
	const before = readFileSync( file, 'utf8' );
	const after = rewrite( before );
	if ( before !== after ) {
		changed.push( file );
		if ( ! check ) {
			writeFileSync( file, after );
		}
	}
}

if ( check ) {
	if ( changed.length ) {
		console.error( `${ changed.length } built pages still have links that ignore the base path ${ base }:` );
		for ( const file of changed.slice( 0, 10 ) ) {
			console.error( `  ${ file.replace( `${ siteDir }/`, '' ) }` );
		}
		process.exit( 1 );
	}
	console.log( `Every link in the built site is under ${ base }.` );
} else {
	console.log( `Prefixed ${ base } onto the article links in ${ changed.length } pages.` );
}
