import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';

/**
 * Saving tickets takes the permission the ticket page asks for. Staff hold "edit" and may
 * do anything to a ticket; a customer replying on their own ticket holds "create" only.
 * The delete button, the canned replies and the bulk actions on the ticket list asked for
 * nothing.
 */

const WRITES = [ 'ticket', 'ticket_message', 'ticket_data', 'ticket_type', 'ticket_data_key', 'ticket_account', 'extra' ];

test.describe( 'Ticket saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let customerId: string;
  let ticketId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    customerId = await aCustomer( admin );
    ticketId = await createRecord( admin, '/?m[]=ticket', {
      _process: 'save_ticket', ticket_id: 'new', subject: 'Permission Ticket ' + Date.now(), customer_id: customerId, _redirect: '',
    }, 'ticket_id' );
    nobody = await loginWith( admin, browser, 'Tickets none' );
    viewer = await loginWith( admin, browser, 'Tickets view', [
      { category: 'Ticket', name: 'Tickets', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    if ( ticketId ) {
      await deleteRecord( admin, '/?m[]=ticket', 'save_ticket', 'ticket_id', ticketId );
    }
    await admin?.close();
  } );

  for ( const who of [ 'nobody', 'viewer' ] as const ) {
    test( `${ who } cannot create, change or delete a ticket`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=ticket', { _process: 'save_ticket', ticket_id: ticketId, subject: 'Renamed Without Permission', status_id: '6' } );
        await r.post( '/?m[]=ticket', { _process: 'save_ticket', ticket_id: 'new', subject: 'Created Without Permission', customer_id: customerId } );
        await r.post( '/?m[]=ticket', { _process: 'save_ticket', ticket_id: ticketId, new_ticket_message: 'Posted Without Permission' } );
        await r.post( '/?m[]=ticket', deleteBody( 'save_ticket', 'ticket_id', ticketId ) );
      }, 'saving a ticket' );
    } );

    test( `${ who } cannot save canned replies or ticket settings`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=ticket', { _process: 'save_saved_response', saved_response_id: 'Canned Without Permission', value: 'text' } );
        await r.post( '/?m[]=ticket', { _process: 'save_ticket_type', ticket_type_id: 'new', name: 'Nope' } );
        await r.post( '/?m[]=ticket', { _process: 'save_ticket_data_key', ticket_data_key_id: 'new', key: 'Nope' } );
        await r.post( '/?m[]=ticket', { _process: 'save_ticket_account', ticket_account_id: 'new', name: 'Nope' } );
      }, 'saving ticket settings' );
    } );
  }

  test( 'a view-only login cannot use the ticket list\'s bulk actions', async () => {
    // The list page loads for it, and the bulk handlers run as it renders.
    await expectNoWrites( WRITES, async () => {
      for ( const bulk of [
        { 'bulk_action[status_resolved]': 'yes', bulk_change_status_id: '5' },
        { 'bulk_action[change_assigned]': 'yes', bulk_change_staff_id: viewer.userId },
        { 'bulk_action[unread]': 'yes' },
        { 'bulk_action[read]': 'yes' },
      ] ) {
        await viewer.post( '/?m[]=ticket&p[]=ticket_admin_list', { bulk_action_go: 'yes', [ `bulk_operation[${ ticketId }]` ]: 'yes', ...bulk } );
      }
    }, 'a bulk ticket action', () => viewer.visitor.goto( '/?m[]=ticket&p[]=ticket_admin_list' ) );
  } );

  test( 'a role that holds the permissions can still save a ticket', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Tickets edit', [
      { category: 'Ticket', name: 'Tickets', actions: [ 'view', 'edit', 'create' ] },
    ] );
    let createdId = '';
    try {
      const created = await editor.post( '/?m[]=ticket', { _process: 'save_ticket', ticket_id: 'new', subject: 'Saved With Permission', customer_id: customerId } );
      expect( created.status ).toBe( 302 );
      createdId = ( created.location.match( /ticket_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating a ticket should redirect to it' ).toBeTruthy();
      const renamed = await editor.post( '/?m[]=ticket', { _process: 'save_ticket', ticket_id: createdId, subject: 'Saved With Permission renamed' } );
      expect( renamed.status ).toBe( 302 );
      await admin.goto( '/?m[]=ticket&p[]=ticket_admin&ticket_id=' + createdId );
      expect( await admin.content() ).toContain( 'Saved With Permission renamed' );
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=ticket', 'save_ticket', 'ticket_id', createdId );
      }
    }
  } );

  test( 'and a customer who may only create tickets can still reply to their own', async ( { browser } ) => {
    const customer = await loginWith( admin, browser, 'Tickets create', [
      { category: 'Ticket', name: 'Tickets', actions: [ 'view', 'create' ] },
    ] );
    try {
      const reply = await customer.post( '/?m[]=ticket', { _process: 'save_ticket', ticket_id: ticketId, new_ticket_message: 'A reply with only create' } );
      expect( reply.status, 'the reply should be accepted and redirect back to the ticket' ).toBe( 302 );
      expect( reply.location ).toContain( 'ticket_id=' + ticketId );
    } finally {
      await customer.cleanup();
    }
  } );
} );
