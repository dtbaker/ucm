import { test, expect, Page } from '@playwright/test';
import { aCustomer, aFile, createRecord, deleteRecord, login } from './seed';
import { deleteBody, expectNoWrites, loginWith, Restricted } from './restricted';

/**
 * Saving files takes the permission the file page asks for: "Create" for a new file,
 * "Edit" for one that exists. A comment needs "File Comments" and a file the user can see.
 */

const WRITES = [ 'file', 'file_comment', 'file_user_rel', 'file_notification' ];

test.describe( 'File saves need the matching permission', () => {
  let admin: Page;
  let nobody: Restricted;
  let viewer: Restricted;
  let fileId: string;

  test.beforeAll( async ( { browser } ) => {
    admin = await browser.newPage();
    await login( admin );
    fileId = await aFile( admin );
    nobody = await loginWith( admin, browser, 'Files none' );
    viewer = await loginWith( admin, browser, 'Files view', [
      { category: 'File', name: 'Files', actions: [ 'view' ] },
    ] );
  } );

  test.afterAll( async () => {
    await nobody?.cleanup();
    await viewer?.cleanup();
    await admin?.close();
  } );

  for ( const who of [ 'nobody', 'viewer' ] as const ) {
    test( `${ who } cannot create, change or delete a file`, async () => {
      const r = who === 'nobody' ? nobody : viewer;
      await expectNoWrites( WRITES, async () => {
        await r.post( '/?m[]=file', { _process: 'save_file', file_id: fileId, file_name: 'Renamed Without Permission', description: 'changed' } );
        await r.post( '/?m[]=file', { _process: 'save_file', file_id: 'new', file_name: 'Created Without Permission' } );
        await r.post( '/?m[]=file', { _process: 'save_file', file_id: fileId, new_comment_text: 'Commented Without Permission' } );
        await r.post( '/?m[]=file', { _process: 'save_file_popup', file_id: fileId, file_name: 'Renamed Without Permission' } );
        await r.post( '/?m[]=file', { _process: 'save_file_popup', file_id: 'new', file_name: 'Created Without Permission' } );
        await r.post( '/?m[]=file', { _process: 'delete_file_popup', file_id: fileId } );
        await r.post( '/?m[]=file', deleteBody( 'save_file', 'file_id', fileId ) );
      }, 'saving a file' );
    } );
  }

  test( 'a role that may create but not edit cannot change an existing file', async ( { browser } ) => {
    const creator = await loginWith( admin, browser, 'Files create', [
      { category: 'File', name: 'Files', actions: [ 'view', 'create' ] },
    ] );
    try {
      await expectNoWrites( WRITES, async () => {
        await creator.post( '/?m[]=file', { _process: 'save_file', file_id: fileId, file_name: 'Renamed With Only Create', description: 'changed' } );
      }, 'editing a file with only create' );
    } finally {
      await creator.cleanup();
    }
  } );

  test( 'a role that holds the permissions can still save a file', async ( { browser } ) => {
    const editor = await loginWith( admin, browser, 'Files edit', [
      { category: 'File', name: 'Files', actions: [ 'view', 'edit', 'create' ] },
    ] );
    let createdId = '';
    try {
      const created = await editor.post( '/?m[]=file', { _process: 'save_file', file_id: 'new', file_name: 'Saved With Permission' } );
      expect( created.status ).toBe( 302 );
      createdId = ( created.location.match( /file_id=(\d+)/ ) || [] )[ 1 ];
      expect( createdId, 'creating a file should redirect to it' ).toBeTruthy();
    } finally {
      await editor.cleanup();
      if ( createdId ) {
        await deleteRecord( admin, '/?m[]=file', 'save_file', 'file_id', createdId );
      }
    }
  } );
} );
