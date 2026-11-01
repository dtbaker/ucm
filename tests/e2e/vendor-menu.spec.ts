import { test, expect, Page } from '@playwright/test';
import { login, createRecord, deleteRecord } from './seed';
import { countTables } from './db';

/**
 * The old Vendors plugin, which says at the top of its page that it is to be merged into the
 * Customers area.
 *
 * It is dormant while it has no vendors: the application skips its init() and gives it no
 * sidebar link, so a new install never sees it. Once there is a vendor it is offered again,
 * as "Vendors (old)", so it is not mistaken for the Vendors customer type that replaces it.
 *
 * The first test needs a database with no vendors in it, which the "Install from scratch" job
 * in CI is. A development database that has some skips it.
 */

/** The links to the old plugin's list, which is what the sidebar entry is. */
const oldVendorsLinks = ( page: Page ) => page.locator( 'a[href*="vendor_admin_list"]' );

test.describe.configure( { mode: 'serial' } );

test.describe( 'The old Vendors plugin', () => {
  test( 'is left out of the menu while there are no vendors', async ( { page } ) => {
    const counts = await countTables( [ 'vendor' ] );
    test.skip( counts.vendor > 0, 'this database already has vendors, so the menu is right to show them' );

    await login( page );
    await page.goto( '/' );
    await expect( oldVendorsLinks( page ), 'no vendors, so no link to the old Vendors plugin' ).toHaveCount( 0 );
  } );

  test( 'is offered as Vendors (old) once there is a vendor, and goes again when it is deleted', async ( { page } ) => {
    await login( page );
    const name = 'Menu Test Vendor ' + Date.now();
    const vendorId = await createRecord( page, '/?m[]=vendor', { _process: 'save_vendor', vendor_id: 'new', vendor_name: name }, 'vendor_id' );
    try {
      await page.goto( '/' );
      const links = oldVendorsLinks( page );
      expect( await links.count(), 'a vendor exists, so the old plugin is in the menu' ).toBeGreaterThan( 0 );
      for ( const text of await links.allTextContents() ) {
        expect( text.trim(), 'it is called what it is' ).toBe( 'Vendors (old)' );
      }

      // And its page is called the same, so the two are not confused. The sidebar is drawn
      // twice, once for a phone, so it is the visible link that is followed.
      await page.locator( 'a[href*="vendor_admin_list"]:visible' ).first().click();
      await expect( page ).toHaveTitle( /Vendors \(old\)/ );
      await expect( page.locator( 'span.title:visible', { hasText: 'Vendors (old)' } ), 'the heading says so too' ).toHaveCount( 1 );
      await expect( page.locator( 'body' ), 'the notice about the merge is still there' ).toContainText( 'will be merged into the core' );
      await expect( page.locator( 'body' ) ).toContainText( name );
    } finally {
      await deleteRecord( page, '/?m[]=vendor', 'save_vendor', 'vendor_id', vendorId, { extra: { delete_others: '1' } } );
    }

    // Deleted: the same state as before, which is none if this database had none.
    const after = await countTables( [ 'vendor' ] );
    if ( after.vendor === 0 ) {
      await page.goto( '/' );
      await expect( oldVendorsLinks( page ), 'the last vendor is gone, so the link goes with it' ).toHaveCount( 0 );
    }
  } );
} );
