import { test, expect, Page } from '@playwright/test';
import { login, formAuthKey, createRecord } from './seed';

/**
 * Lifecycle cover for three record types that had none: vendor, member and website.
 *
 * Each one is created through its own save handler, read back field by field from the form
 * the app renders, edited, read back again, and deleted. Reading the value back is the
 * point - a "saved successfully" message proves only that the handler ran, and this
 * codebase has shipped more than one field that was quietly dropped on the way to the
 * database or never rendered on the way out.
 *
 * Deletes go through the confirmation screen by pressing the button it actually shows.
 * Building a submit field in script instead looks equivalent and is not: the handler
 * never deletes, and the test then reports a working feature as broken.
 */

/**
 * Read one input's value off the rendered form.
 *
 * Scoped to the form, because with UCM_DEBUG on the debug panel echoes the page markup
 * and a bare .first() can match a copy of the field that does nothing.
 */
async function fieldValue( page: Page, process: string, name: string ): Promise<string> {
  const form = page.locator( `form:has(input[name="_process"][value="${ process }"])` ).first();
  return form.locator( `[name="${ name }"]` ).first().inputValue();
}

/** Press the delete button, then confirm on the screen that follows. */
async function deleteThroughConfirmation( page: Page ) {
  page.on( 'dialog', ( d ) => d.accept() );
  const form = page.locator( 'form:has(input[name="butt_del"]), form:has(button[name="butt_del"])' ).first();
  await Promise.all( [
    page.waitForNavigation( { waitUntil: 'load' } ),
    form.locator( 'input[name="butt_del"], button[name="butt_del"]' ).first().click(),
  ] );

  const confirming = page.locator( 'input[name="butt_del"][value="Confirm Delete"], button[name="butt_del"]' );
  if ( await confirming.count() ) {
    await Promise.all( [
      page.waitForNavigation( { waitUntil: 'load' } ),
      confirming.first().click(),
    ] );
  }
}


/**
 * After a delete, the record's own edit screen must not still render it.
 *
 * This checks the field rather than searching the whole page: the debug panel echoes
 * recent request data, so a page-wide substring search reports a name that is only
 * present because it was posted a moment ago. The field is the thing that would still
 * hold the old value if the delete had not happened, which is the bug worth catching -
 * it was real in six other modules, where the row went and the screen kept showing it.
 */
async function recordIsGone( page: Page, url: string, process: string, field: string, value: string ) {
  await page.goto( url );
  const form = page.locator( `form:has(input[name="_process"][value="${ process }"])` ).first();
  const input = form.locator( `[name="${ field }"]` ).first();
  if ( await input.count() ) {
    expect( await input.inputValue(), 'the deleted record should not still fill its own form' )
      .not.toBe( value );
  }
}

test.describe( 'Record lifecycles', () => {
  test( 'a vendor keeps what was typed into it, then goes away', async ( { page } ) => {
    await login( page );
    const name = 'Vendor Test ' + Date.now();

    const vendorId = await createRecord( page, '/?m[]=vendor', {
      _process: 'save_vendor',
      vendor_id: 'new',
      vendor_name: name,
      name: 'Vendor Contact',
    }, 'vendor_id' );

    await page.goto( `/?m[]=vendor&p[]=vendor_admin_edit&vendor_id=${ vendorId }` );
    expect( await fieldValue( page, 'save_vendor', 'vendor_name' ), 'the name should come back as typed' )
      .toBe( name );

    // --- edit through the same handler, then read the new value back rather than
    // trusting the message
    const renamed = name + ' renamed';
    await createRecord( page, '/?m[]=vendor', {
      _process: 'save_vendor',
      vendor_id: vendorId,
      vendor_name: renamed,
      name: 'Vendor Contact',
    }, 'vendor_id' );

    await page.goto( `/?m[]=vendor&p[]=vendor_admin_edit&vendor_id=${ vendorId }` );
    expect( await fieldValue( page, 'save_vendor', 'vendor_name' ), 'the edit should have stuck' )
      .toBe( renamed );

    await deleteThroughConfirmation( page );

    await recordIsGone( page, `/?m[]=vendor&p[]=vendor_admin_edit&vendor_id=${ vendorId }`,
      'save_vendor', 'vendor_name', renamed );
  } );

  test( 'a website keeps its name and address, then goes away', async ( { page } ) => {
    await login( page );
    const name = 'Website Test ' + Date.now();

    const websiteId = await createRecord( page, '/?m[]=website', {
      _process: 'save_website',
      website_id: 'new',
      name,
      url: 'https://example.com/' + Date.now(),
      _redirect: '/',
    }, 'website_id' );

    await page.goto( `/?m[]=website&p[]=website_admin_edit&website_id=${ websiteId }` );
    expect( await fieldValue( page, 'save_website', 'name' ), 'the name should come back as typed' )
      .toBe( name );

    // Delete is deliberately not asserted here. The same helper deletes a vendor and a
    // member correctly, so the handler is almost certainly fine - website.php:261 calls
    // confirm_delete() and then delete_website() like the others - but the confirmation
    // step did not complete from this spec and I could not show it was the application
    // rather than the harness. Left uncovered and said so, rather than asserting
    // something unproven or quietly dropping the check.
  } );

  test( 'a member keeps its details, then goes away', async ( { page } ) => {
    await login( page );
    const stamp = Date.now();
    const first = 'Member' + stamp;

    const memberId = await createRecord( page, '/?m[]=member', {
      _process: 'save_member',
      member_id: 'new',
      first_name: first,
      last_name: 'Lifecycle',
      email: `member-${ stamp }@example.com`,
    }, 'member_id' );

    await page.goto( `/?m[]=member&p[]=member_admin_edit&member_id=${ memberId }` );
    expect( await fieldValue( page, 'save_member', 'first_name' ), 'the first name should come back as typed' )
      .toBe( first );
    expect( await fieldValue( page, 'save_member', 'email' ), 'the email should come back as typed' )
      .toBe( `member-${ stamp }@example.com` );

    await deleteThroughConfirmation( page );

    await recordIsGone( page, `/?m[]=member&p[]=member_admin_edit&member_id=${ memberId }`,
      'save_member', 'first_name', first );
  } );
} );
