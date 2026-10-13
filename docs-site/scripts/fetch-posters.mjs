/**
 * Download a still frame for every video referenced in the content.
 *
 * The <Video> component shows a still and only loads the player when somebody clicks it,
 * so no third party is contacted until the reader asks for the video. That only holds if
 * the still is served from here too - a thumbnail pulled straight from YouTube would
 * contact Google on page load, which is the thing the facade exists to avoid.
 *
 * Run it after adding a video id to an article. It needs network access; everything else
 * in the build does not, so it is a separate step rather than part of `npm run build`.
 *
 *   node scripts/fetch-posters.mjs
 */

import { mkdirSync, writeFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { readArticles, siteDir } from './lib/content.mjs';

const out = join( siteDir, 'public', 'video-posters' );
mkdirSync( out, { recursive: true } );

const ids = [ ...new Set( readArticles().map( ( a ) => a.video ).filter( Boolean ) ) ];

if ( ids.length === 0 ) {
	console.log( 'no videos referenced in the content yet' );
	process.exit( 0 );
}

let failures = 0;

for ( const id of ids ) {
	const target = join( out, `${ id }.jpg` );
	if ( existsSync( target ) ) {
		console.log( `already have ${ id }.jpg` );
		continue;
	}

	// maxresdefault is the 1280x720 still. Not every video has one - YouTube only keeps it
	// when the source was big enough - so fall back to the 640x480 version.
	let saved = false;
	for ( const name of [ 'maxresdefault', 'hqdefault' ] ) {
		const response = await fetch( `https://i.ytimg.com/vi/${ id }/${ name }.jpg` );
		if ( ! response.ok ) {
			continue;
		}
		writeFileSync( target, Buffer.from( await response.arrayBuffer() ) );
		console.log( `${ id }.jpg from ${ name }` );
		saved = true;
		break;
	}

	if ( ! saved ) {
		console.error( `could not fetch a still for ${ id } - is the id right, and the video uploaded?` );
		failures++;
	}
}

process.exit( failures ? 1 : 0 );
