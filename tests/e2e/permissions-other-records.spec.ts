import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';
import { rowsWhere } from './db';

/**
 * The rest of the record screens: websites, contracts, subscriptions, products, members,
 * groups, newsletters, notes, finance, change requests and the encrypted-field store, and
 * the settings screens and bulk tools that write.
 *
 * Posts straight to the save endpoints, with a real form token, as a login that may only
 * sign in, and checks the database did not move. See permissions-customers.spec.ts.
 */

/** PHP's serialize() for the flat string and integer arrays the notes box posts back. */
function phpSerialize( values: Record<string, string | number> ): string {
  const body = Object.entries( values ).map( ( [ key, value ] ) =>
    `s:${ Buffer.byteLength( key ) }:"${ key }";`
    + ( typeof value === 'number' ? `i:${ value };` : `s:${ Buffer.byteLength( value ) }:"${ value }";` ),
  ).join( '' );

  return `a:${ Object.keys( values ).length }:{${ body }}`;
}

test.describe( 'Saves on the other record screens need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let customerId: string;
  const made: Array<{ url: string; process: string; key: string; id: string; confirmKey?: string }> = [];

  /** An existing record for the specs to post at, removed again afterwards. */
  async function aRecord( url: string, process: string, key: string, body: Record<string, string> ): Promise<string> {
    const id = await createRecord( admin, url, { _process: process, [ key ]: 'new', _redirect: '', ...body }, key );
    made.push( { url, process, key, id } );

    return id;
  }

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    customerId = await aCustomer( admin );
    nobody = await loginWith( admin, browser, 'Other none' );
    viewer = await loginWith( admin, browser, 'Other view', [
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
      { category: 'Customer', name: 'Customer Groups', actions: [ 'view' ] },
      { category: 'Config', name: 'Settings', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    for ( const record of made.reverse() ) {
      await deleteRecord( admin, record.url, record.process, record.key, record.id );
    }
    await admin?.close();
  } );

  test( 'websites', async () => {
    const id = await aRecord( '/?m[]=website', 'save_website', 'website_id', { name: 'Permission Website', customer_id: customerId } );
    await expectNoWrites( [ 'website', 'change_request_website', 'extra' ], async () => {
      await nobody.post( '/?m[]=website', { _process: 'save_website', website_id: id, name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=website', { _process: 'save_website', website_id: 'new', name: 'Created Without Permission', customer_id: customerId } );
      await nobody.post( '/?m[]=website', deleteBody( 'save_website', 'website_id', id ) );
    }, 'saving a website' );
  } );

  test( 'contracts', async () => {
    const id = await aRecord( '/?m[]=contract', 'save_contract', 'contract_id', { name: 'Permission Contract', customer_id: customerId } );
    await expectNoWrites( [ 'contract', 'contract_product' ], async () => {
      await nobody.post( '/?m[]=contract', { _process: 'save_contract', contract_id: id, name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=contract', { _process: 'save_contract', contract_id: 'new', name: 'Created Without Permission', customer_id: customerId } );
      await nobody.post( '/?m[]=contract', deleteBody( 'save_contract', 'contract_id', id ) );
    }, 'saving a contract' );
  } );

  test( 'subscriptions', async () => {
    const id = await aRecord( '/?m[]=subscription', 'save_subscription', 'subscription_id', { name: 'Permission Subscription' } );
    await expectNoWrites( [ 'subscription', 'subscription_owner', 'extra' ], async () => {
      await nobody.post( '/?m[]=subscription', { _process: 'save_subscription', subscription_id: id, name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=subscription', { _process: 'save_subscription', subscription_id: 'new', name: 'Created Without Permission' } );
      await nobody.post( '/?m[]=subscription', deleteBody( 'save_subscription', 'subscription_id', id ) );
    }, 'saving a subscription' );
  } );

  test( 'products, their suppliers and categories', async () => {
    const id = await aRecord( '/?m[]=product', 'save_product', 'product_id', { name: 'Permission Product' } );
    await expectNoWrites( [ 'product', 'product_category', 'product_supplier' ], async () => {
      await nobody.post( '/?m[]=product', { _process: 'save_product', product_id: id, name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=product', { _process: 'save_product', product_id: 'new', name: 'Created Without Permission' } );
      await nobody.post( '/?m[]=product', { _process: 'save_product_supplier', product_id: id, customer_id: customerId, new_customer_id: customerId } );
      await nobody.post( '/?m[]=product', { _process: 'save_product_category', product_category_id: 'new', product_category_name: 'Nope' } );
      await nobody.post( '/?m[]=product', deleteBody( 'save_product', 'product_id', id ) );
    }, 'saving a product' );
  } );

  test( 'members', async () => {
    const id = await aRecord( '/?m[]=member', 'save_member', 'member_id', { first_name: 'Permission', email: `permission-member-${ Date.now() }@example.com` } );
    await expectNoWrites( [ 'member', 'extra' ], async () => {
      await nobody.post( '/?m[]=member', { _process: 'save_member', member_id: id, first_name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=member', { _process: 'save_member', member_id: 'new', first_name: 'Created', email: 'nope-member@example.com' } );
      await nobody.post( '/?m[]=member', deleteBody( 'save_member', 'member_id', id ) );
    }, 'saving a member' );
  } );

  test( 'groups', async () => {
    const id = await aRecord( '/?m[]=group', 'save_group', 'group_id', { name: 'Permission Group' } );
    await expectNoWrites( [ 'group', 'group_member' ], async () => {
      await nobody.post( '/?m[]=group', { _process: 'save_group', group_id: id, name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=group', { _process: 'save_group', group_id: 'new', name: 'Created Without Permission' } );
      await nobody.post( '/?m[]=group', deleteBody( 'save_group', 'group_id', id ) );
    }, 'saving a group' );
    // The customer list's "add to group" box writes as the list renders. A login that can
    // see the list and its groups, but not edit them, must not be able to fill one.
    await expectNoWrites( [ 'group_member' ], async () => {
      await viewer.post( '/?m[]=customer&p[]=customer_admin_list', { add_group_go: 'yes', [ `add_to_group[${ id }]` ]: 'yes' } );
    }, 'adding customers to a group', () => viewer.visitor.goto( '/?m[]=customer&p[]=customer_admin_list' ) );
  } );

  test( 'newsletters and their templates', async () => {
    const id = await aRecord( '/?m[]=newsletter', 'save_newsletter', 'newsletter_id', { subject: 'Permission Newsletter' } );
    await expectNoWrites( [ 'newsletter', 'newsletter_content', 'newsletter_send', 'newsletter_template' ], async () => {
      await nobody.post( '/?m[]=newsletter', { _process: 'save_newsletter', newsletter_id: id, subject: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=newsletter', { _process: 'save_newsletter', newsletter_id: 'new', subject: 'Created Without Permission' } );
      await nobody.post( '/?m[]=newsletter', { _process: 'save_newsletter_template', newsletter_template_id: 'new', newsletter_template_name: 'Nope' } );
      await nobody.post( '/?m[]=newsletter', { _process: 'enque_send', newsletter_id: id, send_id: '0' } );
      await nobody.post( '/?m[]=newsletter', deleteBody( 'save_newsletter', 'newsletter_id', id ) );
    }, 'saving a newsletter' );
  } );

  test( 'notes', async () => {
    const options = Buffer.from( phpSerialize( { owner_table: 'customer', owner_id: Number( customerId ), title: 'All Customer Notes' } ) ).toString( 'base64' );
    await expectNoWrites( [ 'note' ], async () => {
      await nobody.post( '/?m[]=note', { _process: 'save_note', note_id: 'new', options, note: 'Added Without Permission', note_time: String( Math.floor( Date.now() / 1000 ) ) } );
    }, 'saving a note' );
    // A note that is not on the record it claims to be on is not a way round the check.
    const forged = Buffer.from( phpSerialize( { owner_table: 'customer', owner_id: Number( customerId ), title: 'Nothing', bypass_security: 1 } ) ).toString( 'base64' );
    await expectNoWrites( [ 'note' ], async () => {
      await nobody.post( '/?m[]=note', { _process: 'save_note', note_id: 'new', options: forged, note: 'Forged Without Permission', note_time: '1' } );
    }, 'saving a note with forged options' );
  } );

  test( 'finance transactions and recurring items', async () => {
    const id = await aRecord( '/?m[]=finance', 'save_finance', 'finance_id', { name: 'Permission Transaction', transaction_date: '01/01/2026', amount: '1', sub_amount: '1', type: 'e' } );
    await expectNoWrites( [ 'finance', 'finance_account', 'finance_category', 'finance_category_rel', 'finance_recurring', 'finance_tax' ], async () => {
      await nobody.post( '/?m[]=finance', { _process: 'save_finance', finance_id: id, name: 'Renamed Without Permission', amount: '2' } );
      await nobody.post( '/?m[]=finance', { _process: 'save_finance', finance_id: 'new', name: 'Created Without Permission', transaction_date: '01/01/2026', amount: '3', type: 'e' } );
      await nobody.post( '/?m[]=finance', { _process: 'quick_save_finance', transaction_date: '01/01/2026', name: 'Quick Without Permission', debit: '4' } );
      await nobody.post( '/?m[]=finance', { _process: 'save_recurring', finance_recurring_id: 'new', name: 'Recurring Without Permission' } );
      await nobody.post( '/?m[]=finance', deleteBody( 'save_finance', 'finance_id', id ) );
    }, 'saving a transaction' );
  } );

  test( 'change requests', async () => {
    const websiteId = await aRecord( '/?m[]=website', 'save_website', 'website_id', { name: 'Permission Request Website', customer_id: customerId } );
    await expectNoWrites( [ 'change_request' ], async () => {
      await nobody.post( '/?m[]=change_request', { _process: 'save_change_request', change_request_id: 'new', website_id: websiteId, request: 'Nope' } );
    }, 'saving a change request' );
  } );

  test( 'the encrypted-field store', async () => {
    await expectNoWrites( [ 'encrypt', 'encrypt_key', 'encrypt_field' ], async () => {
      await nobody.post( '/?m[]=encrypt', { _process: 'save_encrypt', encrypt_id: '0', encrypt_field_id: '1', encrypted_value: 'nope' } );
      await nobody.post( '/?m[]=encrypt', { _process: 'save_encrypt_key', encrypt_key_id: '0', encrypt_field_id: '1', public_key: 'nope' } );
    }, 'saving an encrypted value' );
  } );

  test( 'translations cannot be wiped by someone who may not edit them', async () => {
    await expectNoWrites( [ 'language', 'language_word', 'language_translation' ], async () => {
      await nobody.post( '/?m[]=language', { _process: 'language_reset', really: 'yes' } );
      await nobody.post( '/?m[]=language', { _process: 'language_duplicate_remove', really: 'yep' } );
      await nobody.post( '/?m[]=language', { _process: 'remove_duplicates', duplicate_ids: '[1,2,3]' } );
    }, 'wiping the translations' );
    await expectNoWrites( [ 'language', 'language_word', 'language_translation' ], async () => {
      await viewer.post( '/?m[]=language', { _process: 'language_reset', really: 'yes' } );
    }, 'wiping the translations as a settings viewer' );
  } );

  test( 'an import is not a way round the customer permissions', async () => {
    const options = Buffer.from( JSON.stringify( {
      callback: 'module_customer::handle_import', name: 'Customers', return_url: '/', fields: { 'Customer Name': 'customer_name' },
    } ) ).toString( 'base64' );
    await expectNoWrites( [ 'customer', 'user', 'address' ], async () => {
      await nobody.post( '/?m[]=import_export&p[]=import', {
        import_options: options, run_import: 'true', 'c[0]': 'customer_name', 'r[1][0]': 'Imported Without Permission',
      } );
    }, 'importing a customer' );
    // And the callback has to be an import: this one would otherwise be called with the rows.
    const sneaky = Buffer.from( JSON.stringify( { callback: 'module_customer::delete_customer', name: 'Customers', return_url: '/', fields: {} } ) ).toString( 'base64' );
    await expectNoWrites( [ 'customer' ], async () => {
      await viewer.post( '/?m[]=import_export&p[]=import', { import_options: sneaky, run_import: 'true', 'c[0]': 'customer_id', 'r[1][0]': customerId } );
    }, 'importing with a callback that is not an import' );
  } );

  test( 'settings screens that write need "edit" on the settings, not just "view"', async () => {
    await expectNoWrites( [ 'config' ], async () => {
      await viewer.post( '/?m[]=config&p[]=config_menu', { 'save_menu_config[1][position]': '99' } );
    }, 'saving the menu order' );
  } );

  test( 'the setup wizard is for whoever may change the settings', async () => {
    for ( const step of [ 1, 2, 3, 4, 5 ] ) {
      const response = await viewer.visitor.goto( `/?m[]=setup&p[]=setup&step=${ step }` );
      const body = await response!.text();
      expect( body, `step ${ step } ran for a login that may only view settings` ).not.toMatch( /Step #?\d/ );
    }
  } );

  test( 'ending another login\'s session needs "edit" on the settings', async () => {
    const cookies = await admin.context().cookies();
    const session = cookies.find( c => /sess/i.test( c.name ) );
    test.skip( ! session, 'could not find the administrator\'s session cookie' );
    const before = await rowsWhere( 'session', 'session_id', session!.value );
    test.skip( ! before.length, 'the session is not stored in the session table here' );
    await viewer.visitor.goto( '/?m[]=security&p[]=login_history&end=' + encodeURIComponent( session!.value ) );
    const after = await rowsWhere( 'session', 'session_id', session!.value );
    expect( after.length ).toBe( 1 );
    expect( after[ 0 ].logged_in, 'the administrator was logged out' ).toBe( before[ 0 ].logged_in );
  } );
} );
