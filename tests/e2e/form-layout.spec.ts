import { test, expect } from '@playwright/test';
import { activeTheme, aCustomer, login } from './seed';

/**
 * How a fieldset is laid out, per theme. Run against whichever theme the install has
 * selected (Settings > Theme); a theme with no case here skips rather than failing.
 *
 *  - metis wraps each fieldset in a .box, with the heading in its <header>.
 *  - shadcn ("Modern") uses module_form's own markup and turns the form table into a
 *    label/field grid with CSS, so the check is on computed style, not on markup.
 */
test.describe( 'Form layout by theme', () => {
  test( 'a customer form lays its rows out the way the selected theme says', async ( { page } ) => {
    await login( page );
    const theme = await activeTheme( page );
    test.skip( theme !== 'metis' && theme !== 'shadcn', `no layout expectations for theme ${ theme }` );

    const customerId = await aCustomer( page );
    await page.goto( `/?m[]=customer&p[]=customer_admin_edit&customer_id=${ customerId }` );
    const table = page.locator( 'table.tableclass_form' ).first();
    await expect( table ).toBeVisible();

    if ( theme === 'metis' ) {
      await expect( page.locator( '.box > .body > table.tableclass_form' ).first() ).toBeVisible();
      return;
    }

    // shadcn: the table is a block card, each row a grid, label and field in separate cells.
    const shape = await table.evaluate( el => {
      const row = el.querySelector( 'tbody > tr' ) as HTMLElement;
      const th = row.querySelector( 'th' ) as HTMLElement;
      const td = row.querySelector( 'td' ) as HTMLElement;
      const t = th.getBoundingClientRect();
      const d = td.getBoundingClientRect();

      return {
        table: getComputedStyle( el ).display,
        row: getComputedStyle( row ).display,
        // field is beside the label (wide) or under it (narrow column), never overlapping it
        separate: d.left >= t.right - 1 || d.top >= t.bottom - 1,
        fieldHasWidth: d.width > 80,
      };
    } );
    expect( shape.table ).toBe( 'block' );
    expect( shape.row ).toBe( 'grid' );
    expect( shape.separate, 'label and field overlap' ).toBe( true );
    expect( shape.fieldHasWidth, 'the field column collapsed' ).toBe( true );
  } );

  test( 'the Modern theme switches between light and dark and remembers it', async ( { page } ) => {
    await login( page );
    test.skip( ( await activeTheme( page ) ) !== 'shadcn', 'only the Modern theme has a colour mode toggle' );

    const root = page.locator( 'html' );
    const before = await root.getAttribute( 'data-theme' );
    await page.locator( '#mode-toggle' ).click();
    const after = await root.getAttribute( 'data-theme' );
    expect( after ).not.toBe( before );
    await page.reload();
    await expect( root ).toHaveAttribute( 'data-theme', after as string );
    await page.locator( '#mode-toggle' ).click(); // put it back
  } );
} );
