/**
 * Checks that stop the documentation drifting away from the application.
 *
 * Twenty two *.help.json files shipped to five thousand installations for a decade
 * pointing at articles that had moved, on a site that had been restructured, read by
 * nothing. That happened because nothing ever checked. These are the checks.
 *
 *   node scripts/check-content.mjs
 *
 * Run by `npm run check`, which CI runs. It needs no network and no database - it reads
 * the content tree and the PHP source tree and compares them.
 */

import { readdirSync, existsSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { readArticles, repoDir } from './lib/content.mjs';

const problems = [];

// ---------------------------------------------------------------- the real screens

/**
 * Every value index.php can produce for $page_unique_id, derived the way index.php
 * derives it: the plugin folder and the page filename.
 */
function realPageKeys() {
	const keys = new Set();

	for ( const entry of readdirSync( join( repoDir, 'includes' ) ) ) {
		if ( ! entry.startsWith( 'plugin_' ) ) {
			continue;
		}
		const pages = join( repoDir, 'includes', entry, 'pages' );
		if ( ! existsSync( pages ) || ! statSync( pages ).isDirectory() ) {
			continue;
		}
		const module = entry.replace( /^plugin_/, '' );
		for ( const page of readdirSync( pages ) ) {
			if ( page.endsWith( '.php' ) ) {
				keys.add( `${ module }-${ page.replace( /\.php$/, '' ) }` );
			}
		}
	}

	for ( const page of readdirSync( join( repoDir, 'pages' ) ) ) {
		if ( page.endsWith( '.php' ) ) {
			keys.add( `pages-${ page.replace( /\.php$/, '' ) }` );
		}
	}

	return keys;
}

const articles = readArticles();
const keys = realPageKeys();
const slugs = new Set( articles.map( ( a ) => a.slug ) );

// 1. Every screen an article claims to document has to be a screen that exists. This is
//    the check that would have caught the old files: a page renamed in the PHP leaves a
//    help key pointing at nothing, and nobody notices until a user clicks the button.
for ( const article of articles ) {
	for ( const key of article.helpKeys ) {
		if ( ! keys.has( key ) ) {
			problems.push(
				`${ article.slug }: help_keys lists "${ key }", but no page in the application produces that id`
			);
		}
	}
}

// 2. Two articles claiming the same screen means one of them silently never wins.
const claimed = new Map();
for ( const article of articles ) {
	for ( const key of article.helpKeys ) {
		if ( claimed.has( key ) ) {
			problems.push( `"${ key }" is claimed by both ${ claimed.get( key ) } and ${ article.slug }` );
		}
		claimed.set( key, article.slug );
	}
}

// 3. Internal links have to point at an article that exists. A dead link in the panel is
//    worse than no link, because the reader has already decided to trust it.
for ( const article of articles ) {
	for ( const match of article.body.matchAll( /\]\((\/[^)#]*)(#[^)]*)?\)/g ) ) {
		const slug = match[ 1 ].replace( /^\/|\/$/g, '' ) || 'index';
		if ( ! slugs.has( slug ) ) {
			problems.push( `${ article.slug }: links to /${ slug }/, which is not an article` );
		}
	}
}

// 4. A video id is eleven characters. A pasted watch URL is the usual mistake and it
//    produces a facade that loads nothing.
for ( const article of articles ) {
	if ( article.video && ! /^[A-Za-z0-9_-]{11}$/.test( article.video ) ) {
		problems.push(
			`${ article.slug }: video "${ article.video }" is not an eleven character YouTube id - ` +
				`paste just the id from the share URL, not the whole address`
		);
	}
}

// 5. The archive has to keep its shape. Envato wants documentation in the download, and
//    an article quietly losing `offline: true` is how it gets thinner.
const offline = articles.filter( ( a ) => a.offline );
if ( offline.length < 12 ) {
	problems.push(
		`only ${ offline.length } articles are marked offline: true. The CodeCanyon archive has ` +
			`always had at least 12 pages - check nothing has lost the flag`
	);
}
if ( ! offline.some( ( a ) => a.slug === 'index' ) ) {
	problems.push( 'the archive has no index page - index.md needs offline: true' );
}

// ---------------------------------------------------------------- report

if ( problems.length ) {
	console.error( `${ problems.length } problem${ problems.length === 1 ? '' : 's' } in the documentation:\n` );
	for ( const problem of problems ) {
		console.error( `  ${ problem }` );
	}
	process.exit( 1 );
}

console.log(
	`${ articles.length } articles, ${ claimed.size } screens mapped out of ${ keys.size }, ` +
		`${ offline.length } in the offline archive`
);
