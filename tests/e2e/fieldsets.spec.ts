import { test, expect, Page } from '@playwright/test';
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
 * Seventeen tables are left, and each has a checked reason rather than a judgement:
 *
 *  UNREACHABLE FROM THE TEST ACCOUNT - no passing spec can be written, so no conversion
 *    customer_admin_edit (2), vendor_admin_edit (2)   render only for a user WITHOUT
 *      permission to view contacts. An administrator never sees them; both screens were
 *      loaded and neither string appears.
 *    user_admin_edit_company   the company module is off in this install, so the block
 *      never renders. Checked across three user screens.
 *    newsletter_edit:460   sits inside a commented-out block, so it renders nowhere.
 *
 *  COUPLED TO MARKUP A FIELDSET CANNOT PRODUCE
 *    render_group   opens its <table> in one branch of a switch and closes it in another,
 *      streaming rows between - there is no block to replace. It also carries an id and a
 *      rel that its own script reads.
 *    group.php   puts the checkbox in the <th> with a data-settings-url that
 *      plugin_config/js/settings.js scans for, and a fieldset offers no way to put an
 *      attribute on the title cell. Worth revisiting if that option is ever added.
 *
 *  NOT A FORM LAYOUT - one cell, no <th>, a styled container around a message or summary.
 *  Several are already handed to a fieldset as its elements_before:
 *    customer_summary, contact_summary, user_summary, newsletter_statistics,
 *    invoice_sidebar, member_edit, ticket_create, ticket_priority_sidebar, ticket.php
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
