import { test, expect, Page } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { aCustomer, aFile, createRecord, login } from './seed';
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
 * Still hand-written, and why. Each of these was looked at and left deliberately, rather
 * than converted without a spec to prove the fields survived.
 *
 *  includes/plugin_setup/pages/setup1.php
 *      Cannot be converted. generate_fieldset reads its per-user display settings from
 *      the `form` table, and step 1 of the wizard is where the database credentials are
 *      still being typed in. Converting it replaced the form with "Table 'ucm_form'
 *      doesn't exist" - checked against an empty database with scripts/install-test.sh,
 *      then reverted. Same shape as the form token problem the wizard already opts out of.
 *
 *  includes/plugin_newsletter/pages/newsletter_member.php
 *  includes/plugin_newsletter/pages/newsletter_queue.php
 *  includes/plugin_newsletter/pages/newsletter_queue_watch.php
 *      Read-only statistics tables that only render once a newsletter has been queued to
 *      a send. That needs a group with members in it, and enqueuing refuses with "Please
 *      select at least 1 person" until there is one. The fixture did not come together -
 *      saving a customer with used_group_module set writes no group_member row - so there
 *      is no passing spec to convert them against.
 *
 *  includes/plugin_encrypt/pages/encrypt_popup.php
 *      The encrypt plugin is not reachable in the development install: the popup answers
 *      with a 35 byte access denied, so there is nothing to record a baseline from.
 *
 *  includes/plugin_theme/themes/whitelabel1/pages/login.php
 *      A theme's own login form. It only renders when that theme is active, and themes
 *      are where bespoke markup belongs - generate_fieldset has a theme override hook, so
 *      having a theme call the shared helper points that relationship the wrong way.
 */

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
  const groupId = await createRecord( page, '/?m[]=group', {
    _process: 'save_group',
    group_id: 'new',
    name: 'Send Group ' + stamp,
    owner_table: 'customer',
    _redirect: '/',
  }, 'group_id' );

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
} );
