import { test, expect, Page } from '@playwright/test';
import { DIAGNOSTIC, describeDiagnostics } from './diagnostics';
import { login } from './seed';

/**
 * The public demo, as a visitor gets it.
 *
 * demo.ultimateclientmanager.com runs this same code with UCM_DEMO_MODE=1 and the chrome
 * in custom/. Locally: ./scripts/demo-mode.sh on
 *
 * The suite normally runs with demo mode off, so these skip rather than fail when it is -
 * a run with it off should stay green, and a run with it on is what checks the demo.
 *
 * Two properties matter and neither is obvious from reading a page:
 *
 *  1. The theme switcher in the banner actually switches the theme. It is the main thing
 *     the demo is for, and it was a no-op: the chrome only applied the theme when demo
 *     mode was OFF, because save_config() refuses theme_name when it is ON.
 *  2. Nothing a visitor changes reaches the database. A shared demo where settings
 *     persist is a demo that rots - every visitor inherits the last one's mess.
 */

const BANNER = 'start theme demo code';

/** The theme a page was rendered with, read off the asset paths it asks for. */
async function renderedTheme( page: Page, url = '/' ): Promise<string> {
  const body = await ( await page.request.get( url, { failOnStatusCode: false } ) ).text();
  const match = body.match( /themes\/([a-z0-9_]+)\// );

  return match ? match[ 1 ] : 'none';
}

async function demoModeIsOn( page: Page ): Promise<boolean> {
  const body = await ( await page.request.get( '/', { failOnStatusCode: false } ) ).text();

  return body.includes( BANNER );
}

test.describe( 'The public demo', () => {
  test.beforeEach( async ( { page } ) => {
    test.skip( ! await demoModeIsOn( page ), 'demo mode is off - ./scripts/demo-mode.sh on' );
  } );

  test( 'shows its banner to a visitor and to a signed in user', async ( { page } ) => {
    const loggedOut = await ( await page.request.get( '/' ) ).text();
    expect( loggedOut, 'the login page should carry the demo banner' ).toContain( BANNER );

    await login( page );
    const loggedIn = await ( await page.request.get( '/' ) ).text();
    expect( loggedIn, 'so should a page behind the login' ).toContain( BANNER );
  } );

  test( 'switches theme for the visitor who asked', async ( { page } ) => {
    await login( page );
    const before = await renderedTheme( page );

    // Pick a theme that is installed and is not the one already showing.
    const options = [ 'pro', 'metis', 'blue', 'left' ].filter( ( t ) => t !== before );
    let switched = '';
    for ( const theme of options ) {
      await page.request.get( `/?demo_theme=${ theme }` );
      if ( await renderedTheme( page ) === theme ) {
        switched = theme;
        break;
      }
    }

    expect( switched, `the theme switcher did nothing - still rendering "${ before }"` ).not.toBe( '' );
  } );

  test( 'keeps what a visitor changes out of the database', async ( { browser, page } ) => {
    await login( page );
    const original = await renderedTheme( page );
    const other = [ 'pro', 'metis', 'blue', 'left' ].find( ( t ) => t !== original )!;

    await page.request.get( `/?demo_theme=${ other }` );
    expect( await renderedTheme( page ), 'this visitor should see the theme they picked' ).toBe( other );

    // A different browser context is a different session, so it reads the stored value.
    // If the switch had been written to the config table, this would see it too.
    const fresh = await browser.newContext();
    try {
      const other_page = await fresh.newPage();
      await login( other_page );
      expect( await renderedTheme( other_page ), 'another visitor should still get the stored theme' )
        .toBe( original );
    } finally {
      await fresh.close();
    }
  } );

  test( 'renders its pages without a PHP diagnostic', async ( { page } ) => {
    await login( page );
    const check = await ( await page.request.get( '/?m[]=customer&p[]=customer_admin&customer_id[]=1' ) ).text();
    expect( DIAGNOSTIC.test( check ), 'error reporting is on, so this suite means something' ).toBe( true );

    for ( const url of [
      '/',
      '/?m[]=customer&p[]=customer_admin_list&customer_type_id=0',
      '/?m[]=invoice&p[]=invoice_admin',
      '/?m[]=config&p[]=config_settings',
    ] ) {
      const body = await ( await page.request.get( url, { failOnStatusCode: false } ) ).text();
      expect( describeDiagnostics( body ).join( '\n' ), `${ url } printed a PHP diagnostic` ).toBe( '' );
    }
  } );
} );
