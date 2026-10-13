/**
 * One-time import of the hand written Documentation/*.html pages into Markdown.
 *
 * Kept in the repository rather than deleted so that the provenance of the content under
 * src/content/docs is checkable: these twelve pages were not written here, they were
 * converted from the offline archive, which was the better of the two documents the
 * product had. Run it again only if you want to start that conversion over - it will
 * overwrite, and anything written since would be lost.
 *
 *   node scripts/import-legacy-html.mjs
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import TurndownService from 'turndown';

const here = dirname( fileURLToPath( import.meta.url ) );
const repo = join( here, '..', '..' );
const out = join( here, '..', 'src', 'content', 'docs' );

/**
 * Where each legacy page lands, and what the build should do with it.
 *
 * `offline` keeps a page in the CodeCanyon archive; `web` puts it on the documentation
 * site. The twelve pages below are all of the archive, so all of them stay in it - the
 * archive is a submission requirement and this import must not quietly shrink it.
 */
const pages = [
	{ file: 'index.html', slug: 'index', order: 0, section: null },
	{ file: 'requirements.html', slug: 'install/requirements', order: 1, section: 'install' },
	{ file: 'installation.html', slug: 'install/installing', order: 2, section: 'install' },
	{ file: 'upgrading.html', slug: 'install/upgrading', order: 3, section: 'install' },
	{ file: 'configuration.html', slug: 'running/configuration', order: 1, section: 'running' },
	{ file: 'cron.html', slug: 'running/scheduled-tasks', order: 2, section: 'running' },
	{ file: 'payments.html', slug: 'running/payment-methods', order: 3, section: 'running' },
	{ file: 'plugins.html', slug: 'developers/plugins', order: 1, section: 'developers' },
	{ file: 'customising.html', slug: 'developers/customising', order: 2, section: 'developers' },
	{ file: 'reference/troubleshooting.html', from: 'troubleshooting.html', slug: 'reference/troubleshooting', order: 1, section: 'reference' },
	{ file: 'changelog.html', slug: 'reference/changelog', order: 2, section: 'reference' },
	{ file: 'credits.html', slug: 'reference/credits', order: 3, section: 'reference' },
];

const turndown = new TurndownService( {
	headingStyle: 'atx',
	codeBlockStyle: 'fenced',
	bulletListMarker: '-',
	emDelimiter: '_',
} );

// Tables and definition lists survive as HTML. Markdown's table syntax cannot hold the
// multi-sentence cells these pages use, and both renderers pass HTML through untouched.
turndown.keep( [ 'table', 'dl' ] );

// The callout boxes are the most useful thing in the old archive. Keep them as callouts
// rather than flattening them into ordinary paragraphs.
const asideFor = { note: 'note', ok: 'tip', warn: 'caution', stop: 'danger' };
turndown.addRule( 'callout', {
	filter: ( node ) =>
		node.nodeName === 'DIV' && [ 'note', 'ok', 'warn', 'stop' ].includes( node.getAttribute( 'class' ) ),
	replacement: ( content, node ) => {
		const kind = asideFor[ node.getAttribute( 'class' ) ];
		const strong = node.querySelector( 'strong' );
		const title = strong ? strong.textContent.trim() : '';
		// The title has already been rendered into `content` as bold text; drop that copy.
		const body = content.replace( /^\s*\*\*[^*]+\*\*\s*/, '' ).trim();
		return `\n\n:::${ kind }${ title ? `[${ title }]` : '' }\n${ body }\n:::\n\n`;
	},
} );

turndown.addRule( 'unwrapTableWrap', {
	filter: ( node ) => node.nodeName === 'DIV' && node.getAttribute( 'class' ) === 'table-wrap',
	replacement: ( content ) => content,
} );

// The card grid on the index page. Turndown flattens <strong> and <span> inside a link
// into one run-on string, so give it a shape that survives: name, dash, explanation.
turndown.addRule( 'cards', {
	filter: ( node ) => node.nodeName === 'UL' && node.getAttribute( 'class' ) === 'cards',
	replacement: ( content, node ) => {
		const items = Array.from( node.querySelectorAll( 'li > a' ) ).map( ( link ) => {
			const name = link.querySelector( 'strong' )?.textContent.trim() || link.textContent.trim();
			const blurb = link.querySelector( 'span' )?.textContent.trim() || '';
			return `- [${ name }](${ link.getAttribute( 'href' ) })${ blurb ? ` — ${ blurb }` : '' }`;
		} );
		return `\n\n${ items.join( '\n' ) }\n\n`;
	},
} );

function yaml( value ) {
	return `'${ String( value ).replace( /'/g, "''" ) }'`;
}

for ( const page of pages ) {
	const source = join( repo, 'Documentation', page.from || page.file.replace( /^reference\//, '' ) );
	if ( ! existsSync( source ) ) {
		console.warn( `skipped, not found: ${ source }` );
		continue;
	}
	const html = readFileSync( source, 'utf8' );

	const main = html.match( /<main>([\s\S]*?)<\/main>/ );
	if ( ! main ) {
		console.warn( `skipped, no <main>: ${ source }` );
		continue;
	}
	let body = main[ 1 ];

	const title = ( body.match( /<h1[^>]*>([\s\S]*?)<\/h1>/ ) || [ , '' ] )[ 1 ].replace( /<[^>]+>/g, '' ).trim();
	const lead = ( body.match( /<p class="lead">([\s\S]*?)<\/p>/ ) || [ , '' ] )[ 1 ]
		.replace( /<[^>]+>/g, '' )
		.replace( /\s+/g, ' ' )
		.trim();

	// Page furniture the build regenerates: the title, the kicker above it, the lead
	// paragraph (which becomes the description) and the previous/next pager.
	body = body
		.replace( /<p class="kicker">[\s\S]*?<\/p>/g, '' )
		.replace( /<h1[^>]*>[\s\S]*?<\/h1>/, '' )
		.replace( /<p class="lead">[\s\S]*?<\/p>/, '' )
		.replace( /<nav class="pager"[\s\S]*?<\/nav>/g, '' )
		.replace( /<div class="pager">[\s\S]*?<\/div>/g, '' );

	// The archive links between flat .html files in one folder. The documentation site has
	// sections, so every one of these has to be repointed - and the pair of builds then
	// turns the new path back into a relative .html link for the archive.
	body = body.replace( /href="([a-z0-9\-]+)\.html(#[^"]*)?"/g, ( whole, file, fragment ) => {
		const target = pages.find( ( p ) => ( p.from || p.file ) === `${ file }.html` );
		return target ? `href="/${ target.slug === 'index' ? '' : target.slug + '/' }${ fragment || '' }"` : whole;
	} );

	const markdown = turndown
		.turndown( body )
		.replace( /\n{3,}/g, '\n\n' )
		.trim();

	const frontmatter = [
		'---',
		`title: ${ yaml( title ) }`,
		lead ? `description: ${ yaml( lead ) }` : null,
		`sidebar:`,
		`  order: ${ page.order }`,
		'offline: true',
		'---',
	].filter( Boolean ).join( '\n' );

	const target = join( out, `${ page.slug }.md` );
	mkdirSync( dirname( target ), { recursive: true } );
	writeFileSync( target, `${ frontmatter }\n\n${ markdown }\n` );
	console.log( `${ source } -> ${ target }` );
}
