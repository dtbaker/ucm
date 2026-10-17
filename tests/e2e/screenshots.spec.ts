import { test, expect } from '@playwright/test';
import { login } from './seed';

/**
 * Capture the screenshots the documentation needs.
 *
 * Not run as part of the suite - it is tagged @screenshots and the config leaves it out,
 * because taking pictures is slow and proves nothing about whether the software works.
 * Run it when the documentation needs refreshing:
 *
 *   npx playwright test tests/e2e/screenshots.spec.ts --grep @screenshots
 *
 * Images land in build/screenshots/, which is not committed. The documentation lives in
 * the ucm-web repository, so copy the ones you want into its site/ and keep the names: the
 * articles refer to them.
 *
 * Envato's rejection-factors article names pictorial guides specifically, and the pages
 * below are the ones a buyer actually follows step by step.
 *
 * IMPORTANT: point this at a clean installation, not the development one. The development
 * database has months of test records in it - the menu alone carries a dozen data types
 * called "CSRF Test Data Type" - and a picture of that is worse than no picture. There is
 * already a script for a clean install:
 *
 *   ./scripts/install-test.sh
 *   npx playwright test tests/e2e/install.spec.ts          # walk the wizard
 *   UCM_SCREENSHOTS=1 UCM_BASE_URL=http://localhost:8092 \
 *     npx playwright test tests/e2e/screenshots.spec.ts
 *
 * Add a customer, an invoice and a job to it first, or the screens will be empty - which
 * reads as "this software does nothing" to someone deciding whether to buy it.
 */

const SHOTS: Array<{ name: string; url: string; waitFor?: string }> = [
  { name: 'dashboard', url: '/' },
  { name: 'customers', url: '/?m[]=customer&p[]=customer_admin_list&customer_type_id=0' },
  { name: 'customer-edit', url: '/?m[]=customer&p[]=customer_admin_edit&customer_id=new' },
  { name: 'invoices', url: '/?m[]=invoice&p[]=invoice_admin' },
  { name: 'quotes', url: '/?m[]=quote&p[]=quote_admin' },
  { name: 'jobs', url: '/?m[]=job&p[]=job_admin' },
  { name: 'tickets', url: '/?m[]=ticket&p[]=ticket_admin_list' },
  { name: 'calendar', url: '/?m[]=calendar&p[]=calendar_admin' },
  { name: 'settings', url: '/?m[]=config&p[]=config_admin' },
  { name: 'payment-methods', url: '/?m[]=paymethod_stripe&p[]=stripe_settings' },
  { name: 'files', url: '/?m[]=file&p[]=file_admin_list' },
];

test.describe( 'Documentation screenshots @screenshots', () => {
  for ( const shot of SHOTS ) {
    test( `capture ${ shot.name }`, async ( { page } ) => {
      await login( page );
      await page.setViewportSize( { width: 1280, height: 900 } );
      await page.goto( shot.url );
      await page.waitForLoadState( 'networkidle' ).catch( () => undefined );

      // The debug panel is on in the development container and would be in every picture.
      await page.evaluate( () => {
        document.querySelectorAll( '#debug, .debug, #ucm_debug' ).forEach( ( el ) => el.remove() );
        document.querySelectorAll( 'a' ).forEach( ( a ) => {
          if ( ( a.textContent || '' ).trim().startsWith( 'View Debug' ) ) {
            a.remove();
          }
        } );
      } );

      await page.screenshot( {
        path: `build/screenshots/${ shot.name }.png`,
        fullPage: false,
      } );

      // A blank page is a worse screenshot than none at all, and would go unnoticed.
      const text = ( await page.locator( 'body' ).innerText() ).trim();
      expect( text.length, `${ shot.name } should not be an empty page` ).toBeGreaterThan( 40 );
    } );
  }
} );
