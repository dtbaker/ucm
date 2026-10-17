import { test, expect } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { deleteRecord, formAuthKey, login } from './seed';

/**
 * Saving a security role with every permission ticked.
 *
 * The permission grid has one control per permission per action, and
 * module_security::has_feature_access() registers a permission row the first time any
 * screen asks for one that is not in the table yet - including one per user-created data
 * type. So the grid grows as the install is used: 951 rows one morning, 1300 a few days
 * later, which is 2345 controls on the form.
 *
 * PHP parses at most max_input_vars variables out of a post - 1000 by default - and drops
 * the rest during request startup, before any of this application's code runs and with
 * nothing left for it to test. A form of one field per control therefore stopped fitting,
 * and did it silently: on a production install the role saved with whatever happened to
 * arrive and said "Role saved successfully", and on a development one the startup warning
 * printed before the session cookie went out, so the admin landed back on the login page.
 *
 * The fix posts the whole grid as a single field. These tests hold that: the first fails
 * against a form of one field per checkbox as soon as the table passes about 996 rows'
 * worth of ticked boxes, and the second covers the case where a post is truncated anyway.
 */
/**
 * Every permission checkbox on the grid, under either markup.
 *
 * The form used to give each box a name of its own, `permission[id][action]`, and now
 * gives it `data-permission` instead and posts them as one field. Both are matched so
 * this spec fails on what it is about - a role that will not save - rather than on a
 * selector that found nothing.
 */
const GRID = 'input[type="checkbox"][data-permission], input[type="checkbox"][name^="permission["]';

test.describe( 'Security role permissions', () => {
  test( 'a role saves with every permission ticked', async ( { page } ) => {
    test.setTimeout( 180_000 );
    await login( page );

    const roleName = 'Every Permission ' + Date.now();
    const form = await page.goto( '/?m[]=security&p[]=security_role&security_role_id=new' );
    expect(
      describeDiagnostics( await form!.text() ).join( '\n' ),
      'the role form printed a PHP diagnostic',
    ).toBe( '' );
    await page.locator( 'input[name="name"]' ).fill( roleName );

    // Tick every box on the grid. There are a couple of thousand of them, so they are set
    // in one pass rather than clicked: each still gets the change event the page listens
    // for, which is what collects them into the posted field.
    const offered = await page.evaluate( ( selector ) => {
      const boxes = Array.from( document.querySelectorAll< HTMLInputElement >( selector ) );
      for ( const box of boxes ) {
        box.checked = true;
        box.dispatchEvent( new Event( 'change', { bubbles: true } ) );
      }

      return boxes.length;
    }, GRID );
    expect( offered, 'the role form offered no permission checkboxes' ).toBeGreaterThan( 0 );

    // A grid smaller than max_input_vars cannot show the bug: a form of one field per
    // box still fits, so saving it proves nothing either way. That is a fact about the
    // install, not a fault in it, so this stops rather than fails.
    //
    // It used to be an assertion, back when the permission table sat at 1300 rows. Most
    // of those were the suite's own: has_feature_access() registers a row per data type,
    // the browser suites made a data type on nearly every run and never removed it, and
    // delete_data_type() did not take the permission with it even when they did. With
    // both fixed the table is ~600 rows and the grid is 981 controls - just under.
    //
    // The second test below is the one that holds the regression on any install: it
    // builds its own over-long post rather than relying on the table being big enough.
    test.skip(
      offered <= 1000,
      `this install is too small to show the bug - ${ offered } controls, under max_input_vars`,
    );

    // By name, not by class: the metis theme renders its own action bar and does not
    // carry generate_form_actions()' save_button class through. It also prints the bar
    // twice, top and bottom, so this takes the first.
    await page.locator( 'input[name="butt_save"]' ).first().click();
    await page.waitForLoadState( 'load' );

    // A truncated post used to land here: no session, so the login form instead of the role.
    await expect(
      page.locator( 'input[name="password"]' ),
      'saving the role bounced the admin to the login page',
    ).toHaveCount( 0 );

    const savedId = new URL( page.url() ).searchParams.get( 'security_role_id' );
    expect( savedId, 'saving the role did not redirect to it' ).toMatch( /^\d+$/ );

    try {
      await page.goto( '/?m[]=security&p[]=security_role&security_role_id=' + savedId );
      await expect( page.locator( 'input[name="name"]' ) ).toHaveValue( roleName );

      const saved = await page.evaluate( ( selector ) => ( {
        total: document.querySelectorAll( selector ).length,
        ticked: document.querySelectorAll(
          selector.split( ',' ).map( one => one.trim() + ':checked' ).join( ',' ),
        ).length,
      } ), GRID );
      expect(
        saved.ticked,
        `the role came back with ${ saved.ticked } of ${ saved.total } permissions`,
      ).toBe( saved.total );
    } finally {
      // Roles are never found again by a later run and every customer and invoice screen
      // pays for the ones left behind, so this one goes away again.
      await deleteRecord( page, '/?m[]=security', 'save_security_role', 'security_role_id', String( savedId ) );
    }
  } );

  test( 'a post that exceeds max_input_vars is refused, not half saved', async ( { page } ) => {
    await login( page );

    // The old field names, enough of them to go past the limit. PHP keeps the first
    // thousand and discards the rest; nothing in $_POST says so afterwards.
    const body: Record< string, string > = {
      _process: 'save_security_role',
      security_role_id: 'new',
      name: 'Truncated ' + Date.now(),
      form_auth_key: await formAuthKey( page ),
    };
    for ( let id = 1; id <= 1500; id++ ) {
      body[ `permission[${ id }][view]` ] = '1';
    }

    const response = await page.request.post( '/?m[]=security', {
      form: body,
      failOnStatusCode: false,
      maxRedirects: 0,
    } );

    expect(
      response.status(),
      'an over-long post should be refused outright, not saved with whatever fitted',
    ).toBe( 413 );
    expect( await response.text() ).toContain( 'Nothing has been changed' );
  } );
} );
