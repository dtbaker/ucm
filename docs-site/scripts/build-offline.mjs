/**
 * The offline archive, for the CodeCanyon zip.
 *
 * Envato requires documentation in the download, and it has to work from a zip file on a
 * laptop with no network. That rules out Starlight's output for this one artifact: it
 * links everything from the site root, loads a search index over the network and assumes a
 * server. So the archive is rendered here instead, from the same Markdown, into the same
 * HTML shape and the same stylesheet the hand written archive already used.
 *
 * "One source" does not mean "the same pages". The archive is the operational half -
 * requirements, installing, upgrading, cron, payments, file permissions. The feature
 * documentation belongs on the website and would only pad the archive. Articles opt in
 * with `offline: true` in their frontmatter.
 *
 * Output goes to dist/offline/. It is deliberately NOT written over Documentation/ -
 * compare the two and copy it across when you are happy, or let the release script do it.
 * See docs-site/README.md.
 *
 *   node scripts/build-offline.mjs
 */

import { cpSync, mkdirSync, writeFileSync, existsSync, rmSync } from 'node:fs';
import { join } from 'node:path';
import { readArticles, renderMarkdown, siteDir, repoDir } from './lib/content.mjs';
import { absolute } from '../site.mjs';

const out = join( siteDir, 'dist', 'offline' );

/** The archive is flat: one folder of .html files, which is what a zip reader expects. */
function fileFor( slug ) {
	return slug === 'index' ? 'index.html' : `${ slug.replace( /\//g, '-' ) }.html`;
}

const sections = {
	install: 'Getting started',
	running: 'Running it',
	developers: 'Making it yours',
	reference: 'Reference',
};

const articles = readArticles().filter( ( a ) => a.offline );
const byFile = new Map( readArticles().map( ( a ) => [ a.slug, a ] ) );

/**
 * Links in the content are absolute paths into the website. Turn them back into the
 * relative filenames this archive uses - and where an article is website-only, send the
 * reader to the website rather than to a page that is not in the zip.
 */
function rewrite( href ) {
	if ( ! href.startsWith( '/' ) ) {
		return href;
	}
	const [ path, fragment ] = href.split( '#' );
	const slug = path.replace( /^\/|\/$/g, '' ) || 'index';
	const article = byFile.get( slug );
	if ( article && article.offline ) {
		return fileFor( slug ) + ( fragment ? `#${ fragment }` : '' );
	}
	return absolute( href );
}

function escape( text ) {
	return String( text ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
}

function sidebar( current ) {
	const grouped = {};
	for ( const article of articles ) {
		if ( article.slug === 'index' ) {
			continue;
		}
		( grouped[ article.section ] = grouped[ article.section ] || [] ).push( article );
	}

	const parts = [
		`\t<a class="brand" href="index.html">Ultimate Client Manager<span>Documentation</span></a>`,
		`\t<nav aria-label="Documentation">`,
		`\t\t<h2>${ sections.install }</h2>`,
		`\t\t<ol>`,
		`\t\t\t<li><a href="index.html"${ current === 'index' ? ' aria-current="page"' : '' }>Overview</a></li>`,
	];
	for ( const article of grouped.install || [] ) {
		parts.push(
			`\t\t\t<li><a href="${ fileFor( article.slug ) }"${ current === article.slug ? ' aria-current="page"' : '' }>${ escape( article.title ) }</a></li>`
		);
	}
	parts.push( `\t\t</ol>` );

	for ( const key of [ 'running', 'developers', 'reference' ] ) {
		if ( ! grouped[ key ] ) {
			continue;
		}
		parts.push( `\t\t<h2>${ sections[ key ] }</h2>`, `\t\t<ol>` );
		for ( const article of grouped[ key ] ) {
			parts.push(
				`\t\t\t<li><a href="${ fileFor( article.slug ) }"${ current === article.slug ? ' aria-current="page"' : '' }>${ escape( article.title ) }</a></li>`
			);
		}
		parts.push( `\t\t</ol>` );
	}
	parts.push( `\t</nav>` );

	return parts.join( '\n' );
}

function pager( position ) {
	const previous = articles[ position - 1 ];
	const next = articles[ position + 1 ];

	return [
		'<div class="pager">',
		previous ? `\t<a href="${ fileFor( previous.slug ) }">${ escape( previous.title ) }</a>` : '\t<span></span>',
		next ? `\t<a class="next" href="${ fileFor( next.slug ) }">${ escape( next.title ) }</a>` : '\t<span></span>',
		'</div>',
	].join( '\n' );
}

if ( existsSync( out ) ) {
	rmSync( out, { recursive: true } );
}
mkdirSync( join( out, 'assets' ), { recursive: true } );

// The stylesheet the archive already had. It is good, it is self contained, and it is
// what makes the archive and the website read as one document - so it is reproduced
// rather than redesigned.
cpSync( join( repoDir, 'Documentation', 'assets', 'docs.css' ), join( out, 'assets', 'docs.css' ) );

articles.forEach( ( article, position ) => {
	const kicker = article.slug === 'index' ? 'Documentation' : sections[ article.section ] || 'Documentation';
	const body = renderMarkdown( article.body, rewrite );

	const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${ escape( article.title ) } &mdash; Ultimate Client Manager</title>
<link rel="stylesheet" href="assets/docs.css">
</head>
<!--
	Generated from docs-site/src/content/docs/${ article.slug }.md by
	docs-site/scripts/build-offline.mjs. Edits made here are lost on the next build -
	change the Markdown instead.
-->
<body>
<div class="layout">
<aside class="sidebar">
${ sidebar( article.slug ) }
</aside>
<main>

<p class="kicker">${ escape( kicker ) }</p>
<h1>${ escape( article.title ) }</h1>
${ article.summary ? `<p class="lead">${ escape( article.summary ) }</p>` : '' }

${ body }

${ pager( position ) }

</main>
</div>
</body>
</html>
`;

	writeFileSync( join( out, fileFor( article.slug ) ), html );
} );

console.log( `${ articles.length } pages -> ${ out }` );
console.log( 'Compare against Documentation/ and copy across when you are happy with it.' );
