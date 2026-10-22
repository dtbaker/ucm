import { test, expect, Page } from '@playwright/test';
import { aCustomer, anAddress, createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';
import { checksumTables } from './db';

/**
 * Saving customers, contacts and users takes the permission the matching page asks for.
 *
 * The pages and menus have always honoured a role. These specs post straight to the save
 * endpoints instead, with a real form token, as a login whose role holds nothing but the
 * right to sign in, and check that the database did not move. Each post is one a form on
 * the matching page can make, so they are what a role that is meant to be read-only must
 * not be able to do.
 */

const WRITES = [ 'customer', 'customer_type', 'user', 'user_role', 'address', 'customer_user_rel', 'customer_config' ];

test.describe( 'Customer, contact and user saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let customerId: string;
  let contactId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    customerId = await aCustomer( admin );
    contactId = await createRecord( admin, '/?m[]=user', {
      _process: 'save_user',
      user_id: 'new',
      customer_id: customerId,
      name: 'Permission Contact',
      email: `permission-contact-${ Date.now() }@example.com`,
      _redirect: '/',
    }, 'user_id' );
    nobody = await loginWith( admin, browser, 'Customers none' );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    if ( contactId ) {
      await deleteRecord( admin, '/?m[]=user', 'save_user', 'user_id', contactId, { button: 'butt_del_contact' } );
    }
    await admin?.close();
  } );

  test( 'a login with no customer permissions cannot rename or create a customer', async () => {
    await expectNoWrites( WRITES, async () => {
      await nobody.post( '/?m[]=customer', { _process: 'save_customer', customer_id: customerId, customer_name: 'Renamed Without Permission' } );
      await nobody.post( '/?m[]=customer', { _process: 'save_customer', customer_id: 'new', customer_name: 'Created Without Permission' } );
      // The archive button is a save with one more field.
      await nobody.post( '/?m[]=customer', { _process: 'save_customer', customer_id: customerId, customer_name: 'Seed', butt_archive: '1' } );
    }, 'saving a customer' );
  } );

  test( 'or give a customer credit, change its type or delete it', async () => {
    await expectNoWrites( WRITES, async () => {
      await nobody.post( '/?m[]=customer', { _process: 'save_customer', customer_id: customerId, credit: '9999' } );
      await nobody.post( '/?m[]=customer', { _process: 'save_customer', customer_id: customerId, customer_type_id: '1' } );
      await nobody.post( '/?m[]=customer', deleteBody( 'save_customer', 'customer_id', customerId ) );
    }, 'a customer credit, type or delete' );
  } );

  test( 'customer types are settings', async () => {
    await expectNoWrites( WRITES, async () => {
      await nobody.post( '/?m[]=customer', { _process: 'save_customer_type', customer_type_id: 'new', type_name: 'Nope', type_name_plural: 'Nopes' } );
      await nobody.post( '/?m[]=customer', deleteBody( 'save_customer_type', 'customer_type_id', '1' ) );
    }, 'saving a customer type' );
  } );

  test( 'a customer\'s address can only be changed by someone who may edit the customer', async () => {
    const address = await anAddress( admin );
    expect( address, 'the seed customer should have an address' ).toBeTruthy();
    await expectNoWrites( WRITES, async () => {
      await nobody.post( '/?m[]=address', { _process: 'save_from_popup', 'address[x][address_id]': String( address ), 'address[x][line_1]': 'Changed Without Permission' } );
    }, 'saving an address' );
  } );

  test( 'a login with no contact or user permissions cannot create or change either', async () => {
    await expectNoWrites( WRITES, async () => {
      await nobody.post( '/?m[]=user', { _process: 'save_user', user_id: contactId, customer_id: customerId, name: 'Changed Contact' } );
      await nobody.post( '/?m[]=user', { _process: 'save_user', user_id: 'new', customer_id: customerId, name: 'New Contact', email: 'nope-contact@example.com' } );
      await nobody.post( '/?m[]=user', { _process: 'save_user', user_id: 'new', name: 'New Staff', email: 'nope-staff@example.com', password_new: 'Nope-staff-9142!' } );
      await nobody.post( '/?m[]=user', deleteBody( 'save_user', 'user_id', contactId, 'butt_del_contact' ) );
    }, 'saving a contact or user' );
  } );

  test( 'a login that may edit users cannot give itself a role', async ( { browser } ) => {
    // "Users" under Config is what the staff user page asks for. Choosing the role is a
    // separate permission, "Users Permissions", which this login does not hold.
    const staffEditor = await loginWith( admin, browser, 'Users edit', [
      { category: 'Config', name: 'Users', actions: [ 'view', 'edit', 'create' ] },
    ] );
    try {
      const before = await checksumTables( [ 'user_role' ] );
      const res = await staffEditor.post( '/?m[]=user', {
        _process: 'save_user', user_id: staffEditor.userId, name: 'Restricted Users edit', role_id: '1',
      } );
      expect( res.status, 'the save itself is allowed' ).toBe( 302 );
      expect( ( await checksumTables( [ 'user_role' ] ) ).user_role, 'the role was changed' ).toBe( before.user_role );
    } finally {
      await staffEditor.cleanup();
    }
  } );

  test( 'a role that holds the permissions can still save a customer', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Customers edit', [
      { category: 'Customer', name: 'Customers', actions: [ 'view', 'edit', 'create' ] },
    ] );
    let createdId = '';
    try {
      const name = 'Saved With Permission ' + Date.now();
      const created = await editor.post( '/?m[]=customer', { _process: 'save_customer', customer_id: 'new', customer_name: name } );
      expect( created.status ).toBe( 302 );
      createdId = ( created.location.match( /customer_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating a customer should redirect to it' ).toBeTruthy();

      const renamed = await editor.post( '/?m[]=customer', { _process: 'save_customer', customer_id: createdId, customer_name: name + ' renamed' } );
      expect( renamed.status ).toBe( 302 );
      await admin.goto( '/?m[]=customer&p[]=customer_admin_edit&customer_id=' + createdId );
      await expect( admin.locator( 'input[name="customer_name"]' ) ).toHaveValue( name + ' renamed' );
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=customer', 'save_customer', 'customer_id', createdId );
      }
    }
  } );

  test( 'and cannot change a customer its data access hides', async ( { browser } ) => {
    // Allowed to edit customers, but only those it is assigned to as staff - and it is
    // assigned to none. The form would not list the seed customer for it.
    const limited = await loginWith( admin, browser, 'Customers limited', [
      { category: 'Customer', name: 'Customers', actions: [ 'view', 'edit', 'create' ] },
    ], [ 'Only customers I am assigned to as a staff member' ] );
    try {
      await expectNoWrites( WRITES, async () => {
        await limited.post( '/?m[]=customer', { _process: 'save_customer', customer_id: customerId, customer_name: 'Renamed Out Of Reach' } );
      }, 'renaming a customer out of reach' );
    } finally {
      await limited.cleanup();
    }
  } );
} );
