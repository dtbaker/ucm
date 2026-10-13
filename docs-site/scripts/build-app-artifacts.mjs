/**
 * The three artifacts the application itself needs, built from the content tree.
 *
 *   public/api/v1/                        what the help panel fetches, served by the CDN
 *   ../includes/plugin_help/content/      the same thing, shipped inside the download
 *   ../includes/plugin_NAME/NAME.help.json  which screen gets which article
 *
 * The last one is the interesting one. Twenty two *.help.json files have shipped to five
 * thousand installations since 2014 and nothing has ever read them; they rotted because
 * they were hand maintained data with no consumer. They are now build output with a
 * consumer, a --check mode and scripts/check-content.mjs, which is the only arrangement
 * that stays true.
 *
 *   node scripts/build-app-artifacts.mjs [--check]
 *
 * --check writes nothing and exits non-zero if what is on disk differs from what would be
 * written. That is what CI runs.
 */

import { mkdirSync, readFileSync, writeFileSync, existsSync, readdirSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { readArticles, renderMarkdown, stripMarkdown, siteDir, repoDir } from './lib/content.mjs';
import { absolute } from '../site.mjs';

const check = process.argv.includes( '--check' );
const differences = [];

function emit( path, contents ) {
	if ( check ) {
		// Only the artifacts that are committed can be checked. public/api/v1 is the copy
		// the CDN serves: it is gitignored and rebuilt by the deploy, so on a clean
		// checkout it simply is not there, and comparing against it would fail every time
		// for a reason nobody can act on. The shipped bundle under includes/ is the one
		// that has to stay in step, because it goes out inside the product.
		if ( path.includes( '/public/api/' ) ) {
			return;
		}
		const current = existsSync( path ) ? readFileSync( path, 'utf8' ) : null;
		if ( current !== contents ) {
			differences.push( path );
		}
		return;
	}
	mkdirSync( dirname( path ), { recursive: true } );
	writeFileSync( path, contents );
}

const articles = readArticles();

// ---------------------------------------------------------------- the article payloads

function payload( article ) {
	return {
		slug: article.slug,
		title: article.title,
		summary: article.summary,
		// Links in the panel open the website in a new tab, so they carry the whole
		// address rather than a path that means nothing inside an application.
		html: renderMarkdown( article.body, absolute ),
		video: article.video,
		updated: '',
	};
}

/**
 * The catalogue doubles as the list of slugs the application is permitted to build a URL
 * out of, so it has to contain every article and nothing else. See
 * ucm_help_docs::is_known_slug().
 *
 * It is called _index.json rather than index.json because the site's own front page has
 * the slug `index`, and an article payload called index.json would land on top of it.
 * clean_slug() will not produce a leading underscore followed by this name from anything
 * a caller can send, so the two can never collide.
 */
const CATALOGUE = '_index.json';
const index = {};
for ( const article of articles ) {
	index[ article.slug ] = {
		title: article.title,
		summary: article.summary,
		featured: article.featured,
		...( article.video ? { video: article.video } : {} ),
	};
}

const json = ( value ) => `${ JSON.stringify( value, null, '\t' ) }\n`;

// 1. What the website serves. Astro copies public/ into dist/web untouched.
emit( join( siteDir, 'public', 'api', 'v1', CATALOGUE ), json( index ) );
for ( const article of articles ) {
	emit( join( siteDir, 'public', 'api', 'v1', `${ article.slug }.json` ), json( payload( article ) ) );
}

// 2. What ships in the download. Flat filenames, because the application reads these with
//    a path it builds itself and a flat directory means it cannot be made to walk out of.
const bundleDir = join( repoDir, 'includes', 'plugin_help', 'content' );
if ( ! check && existsSync( bundleDir ) ) {
	for ( const entry of readdirSync( bundleDir ) ) {
		if ( entry.endsWith( '.json' ) ) {
			rmSync( join( bundleDir, entry ) );
		}
	}
}
emit( join( bundleDir, CATALOGUE ), json( index ) );
for ( const article of articles ) {
	emit( join( bundleDir, `${ article.slug.replace( /\//g, '--' ) }.json` ), json( payload( article ) ) );
}

// ---------------------------------------------------------------- the help maps

/**
 * Invert the articles' help_keys into one file per plugin.
 *
 * A key looks like `invoice-invoice_admin_edit`: the plugin, then the page. Everything
 * before the first dash names the file the map is written to.
 */
const maps = {};
for ( const article of articles ) {
	for ( const key of article.helpKeys ) {
		const module = key.split( '-' )[ 0 ];
		maps[ module ] = maps[ module ] || { pages: {} };
		maps[ module ].pages[ key ] = article.slug;
	}
}

/**
 * The article a plugin falls back to when a screen has no article of its own. Taken from
 * whichever of its articles covers the most screens, which in practice is the overview.
 */
for ( const [ module, map ] of Object.entries( maps ) ) {
	const tally = {};
	for ( const slug of Object.values( map.pages ) ) {
		tally[ slug ] = ( tally[ slug ] || 0 ) + 1;
	}
	map.article = Object.entries( tally ).sort( ( a, b ) => b[ 1 ] - a[ 1 ] )[ 0 ][ 0 ];
}

/**
 * Where a plugin's map lives. These paths are the convention the dead files already used,
 * and the application looks for them in exactly these places.
 */
function mapPath( module ) {
	if ( module === 'pages' ) {
		return join( repoDir, 'pages', 'home.help.json' );
	}
	return join( repoDir, 'includes', `plugin_${ module }`, `${ module }.help.json` );
}

for ( const [ module, map ] of Object.entries( maps ) ) {
	const path = mapPath( module );
	if ( ! existsSync( dirname( path ) ) ) {
		console.warn( `no such plugin, skipping map: ${ module }` );
		continue;
	}

	// Keep the title, the description and the per-setting help that were written by hand
	// in the original files. Only the parts that point somewhere are regenerated - the
	// prose was never the thing that rotted.
	let kept = {};
	if ( existsSync( path ) ) {
		try {
			const previous = JSON.parse( readFileSync( path, 'utf8' ) );
			kept = {
				...( previous.title ? { title: previous.title } : {} ),
				...( previous.description ? { description: previous.description } : {} ),
				...( previous.config ? { config: previous.config } : {} ),
			};
		} catch {
			console.warn( `could not read, regenerating from scratch: ${ path }` );
		}
	}

	const ordered = {
		_generated: 'Written by docs-site/scripts/build-app-artifacts.mjs. Edit the article frontmatter, not this file.',
		...kept,
		article: map.article,
		pages: Object.fromEntries( Object.entries( map.pages ).sort( ( a, b ) => a[ 0 ].localeCompare( b[ 0 ] ) ) ),
	};

	emit( path, json( ordered ) );
}

// ---------------------------------------------------------------- report

const words = articles.reduce( ( total, a ) => total + stripMarkdown( a.body ).split( /\s+/ ).length, 0 );
const bundleBytes = articles.reduce( ( total, a ) => total + json( payload( a ) ).length, 0 );

if ( check ) {
	if ( differences.length ) {
		console.error( 'The generated help files are out of date. Run: npm run build:app\n' );
		for ( const path of differences ) {
			console.error( `  ${ path }` );
		}
		process.exit( 1 );
	}
	console.log( `help files are in step with the content (${ articles.length } articles)` );
} else {
	console.log(
		`${ articles.length } articles, ${ words.toLocaleString() } words, ` +
			`${ Math.round( bundleBytes / 1024 ) } KB bundled, ` +
			`${ Object.keys( maps ).length } plugin help maps`
	);
}
