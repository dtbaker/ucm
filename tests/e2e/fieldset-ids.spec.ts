import { test, expect, Page } from '@playwright/test';
import { anInvoice, login } from './seed';

/**
 * A fieldset with neither an id nor a heading must not invent one.
 *
 * module_form::generate_fieldset() leaves $fieldset_id false in that case, which is what
 * the read-only blocks and the public pages rely on: a false id means no `form` table
 * lookup, no field-customisation controls, and no id attribute to collide with anything.
 * The metis theme replaces that function wholesale, and its copy computed
 *
 *     'fieldset_' . substr( md5( serialize( $options['heading'] ) ), 0, 5 )
 *
 * with no guard on the heading, so every headingless fieldset got the same id back -
 * md5( serialize( false ) ) - and emitted it as its own HTML id and as the prefix of every
 * row id inside it. Nothing referenced that id, which is why it went unnoticed.
 *
 * Each test below was checked by putting the unguarded line back:
 *  - the theme settings screen renders two headingless fieldsets, and they came out with
 *    the same id attribute;
 *  - the collapsed id appeared in the markup of every screen that has such a fieldset;
 *  - those fieldsets came out editable, offering to save display settings for a fieldset
 *    with no settings row to save them in.
 *
 * The theme settings screen is the duplicate-id case because it is the only screen found
 * that renders more than one headingless fieldset - the invoice edit screen renders nine
 * fieldsets but only one of them is headingless, so it cannot show a collision.
 */

/** The id metis used to hand to every fieldset that had no heading of its own. */
const COLLAPSED_ID = 'fieldset_' + 'a2072';

/** Pages that render at least one fieldset with neither an id nor a heading. */
const THEME_SETTINGS = '/?m[]=theme&p[]=theme_settings';

/**
 * Duplicate ids among the elements a fieldset actually emits: the container, which carries
 * data-fieldset-id, and its rows, which carry data-element-id. Both renderers set
 * data-element-id on every row, so this sees the same rows under either one.
 *
 * Deliberately not every [id] on the page. These screens have duplicate ids of their own
 * that have nothing to do with fieldsets - module_extra builds its row id from the extra's
 * key, so two extras whose keys differ only in punctuation both come out as `extra_new`,
 * and a help topic used twice renders two `help_<md5>` icons. Those are real and tracked
 * separately; counting them here would make this spec fail for a reason it is not about.
 */
async function duplicateFieldsetIds( page: Page, url: string ): Promise<string[]> {
  await page.goto( url );
  await expect( page.locator( 'body' ) ).toBeVisible();

  return page.evaluate( () => {
    const seen = new Map<string, number>();
    for ( const el of Array.from( document.querySelectorAll( '[data-fieldset-id], tr[data-element-id]' ) ) ) {
      const id = el.getAttribute( 'id' );
      if ( ! id ) {
        continue; // id="" is what a false fieldset id renders as; that is the point here.
      }
      seen.set( id, ( seen.get( id ) ?? 0 ) + 1 );
    }

    return Array.from( seen.entries() ).filter( ( [ , n ] ) => n > 1 ).map( ( [ id, n ] ) => `${ id } x${ n }` );
  } );
}

test.describe( 'Fieldsets with no id and no heading', () => {
  test( 'two headingless fieldsets on one screen do not share an id', async ( { page } ) => {
    await login( page );
    // Guard the premise: with no headingless fieldset on the page there is nothing to collide.
    await page.goto( THEME_SETTINGS );
    const headingless = await page.locator( '[data-fieldset-id=""]' ).count();
    expect( headingless, 'theme settings no longer renders headingless fieldsets' ).toBeGreaterThan( 1 );

    const dupes = await duplicateFieldsetIds( page, THEME_SETTINGS );
    expect( dupes, 'fieldset ids repeated on the theme settings screen' ).toEqual( [] );
  } );

  test( 'no screen hands out the id metis derived from an absent heading', async ( { page } ) => {
    await login( page );
    const invoiceId = await anInvoice( page );
    const urls = [
      THEME_SETTINGS,
      `/?m[]=invoice&p[]=invoice_admin&invoice_id=${ invoiceId }`,
    ];
    for ( const url of urls ) {
      const body = await ( await page.request.get( url ) ).text();
      expect( body, `${ url } still carries the collapsed fieldset id` ).not.toContain( COLLAPSED_ID );
    }
  } );

  test( 'a headingless fieldset is rendered as not editable', async ( { page } ) => {
    await login( page );
    await page.goto( THEME_SETTINGS );
    // data-fieldset-settings carries editable:0 whenever the fieldset has no real id, so
    // the theme does not offer to save field display settings that have nowhere to go.
    const settings = await page.locator( '[data-fieldset-id=""]' ).first()
      .getAttribute( 'data-fieldset-settings' );
    expect( settings, 'no headingless fieldset found to check' ).not.toBeNull();
    expect( JSON.parse( settings as string ).editable ).toBe( 0 );
  } );
} );
