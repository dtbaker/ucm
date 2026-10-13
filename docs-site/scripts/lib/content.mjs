/**
 * Reading the content tree.
 *
 * Shared by every build script so there is one answer to "what articles are there and
 * what does each one claim", rather than three slightly different answers that drift.
 */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import matter from 'gray-matter';
import { marked } from 'marked';

export const scriptsDir = dirname( dirname( fileURLToPath( import.meta.url ) ) );
export const siteDir = dirname( scriptsDir );
export const repoDir = dirname( siteDir );
export const contentDir = join( siteDir, 'src', 'content', 'docs' );

/** Headings, links and callouts all render the same way in every artifact. */
marked.setOptions( { gfm: true, breaks: false } );

function walk( dir ) {
	const found = [];
	for ( const entry of readdirSync( dir ) ) {
		const full = join( dir, entry );
		if ( statSync( full ).isDirectory() ) {
			found.push( ...walk( full ) );
		} else if ( entry.endsWith( '.md' ) || entry.endsWith( '.mdx' ) ) {
			found.push( full );
		}
	}
	return found;
}

/**
 * Every article, in sidebar order.
 *
 * @returns {Array<{slug: string, section: string, title: string, summary: string,
 *                  helpKeys: string[], video: string, offline: boolean,
 *                  featured: boolean, order: number, body: string, file: string}>}
 */
export function readArticles() {
	const articles = walk( contentDir ).map( ( file ) => {
		const { data, content } = matter( readFileSync( file, 'utf8' ) );
		const rel = relative( contentDir, file ).replace( /\.mdx?$/, '' ).split( sep ).join( '/' );
		const slug = rel === 'index' ? 'index' : rel;
		return {
			file,
			slug,
			section: slug.includes( '/' ) ? slug.split( '/' )[ 0 ] : '',
			title: data.title || slug,
			summary: data.description || '',
			helpKeys: Array.isArray( data.help_keys ) ? data.help_keys : [],
			video: typeof data.video === 'string' ? data.video : '',
			offline: data.offline === true,
			featured: data.featured === true,
			order: data.sidebar?.order ?? 99,
			body: content,
		};
	} );

	// Index first, then by section in the order the site presents them, then by the
	// sidebar order each article declares.
	const sections = [ '', 'install', 'running', 'using', 'developers', 'reference' ];
	articles.sort( ( a, b ) => {
		if ( a.slug === 'index' ) return -1;
		if ( b.slug === 'index' ) return 1;
		const bySection = sections.indexOf( a.section ) - sections.indexOf( b.section );
		if ( bySection !== 0 ) return bySection;
		if ( a.order !== b.order ) return a.order - b.order;
		return a.slug.localeCompare( b.slug );
	} );

	return articles;
}

/**
 * Markdown to HTML, with the callouts the content uses rendered as the boxes the archive
 * stylesheet already knows how to draw.
 *
 *   :::caution[Title]   ->  <div class="warn"><strong>Title</strong>...</div>
 *
 * @param {string} markdown
 * @param {(href: string) => string} linkRewriter
 */
export function renderMarkdown( markdown, linkRewriter = ( href ) => href ) {
	const kinds = { note: 'note', tip: 'ok', caution: 'warn', danger: 'stop' };

	// The blank lines around the div and before the closing tag are load-bearing: an HTML
	// block in Markdown runs until a blank line, so without them the opening tag swallows
	// the callout's own text and the closing tag swallows the heading that follows it.
	const withCallouts = markdown.replace(
		/^:::(note|tip|caution|danger)(?:\[([^\]]*)\])?\n([\s\S]*?)^:::[ \t]*$/gm,
		( whole, kind, title, body ) =>
			[
				'',
				`<div class="${ kinds[ kind ] }">`,
				'',
				title ? `**${ title }**\n` : '',
				body.trim(),
				'',
				'</div>',
				'',
			].join( '\n' )
	);

	let html = marked.parse( withCallouts );

	// Tables came through the import as raw HTML; wrap them so a wide one scrolls inside
	// the article rather than widening the page.
	html = html.replace( /<table>/g, '<div class="table-wrap"><table>' ).replace( /<\/table>/g, '</table></div>' );

	html = html.replace( /href="([^"]+)"/g, ( whole, href ) => `href="${ linkRewriter( href ) }"` );

	return html.trim();
}

/** Plain text, for the summary line and for word counts. */
export function stripMarkdown( markdown ) {
	return markdown
		.replace( /^:::.*$/gm, '' )
		.replace( /<[^>]+>/g, ' ' )
		.replace( /\[([^\]]*)\]\([^)]*\)/g, '$1' )
		.replace( /[#*_`>-]/g, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();
}
