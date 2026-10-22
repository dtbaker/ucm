import { test, expect, Page } from '@playwright/test';
import { createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';

/**
 * Saving vendors takes the permission the vendor page asks for: "Create" for a new vendor,
 * "Edit" for one that exists.
 */

const WRITES = [ 'vendor', 'user', 'address', 'user_vendor_rel' ];

test.describe( 'Vendor saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let vendorId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    vendorId = await createRecord( admin, '/?m[]=vendor', {
      _process: 'save_vendor', vendor_id: 'new', vendor_name: 'Permission Vendor ' + Date.now(), _redirect: '',
    }, 'vendor_id' );
    nobody = await loginWith( admin, browser, 'Vendors none' );
    viewer = await loginWith( admin, browser, 'Vendors view', [
      { category: 'Vendor', name: 'Vendors', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    if ( vendorId ) {
      await deleteRecord( admin, '/?m[]=vendor', 'save_vendor', 'vendor_id', vendorId );
    }
    await admin?.close();
  } );

  for ( const who of [ 'nobody', 'viewer' ] as const ) {
    test( `${ who } cannot create, change or delete a vendor`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=vendor', { _process: 'save_vendor', vendor_id: vendorId, vendor_name: 'Renamed Without Permission' } );
        await r.post( '/?m[]=vendor', { _process: 'save_vendor', vendor_id: 'new', vendor_name: 'Created Without Permission' } );
        await r.post( '/?m[]=vendor', deleteBody( 'save_vendor', 'vendor_id', vendorId ) );
      }, 'saving a vendor' );
    } );
  }

  test( 'a role that holds the permissions can still save a vendor', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Vendors edit', [
      { category: 'Vendor', name: 'Vendors', actions: [ 'view', 'edit', 'create' ] },
    ] );
    let createdId = '';
    try {
      const created = await editor.post( '/?m[]=vendor', { _process: 'save_vendor', vendor_id: 'new', vendor_name: 'Saved With Permission' } );
      expect( created.status ).toBe( 302 );
      createdId = ( created.location.match( /vendor_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating a vendor should redirect to it' ).toBeTruthy();
      const renamed = await editor.post( '/?m[]=vendor', { _process: 'save_vendor', vendor_id: createdId, vendor_name: 'Saved With Permission renamed' } );
      expect( renamed.status ).toBe( 302 );
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=vendor', 'save_vendor', 'vendor_id', createdId );
      }
    }
  } );
} );
