/**
 * Where the documentation is published.
 *
 * One answer, in one file. The Astro config, the script that writes the application's
 * JSON, the script that writes the offline archive and the deploy script all read it
 * from here, because the previous arrangement had the address typed out in four places
 * and they were only ever going to agree for as long as nobody moved the site.
 *
 * The default is a folder on the main website:
 *
 *   https://ultimateclientmanager.com/docs/
 *
 * That is a plain folder of static files uploaded into public_html/docs/. It needs no
 * DNS record, no second host and no Node on the server. WordPress leaves it alone,
 * because its rewrite rules exempt paths that exist on disk. See README.md.
 *
 * To build for somewhere else - a subdomain, a staging host, a local check - set the
 * environment variables rather than editing this file:
 *
 *   UCM_DOCS_ORIGIN=https://docs.ultimateclientmanager.com UCM_DOCS_BASE= npm run build
 *
 * Changing either of these changes the URL the application asks for help at. The
 * matching constants are DOCS_WEB and DOCS_API in includes/plugin_help/docs_api.php,
 * and tests/unit/HelpDocsTest.php pins them. Move one without the other and five
 * thousand installations quietly fall back to their bundled copy.
 */

/** The scheme and host, with no trailing slash. */
export const origin = String( process.env.UCM_DOCS_ORIGIN || 'https://ultimateclientmanager.com' ).replace(
	/\/+$/,
	''
);

/**
 * The folder the site sits in, with a leading slash and no trailing one, or '' for the
 * root of a host. Astro wants it in exactly that shape and so does everything below.
 */
export const base = String( process.env.UCM_DOCS_BASE ?? '/docs' )
	.trim()
	.replace( /\/+$/, '' )
	.replace( /^(?!$|\/)/, '/' );

/** The home page, with a trailing slash: what a person types to read the documentation. */
export const url = `${ origin }${ base }/`;

/** Where the application fetches articles from. Static JSON, no server behind it. */
export const apiUrl = `${ url }api/v1/`;

/**
 * A path written in an article, as a full address.
 *
 * Articles link to each other as `/using/invoices/`, which is the path within the site
 * and not the path on the server once the site lives in a folder. Anything that leaves
 * the site - the JSON the help panel reads, the offline archive in the download - has to
 * carry the whole address, so this is where the two are joined up.
 *
 * @param {string} href
 *
 * @returns {string}
 */
export function absolute( href ) {
	return href.startsWith( '/' ) ? origin + base + href : href;
}
