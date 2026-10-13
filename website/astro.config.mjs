// @ts-check
import { defineConfig } from 'astro/config';

/**
 * The marketing website - a proof. See README.md and docs/proposals/website.md.
 *
 * Static output, no adapter, nothing to run on the server. It is deliberately the same
 * toolchain and the same Astro version as docs-site/, because the point of the proposal
 * is that the two become one project and one upload.
 */
export default defineConfig( {
	site: 'https://ultimateclientmanager.com',
	outDir: './dist',
	build: {
		// One folder per page with an index.html inside, so the published addresses have
		// no file extension and Apache serves them with no configuration at all.
		format: 'directory',
	},
} );
