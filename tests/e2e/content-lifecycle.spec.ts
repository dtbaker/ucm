import { test, expect, Page } from '@playwright/test';
import { login, createRecord, aCustomer } from './seed';

/**
 * Lifecycle cover for the last four record types that had none: newsletter, calendar
 * entry, file and custom data.
 *
 * Same shape as records-lifecycle.spec.ts - create through the real handler, read the
 * value back off the form the app renders, then edit and read back again. Reading the
 * value back is the whole point: a "saved successfully" message proves the handler ran
 * and nothing more, and this codebase has shipped fields that were dropped on the way in
 * and fields that were never rendered on the way out.
 *
 * Every locator is scoped to its form. With UCM_DEBUG on, the debug panel echoes the page
 * markup, so an unscoped .first() can match a copy of a field that does nothing.
 */

async function fieldValue( page: Page, process: string, name: string ): Promise<string> {
  const form = page.locator( `form:has(input[name="_process"][value="${ process }"])` ).first();
  return form.locator( `[name="${ name }"]` ).first().inputValue();
}

test.describe( 'Content lifecycles', () => {
  test( 'a newsletter keeps its subject and sender, and an edit sticks', async ( { page } ) => {
    await login( page );
    const subject = 'Newsletter Test ' + Date.now();

    const newsletterId = await createRecord( page, '/?m[]=newsletter', {
      _process: 'save_newsletter',
      newsletter_id: 'new',
      subject,
      from_name: 'Lifecycle Sender',
      from_email: 'sender@example.com',
      content: 'Body text for the lifecycle test.',
    }, 'newsletter_id' );

    await page.goto( `/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=${ newsletterId }` );
    expect( await fieldValue( page, 'save_newsletter', 'subject' ), 'the subject should come back as typed' )
      .toBe( subject );
    expect( await fieldValue( page, 'save_newsletter', 'from_name' ), 'the sender name should come back as typed' )
      .toBe( 'Lifecycle Sender' );

    const renamed = subject + ' edited';
    await createRecord( page, '/?m[]=newsletter', {
      _process: 'save_newsletter',
      newsletter_id: newsletterId,
      subject: renamed,
      from_name: 'Lifecycle Sender',
      from_email: 'sender@example.com',
      content: 'Body text for the lifecycle test.',
    }, 'newsletter_id' );

    await page.goto( `/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=${ newsletterId }` );
    expect( await fieldValue( page, 'save_newsletter', 'subject' ), 'the edit should have stuck' )
      .toBe( renamed );
  } );


  test( 'a file record keeps the status it was given, and an edit sticks', async ( { page } ) => {
    await login( page );
    const customerId = await aCustomer( page );

    // The Files screen is file_admin. file_admin_edit is a section of it rather than a page
    // of its own and refuses to load alone, which is why an earlier attempt at this went
    // nowhere. The file name is not an editable field either - it comes from the upload -
    // so the status is what a save can be checked against.
    const fileId = await createRecord( page, '/?m[]=file', {
      _process: 'save_file',
      file_id: 'new',
      owner_table: 'customer',
      owner_id: customerId,
      customer_id: customerId,
    }, 'file_id' );

    await page.goto( `/?m[]=file&p[]=file_admin&file_id=${ fileId }` );
    const form = page.locator( 'form:has(input[name="_process"][value="save_file"])' ).first();
    const status = form.locator( 'select[name="status"]' ).first();

    // Take a status the install actually offers rather than inventing one - they are
    // configurable, so a hard coded value would pass here and fail on a real site.
    // The list carries its own affordances - Select, Create New, Manage Options - which
    // are not statuses. Picking one of those is how this test first failed.
    const options = ( await status.locator( 'option' ).allTextContents() )
      .map( ( t ) => t.trim() )
      .filter( ( t ) => t.length > 0 && ! t.startsWith( '-' ) );
    expect( options.length, 'the status list should offer a real status' ).toBeGreaterThan( 0 );
    const chosen = options[ 0 ];

    await status.selectOption( { label: chosen } );
    await Promise.all( [
      page.waitForNavigation( { waitUntil: 'load' } ),
      form.locator( 'input[name="butt_save"]' ).first().click(),
    ] );

    await page.goto( `/?m[]=file&p[]=file_admin&file_id=${ fileId }` );
    const after = page.locator( 'form:has(input[name="_process"][value="save_file"])' ).first()
      .locator( 'select[name="status"]' ).first();
    const selected = ( await after.locator( 'option:checked' ).allTextContents() )
      .map( ( t ) => t.trim() );
    expect( selected, 'the status should come back as it was set' ).toContain( chosen );
  } );


  test( 'the calendar feed answers for any date it is given', async ( { page } ) => {
    await login( page );

    // The calendar screen is drawn by a script that fetches its own data, so this is the
    // calendar's real data path - there is no form to fill in.
    await page.goto( '/?m[]=calendar&p[]=calendar_admin' );
    const feedUrl = await page.evaluate( () => {
      const m = document.documentElement.innerHTML.match( /DATA_FEED_URL\s*=\s*"([^"]+)"/ );
      return m ? m[ 1 ] : null;
    } );
    expect( feedUrl, 'the calendar page should publish a data feed url' ).toBeTruthy();

    // The feed used to die on anything other than m/d/Y: the date helper returned an empty
    // string for what it did not recognise, and the callers hand that to date(), which is
    // a fatal on PHP 8. One unexpected date took the whole calendar out, and because the
    // response is still 200 with the error in the body, a status check does not see it.
    for ( const showdate of [ '2026-10-13', '10/13/2026', 'not-a-date', '' ] ) {
      const response = await page.request.post( `${ feedUrl }_process=ajax_calendar&method=list`, {
        form: { showdate, viewtype: 'month' },
      } );
      const body = await response.text();

      expect( body, `the feed should not die on "${ showdate }"` ).not.toContain( 'Fatal error' );
      expect( body, `the feed should not warn on "${ showdate }"` ).not.toContain( 'Uncaught' );
      expect( () => JSON.parse( body ), `the feed should return json for "${ showdate }"` ).not.toThrow();
    }
  } );

  /**
   * Custom data groups are not covered here.
   *
   * Each was attempted and each needs more than a save and a read-back, so guessing at it
   * was costing more than it was proving:
   *
   *  - save_data_type creates a group and returns its id, but admin_data_list_type answers
   *    "No $data_type" for it, so either the page wants a different parameter or the group
   *    needs a field group before it will render.
   *
   * None of that is evidence of a bug - it is evidence that these three need a person to
   * drive the screens once and see how they actually fit together. Left honestly
   * uncovered rather than asserted on a guess.
   */
} );
