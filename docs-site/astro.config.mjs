// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

/**
 * The documentation site: docs.ultimateclientmanager.com.
 *
 * This is one of several things built from src/content/docs. The others are the offline
 * archive that goes in the CodeCanyon zip, the JSON the application's help panel reads,
 * and the copy of that JSON shipped inside the download. See scripts/ and README.md.
 *
 * Pages marked `web: false` in their frontmatter are left out here - that is how the
 * installation and troubleshooting material and the feature documentation stay in one
 * source tree without the website filling up with chmod advice.
 */
export default defineConfig( {
	site: 'https://docs.ultimateclientmanager.com',
	outDir: './dist/web',
	integrations: [
		starlight( {
			title: 'Ultimate Client Manager',
			description:
				'Documentation for Ultimate Client Manager, a self hosted CRM, quoting, invoicing and job tracking system.',
			// Nothing is loaded from anywhere else. The offline archive is built from the
			// same content and has to work from a zip file with no network, so a web font
			// or a hosted script here would be a difference between the two builds that
			// only shows up at submission time.
			customCss: [ './src/styles/ucm.css' ],
			pagefind: true,
			lastUpdated: true,
			editLink: {
				baseUrl: 'https://github.com/dtbaker/ucm/edit/master/docs-site/',
			},
			sidebar: [
				{ label: 'Overview', link: '/' },
				{ label: 'Installing', items: [ { autogenerate: { directory: 'install' } } ] },
				{ label: 'Running it', items: [ { autogenerate: { directory: 'running' } } ] },
				{ label: 'Using it', items: [ { autogenerate: { directory: 'using' } } ] },
				{ label: 'Making it yours', items: [ { autogenerate: { directory: 'developers' } } ] },
				{ label: 'Reference', items: [ { autogenerate: { directory: 'reference' } } ] },
				{ label: 'Videos', link: '/videos/' },
			],
		} ),
	],
} );
