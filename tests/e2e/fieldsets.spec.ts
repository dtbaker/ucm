import { test, expect, Browser, Page } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { aCustomer, aFile, anInvoice, createRecord, formAuthKey, login } from './seed';
import { byProcess, expectFields, expectFieldsIn, fieldsIn } from './form-fields';

/**
 * Pages whose form markup is being moved from a hand-written
 * <table class="tableclass_form"> to module_form::generate_fieldset().
 *
 * Each page is covered here BEFORE it is converted, and the spec has to go on passing
 * afterwards. Two things are checked, because only the second one is interesting on its
 * own and only the first one catches the failure that matters:
 *
 *  1. The form renders the same named controls. A conversion that drops or renames a
 *     control makes the value stop reaching the database, and the page still says it
 *     saved - this codebase has shipped exactly that before.
 *  2. A value typed into the form comes back out of it. That proves the control is
 *     wired to the handler, not merely present.
 */

/** A data type with a fieldset, so the data field screen has something to open. */
async function aDataFieldGroup( page: Page ): Promise<{ typeId: string; groupId: string }> {
  const stamp = Date.now() + '-' + Math.floor( Math.random() * 10000 );
  const typeId = await createRecord( page, '/?m[]=data', {
    _process: 'save_data_type',
    data_type_id: 'new',
    data_type_name: 'Fieldset Type ' + stamp,
  }, 'data_type_id' );
  const groupId = await createRecord( page, '/?m[]=data', {
    _process: 'save_data_field_group',
    data_field_group_id: 'new',
    data_type_id: typeId,
    name: 'Fieldset Group ' + stamp,
    _redirect: '/',
  }, 'data_field_group_id' );

  return { typeId, groupId };
}

/**
 * How the conversions were shown not to change anything, and what is left.
 *
 * Twenty-one pages were converted. ./scripts/fieldset-parity.sh puts each one back to the
 * markup it had before, and runs these specs against it: they assert an exact list of
 * controls, so a run that passes against the old markup and the new one is saying both
 * render the same fields. Four fail on one named assertion each - a row class their script
 * switches on, which the old <tbody id> markup had no equivalent of, or a PHP 8 bug fixed
 * alongside - and the script knows which, so anything else is reported as a difference.
 *
 * No page prints its own form table any more. tests/unit/FieldsetMarkupTest.php scans
 * includes/ and allows none.
 *
 * Every reason this list once held for leaving a form table alone turned out to be wrong,
 * every time in the direction of not converting - so what follows is the record of what
 * each reason actually was, kept because the pattern is the useful part.
 *
 *  WHAT THE LAST ONE NEEDED
 *    ticket_create (faq hook) had a SECOND <tbody id="faq_product_area"> inside the same
 *      table, which includes/plugin_faq/faq.php fills with rows over ajax
 *      ($('#faq_product_area').html(...)). The injected content is table rows, which need
 *      a row parent or the browser hoists them out, and generate_fieldset emitted one
 *      tbody with no way to ask for another - so the first attempt at this gave the target
 *      a second <table> of its own and left it in the allow-list as an exception. That was
 *      the wrong answer. A table may carry several tbodies, so generate_fieldset now takes
 *      tbody_after and the target is a second tbody inside the fieldset's own table, which
 *      is what the hand-written one was imitating: one table, the product row above it
 *      untouched when the script replaces the rows below. The spec checks the target is a
 *      tbody in the same table as the product select, and that an injected row survives
 *      there - this install has no faq_product_rel rows, so a real product change injects
 *      nothing and the row has to be put in by hand. It fails with tbody_after removed.
 *
 *      What faq.php sends is still hand-built markup, and deliberately so: it is a
 *      fragment of <tr>s for a table that already exists, and generate_fieldset renders
 *      whole tables, not fragments. Converting it would need a rows-only mode on both
 *      renderers, which is a lot of renderer for links and a line of guidance.
 *
 *      That exemption only holds while the fragment carries no form controls, so it is
 *      checked rather than asserted: the last test in this file asks the ajax endpoint for
 *      a real fragment and fails if it contains an input, select, textarea, button or a
 *      name attribute. Checked by putting an input in it and watching the test fail. If it
 *      ever does gain a control, the rows-only mode is the answer, not an exception.
 *
 *  NOT A FORM LAYOUT - one cell, no <th>, a styled container around a message or summary,
 *  already handed to a fieldset as its elements_before:
 *    (none left - see below)
 *
 * What was removed from this list, and why it was wrong:
 *
 *    customer_admin_edit and vendor_admin_edit (two each) were "unreachable from the test
 *      account - no passing spec can be written". They are reachable. They render for a
 *      staff member who cannot view, or cannot edit, that record's contacts, and an
 *      administrator genuinely cannot see them, because has_feature_access() returns true
 *      unconditionally for user id 1 - so the way to reach them is to stop being user 1.
 *      The tests at the bottom of this file build a restricted role, put a staff account
 *      on it, sign in through the account's own auto-login link and assert both branches,
 *      on both screens, then delete the role and the account again. Converting them turned
 *      up a bug: the customer screen's read-only branch captured the contact summary into
 *      an inner ob_start() that only fed the responsive heading, so a staff member who may
 *      view but not edit contacts got a "Primary Contact Details" box with an empty cell.
 *      The spec for it failed before that was fixed. The vendor copy never had the bug.
 *
 *    contact_summary, user_summary, customer_summary were filed under "one cell, no <th>".
 *      Each is a label/value table with a <th> per row - five, six and one respectively.
 *      All three are converted. None is reachable through the UI (print_user_summary and
 *      print_customer_summary have no callers, print_contact_summary has no 'full' branch),
 *      so scripts/render-partials.php renders them instead of a spec and the text and
 *      hrefs are diffed across the change.
 *    newsletter_statistics and two blocks in newsletter_edit were "inside a commented-out
 *      block, so it renders nowhere". True, and commenting markup out is not converting
 *      it: all three are deleted. newsletter_statistics.php was 145 lines of which 139 sat
 *      after an unconditional `return;` AND inside a block comment; its live replacement is
 *      the fieldset in newsletter_queue_watch.php. Both pages were captured before and
 *      after - the only lines that differ are the footer's render time and the debug
 *      timing table.
 *    user_admin_edit_company was "the company module is off in this install, checked
 *      across three user screens". It is not off. Those screens were requested as
 *      p[]=user_admin_edit, which is a partial and not a route, so they came back as four
 *      byte pages and an empty page was read as an absent block. module_company::is_enabled()
 *      wants one company row, not a config change. Converted, with a test below.
 *    ticket.php's invoice sidebar, member_edit and ticket_priority_sidebar were counted as
 *      summaries. The first is three label/value rows; the second is one; the third is a
 *      genuine single cell, which an element with 'fields' and no 'title' renders exactly -
 *      both renderers emit a <th> only when an element has a title. All converted.
 *    render_group was "opens its <table> in one branch of a switch and closes it in another,
 *      streaming rows between - there is no block to replace". It did, in six places, but
 *      every one of those switches had exactly one reachable branch: the file opened by
 *      setting layout to 'table' unconditionally and nothing anywhere sets 'float'. With
 *      the dead half gone the rows are a plain list. The id and rel are real - jQuery UI's
 *      sortable('serialize') builds container_data_field[] from the row id and module_data
 *      reads it to save the field order - and row_attributes carries them now. The data
 *      record form renders the same 22 field names, the same three rows with matching
 *      id and rel, and the same row classes as before.
 *    group.php was "puts the checkbox in the <th> with a data-settings-url that settings.js
 *      scans for, and a fieldset offers no way to put an attribute on the title cell".
 *      Half right. Nothing reads the <tr id="group_N"> it also carried (the only #group_
 *      in the tree is #group_popdown, unrelated), and settings.js matches the attribute
 *      wherever it sits rather than requiring a <th>. The real blocker was that 'title' is
 *      escaped plain text and that cell holds a checkbox. generate_fieldset now takes
 *      title_fields and title_attributes, added to both renderers; the picker is
 *      converted, and the customer and member screens render the same field names, the
 *      same groupchk ids and the same number of data-settings-url attributes as before.
 *    invoice_sidebar (subscription hook) was the same single-cell shape, one row per
 *      subscription. Converted. It no longer renders an empty box when every subscription
 *      in the list has no owner record; before, the heading appeared above an empty table.
 */

/**
 * One customer group, reused across runs.
 *
 * The customer edit screen renders a checkbox per customer group, so a fixture that
 * creates a new one every run grows that form until Playwright can no longer drive it.
 * That is not hypothetical: several hundred leftover groups had pushed the customer edit
 * page past 1.8MB and the customer lifecycle spec started failing on a save that had
 * worked for months.
 */
async function aCustomerGroup( page: Page ): Promise<string> {
  const GROUP_NAME = 'Fieldset Send Group';
  await page.goto( '/?m[]=group&p[]=group_settings' );
  const existing = ( await page.content() ).match(
    new RegExp( `group_id=(\\d+)[^>]*>\\s*${ GROUP_NAME }` ) );
  if ( existing ) {
    return existing[ 1 ];
  }

  return createRecord( page, '/?m[]=group', {
    _process: 'save_group',
    group_id: 'new',
    name: GROUP_NAME,
    owner_table: 'customer',
    _redirect: '/',
  }, 'group_id' );
}

/**
 * A queued newsletter send, which is what the queue and statistics screens need before
 * they render anything at all.
 *
 * Two parameters are needed to put a customer in a group, not one: the group plugin reads
 * group_module[table][group_id] for the owner id and used_group_module[table][group_id]
 * for the tick. Sending with only the second writes no membership row, enqueuing then
 * refuses with "Please select at least 1 person", and the screens below stay empty - which
 * is what made these three look unreachable on a first pass.
 */
async function aQueuedSend( page: Page ): Promise<{ newsletterId: string; sendId: string }> {
  const stamp = Date.now() + '-' + Math.floor( Math.random() * 10000 );

  const groupId = await aCustomerGroup( page );

  const customerId = await aCustomer( page );
  await page.goto( '/' );
  const key = await page.locator( 'input[name="form_auth_key"]' ).first().inputValue();
  await page.request.post( '/', {
    form: {
      [ `group_module[customer][${ groupId }]` ]: customerId,
      [ `used_group_module[customer][${ groupId }]` ]: '1',
      form_auth_key: key,
    },
    failOnStatusCode: false,
  } );

  const newsletterId = await createRecord( page, '/?m[]=newsletter', {
    _process: 'save_newsletter',
    newsletter_id: 'new',
    subject: 'Queued Newsletter ' + stamp,
    from_name: 'Fieldset Sender',
    from_email: 'sender@example.com',
    content: 'Body text.',
  }, 'newsletter_id' );

  await page.goto( `/?m[]=newsletter&p[]=newsletter_send&newsletter_id=${ newsletterId }` );
  await page.locator( `input[name="group[${ groupId }]"]` ).check();
  await Promise.all( [
    page.waitForNavigation( { waitUntil: 'load' } ),
    page.locator( 'input[name="send"]' ).first().click(),
  ] );

  const sendId = new URL( page.url() ).searchParams.get( 'send_id' );
  expect( sendId, 'the send should have been queued' ).toBeTruthy();

  return { newsletterId, sendId: sendId as string };
}

test.describe( 'Forms moved to generate_fieldset', () => {
  test( 'the data field screen keeps its controls and saves what is typed', async ( { page } ) => {
    await login( page );
    const { typeId, groupId } = await aDataFieldGroup( page );
    const url = `/?m[]=data&p[]=data_type_admin_field_open&data_type_id=${ typeId }`
      + `&data_field_group_id=${ groupId }&data_field_id=new`;

    await expectFields( page, url, 'save_data_field', [
      '_redirect|input|hidden',
      'butt_dell|input|submit',
      'butt_save|input|submit',
      'cancel|input|button',
      'data_field_group_id|input|hidden',
      'data_field_id|input|hidden',
      'data_type_id|input|hidden',
      'default|input|text',
      'field_data|textarea|',
      'field_type|select|',
      'multiple|input|checkbox',
      'order|input|text',
      'required|input|checkbox',
      'searchable|input|checkbox',
      'show_list|input|checkbox',
      'title|input|text',
    ] );

    // --- and the fields are wired up, not just drawn
    const title = 'Field ' + Date.now();
    await page.goto( url );
    const form = page.locator( 'form:has(input[name="_process"][value="save_data_field"])' ).first();
    await form.locator( 'input[name="title"]' ).first().fill( title );
    await form.locator( 'input[name="order"]' ).first().fill( '7' );
    await form.locator( 'input[name="default"]' ).first().fill( 'a default' );
    await form.locator( 'input[name="required"]' ).first().check();
    await Promise.all( [
      page.waitForNavigation( { waitUntil: 'load' } ),
      form.locator( 'input[name="butt_save"]' ).first().click(),
    ] );

    // The handler rewrites _redirect to the data module's own page, so the new id never
    // reaches a URL we control. data_type_admin_group_open is the screen that lists the
    // fields in a fieldset, so that is where to find it again.
    const groupPage = await ( await page.request.get(
      `/?m[]=data&p[]=data_type_admin_group_open&data_type_id=${ typeId }`
      + `&data_field_group_id=${ groupId }` ) ).text();
    const ids = [ ...groupPage.matchAll( /data_field_id=(\d+)/g ) ].map( ( m ) => m[ 1 ] );
    expect( ids.length, 'the saved field should be listed on the fieldset screen' ).toBeGreaterThan( 0 );

    let saved = null;
    for ( const id of ids ) {
      const open = `/?m[]=data&p[]=data_type_admin_field_open&data_type_id=${ typeId }`
        + `&data_field_group_id=${ groupId }&data_field_id=${ id }`;
      await page.goto( open );
      const candidate = page.locator( 'form:has(input[name="_process"][value="save_data_field"])' ).first();
      if ( await candidate.locator( 'input[name="title"]' ).first().inputValue() === title ) {
        saved = candidate;
        break;
      }
    }
    expect( saved, 'the field that was just saved should be openable' ).not.toBeNull();

    expect( await saved!.locator( 'input[name="order"]' ).first().inputValue(), 'the position should come back' )
      .toBe( '7' );
    expect( await saved!.locator( 'input[name="default"]' ).first().inputValue(), 'the default should come back' )
      .toBe( 'a default' );
    expect( await saved!.locator( 'input[name="required"]' ).first().isChecked(), 'required should have stuck' )
      .toBe( true );
  } );

  test( 'the import screen keeps its controls', async ( { page } ) => {
    await login( page );

    // The import screen is only reachable by posting: a list page renders a hidden form
    // carrying the import options, and the Import link submits it. Building the options
    // by hand here would test a request the application never makes.
    await page.goto( '/?m[]=customer&p[]=customer_admin_list&customer_type_id=0' );
    await Promise.all( [
      page.waitForNavigation( { waitUntil: 'load' } ),
      page.evaluate( () => ( document.forms as any ).import_form.submit() ),
    ] );

    await expectFieldsIn( page, 'form:has(input[name="csv"])', [
      'add_to_group[*]|input|checkbox',
      'cancel|input|button',
      'csv|input|file',
      'customer_type_id|select|',
      'go|input|submit',
      'import_options|input|hidden',
      'upload_import|input|hidden',
    ] );
  } );

  test( 'the newsletter send screen keeps its controls', async ( { page } ) => {
    await login( page );
    // The screen lists a checkbox per group, so one has to exist for group[*] to appear.
    await aCustomerGroup( page );
    const stamp = Date.now();
    const newsletterId = await createRecord( page, '/?m[]=newsletter', {
      _process: 'save_newsletter',
      newsletter_id: 'new',
      subject: 'Fieldset Newsletter ' + stamp,
      from_name: 'Fieldset Sender',
      from_email: 'sender@example.com',
      content: 'Body text.',
    }, 'newsletter_id' );

    // start_time is a text input in the source and a hidden one here: the date_time_field
    // class hands it to a picker widget on load. This reads the page as a person gets it,
    // which is the thing that has to keep working.
    await expectFields( page, `/?m[]=newsletter&p[]=newsletter_send&newsletter_id=${ newsletterId }`,
      'enque_send', [
        'cancel|input|button',
        'group[*]|input|checkbox',
        'ignore|input|text',
        'newsletter_id|input|hidden',
        'send_id|input|hidden',
        'send|input|submit',
        'start_time|input|hidden',
      ] );
  } );

  test( 'the customer file screen keeps its controls', async ( { page } ) => {
    await login( page );
    const fileId = await aFile( page );

    await expectFields( page, `/?m[]=file&p[]=file_customer_edit&file_id=${ fileId }`, 'save_file', [
      '_plugin_form_name|input|hidden',
      'butt_del|input|submit',
      'butt_save|input|submit',
      'cancel|input|button',
      'description|textarea|',
      'file_id|input|hidden',
      'file_upload|input|file',
      'prevent_exit_*|input|hidden',
    ] );
  } );

  test( 'the public ticket form keeps its controls', async ( { page } ) => {
    // No login: this is the page an anonymous visitor reaches through ext.php, which is
    // the reason to be careful with it. generate_fieldset only reads or writes its
    // per-user display settings when the fieldset has an id, so this one is given none.
    await page.goto( '/ext.php?m=ticket&h=public_new' );

    await expectFieldsIn( page, '#ticket_form', [
      '_plugin_form_name|input|hidden',
      'attachment[]|input|file',
      'butt_save|input|submit',
      'email|input|text',
      'faq_product_id|select|',
      'name|input|text',
      'new_ticket_message|textarea|',
      'subject|input|text',
      'ticket_extra[*]|input|text',
      'ticket_type_id|select|',
    ] );
  } );

  test( 'the encrypt popup keeps its controls', async ( { page } ) => {
    await login( page );

    // encrypt_field_id is cast to int, so a non-numeric one makes the page take the create
    // branch - which is the branch with the hand-written table in it.
    const url = '/?m[]=encrypt&p[]=encrypt_popup&encrypt_field_id=0'
      + '&encrypt_field_name=fieldset_probe&page_name=test'
      + '&callback_id=encrypt_hidden_fieldset_probe&editable=1';
    await page.goto( url );

    await expectFieldsIn( page, 'form:has(#enc_create_new)', [
      'encrypt_key_name|input|text',
      'new_passphrase|input|password',
      'save|input|button',
    ] );

    // The script on that page shows and hides this block by id, so the id and the hidden
    // state have to survive. They live on a wrapper, not on the fieldset: the metis theme
    // renders fieldsets itself through the generate_fieldset hook and its version carries
    // neither, so anything the script needs to find must sit outside the helper's output.
    await expect( page.locator( '#enc_create_new' ), 'the create block should keep its id' )
      .toBeAttached();
    await expect( page.locator( '#enc_create_new' ), 'and start hidden' ).toBeHidden();
    await expect( page.locator( '#enc_create_new input[name="encrypt_key_name"]' ),
      'with the fieldset inside it' ).toBeAttached();

    // The existing-vault block switched three <tbody> groups by id as the passphrase is
    // entered and the value decrypts. Those are row classes now, and the script switches
    // on the class - so the classes have to be on the rows.
    const body = await page.content();
    for ( const group of [ 'password_box', 'unlocking_box', 'decrypt_box' ] ) {
      expect( body, `the ${ group } rows need their class on the row` )
        .toMatch( new RegExp( `<tr[^>]*\\b${ group }\\b` ) );
      expect( body, `and the script should switch ${ group } by class` )
        .toContain( `$('.${ group }')` );
    }
    await expect( page.locator( '#env_vault_name' ), 'the vault name still needs its id' )
      .toBeAttached();
  } );

  test( 'the newsletter queue keeps its send options', async ( { page } ) => {
    await login( page );
    const { newsletterId, sendId } = await aQueuedSend( page );
    const body = await ( await page.request.get(
      `/?m[]=newsletter&p[]=newsletter_queue&newsletter_id=${ newsletterId }&send_id=${ sendId }` ) ).text();

    // Read-only rows, so the invariant is the labels rather than a set of controls: a row
    // dropped by a layout change takes its label off the page with it.
    // Duplicates is deliberately not here: that row only appears once the newsletter has
    // been sent before, and this one is new.
    for ( const label of [ 'Shedule send' ] ) {
      expect( body, `the send options should still list "${ label }"` ).toContain( label );
    }

    // The dynamic field preview below it: one row per placeholder, a checkbox on the ones
    // past the first five that the extra fields offer, and a Preview button.
    expect( ( body.match( /\{[A-Z_]+\}/g ) ?? [] ).length,
      'the preview should list the placeholders' ).toBeGreaterThan( 4 );
    expect( body, 'and still offer a preview' ).toContain( 'name="pp"' );
    expect( describeDiagnostics( body ).join( '\n' ), 'the queue printed a PHP diagnostic' ).toBe( '' );
  } );

  test( 'the newsletter queue watcher keeps its statistics', async ( { page } ) => {
    await login( page );
    const { newsletterId, sendId } = await aQueuedSend( page );
    const body = await ( await page.request.get(
      `/?m[]=newsletter&p[]=newsletter_queue_watch&newsletter_id=${ newsletterId }&send_id=${ sendId }` ) ).text();

    for ( const label of [ 'Start sending', 'Sent To', 'Viewed By', 'Clicks By', 'Bounces', 'Failures' ] ) {
      expect( body, `the statistics should still list "${ label }"` ).toContain( label );
    }

    // These ids are what the page's own polling writes into - newsletter_queue_manual
    // answers with { '#sent_to': '...' } and the script does $(x).html(d[x]). Lose one and
    // the figure silently stops updating while the page still looks right.
    for ( const id of [
      'sent_to', 'open_rate', 'total_link_clicks', 'bounces', 'failures',
      'unsubscribed', 'current_status', 'change_status_buttons', 'status',
    ] ) {
      expect( body, `the live statistics need #${ id } to exist` ).toContain( `id="${ id }"` );
    }

    expect( describeDiagnostics( body ).join( '\n' ), 'the watcher printed a PHP diagnostic' ).toBe( '' );
  } );

  test( 'the newsletter member statistics keep their controls', async ( { page } ) => {
    await login( page );
    const { newsletterId, sendId } = await aQueuedSend( page );

    // Queuing the send is what creates newsletter members. Find one the way the screens
    // link to it, by newsletter_member_id - which is the path that used to die with
    // "Cannot access offset of type string on string" before reaching the form.
    const watch = await ( await page.request.get(
      `/?m[]=newsletter&p[]=newsletter_queue_watch&newsletter_id=${ newsletterId }&send_id=${ sendId }` ) ).text();
    // The rows are id="newsletter_member_<n>" - that is what the page's own polling
    // updates, and the only place the ids appear.
    const ids = [ ...new Set( [ ...watch.matchAll( /newsletter_member_(\d+)/g ) ].map( ( m ) => m[ 1 ] ) ) ];
    expect( ids.length, 'the queued send should have members' ).toBeGreaterThan( 0 );

    const url = `/?m[]=newsletter&p[]=newsletter_member&newsletter_member_id=${ ids[ 0 ] }`;
    const body = await ( await page.request.get( url, { failOnStatusCode: false } ) ).text();
    expect( body, 'the member screen should not be dying' ).not.toContain( 'Fatal error' );
    expect( body, 'and should be the member statistics' ).toContain( 'Member Statistics' );

    await page.goto( url );
    await expectFieldsIn( page, 'form:has(select[name^="receive_email"])', [
      'receive_email[*]|select|',
      'save|input|submit',
    ] );
  } );

  test( 'the recurring transaction screen keeps its controls', async ( { page } ) => {
    await login( page );
    const name = 'Recurring Fieldset ' + Date.now();

    // save_recurring redirects to the list rather than the record, so the id comes from
    // following the row's own link - looking for the newest id races a parallel run.
    const key = await formAuthKey( page );
    await page.request.post( '/?m[]=finance', {
      form: {
        _process: 'save_recurring',
        finance_recurring_id: 'new',
        name,
        description: 'created for the fieldset conversion',
        amount: '10.00',
        currency_id: '1',
        type: 'e',
        recurringtype: 'recurring',
        start_date: '2026-01-01',
        end_date: '',
        days: '0',
        months: '1',
        years: '0',
        finance_account_id: '',
        form_auth_key: key,
      },
      failOnStatusCode: false,
    } );

    // The recurring list projects every future occurrence before it renders, so it is slow
    // enough that the default five seconds flaked when the suite runs in parallel.
    await page.goto( '/?m[]=finance&p[]=recurring_list' );
    const row = page.getByRole( 'link', { name } ).first();
    await expect( row, 'the recurring transaction should be listed' ).toBeVisible( { timeout: 20000 } );

    // Read the id off the row and open the edit screen directly. Clicking the row lands on
    // the "record a transaction" screen instead, which is a different form.
    const href = await row.getAttribute( 'href' );
    const recurringId = ( href ?? '' ).match( /finance_recurring_id[=\/]\.?(\d+)/ );
    expect( recurringId, `could not read an id out of "${ href }"` ).not.toBeNull();
    await page.goto( `/?m[]=finance&p[]=recurring&finance_recurring_id=${ recurringId![ 1 ] }` );

    await expectFieldsIn( page, byProcess( 'save_recurring' ), [
      '_plugin_form_name|input|hidden',
      'amount|input|text',
      'butt_del|input|submit',
      'butt_save|input|submit',
      'cancel|input|button',
      'currency_id|select|',
      'days|input|text',
      'description|textarea|',
      'end_date|input|text',
      'finance_account_id|select|',
      'finance_category_new_checked|input|checkbox',
      'finance_category_new|input|text',
      'finance_recurring_id|input|hidden',
      'months|input|text',
      'name|input|text',
      'prevent_exit_*|input|hidden',
      'recurringtype|select|',
      'set_next_due_date|input|text',
      'start_date|input|text',
      'type|input|radio',
      'years|input|text',
    ] );

    expect( await page.locator( `${ byProcess( 'save_recurring' ) } [name="name"]` ).first().inputValue(),
      'the name should come back as saved' ).toBe( name );

    // The once-off toggle hid a <tbody id="recurring_bits">. A fieldset has no row groups,
    // so those two rows carry the class instead and the script hides them by class - which
    // only works if the class really is on the row.
    const body = await page.content();
    expect( ( body.match( /<tr[^>]*\brecurring_bits\b/g ) ?? [] ).length,
      'the end date and repeat rows need the class the toggle hides' ).toBe( 2 );
    expect( body, 'and the toggle should be hiding by class, not by the old id' )
      .toContain( "$('.recurring_bits')" );

    // Clean up. The recurring list projects every future occurrence of every schedule, so
    // a test that leaves one behind on each run eventually buries other tests' rows under
    // its own - which is exactly what happened while this was being written.
    await page.request.post( '/?m[]=finance', {
      form: {
        _process: 'save_recurring',
        finance_recurring_id: recurringId![ 1 ],
        butt_del: 'Delete',
        form_auth_key: await formAuthKey( page ),
      },
      failOnStatusCode: false,
    } );
  } );

  test( 'the public ticket view keeps its controls', async ( { page } ) => {
    await login( page );

    // The customer's view of a ticket is reached with a hash the admin screen hands out.
    // Taking the link from the page is the point: computing the hash here would test a
    // URL the application never produces.
    const list = await ( await page.request.get( '/?m[]=ticket&p[]=ticket_admin' ) ).text();
    const ids = [ ...new Set( [ ...list.matchAll( /ticket_id=(\d+)/g ) ].map( ( m ) => m[ 1 ] ) ) ];
    expect( ids.length, 'there should be a ticket to open' ).toBeGreaterThan( 0 );

    const open = await ( await page.request.get( `/?m[]=ticket&p[]=ticket_admin&ticket_id=${ ids[ 0 ] }` ) ).text();
    const link = open.match( /https?:\/\/[^"']*m\.ticket\/h\.public\/i\.\d+\/hash\.[a-f0-9]+/ );
    expect( link, 'the ticket screen should publish a customer link' ).not.toBeNull();

    await page.goto( link![ 0 ] );
    await expectFieldsIn( page, byProcess( 'send_public_ticket' ), [
      'attachment[]|input|file',
      'creator_hash|input|hidden',
      'creator_id|input|hidden',
      'new_ticket_message|textarea|',
      'newmsg|input|submit',
      'ticket_extra[*]|input|text',
    ] );

    const body = await page.content();
    // Both tables on this page: the ticket details on the left and the Related To block
    // under it. Customer comes from the second one.
    for ( const label of [ 'Ticket Number', 'Status', 'Related To', 'Customer' ] ) {
      expect( body, `the customer should still see "${ label }"` ).toContain( label );
    }
    expect( body, 'the customer view should not be dying' ).not.toContain( 'Fatal error' );
  } );

  test( 'the invoice payment block keeps its controls and its row hooks', async ( { page } ) => {
    await login( page );
    const invoiceId = await anInvoice( page );
    await page.goto( `/?m[]=invoice&p[]=invoice_admin&invoice_id=${ invoiceId }` );

    // The whole invoice screen is one form, so rather than list sixty controls this checks
    // the four the payment block owns, plus the hooks invoice.js needs.
    const form = page.locator( 'form:has(input[name="payment_amount"])' ).first();
    for ( const name of [ 'payment_amount', 'payment_method', 'butt_makepayment', 'buttpay' ] ) {
      await expect( form.locator( `[name="${ name }"]` ).first(),
        `the payment block should still have ${ name }` ).toBeAttached();
    }

    const body = await page.content();
    // invoice.js does $('.payment_type_online').show() / .hide() and writes the offline
    // instructions into #payment_type_offline_info. Both have to survive the layout change.
    // Matched on the row itself. Checking for the bare class name passes on the script
    // that references it, which is how an earlier version of this passed with the row
    // class removed.
    expect( ( body.match( /<tr[^>]*\bpayment_type_online\b/g ) ?? [] ).length,
      'the online payment rows need their class on the row' ).toBeGreaterThan( 1 );
    expect( body, 'the offline row needs its class on the row' )
      .toMatch( /<tr[^>]*\bpayment_type_offline\b/ );
    expect( body, 'and the offline instructions need somewhere to be written' )
      .toContain( 'id="payment_type_offline_info"' );
  } );

  test( 'the newsletter editor keeps its dynamic field list', async ( { page } ) => {
    await login( page );
    const stamp = Date.now() + '-' + Math.floor( Math.random() * 10000 );
    const newsletterId = await createRecord( page, '/?m[]=newsletter', {
      _process: 'save_newsletter',
      newsletter_id: 'new',
      subject: 'Dynamic Fields ' + stamp,
      from_name: 'Fieldset Sender',
      from_email: 'sender@example.com',
      content: 'Body text.',
    }, 'newsletter_id' );

    const body = await ( await page.request.get(
      `/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=${ newsletterId }` ) ).text();

    // Every placeholder is an insert-into-the-editor link, and everything past the first
    // four is hidden until View All. Both have to survive.
    const inserters = ( body.match( /onmousedown="var ed=typeof tinymce/g ) ?? [] ).length;
    expect( inserters, 'each dynamic field should still insert itself into the editor' )
      .toBeGreaterThan( 4 );
    expect( body, 'the overflow still needs somewhere to hide' ).toMatch( /dynamic_more/ );
    expect( body, 'and a View All to reveal it' ).toContain( 'View All' );
    expect( describeDiagnostics( body ).join( '\n' ), 'the editor printed a PHP diagnostic' ).toBe( '' );
  } );

  test( 'the custom data search keeps a row per searchable field', async ( { page } ) => {
    await login( page );
    const stamp = Date.now() + '-' + Math.floor( Math.random() * 10000 );
    const typeId = await createRecord( page, '/?m[]=data', {
      _process: 'save_data_type',
      data_type_id: 'new',
      data_type_name: 'Search Type ' + stamp,
    }, 'data_type_id' );
    const groupId = await createRecord( page, '/?m[]=data', {
      _process: 'save_data_field_group',
      data_field_group_id: 'new',
      data_type_id: typeId,
      name: 'Search Fieldset ' + stamp,
      _redirect: '/',
    }, 'data_field_group_id' );

    const key = await formAuthKey( page );
    await page.request.post( '/?m[]=data', {
      form: {
        _process: 'save_data_field',
        data_field_id: 'new',
        data_type_id: typeId,
        data_field_group_id: groupId,
        title: 'Searchable Note',
        order: '1',
        field_type: 'text',
        // save_field() zeroes any checkbox it was not sent, and the search screen only
        // lists fields marked searchable.
        searchable: '1',
        show_list: '1',
        form_auth_key: key,
      },
      failOnStatusCode: false,
    } );

    const body = await ( await page.request.get(
      `/?m[]=data&p[]=admin_data_search&data_type_id=${ typeId }`, { failOnStatusCode: false } ) ).text();

    expect( ( body.match( /name="search_do\[/g ) ?? [] ).length,
      'each searchable field needs its tick box' ).toBe( 1 );
    expect( body, 'the field should be named on its row' ).toContain( 'Searchable Note' );
    expect( body, 'and the field itself should be there to type into' )
      .toMatch( /id="search_field_\d+"/ );
    expect( describeDiagnostics( body ).join( '\n' ), 'the search screen printed a PHP diagnostic' )
      .toBe( '' );
  } );

  test( 'the newsletter template editor keeps its placeholder list', async ( { page } ) => {
    await login( page );
    const body = await ( await page.request.get(
      '/?m[]=newsletter&p[]=newsletter_template_edit&newsletter_template_id=new',
      { failOnStatusCode: false } ) ).text();

    // Each placeholder appends itself to the template body when clicked. {BODY} and the
    // raw PHP tag are hand-written rows; the rest come from get_replace_fields().
    expect( ( body.match( /onmousedown="\$\('#template_body'\)/g ) ?? [] ).length,
      'every placeholder should still append itself to the template' ).toBeGreaterThan( 4 );
    expect( body, '{BODY} should still be offered' ).toContain( '{BODY}' );
    expect( body, 'and the note about picking recipients should survive' )
      .toContain( 'once you pick your recipients' );
    expect( describeDiagnostics( body ).join( '\n' ), 'the template editor printed a PHP diagnostic' )
      .toBe( '' );
  } );

  test( 'the ticket screen keeps its other-tickets list', async ( { page } ) => {
    await login( page );

    // The block only renders when the same contact or customer has more than one ticket,
    // so the ticket to open is found rather than assumed.
    const list = await ( await page.request.get( '/?m[]=ticket&p[]=ticket_admin' ) ).text();
    const ids = [ ...new Set( [ ...list.matchAll( /ticket_id=(\d+)/g ) ].map( ( m ) => m[ 1 ] ) ) ];

    let body = '';
    for ( const id of ids.slice( 0, 8 ) ) {
      const candidate = await ( await page.request.get(
        `/?m[]=ticket&p[]=ticket_admin&ticket_id=${ id }`, { failOnStatusCode: false } ) ).text();
      if ( candidate.includes( 'Other Support Tickets' ) ) {
        body = candidate;
        break;
      }
    }
    test.skip( body === '', 'no ticket here shares a contact with another' );

    // One row per sibling ticket, each linking to it, and the current one marked.
    expect( body, 'the current ticket should be marked in the list' ).toContain( '&raquo;' );
    expect( ( body.match( /ticket_id=\d+/g ) ?? [] ).length,
      'the siblings should still link to themselves' ).toBeGreaterThan( 1 );
    expect( describeDiagnostics( body ).join( '\n' ), 'the ticket screen printed a PHP diagnostic' )
      .toBe( '' );
  } );

  test( 'the note editor keeps its controls', async ( { page } ) => {
    await login( page );
    const customerId = await aCustomer( page );

    // note_admin wants a signed options blob, so the link is taken from the customer screen
    // that produces it. Note also that this form puts _process in its action URL rather
    // than in a hidden field, which is why it is found by note_id instead.
    const page_body = await ( await page.request.get(
      `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }` ) ).text();
    const link = [ ...new Set( [ ...page_body.matchAll( /https?:\/\/[^"']*note\.note_admin[^"']*/g ) ]
      .map( ( m ) => m[ 0 ].replace( /&amp;/g, '&' ) ) ) ][ 0 ];
    expect( link, 'the customer screen should publish a notes link' ).toBeTruthy();

    await page.goto( link );
    await expectFieldsIn( page, 'form:has(input[name="note_id"])', [
      'cancel|input|button',
      'ignore|input|text',
      'note_id|input|hidden',
      'note_time|input|hidden',
      'note|textarea|',
      'options|input|hidden',
      'rel_data|input|hidden',
      'reminder|input|checkbox',
      'save|input|submit',
      'user_id|select|',
    ] );
  } );

  test( 'the ticket reply form keeps its controls', async ( { page } ) => {
    await login( page );
    const list = await ( await page.request.get( '/?m[]=ticket&p[]=ticket_admin' ) ).text();
    const ids = [ ...new Set( [ ...list.matchAll( /ticket_id=(\d+)/g ) ].map( ( m ) => m[ 1 ] ) ) ];
    expect( ids.length, 'there should be a ticket to reply to' ).toBeGreaterThan( 0 );

    await page.goto( `/?m[]=ticket&p[]=ticket_admin&ticket_id=${ ids[ 0 ] }` );

    // The reply block is one table inside a much larger form, so rather than list forty
    // controls this names the ones that table owns.
    const form = page.locator( 'form:has(textarea[name="new_ticket_message"])' ).first();
    for ( const name of [
      'attachment[]', 'change_status_id', 'assigned_user_id', 'change_user_id',
      'ticket_cc_staff[*]'.replace( '[*]', '' ), 'ticket_cc', 'ticket_bcc',
      'private_message', 'canned_response_id',
    ] ) {
      await expect( form.locator( `[name^="${ name }"]` ).first(),
        `the reply block should still have ${ name }` ).toBeAttached();
    }

    // The CC and BCC rows were a <tbody id="ticket_cc_bcc"> that the Show CC/BCC button
    // reveals. They carry the class now and the button switches on it.
    const body = await page.content();
    expect( ( body.match( /<tr[^>]*\bticket_cc_bcc\b/g ) ?? [] ).length,
      'the cc and bcc rows need the class the button reveals' ).toBe( 3 );
    expect( body, 'and the button should reveal them by class' )
      .toContain( "$('.ticket_cc_bcc')" );
  } );

  test( 'the address block keeps its fields', async ( { page } ) => {
    await login( page );
    const customerId = await aCustomer( page );
    const body = await ( await page.request.get(
      `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }` ) ).text();

    // The address hash differs per owner, so the names are compared with it taken out.
    const names = [ ...new Set( [ ...body.matchAll( /name="(address\[[^\]]+\]\[[a-z_0-9]+\])"/g ) ]
      .map( ( m ) => m[ 1 ].replace( /\[[^\]]{6,}\]/, '[hash]' ) ) ) ].sort();

    expect( names, 'the address block should render the same fields' ).toEqual( [
      'address[hash][address_id]',
      'address[hash][address_type]',
      'address[hash][country]',
      'address[hash][line_1]',
      'address[hash][line_2]',
      'address[hash][owner_id]',
      'address[hash][owner_table]',
      'address[hash][post_code]',
      'address[hash][region]',
      'address[hash][state]',
      'address[hash][suburb]',
    ] );
  } );
} );

/**
 * One company, reused across runs.
 *
 * module_company::is_enabled() is false until a company row exists, and the assigned
 * company block on the user edit screen is behind that check - which is why the block was
 * first written off as unreachable. It only needs a record, not a config change. Reused
 * rather than recreated for the same reason as the customer group above: the user edit
 * screen renders a checkbox per company.
 */
async function aCompany( page: Page ): Promise<string> {
  const COMPANY_NAME = 'Fieldset Company';
  await page.goto( '/?m[]=company&p[]=company_admin' );
  const existing = ( await page.content() ).match(
    new RegExp( `company_id=(\\d+)[^>]*>\\s*${ COMPANY_NAME }` ) );
  if ( existing ) {
    return existing[ 1 ];
  }

  return createRecord( page, '/?m[]=company', {
    _process: 'save_company',
    company_id: 'new',
    name: COMPANY_NAME,
    _redirect: '/',
  }, 'company_id' );
}

test.describe( 'Forms moved to generate_fieldset (assigned company)', () => {
  test( 'the staff edit screen keeps its company checkboxes', async ( { page } ) => {
    await login( page );
    const companyId = await aCompany( page );

    // user_admin is the route; user_admin_edit.php is a partial it includes. Asking for
    // p[]=user_admin_edit returns a four byte page, which is what made this block look
    // unreachable the first time round.
    const list = await ( await page.request.get( '/?m[]=user&p[]=user_admin' ) ).text();
    const ids = Array.from( list.matchAll( /user_id=(\d+)/g ) ).map( m => m[ 1 ] );
    expect( ids.length, 'no staff users to open' ).toBeGreaterThan( 0 );

    const body = await ( await page.request.get( `/?m[]=user&p[]=user_admin&user_id=${ ids[ 0 ] }` ) ).text();

    // The premise: with no company row this block does not render at all, and a spec that
    // passed either way would be saying nothing about the conversion.
    expect( body, 'the assigned company block did not render' ).toContain( 'Assigned Company' );

    // The two controls the block exists to post: a checkbox per company, and the hidden
    // companion that tells save_user the checkbox was on the form at all. Dropping the
    // hidden one makes unticking a company silently do nothing.
    expect( body ).toContain( `name="user_company[${ companyId }]"` );
    expect( body ).toContain( `name="available_user_company[${ companyId }]"` );
    expect( body ).toContain( `id="customer_company_${ companyId }"` );
  } );
} );

test.describe( 'Forms moved to generate_fieldset (subscriptions block)', () => {
  test( 'the member edit screen keeps its subscription controls', async ( { page } ) => {
    await login( page );

    const list = await ( await page.request.get( '/?m[]=member&p[]=member_admin' ) ).text();
    const ids = Array.from( list.matchAll( /member_id=(\d+)/g ) ).map( m => m[ 1 ] );
    expect( ids.length, 'no members to open' ).toBeGreaterThan( 0 );

    const body = await ( await page.request.get(
      `/?m[]=member&p[]=member_admin_edit&member_id=${ ids[ 0 ] }` ) ).text();

    expect( body, 'the subscriptions block did not render' ).toContain( 'Subscriptions' );

    // The hidden inputs are the whole contract with save_member: without
    // member_subscriptions_save the handler does not look at the subscription fields at
    // all, so losing it makes every change on this block silently do nothing.
    for ( const name of [
      'member_subscriptions_save',
      'subscription_add_payment',
      'subscription_add_payment_date',
      'subscription_add_payment_amount',
    ] ) {
      expect( body, `missing control ${ name }` ).toContain( `name="${ name }"` );
    }
  } );
} );

/**
 * Turns the priority support setting on for one test and puts it back afterwards.
 *
 * The priority block is behind module_config::c( 'ticket_allow_priority', 0 ), which is off
 * in this install and has no priority tickets to satisfy the other half of the condition,
 * so without this the block renders nowhere and a spec over it would assert nothing. Set
 * through the real settings form so the save goes through save_config with its CSRF token,
 * the way a person would do it.
 */
async function withTicketPriority( page: Page, body: () => Promise<void> ): Promise<void> {
  const set = async ( on: boolean ) => {
    await page.goto( '/?m[]=ticket&p[]=ticket_settings_basic' );
    const box = page.locator( 'input[name="config[ticket_allow_priority]"]' );
    await box.setChecked( on );
    // Target the named save button: this page carries two other submit controls, the
    // first of which is hidden, so .first() is not clickable.
    await page.locator( 'input[type="submit"][name="save"]' ).click();
    await page.waitForLoadState();
  };
  await set( true );
  try {
    await body();
  } finally {
    await set( false );
  }
}

test.describe( 'Forms moved to generate_fieldset (priority support)', () => {
  test( 'the ticket screen keeps its priority support block', async ( { page } ) => {
    await login( page );

    const list = await ( await page.request.get( '/?m[]=ticket&p[]=ticket_admin' ) ).text();
    const ids = Array.from( list.matchAll( /ticket_id=(\d+)/g ) ).map( m => m[ 1 ] );
    expect( ids.length, 'no tickets to open' ).toBeGreaterThan( 0 );

    await withTicketPriority( page, async () => {
      await page.goto( `/?m[]=ticket&p[]=ticket_admin&ticket_id=${ ids[ 0 ] }` );
      const body = await page.content();
      expect( body, 'the priority support block did not render' ).toContain( 'Priority Support' );

      // Which message the block shows depends on whether the ticket already has an
      // invoice, so assert that it said one of the three things rather than picking one.
      expect(
        [ 'Priority Support costs', 'Please pay', 'Thank you for purchasing', 'Current ticket position' ]
          .some( t => body.includes( t ) ),
        'the block rendered but said none of the things it can say',
      ).toBe( true );

      // The shape the conversion had to preserve: one cell, no header cell. An element
      // passed to generate_fieldset with 'fields' and no 'title' renders exactly that,
      // and getting it wrong would put an empty <th> in front of the text.
      const shape = await page.evaluate( () => {
        const header = Array.from( document.querySelectorAll( 'h3, h5, .title' ) )
          .find( h => ( h.textContent || '' ).includes( 'Priority Support' ) );
        const box = header?.closest( '.box, div' );
        const row = box?.querySelector( 'table.tableclass_form tbody tr' );

        return row ? { tds: row.querySelectorAll( 'td' ).length, ths: row.querySelectorAll( 'th' ).length } : null;
      } );
      expect( shape, 'no fieldset row found under the Priority Support heading' ).not.toBeNull();
      expect( shape ).toEqual( { tds: 1, ths: 0 } );
    } );
  } );
} );

test.describe( 'Forms moved to generate_fieldset (group picker)', () => {
  test( 'the customer screen keeps its group picker', async ( { page } ) => {
    await login( page );
    const groupId = await aCustomerGroup( page );
    const customerId = await aCustomer( page );

    await page.goto( `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }` );
    const body = await page.content();

    // The three controls one group row posts. group_module carries the owner id and is
    // what tells save_groups the row was on the form; without it an unticked box is
    // indistinguishable from a box that was never rendered.
    expect( body ).toContain( `name="group_module[customer][${ groupId }]"` );
    expect( body ).toContain( `name="used_group_module[customer][${ groupId }]"` );
    expect( body ).toContain( `id="groupchkcustomer${ groupId }"` );
    expect( body ).toContain( `for="groupchkcustomer${ groupId }"` );

    // The shape: the checkbox sits in the header cell and the label in the data cell, and
    // the header cell carries the data-settings-url that plugin_config/js/settings.js
    // prepends its settings icon into. settings.js matches the attribute wherever it is,
    // but it has to be on an element that wraps something.
    const shape = await page.evaluate( ( id: string ) => {
      const box = document.querySelector( `#groupchkcustomer${ id }` );
      const row = box?.closest( 'tr' );
      if ( ! row ) {
        return null;
      }
      const head = row.querySelector( 'th' );

      return {
        checkboxInHeader: !! head?.contains( box as Node ),
        labelInData: !! row.querySelector( `td label[for="groupchkcustomer${ id }"]` ),
        settingsUrlOnSomething: !! row.querySelector( '[data-settings-url]' ),
      };
    }, groupId );

    expect( shape, 'no group row found for the seeded group' ).not.toBeNull();
    expect( shape ).toEqual( {
      checkboxInHeader: true,
      labelInData: true,
      settingsUrlOnSomething: true,
    } );
  } );
} );

/** A data type with one field group and one field in it, reused across runs. */
async function aDataFieldWithGroup( page: Page ): Promise<{ typeId: string; groupId: string; fieldId: string }> {
  const { typeId, groupId } = await aDataFieldGroup( page );
  const key = await formAuthKey( page );
  await page.request.post( '/?m[]=data', {
    form: {
      _process: 'save_data_field',
      data_field_id: 'new',
      data_type_id: typeId,
      data_field_group_id: groupId,
      title: 'Fieldset Probe Field',
      order: '1',
      field_type: 'text',
      // save_field() zeroes any checkbox it was not sent, so without this the field is
      // hidden from the listing.
      show_list: '1',
      form_auth_key: key,
    },
    failOnStatusCode: false,
  } );

  // admin_data_open is where render_group.php actually renders the rows; the group admin
  // route answers without them.
  const body = await ( await page.request.get(
    `/?m[]=data&p[]=admin_data_open&data_type_id=${ typeId }&data_record_id=new` ) ).text();
  const found = body.match( /id="container_data_field_(\d+)"/ );
  expect( found, 'the field group rendered no data field row' ).not.toBeNull();

  return { typeId, groupId, fieldId: ( found as RegExpMatchArray )[ 1 ] };
}

test.describe( 'Forms moved to generate_fieldset (data field group)', () => {
  test( 'a data field row keeps the id and rel its own scripts read', async ( { page } ) => {
    await login( page );
    const { typeId, fieldId } = await aDataFieldWithGroup( page );

    await page.goto( `/?m[]=data&p[]=admin_data_open&data_type_id=${ typeId }&data_record_id=new` );

    const row = await page.evaluate( ( id: string ) => {
      const tr = document.querySelector( `tr#container_data_field_${ id }` );
      if ( ! tr ) {
        return null;
      }

      return {
        rel: tr.getAttribute( 'rel' ),
        classes: ( tr.getAttribute( 'class' ) || '' ).split( /\s+/ ).filter( Boolean ).sort(),
        inSortableTbody: !! tr.closest( 'table.data_group_fields > tbody' ),
      };
    }, fieldId );

    expect( row, 'no row with the id the sortable serialises' ).not.toBeNull();
    // jQuery UI's sortable('serialize') builds container_data_field[] from the row id, and
    // includes/plugin_data/data.php reads $_REQUEST['container_data_field'] to save the new
    // order - so the id is the reorder feature, not decoration. The resize handlers read
    // rel off the same row ( data_type_admin_group_open.php reads
    // .parents('tr').first().attr('rel') ).
    expect( row!.rel, 'rel must carry the data field id' ).toBe( fieldId );
    expect( row!.classes ).toContain( 'data_field' );
    expect( row!.classes ).toContain( 'data_field_text' );
    expect( row!.inSortableTbody, 'the row must sit in the tbody the sortable binds to' ).toBe( true );
  } );
} );

/**
 * A security role granting view on exactly the permissions listed, and nothing else.
 *
 * The two primary contact blocks on the customer and vendor screens only render for a
 * staff member who cannot see, or cannot edit, that record's contacts - which is why they
 * were written off as unreachable. An administrator cannot reach them at all:
 * module_security::has_feature_access() returns true unconditionally for user id 1.
 *
 * An explicit grant list rather than "everything except X", because the permission table
 * grows: has_feature_access() inserts a row the first time any page asks for a permission
 * it has not seen, so the count climbs as the suite covers more screens - 951 rows at the
 * start of this work, 1133 a few hours later. PHP's max_input_vars is 1000, and the role
 * form posts one checkbox per permission per action, so "tick everything" silently
 * truncates and lands back on the login screen. Granting a fixed handful is both stable
 * and well under the limit. (That the form is unsaveable at all past ~1000 permissions is
 * a real bug, raised separately; the assertion below is here so this fixture cannot drift
 * back into it unnoticed.)
 *
 * The ids: 82 Can User Login - never omit it, or establish_session() answers "Account
 * disabled" and the account is unusable. 33 Customers and 199 Customer, 180 Vendors, and
 * 15/16 Contacts are what the two screens check before rendering at all. 105 All Customer
 * Contacts and 179 All Vendor Contacts are the ones these blocks turn on.
 */
const CAN_LOG_IN_AND_OPEN_RECORDS = [ '82', '33', '199', '180', '15', '16' ];
const ALL_CONTACTS = [ '105', '179' ];

async function aRoleGranting( page: Page, roleName: string, grant: string[] ): Promise<string> {
  const form = await ( await page.request.get( '/?m[]=security&p[]=security_role&security_role_id=new' ) ).text();
  const token = ( form.match( /name="form_auth_key" value="([^"]+)"/ ) || [] )[ 1 ];
  expect( token, 'the role form published no token' ).toBeTruthy();

  const body: Record<string, string> = {
    _process: 'save_security_role',
    security_role_id: 'new',
    name: roleName,
    form_auth_key: token,
    _redirect: '/',
  };
  for ( const permissionId of grant ) {
    expect(
      form,
      `the role form has no view checkbox for permission ${ permissionId }`,
    ).toContain( `name="permission[${ permissionId }][view]"` );
    body[ `permission[${ permissionId }][view]` ] = '1';
  }
  expect(
    Object.keys( body ).length,
    'this post exceeds max_input_vars (1000) and will be silently truncated',
  ).toBeLessThan( 1000 );

  // The save redirects to the new role, which is the only reliable way to learn its id:
  // the role list shows twenty at a time and offers no paging parameter to follow.
  const saved = await page.request.post( '/?m[]=security',
    { form: body, failOnStatusCode: false, maxRedirects: 0 } );
  const created = ( ( saved.headers()[ 'location' ] ?? '' ).match( /security_role_id=(\d+)/ ) || [] )[ 1 ];
  expect( created, `the role ${ roleName } was not created` ).toBeTruthy();

  return created;
}

/**
 * A staff account with a restricted role, and a browser signed in as it.
 *
 * Both the account and the role are created per run and removed again by the returned
 * cleanup, because neither can be found a second time: the user list and the role list
 * each show about twenty rows with no paging parameter to follow, and a record created by
 * a test is not on either of them. Leaving them behind is how the CSRF spec ended up with
 * 188 roles. module_form::confirm_delete() takes a hash of the key and its value, so a
 * test can go through the app's own delete rather than reaching into the database.
 */
async function signedInAs(
  page: Page,
  browser: Browser,
  label: string,
  grant: string[],
): Promise<{ visitor: Page; cleanup: () => Promise<void> }> {
  const roleId = await aRoleGranting( page, `Fieldset Role ${ label } ${ Date.now() }`, grant );
  const userId = await createRecord( page, '/?m[]=user', {
    _process: 'save_user',
    user_id: 'new',
    name: `Fieldset ${ label }`,
    email: `fieldset-${ label.toLowerCase().replace( /\W+/g, '-' ) }-${ Date.now() }@example.com`,
    // An admin setting somebody else's password uses password_new. Without a real stored
    // hash, get_auto_login_string() has nothing to derive a key from.
    password_new: 'Fieldset-' + label.replace( /\W+/g, '' ) + '-9142!',
    role_id: roleId,
    _redirect: '/',
  }, 'user_id' );

  const cleanup = async () => {
    await deleteRecord( page, '/?m[]=user', 'save_user', 'user_id', userId );
    await deleteRecord( page, '/?m[]=security', 'save_security_role', 'security_role_id', roleId );
  };

  try {
    await page.goto( '/?m[]=user&p[]=user_admin&user_id=' + userId );
    const link = await page.evaluate( () => {
      const a = Array.from( document.querySelectorAll( 'a[href*="auto_login="]' ) )[ 0 ] as HTMLAnchorElement | undefined;

      return a ? a.href : '';
    } );
    expect( link, 'the user screen should offer an auto-login link' ).toContain( 'auto_login=' + userId );

    const context = await browser.newContext();
    const visitor = await context.newPage();
    await visitor.goto( link );
    await visitor.waitForLoadState( 'load' );
    await expect(
      visitor.locator( 'input[name="password"]' ),
      'the restricted account did not sign in - check it still has the Can User Login permission (82)',
    ).toHaveCount( 0 );

    return {
      visitor,
      cleanup: async () => {
        await context.close();
        await cleanup();
      },
    };
  } catch ( error ) {
    await cleanup();
    throw error;
  }
}

/** Deletes a record through the app's own confirm-delete, hash and all. */
async function deleteRecord(
  page: Page,
  postUrl: string,
  process: string,
  key: string,
  id: string,
): Promise<void> {
  const { createHash } = await import( 'node:crypto' );
  await page.request.post( postUrl, {
    form: {
      _process: process,
      [ key ]: id,
      butt_del: '1',
      // module_form::confirm_delete() accepts md5( 'delete <key> <value>' ) plus the
      // literal 'yep', which is how the confirmation screen posts back.
      _confirm_delete: createHash( 'md5' ).update( `delete ${ key } ${ id }` ).digest( 'hex' ),
      really_confirm_delete: 'yep',
      form_auth_key: await formAuthKey( page ),
      _redirect: '/',
    },
    failOnStatusCode: false,
  } );
}


test.describe( 'Forms moved to generate_fieldset (restricted contact blocks)', () => {
  test( 'a staff member who cannot view contacts is told the details are hidden', async ( { page, browser } ) => {
    await login( page );
    const customerId = await aCustomer( page );
    const url = `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }`;

    // The premise: an administrator cannot reach this branch at all, because
    // has_feature_access() returns true unconditionally for user id 1. If this ever shows
    // up for the admin then the test below is not proving what it says.
    const asAdmin = await ( await page.request.get( url ) ).text();
    expect( asAdmin, 'the admin should never see the hidden-details block' ).not.toContain( 'Details hidden' );

    // Granting neither 105 nor 179 is what puts this account in the first branch, on both
    // screens, so one account covers the customer and the vendor one.
    const vendorList = await ( await page.request.get( '/?m[]=vendor&p[]=vendor_admin' ) ).text();
    const vendorId = ( vendorList.match( /vendor_id=(\d+)/ ) || [] )[ 1 ];
    const vendorUrl = vendorId ? `/?m[]=vendor&p[]=vendor_admin_edit&vendor_id=${ vendorId }` : '';

    const { visitor, cleanup } = await signedInAs( page, browser, 'No Contact View', CAN_LOG_IN_AND_OPEN_RECORDS );
    try {
      const body = await ( await visitor.request.get( url ) ).text();
      expect( body, 'the restricted account could not open the customer screen' )
        .toContain( 'Primary Contact Details' );
      expect( body, 'the hidden-details block did not render' ).toContain( 'Details hidden' );

      if ( vendorUrl ) {
        const vendorBody = await ( await visitor.request.get( vendorUrl ) ).text();
        expect( vendorBody, 'the restricted account could not open the vendor screen' )
          .toContain( 'Primary Contact Details' );
        expect( vendorBody, 'the vendor hidden-details block did not render' ).toContain( 'Details hidden' );
      }
    } finally {
      await cleanup();
    }
  } );
} );

test.describe( 'Forms moved to generate_fieldset (read-only contact block)', () => {
  test( 'a staff member who can view but not edit contacts still sees them', async ( { page, browser } ) => {
    await login( page );
    const customerId = await aCustomer( page );
    const url = `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }`;

    // The contact this customer was seeded with, read as the administrator so the test
    // knows what the restricted view is supposed to show.
    await page.goto( url );
    const contactName = await page.evaluate( () => {
      const input = document.querySelector( 'input[name="name"]' ) as HTMLInputElement | null;

      return input ? input.value : '';
    } );
    expect( contactName, 'the seeded customer has no primary contact name' ).not.toBe( '' );

    // Granted view on All Customer Contacts but never edit - no role here grants edit - so
    // this is the second branch: the read-only summary rather than "Details hidden".
    const { visitor, cleanup } = await signedInAs( page, browser, 'View Not Edit', [ ...CAN_LOG_IN_AND_OPEN_RECORDS, ...ALL_CONTACTS ] );
    try {
      const body = await ( await visitor.request.get( url ) ).text();
      expect( body, 'the restricted account could not open the customer screen' )
        .toContain( 'Primary Contact Details' );
      expect( body, 'this is the read-only branch, not the hidden one' ).not.toContain( 'Details hidden' );

      // Scoped to the block's own cell, not the page. The name also appears in the
      // heading's responsive summary attribute, so a page-wide contains() passes even when
      // the cell is empty - which is exactly the state this is meant to catch.
      await visitor.goto( url );
      const cellText = await visitor.evaluate( () => {
        const heading = Array.from( document.querySelectorAll( 'h3, h5, .title' ) )
          .find( h => ( h.textContent || '' ).includes( 'Primary Contact Details' ) );
        const box = heading?.closest( '.box' ) ?? heading?.parentElement?.parentElement;
        const cell = box?.querySelector( 'table.tableclass_form td' );

        return cell ? ( cell.textContent || '' ).trim() : null;
      } );
      expect( cellText, 'no cell found under the Primary Contact Details heading' ).not.toBeNull();
      // The block exists to show the contact to somebody who may not edit them. An inner
      // ob_start() used to swallow exactly that, leaving the cell empty.
      expect( cellText, 'the read-only contact block rendered an empty cell' ).toContain( contactName );
    } finally {
      await cleanup();
    }
  } );
} );

test.describe( 'Forms moved to generate_fieldset (faq product row)', () => {
  test( 'the public ticket form keeps its product selector and its ajax target', async ( { browser } ) => {
    // A context with no session: this is a public form.
    const context = await browser.newContext();
    const page = await context.newPage();
    try {
      await page.goto( '/ext.php?m=ticket&h=public_new' );
      const body = await page.content();

      expect( body, 'the faq product block did not render' ).toContain( 'Product Details' );
      expect( body ).toContain( 'name="faq_product_id"' );
      expect( body ).toContain( 'id="faq_product_id"' );

      // includes/plugin_faq/faq.php answers the product change over ajax with
      // $('#faq_product_area').html('<tr>...</tr>'), so the target has to stay a tbody -
      // table rows need a row parent or the browser throws them out of the table.
      const target = await page.evaluate( () => {
        const el = document.querySelector( '#faq_product_area' );
        const select = document.querySelector( '#faq_product_id' );

        return el
          ? {
            tag: el.tagName,
            // A table may hold several tbodies. The target is a second one in the
            // fieldset's own table, so the injected rows line up with the product row
            // above them and replacing them leaves it alone - which is what the
            // hand-written table used to do, and what tbody_after now does.
            sameTableAsTheProductRow: !! ( select && el.closest( 'table' ) === select.closest( 'table' ) ),
          }
          : null;
      } );
      expect( target, 'the ajax target #faq_product_area is gone' ).not.toBeNull();
      expect( target ).toEqual( { tag: 'TBODY', sameTableAsTheProductRow: true } );

      // And that it still accepts them. This install has no faq_product_rel rows, so a
      // real product change injects nothing; putting a row in the way faq.php does is what
      // actually exercises the risk, which is the browser hoisting rows out of a parent
      // that is no longer part of a table.
      const injected = await page.evaluate( () => {
        const target = document.querySelector( '#faq_product_area' ) as HTMLElement;
        target.innerHTML = '<tr id="injected_probe"><th>FAQ</th><td>probe</td></tr>';
        const row = document.querySelector( '#injected_probe' );

        return row
          ? { parent: row.parentElement?.id, inTable: !! row.closest( 'table' ), cells: row.children.length }
          : null;
      } );
      expect( injected, 'an injected row did not survive in the document' ).not.toBeNull();
      expect( injected ).toEqual( { parent: 'faq_product_area', inTable: true, cells: 2 } );
    } finally {
      await context.close();
    }
  } );
} );

/**
 * The one piece of form markup that is still hand-built, and the check that it is not a
 * form.
 *
 * includes/plugin_faq/faq.php answers a product change with a fragment of <tr>s that the
 * page drops into the fieldset's second tbody. It is hand-built and stays that way:
 * generate_fieldset renders whole tables, not fragments, so converting it would need a
 * rows-only mode on both renderers. That is only defensible while the fragment carries no
 * form controls - the moment somebody puts an input in it, it is form markup bypassing the
 * helper, and the exemption stops being true. Asserted rather than assumed.
 */
test.describe( 'The faq ajax fragment', () => {
  const AJAX = '/external/m.faq/h.ticket_list/';
  const QUESTION = 'Fieldset FAQ probe question';

  test( 'carries FAQ content and no form controls', async ( { page, browser } ) => {
    await login( page );

    const form = await ( await page.request.get( '/ext.php?m=ticket&h=public_new' ) ).text();
    const select = form.slice( form.indexOf( 'name="faq_product_id"' ) );
    const productId = ( select.match( /<option value="(\d+)"/ ) || [] )[ 1 ];
    expect( productId, 'the public ticket form offers no faq product to ask about' ).toBeTruthy();

    const fragmentFor = async ( context: import( '@playwright/test' ).APIRequestContext ) =>
      ( await context.post( AJAX, { form: { faq_product_id: productId as string }, failOnStatusCode: false } ) ).text();

    // Reuse: only create a FAQ for this product if the ajax has nothing to say about it
    // yet, so repeated runs do not pile rows into ucm_faq.
    if ( ! ( await fragmentFor( page.request ) ).includes( 'table_faq_class' ) ) {
      await page.request.post( '/?m[]=faq', {
        form: {
          _process: 'save_faq',
          faq_id: 'new',
          question: QUESTION,
          answer: 'So that the ajax fragment has something to return.',
          'faq_product_ids[]': productId as string,
          form_auth_key: await formAuthKey( page ),
          _redirect: '/',
        },
        failOnStatusCode: false,
      } );
    }

    // Public, with no session: this is what a visitor's browser receives.
    const context = await browser.newContext();
    try {
      const body = await fragmentFor( context.request );

      // Non-vacuous: a fragment that came back empty would pass the controls check below
      // while proving nothing.
      expect( body, 'the ajax returned no FAQ content to inspect' ).toContain( 'table_faq_class' );
      expect( body ).toContain( '$(\'#faq_product_area\').html(' );

      // The fragment is links and a line of guidance. Anything that posts a value belongs
      // in the fieldset instead.
      for ( const control of [ '<input', '<select', '<textarea', '<button', 'name=' ] ) {
        expect(
          body.toLowerCase(),
          `the faq ajax fragment now contains ${ control } - it is form markup, so it has to go`
          + ' through generate_fieldset rather than being built by hand',
        ).not.toContain( control );
      }
    } finally {
      await context.close();
    }
  } );
} );
