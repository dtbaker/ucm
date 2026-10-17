import { test, expect, Page } from '@playwright/test';
import { DIAGNOSTIC, describeDiagnostics } from './diagnostics';
import { expectFieldsIn } from './form-fields';
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

/**
 * The theme a page was rendered with, as the marker the theme puts on <body>.
 *
 * Asset paths are no good for this: looking for themes/<name>/ misses the themes that are
 * plugins rather than folders, and looking for plugin_<name> matches every time, because
 * each theme plugin's own CSS loads whatever the active theme is. An earlier version of
 * this file used both and reported that every theme applied, including when none had.
 *
 * The body carries class="theme-metis", "theme-adminlte" and so on. The default theme,
 * pro, carries no theme class at all, which is why the empty string is a valid answer.
 */
async function activeTheme( page: Page ): Promise<string> {
  const body = await ( await page.request.get( '/', { failOnStatusCode: false } ) ).text();
  const tag = body.match( /<body[^>]*>/ );
  const marker = tag ? tag[ 0 ].match( /\btheme-([a-z0-9_]+)/ ) : null;

  return marker ? marker[ 1 ] : '';
}

/** What activeTheme() should say once `theme` has been picked. */
function markerFor( theme: string ): string {
  return theme === 'pro' ? '' : theme.replace( /^theme_/, '' );
}

/** The themes the banner actually offers a visitor. */
async function offeredThemes( page: Page ): Promise<string[]> {
  const body = await ( await page.request.get( '/', { failOnStatusCode: false } ) ).text();

  return [ ...new Set( [ ...body.matchAll( /demo_theme=([a-z0-9_]+)/g ) ].map( ( m ) => m[ 1 ] ) ) ];
}

async function demoModeIsOn( page: Page ): Promise<boolean> {
  const body = await ( await page.request.get( '/', { failOnStatusCode: false } ) ).text();

  return body.includes( BANNER );
}

test.describe( 'The public demo @demo', () => {
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

  test( 'does not send a visitor to change a password they cannot change', async ( { page } ) => {
    // Every demo account keeps the install password, and demo mode refuses a change to it.
    // Off the demo, signing in with that password lands on the account page with a prompt
    // to change it; on the demo that would happen on every sign-in, for nothing.
    await login( page );
    await page.waitForLoadState( 'load' );
    expect( page.url(), 'signing in should not land on the account page' ).not.toContain( 'user_admin' );
    await expect( page.locator( 'body' ) ).not.toContainText( 'Please set a password' );
  } );

  test( 'links to the documentation, GitHub and the store, and nowhere else to buy', async ( { page } ) => {
    await login( page );
    const bar = page.locator( '#ucm-demo' );
    await expect( bar ).toContainText( 'DEMO' );
    await expect( bar.locator( 'a[href="https://ultimateclientmanager.com/docs/"]' ) ).toHaveCount( 1 );
    await expect( bar.locator( 'a[href="https://github.com/dtbaker/ucm"]' ) ).toHaveCount( 1 );
    await expect( bar.locator( 'a', { hasText: 'Buy now' } ) ).toHaveAttribute( 'href', /codecanyon\.net\/item\// );

    // Every theme ships with the product now, so none of them is sold separately any more.
    for ( const theme of await offeredThemes( page ) ) {
      const body = await ( await page.request.get( `/?demo_theme=${ theme }` ) ).text();
      expect( body, `the ${ theme } theme still links to ThemeForest` ).not.toContain( 'themeforest.net' );
    }
  } );

  test( 'picks a theme from the menu', async ( { page } ) => {
    await login( page );
    await page.goto( '/?demo_theme=pro' );
    await page.locator( '#ucm-demo summary' ).click();
    const menu = page.locator( '#ucm-demo .ucm-demo-menu' );
    await expect( menu ).toBeVisible();
    await expect( menu.locator( 'a.current' ) ).toHaveText( /Legacy/ );

    // The default theme leads the menu and the legacy one closes it.
    const names = ( await menu.locator( 'a' ).allTextContents() ).map( ( n ) => n.trim() );
    expect( names[ 0 ] ).toBe( 'Default' );
    expect( names[ names.length - 1 ] ).toBe( 'Legacy' );

    await Promise.all( [
      page.waitForURL( /demo_theme=metis/ ),
      menu.locator( 'a', { hasText: 'Default' } ).click(),
    ] );
    await expect( page.locator( '#ucm-demo summary' ) ).toContainText( 'Default' );
  } );

  test( 'stays closed once a visitor closes it', async ( { page } ) => {
    await login( page );
    await page.locator( '#ucm-demo-hide' ).click();
    await expect( page.locator( '#ucm-demo' ) ).toBeHidden();

    // Closed from the first paint of the next page, not hidden later by a script.
    const body = await ( await page.request.get( '/' ) ).text();
    expect( body ).toMatch( /<body[^>]*class="[^"]*ucm-demo-hidden/ );
    await page.reload();
    await expect( page.locator( '#ucm-demo' ) ).toBeHidden();

    await page.locator( '#ucm-demo-show' ).click();
    await expect( page.locator( '#ucm-demo' ) ).toBeVisible();
    await page.reload();
    await expect( page.locator( '#ucm-demo' ) ).toBeVisible();
  } );

  test( 'applies every theme its banner offers', async ( { page } ) => {
    await login( page );
    const themes = await offeredThemes( page );
    expect( themes.length, 'the banner should offer some themes to switch between' )
      .toBeGreaterThan( 1 );

    // Each one, not just one of them: the switcher is the main thing the demo is for, and
    // a theme that is advertised and does not apply is the visible kind of broken.
    for ( const theme of themes ) {
      await page.request.get( `/?demo_theme=${ theme }` );
      // Polled rather than read once: with the whole suite running in parallel the app is
      // slow enough that a single read raced the switch and this flaked.
      await expect.poll( () => activeTheme( page ), {
        message: `picking "${ theme }" did not apply it`,
        timeout: 10000,
      } ).toBe( markerFor( theme ) );
    }
  } );

  test( 'keeps what a visitor changes out of the database', async ( { browser, page } ) => {
    await login( page );
    const themes = await offeredThemes( page );

    // Which theme is the stored one? Ask a session that has not picked anything.
    const stored = await browser.newContext();
    let storedMarker = '';
    try {
      const probe = await stored.newPage();
      await login( probe );
      storedMarker = await activeTheme( probe );
    } finally {
      await stored.close();
    }

    const picked = themes.find( ( t ) => markerFor( t ) !== storedMarker );
    expect( picked, 'every offered theme is the one already stored - nothing to switch to' )
      .toBeTruthy();

    await page.request.get( `/?demo_theme=${ picked }` );
    await expect.poll( () => activeTheme( page ), {
      message: 'this visitor should see the theme they picked',
      timeout: 10000,
    } ).toBe( markerFor( picked! ) );

    // A different browser context is a different session, so it reads the stored value.
    // If the pick had been written to the config table, this would see it too.
    const fresh = await browser.newContext();
    try {
      const other_page = await fresh.newPage();
      await login( other_page );
      expect( await activeTheme( other_page ),
        'another visitor picked up this one\'s choice - it reached the config table' )
        .toBe( storedMarker );
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

  test( 'the whitelabel theme login keeps its controls', async ( { page } ) => {
    // That theme's login page only renders when the theme is active, which demo mode is
    // what makes reachable - picking it stores the choice in this session.
    await page.goto( '/?demo_theme=whitelabel1' );
    await page.goto( '/' );
    await expect( page.locator( 'body' ), 'the whitelabel theme should be the active one' )
      .toHaveClass( /theme-whitelabel1/ );

    await expectFieldsIn( page, 'form:has(input[name="_process_login"])', [
      '_process_login|input|hidden',
      'email|input|text',
      'login|input|submit',
      'password|input|password',
    ] );

    // And it still signs somebody in. The controls being present is not the same as the
    // form still working, which is the thing a layout change can quietly take away.
    await page.locator( 'input[name="email"]' ).first().fill( 'admin@example.com' );
    await page.locator( 'input[name="password"]' ).first().fill( 'password' );
    await Promise.all( [
      page.waitForNavigation( { waitUntil: 'load' } ),
      page.locator( 'input[name="login"]' ).first().click(),
    ] );
    expect( await page.content(), 'the login form should be gone after signing in' )
      .not.toContain( '_process_login' );
  } );
} );
