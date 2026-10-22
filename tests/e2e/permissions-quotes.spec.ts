import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';

/**
 * Saving quotes takes the permission the quote page asks for. Only "Create" was ever asked,
 * inside save_quote(); a change to a quote that already exists asked for nothing.
 */

const WRITES = [ 'quote', 'quote_task', 'quote_tax', 'customer', 'website' ];

test.describe( 'Quote saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let customerId: string;
  let quoteId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    customerId = await aCustomer( admin );
    quoteId = await createRecord( admin, '/?m[]=quote', {
      _process: 'save_quote', quote_id: 'new', name: 'Permission Quote ' + Date.now(), customer_id: customerId, _redirect: '',
    }, 'quote_id' );
    nobody = await loginWith( admin, browser, 'Quotes none' );
    viewer = await loginWith( admin, browser, 'Quotes view', [
      { category: 'Quote', name: 'Quotes', actions: [ 'view' ] },
      { category: 'Quote', name: 'Quote Tasks', actions: [ 'view' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    if ( quoteId ) {
      await deleteRecord( admin, '/?m[]=quote', 'save_quote', 'quote_id', quoteId );
    }
    await admin?.close();
  } );

  for ( const who of [ 'nobody', 'viewer' ] as const ) {
    test( `${ who } cannot create, change or delete a quote`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=quote', { _process: 'save_quote', quote_id: quoteId, name: 'Renamed Without Permission', customer_id: customerId } );
        await r.post( '/?m[]=quote', { _process: 'save_quote', quote_id: 'new', name: 'Created Without Permission', customer_id: customerId } );
        await r.post( '/?m[]=quote', { _process: 'save_quote', quote_id: quoteId, name: 'Archive', butt_archive: '1' } );
        await r.post( '/?m[]=quote', { _process: 'save_quote', quote_id: quoteId, 'quote_task[new][description]': 'Added Without Permission', 'quote_task[new][hours]': '1' } );
        await r.post( '/?m[]=quote', deleteBody( 'save_quote', 'quote_id', quoteId ) );
      }, 'saving a quote' );
    } );
  }

  test( 'a role that holds the permissions can still save a quote', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Quotes edit', [
      { category: 'Quote', name: 'Quotes', actions: [ 'view', 'edit', 'create' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
    let createdId = '';
    try {
      const created = await editor.post( '/?m[]=quote', { _process: 'save_quote', quote_id: 'new', name: 'Saved With Permission', customer_id: customerId, _redirect: '' } );
      expect( created.status ).toBe( 302 );
      createdId = ( created.location.match( /quote_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating a quote should redirect to it' ).toBeTruthy();

      const renamed = await editor.post( '/?m[]=quote', { _process: 'save_quote', quote_id: createdId, name: 'Saved With Permission renamed', customer_id: customerId, _redirect: '' } );
      expect( renamed.status ).toBe( 302 );
      await admin.goto( '/?m[]=quote&p[]=quote_admin&quote_id=' + createdId );
      await expect( admin.locator( 'input[name="name"]' ).first() ).toHaveValue( 'Saved With Permission renamed' );
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=quote', 'save_quote', 'quote_id', createdId );
      }
    }
  } );
} );
