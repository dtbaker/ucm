/**
 * Produce one folder to upload, and tell the person uploading it exactly what to do.
 *
 *   node scripts/build-upload.mjs
 *
 * Deployment is: build, then copy a folder onto the web server. There is no server side
 * component, no Node on the host, no database and no build step on the far end. The
 * output of this script is the whole site, laid out the way it has to sit on disk.
 *
 * It expects `npm run build` to have run first, because it deliberately does not build
 * anything itself - a deploy script that quietly rebuilds is a deploy script that can
 * ship something nobody looked at.
 */

import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { siteDir } from './lib/content.mjs';
import { origin, base, url, apiUrl } from '../site.mjs';

const web = join( siteDir, 'dist', 'web' );
const out = join( siteDir, 'dist', 'upload' );

if ( ! existsSync( join( web, 'index.html' ) ) ) {
	console.error( 'dist/web is not built. Run: npm run build' );
	process.exit( 1 );
}

// The folder the site sits in, as a name: '/docs' -> 'docs'. An empty base means the
// site is the whole host, and then the upload folder's contents go straight into the
// document root rather than into a folder inside it.
const folder = base.replace( /^\//, '' );

rmSync( out, { recursive: true, force: true } );
mkdirSync( out, { recursive: true } );

const target = folder ? join( out, folder ) : out;
cpSync( web, target, { recursive: true } );

// The 404 page has to be named from the document root, because that is the only address
// Apache will accept here. site.mjs knows where the site lives; the file does not.
const htaccess = join( target, '.htaccess' );
if ( existsSync( htaccess ) ) {
	writeFileSync(
		htaccess,
		readFileSync( htaccess, 'utf8' ).replace(
			/^ErrorDocument 404 .*$/m,
			`ErrorDocument 404 ${ base }/404.html`
		)
	);
} else {
	console.error( 'dist/web has no .htaccess - public/.htaccess should have been copied into it.' );
	process.exit( 1 );
}

function measure( dir ) {
	let files = 0;
	let bytes = 0;
	for ( const entry of readdirSync( dir, { withFileTypes: true } ) ) {
		const full = join( dir, entry.name );
		if ( entry.isDirectory() ) {
			const inner = measure( full );
			files += inner.files;
			bytes += inner.bytes;
		} else {
			files++;
			bytes += statSync( full ).size;
		}
	}
	return { files, bytes };
}

const { files, bytes } = measure( out );
const mb = ( bytes / 1024 / 1024 ).toFixed( 1 );
const where = folder ? `public_html/${ folder }/` : 'public_html/';
const instruction = folder
	? `Upload the "${ folder }" folder into public_html/ on the web server, so that the files\nend up at:`
	: `Upload the contents of this folder into public_html/ on the web server, so that the\nfiles end up at:`;

writeFileSync(
	join( out, 'UPLOAD-THIS.txt' ),
	`Ultimate Client Manager documentation
=====================================

${ files } files, ${ mb } MB. Static HTML, CSS, JavaScript and JSON. Nothing to run,
nothing to install, no database.

To publish it
-------------

${ instruction }

    ${ where }index.html
    ${ where }using/invoices/index.html
    ${ where }api/v1/using/invoices.json

That is the entire deployment. Replace the folder wholesale each time - do not merge
into the old one, or files deleted since the last build stay behind.

Then check these two addresses in a browser:

    ${ url }
    ${ apiUrl }using/invoices.json     (should be JSON, starting with { )

If the second one does not return JSON, every installation quietly keeps using the copy
of the documentation inside its own download, which is the designed behaviour. Nothing
is broken while you sort it out.

Notes
-----

.htaccess is included and matters. It stops the site above this folder from rewriting
these addresses, and points 404s at this site's own 404 page. Make sure your upload tool
is not hiding dotfiles.

The address here is ${ origin }${ base }/. It is set in docs-site/site.mjs,
and it has to agree with DOCS_WEB and DOCS_API in
includes/plugin_help/docs_api.php. If you move the site, move both.
`
);

console.log( `dist/upload/ is ready: ${ files } files, ${ mb } MB` );
console.log( `Upload dist/upload/${ folder ? `${ folder }/` : '' } to ${ where } - see dist/upload/UPLOAD-THIS.txt` );
