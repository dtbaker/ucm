import { test, expect, Page } from '@playwright/test';
import { aCustomer, anInvoice, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';

/**
 * Saving invoices takes the permission the invoice page asks for: "Create" for a new one,
 * "Edit" for one that exists. Posts straight to the save endpoint as a login that may only
 * sign in, and as one that may only view invoices, and checks the database did not move.
 */

const WRITES = [ 'invoice', 'invoice_item', 'invoice_payment', 'invoice_tax', 'customer', 'job' ];

test.describe( 'Invoice saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let customerId: string;
  let invoiceId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    customerId = await aCustomer( admin );
    invoiceId = await anInvoice( admin );
    nobody = await loginWith( admin, browser, 'Invoices none' );
    viewer = await loginWith( admin, browser, 'Invoices view', [
      { category: 'Invoice', name: 'Invoices', actions: [ 'view' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    await admin?.close();
  } );

  for ( const who of [ 'nobody', 'viewer' ] as const ) {
    test( `${ who } cannot create, change or delete an invoice`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: invoiceId, name: 'Renamed Without Permission' } );
        await r.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: 'new', name: 'Created Without Permission', customer_id: customerId } );
        await r.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: invoiceId, butt_generate_credit: '1' } );
        await r.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: invoiceId, generate_renewal: '1' } );
        await r.post( '/?m[]=invoice', { _process: 'assign_credit_to_customer', invoice_id: invoiceId } );
        await r.post( '/?m[]=invoice', deleteBody( 'save_invoice', 'invoice_id', invoiceId ) );
      }, 'saving an invoice' );
    } );
  }

  test( 'a role that may create but not edit cannot change an existing invoice', async ( { browser } ) => {
    const creator = await loginWith( admin, browser, 'Invoices create', [
      { category: 'Invoice', name: 'Invoices', actions: [ 'view', 'create' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
    try {
      await expectNoWrites( WRITES, async () => {
        await creator.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: invoiceId, name: 'Renamed Without Permission' } );
      }, 'editing an invoice with only create' );
    } finally {
      await creator.cleanup();
    }
  } );

  test( 'a role that holds the permissions can still save an invoice', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Invoices edit', [
      { category: 'Invoice', name: 'Invoices', actions: [ 'view', 'edit', 'create' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
    let createdId = '';
    try {
      const created = await editor.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: 'new', name: 'Saved With Permission', customer_id: customerId, _redirect: '' } );
      expect( created.status ).toBe( 302 );
      createdId = ( created.location.match( /invoice_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating an invoice should redirect to it' ).toBeTruthy();

      const renamed = await editor.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: createdId, name: 'Saved With Permission renamed', customer_id: customerId, _redirect: '' } );
      expect( renamed.status ).toBe( 302 );
      await admin.goto( '/?m[]=invoice&p[]=invoice_admin&invoice_id=' + createdId );
      await expect( admin.locator( 'input[name="name"]' ).first() ).toHaveValue( 'Saved With Permission renamed' );
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=invoice', 'save_invoice', 'invoice_id', createdId );
      }
    }
  } );

  test( 'and cannot put an invoice on a customer its data access hides', async ( { browser } ) => {
    const limited = await loginWith( admin, browser, 'Invoices limited', [
      { category: 'Invoice', name: 'Invoices', actions: [ 'view', 'edit', 'create' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ], [ 'Only customers I am assigned to as a staff member' ] );
    try {
      await expectNoWrites( WRITES, async () => {
        await limited.post( '/?m[]=invoice', { _process: 'save_invoice', invoice_id: 'new', name: 'Out Of Reach', customer_id: customerId } );
      }, 'invoicing a customer out of reach' );
    } finally {
      await limited.cleanup();
    }
  } );
} );
