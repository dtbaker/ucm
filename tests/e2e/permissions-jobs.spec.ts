import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';
import { idsMatching } from './db';

/**
 * Saving jobs and their tasks takes the permission the job page asks for.
 *
 * Posts straight to the save endpoints, with a real form token, as a login whose role
 * holds nothing but the right to sign in, and as one that may only view jobs. The database
 * must not move. See permissions-customers.spec.ts for the shape of these.
 */

const WRITES = [ 'job', 'task', 'task_log', 'job_tax', 'invoice', 'invoice_item', 'extra', 'customer' ];

test.describe( 'Job and task saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let customerId: string;
  let jobId: string;
  let taskId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    customerId = await aCustomer( admin );
    jobId = await createRecord( admin, '/?m[]=job', {
      _process: 'save_job', job_id: 'new', name: 'Permission Job ' + Date.now(), customer_id: customerId, _redirect: '',
    }, 'job_id' );
    // Opening a job for the first time refreshes figures stored on it, which is a write
    // of its own and would be mistaken for the one under test.
    await admin.goto( '/?m[]=job&p[]=job_admin&job_id=' + jobId );
    // one task on it, to change and to remove
    await admin.request.post( '/?m[]=job', {
      form: {
        _process: 'save_job_tasks_ajax',
        job_id: jobId,
        'job_task[new][description]': 'Permission Task',
        'job_task[new][hours]': '1',
        form_auth_key: await ( async () => { await admin.goto( '/?m[]=customer&p[]=customer_admin' ); return String( await admin.evaluate( () => ( window as any ).ucm.form_auth_key ) ); } )(),
      },
      failOnStatusCode: false,
    } );
    [ taskId ] = await idsMatching( { table: 'task', column: 'description', like: 'Permission Task' }, 'task_id', 1 );
    expect( taskId, 'the task was not created' ).toBeTruthy();

    nobody = await loginWith( admin, browser, 'Jobs none' );
    viewer = await loginWith( admin, browser, 'Jobs view', [
      { category: 'Job', name: 'Jobs', actions: [ 'view' ] },
      { category: 'Job', name: 'Job Tasks', actions: [ 'view' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    if ( jobId ) {
      await deleteRecord( admin, '/?m[]=job', 'save_job', 'job_id', jobId );
    }
    await admin?.close();
  } );

  for ( const who of [ 'nobody', 'viewer' ] as const ) {
    test( `${ who } cannot create, change or delete a job`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=job', { _process: 'save_job', job_id: jobId, name: 'Renamed Without Permission', customer_id: customerId } );
        await r.post( '/?m[]=job', { _process: 'save_job', job_id: 'new', name: 'Created Without Permission', customer_id: customerId } );
        await r.post( '/?m[]=job', { _process: 'save_job', job_id: jobId, name: 'Renew', generate_renewal: '1' } );
        await r.post( '/?m[]=job', { _process: 'save_job', job_id: jobId, name: 'Archive', butt_archive: '1' } );
        await r.post( '/?m[]=job', deleteBody( 'save_job', 'job_id', jobId ) );
      }, 'saving a job', () => r.post( '/?m[]=job', { _process: 'save_job_tasks_ajax', job_id: jobId } ) );
    } );

    test( `${ who } cannot add, change or remove a task`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=job', { _process: 'save_job_tasks_ajax', job_id: jobId, 'job_task[new][description]': 'Added Without Permission', 'job_task[new][hours]': '1' } );
        await r.post( '/?m[]=job', { _process: 'save_job_tasks_ajax', job_id: jobId, [ `job_task[${ taskId }][description]` ]: 'Edited Without Permission' } );
        await r.post( '/?m[]=job', { _process: 'save_job_tasks_ajax', job_id: jobId, [ `job_task[${ taskId }][description]` ]: '!~!' } );
        await r.post( '/?m[]=job', { _process: 'save_job_task', job_id: jobId, task_id: taskId, description: 'Edited Without Permission' } );
        await r.post( '/?m[]=job', { _process: 'ajax_task', job_id: jobId, task_id: taskId, toggle_completed: '1' } );
        await r.post( '/?m[]=job', { _process: 'ajax_task', job_id: jobId, task_id: taskId, update_task_order: '1', [ `task_order[${ taskId }]` ]: '9' } );
      }, 'saving a task', () => r.post( '/?m[]=job', { _process: 'save_job_tasks_ajax', job_id: jobId } ) );
    } );
  }

  test( 'a role that holds the permissions can still save a job and its tasks', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Jobs edit', [
      { category: 'Job', name: 'Jobs', actions: [ 'view', 'edit', 'create' ] },
      { category: 'Job', name: 'Job Tasks', actions: [ 'view', 'edit', 'create' ] },
      { category: 'Customer', name: 'Customers', actions: [ 'view' ] },
    ] );
    let createdId = '';
    try {
      const name = 'Saved With Permission ' + Date.now();
      const created = await editor.post( '/?m[]=job', { _process: 'save_job', job_id: 'new', name, customer_id: customerId } );
      createdId = ( created.location.match( /job_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating a job should redirect to it' ).toBeTruthy();

      const renamed = await editor.post( '/?m[]=job', { _process: 'save_job', job_id: createdId, name: name + ' renamed', customer_id: customerId } );
      expect( renamed.status ).toBe( 302 );
      await admin.goto( '/?m[]=job&p[]=job_admin&job_id=' + createdId );
      await expect( admin.locator( 'input[name="name"]' ).first() ).toHaveValue( name + ' renamed' );

      await editor.post( '/?m[]=job', { _process: 'save_job_tasks_ajax', job_id: createdId, 'job_task[new][description]': 'Task With Permission', 'job_task[new][hours]': '1' } );
      expect( ( await idsMatching( { table: 'task', column: 'description', like: 'Task With Permission' }, 'task_id', 1 ) ).length, 'the task was not saved' ).toBe( 1 );
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=job', 'save_job', 'job_id', createdId );
      }
    }
  } );
} );
