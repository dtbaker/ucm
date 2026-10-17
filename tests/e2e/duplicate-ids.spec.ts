import { test, expect, Page } from '@playwright/test';
import { aCustomer, login } from './seed';

/**
 * Two id generators used to hand the same id out twice on one screen.
 *
 * module_extra::display_extras() built every id in its block from the extra's key and id
 * alone, which only run unique inside one block. The customer edit screen renders two
 * blocks - the customer's extras and the contact user's - so the blank "new field" row at
 * the end of each came out as `extra_new` both times. The same block-local naming meant
 * two extras whose keys differ only in punctuation would collapse onto one id as well,
 * because the key is sanitised with `preg_replace( '#\W+#', '_', ... )`.
 *
 * _h() derives the help icon's id from md5() of the help text, so a topic used twice on a
 * page emitted `help_<md5>` twice. The icon is wired to its text through a jQuery selector
 * in data-ajax-modal, and jQuery takes the first match for a duplicated id, so the second
 * icon opened the first icon's copy of the text.
 *
 * Nothing in the tree selects on either id - no JS, no CSS, no test - so neither collision
 * broke anything a user could see. They were invalid HTML, and the help one quietly made
 * one icon's modal belong to another icon.
 *
 * Each assertion below was checked against the unfixed code: restoring either generator
 * fails the matching test here.
 *
 * The customer edit screen is the one to test because it is where both collisions show up
 * at once: it is the screen that renders two extras blocks, and it uses the "Enter anything
 * you like in this blank field" help topic once per block.
 *
 * Duplicate ids outside these two generators are deliberately out of scope - this screen
 * still repeats its fieldset ids and a couple of form element ids, which have nothing to
 * do with either generator. See fieldset-ids.spec.ts for the fieldset side.
 */

/** Every id on the page that one of the two generators produced, with how often it appears. */
async function generatedIdCounts( page: Page ): Promise<Record<string, number>> {
  return page.evaluate( () => {
    const counts: Record<string, number> = {};
    for ( const el of Array.from( document.querySelectorAll( '[id^="extra_"], [id^="help_"]' ) ) ) {
      const id = el.id;
      counts[ id ] = ( counts[ id ] ?? 0 ) + 1;
    }
    return counts;
  } );
}

async function customerEdit( page: Page ): Promise<void> {
  const customerId = await aCustomer( page );
  await page.goto( `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }` );
  await expect( page.locator( 'body' ) ).toBeVisible();
}

test.describe( 'Ids that used to repeat on the customer edit screen', () => {
  test( 'the two blocks of extra fields do not share an id', async ( { page } ) => {
    await login( page );
    await customerEdit( page );

    // Guard the premise: with only one block on the page there is nothing to collide.
    const blocks = await page.locator( 'tr.extra_fields_show_more' ).count();
    expect( blocks, 'customer edit no longer renders two blocks of extra fields' ).toBeGreaterThan( 1 );

    const counts = await generatedIdCounts( page );
    const repeated = Object.entries( counts )
      .filter( ( [ id, n ] ) => id.startsWith( 'extra_' ) && n > 1 )
      .map( ( [ id, n ] ) => `${ id } x${ n }` );
    expect( repeated, 'extra field ids repeated on the customer edit screen' ).toEqual( [] );

    // The blocks are told apart by their owner table, so each one keeps its own new row.
    await expect( page.locator( '#extra_customer_new' ) ).toHaveCount( 1 );
    await expect( page.locator( '#extra_user_new' ) ).toHaveCount( 1 );
  } );

  test( 'a help topic used twice gets two ids', async ( { page } ) => {
    await login( page );
    await customerEdit( page );

    const counts = await generatedIdCounts( page );
    const repeated = Object.entries( counts )
      .filter( ( [ id, n ] ) => id.startsWith( 'help_' ) && n > 1 )
      .map( ( [ id, n ] ) => `${ id } x${ n }` );
    expect( repeated, 'help ids repeated on the customer edit screen' ).toEqual( [] );

    // Guard the premise: the screen has to use one topic twice for this to mean anything.
    // The repeats are numbered from the second occurrence, so a `_2` is the evidence.
    const numbered = Object.keys( counts ).filter( id => /^help_[0-9a-f]{32}_\d+$/.test( id ) );
    expect( numbered.length, 'customer edit no longer uses any help topic twice' ).toBeGreaterThan( 0 );
  } );

  test( 'every help icon opens its own copy of the text', async ( { page } ) => {
    await login( page );
    await customerEdit( page );

    // The icon finds its text with the selector in data-ajax-modal. jQuery takes the first
    // match, so a duplicated id silently points one icon at another icon's text.
    const mismatched = await page.evaluate( () => {
      const bad: string[] = [];
      for ( const icon of Array.from( document.querySelectorAll( '[data-ajax-modal]' ) ) ) {
        const settings = icon.getAttribute( 'data-ajax-modal' ) ?? '';
        const content = ( JSON.parse( settings ) as { content?: string } ).content;
        if ( ! content || ! content.startsWith( '#help_' ) ) {
          continue;
        }
        const targets = document.querySelectorAll( content );
        if ( targets.length !== 1 || targets[ 0 ] !== icon.nextElementSibling ) {
          bad.push( `${ content } -> ${ targets.length } target(s)` );
        }
      }
      return bad;
    } );
    expect( mismatched, 'help icons whose modal selector does not resolve to their own text' ).toEqual( [] );
  } );
} );
