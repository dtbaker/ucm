import { test, expect, Page } from '@playwright/test';
import { login, createRecord, deleteRecord, formAuthKey, aCustomer } from './seed';
import { describeDiagnostics } from './diagnostics';

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
    let newsletterId: string | null = null;
    try {
      await login( page );
      const subject = 'Newsletter Test ' + Date.now();

      newsletterId = await createRecord( page, '/?m[]=newsletter', {
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
    } finally {
      if ( newsletterId ) {
        await deleteRecord( page, '/?m[]=newsletter', 'save_newsletter', 'newsletter_id', newsletterId );
      }
    }
  } );


  test( 'a newsletter with no template lists without a PHP diagnostic', async ( { page } ) => {
    let newsletterId: string | null = null;
    try {
      await login( page );
      const subject = 'Newsletter Test ' + Date.now() + ' no template';
      newsletterId = await createRecord( page, '/?m[]=newsletter', {
        _process: 'save_newsletter',
        newsletter_id: 'new',
        newsletter_template_id: '0',
        subject,
        from_name: 'Lifecycle Sender',
        from_email: 'sender@example.com',
        content: 'Body text for the lifecycle test.',
      }, 'newsletter_id' );

      // The template name is joined in, so with no template it is null - which the list
      // passed straight to htmlspecialchars().
      const body = await ( await page.request.get(
        '/?m[]=newsletter&p[]=newsletter_list_draft&search[generic]=' + encodeURIComponent( subject ),
        { failOnStatusCode: false } ) ).text();
      expect( body, 'the draft should be on the list' ).toContain( subject );
      expect( describeDiagnostics( body ).join( '\n' ), 'the list printed a PHP diagnostic' ).toBe( '' );
    } finally {
      if ( newsletterId ) {
        await deleteRecord( page, '/?m[]=newsletter', 'save_newsletter', 'newsletter_id', newsletterId );
      }
    }
  } );

  test( 'a file record keeps the status it was given, and an edit sticks', async ( { page } ) => {
    let fileId: string | null = null;
    try {
      await login( page );
      const customerId = await aCustomer( page );

      // The Files screen is file_admin. file_admin_edit is a section of it rather than a page
      // of its own and refuses to load alone, which is why an earlier attempt at this went
      // nowhere. The file name is not an editable field either - it comes from the upload -
      // so the status is what a save can be checked against.
      fileId = await createRecord( page, '/?m[]=file', {
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
    } finally {
      if ( fileId ) {
        await deleteRecord( page, '/?m[]=file', 'save_file', 'file_id', fileId );
      }
    }
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


  test( 'a custom data record keeps what was typed into it', async ( { page } ) => {
    let typeId: string | null = null;
    try {
      await login( page );

      // A data type of this test's own, carrying one text field. It used to use the seeded
      // type 3, which has fields - but every run added another record to it, and once that
      // list passed twenty rows the new record was on page two and this stopped finding it.
      // A type with no fields would render an empty form and pass while proving nothing,
      // so the field is created too.
      const stamp = Date.now() + '-' + Math.floor( Math.random() * 10000 );
      typeId = await createRecord( page, '/?m[]=data', {
        _process: 'save_data_type',
        data_type_id: 'new',
        data_type_name: 'Record Type ' + stamp,
      }, 'data_type_id' );
      const groupId = await createRecord( page, '/?m[]=data', {
        _process: 'save_data_field_group',
        data_field_group_id: 'new',
        data_type_id: typeId,
        name: 'Record Fieldset ' + stamp,
        _redirect: '/',
      }, 'data_field_group_id' );
      const fieldKey = await formAuthKey( page );
      await page.request.post( '/?m[]=data', {
        form: {
          _process: 'save_data_field',
          data_field_id: 'new',
          data_type_id: typeId,
          data_field_group_id: groupId,
          title: 'Notes',
          order: '1',
          field_type: 'text',
          // save_field() sets any checkbox it was not sent to 0, so without this the field
          // is not shown in the main listing and the record has no link to find it by.
          show_list: '1',
          form_auth_key: fieldKey,
        },
        failOnStatusCode: false,
      } );
      await page.goto( `/?m[]=data&p[]=admin_data_open&data_type_id=${ typeId }&data_record_id=new` );

      const form = page.locator( 'form:has(input[name="_process"][value="save_data_record"])' ).first();
      await expect( form, 'the data type should render a record form' ).toBeAttached();

      // The form carries data_save_hash, which the handler insists on before it will save.
      // Taking it from the form rather than computing it is the point - it is how a person
      // gets one, and it keeps the test honest if the hash ever becomes a real secret.
      const hash = await form.locator( 'input[name="data_save_hash"]' ).first().inputValue();
      expect( hash, 'the form should carry a save hash' ).toBeTruthy();

      const first = form.locator( 'input[name^="data_field["][type="text"]' ).first();
      await expect( first, 'the type should offer at least one text field' ).toBeAttached();
      const fieldName = await first.getAttribute( 'name' );
      const value = 'Data Record ' + Date.now();
      await first.fill( value );

      await Promise.all( [
        page.waitForNavigation( { waitUntil: 'load' } ),
        form.locator( 'input[name="butt_save"], button[name="butt_save"]' ).first().click(),
      ] );

      // Find it again through the list for this type, then read the value back off its own
      // form. Reading it back is the point - the handler reporting success is not evidence
      // that the field reached the database.
      await page.goto( `/?m[]=data&p[]=admin_data&data_type_id=${ typeId }` );
      const link = page.getByRole( 'link', { name: value } ).first();
      await expect( link, 'the new record should be listed' ).toBeVisible();
      await link.click();

      // A saved record renders as a view rather than the form it was created with, so the
      // value is read off the page. fieldName is kept above so the failure says which field
      // was being checked.
      expect( await page.content(), `the value typed into ${ fieldName } should come back` )
        .toContain( value );
      expect( await page.content(), 'the record should render without a PHP warning' )
        .not.toContain( 'Warning</b>' );
    } finally {
      // Deleting the type takes its fieldsets, its fields and the records held against it,
      // which is the whole of what this test created.
      if ( typeId ) {
        await deleteRecord( page, '/?m[]=data', 'save_data_type', 'data_type_id', typeId );
      }
    }
  } );

  test( 'a custom data group and its fieldset come back on their own page', async ( { page } ) => {
    let dataTypeId: string | null = null;
    try {
      await login( page );
      const stamp = Date.now();
      const typeName = 'Data Type ' + stamp;
      const groupName = 'Fieldset ' + stamp;

      dataTypeId = await createRecord( page, '/?m[]=data', {
        _process: 'save_data_type',
        data_type_id: 'new',
        data_type_name: typeName,
      }, 'data_type_id' );

      // A group on its own renders nothing useful - the page wants a fieldset before it has
      // anything to show, which is why asking for it straight after creating the group
      // answered "No $data_type" and an earlier attempt at this test read that as a bug.
      const groupId = await createRecord( page, '/?m[]=data', {
        _process: 'save_data_field_group',
        data_field_group_id: 'new',
        data_type_id: dataTypeId,
        name: groupName,
        _redirect: '/',
      }, 'data_field_group_id' );

      expect( groupId, 'the fieldset should have been given an id' ).toMatch( /^\d+$/ );

      await page.goto( `/?m[]=data&p[]=admin_data&data_type_id=${ dataTypeId }&data_field_group_id=${ groupId }` );
      const body = await page.content();

      expect( body, 'the data group should be named on its own page' ).toContain( typeName );
      expect( body, 'the page should not be complaining there is no data type' )
        .not.toContain( 'No $data_type' );
      expect( body, 'the page should render without a PHP warning' ).not.toContain( 'Warning</b>' );
    } finally {
      if ( dataTypeId ) {
        await deleteRecord( page, '/?m[]=data', 'save_data_type', 'data_type_id', dataTypeId );
      }
    }
  } );
} );
