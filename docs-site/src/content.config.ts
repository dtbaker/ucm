import { defineCollection } from 'astro:content';
import { docsLoader } from '@astrojs/starlight/loaders';
import { docsSchema } from '@astrojs/starlight/schema';
import { z } from 'astro:content';

/**
 * Starlight's own frontmatter, plus the four fields this product adds.
 *
 * `help_keys` is the important one. It lists the screens an article explains, using the
 * page identity index.php already computes for every request - `invoice-invoice_admin_edit`
 * and so on. The build inverts those lists into the *.help.json file next to each plugin,
 * which is what the help button reads. Declaring the relationship on the article means
 * there is one place to edit when a screen gains documentation, and a build step that
 * keeps the application's copy honest rather than twenty two hand maintained files that
 * drift until nothing reads them - which is exactly what happened to the last set.
 */
export const collections = {
	docs: defineCollection( {
		loader: docsLoader(),
		schema: docsSchema( {
			extend: z.object( {
				/**
				 * Screens this article documents, as index.php's $page_unique_id.
				 * eg: ['invoice-invoice_admin_edit', 'invoice-invoice_admin_list']
				 */
				help_keys: z.array( z.string() ).default( [] ),

				/**
				 * A YouTube video id, eleven characters, from the share URL.
				 *
				 * Paste it here and commit: the website gets a click-to-load player, the
				 * videos index page gets an entry, and the help panel in the application
				 * gets a "watch the video" link. Nothing else to do.
				 */
				video: z.string().length( 11 ).optional(),

				/**
				 * Include this page in the CodeCanyon offline archive.
				 *
				 * The archive and the website are not the same document. The archive is
				 * the operational half - requirements, installing, upgrading, cron, file
				 * permissions - and Envato requires it to work from a zip file with no
				 * network. The feature documentation belongs on the website and would
				 * only pad the archive out. Everything in the content tree goes on the
				 * website; this flag picks the subset that also goes in the download.
				 */
				offline: z.boolean().default( false ),

				/**
				 * Put this article in the short list the help panel offers when a screen
				 * has no article of its own.
				 */
				featured: z.boolean().default( false ),
			} ),
		} ),
	} ),
};
