import { test, expect } from '@playwright/test';
import { login } from './seed';

/**
 * What Settings > Theme offers, and in which order.
 *
 * The theme in the metis folder is the one every install gets until someone picks
 * another, so it is called the Default Theme and comes first. The old pro theme is kept
 * for installs that still use it, as the Legacy Theme, and comes last. The folder names
 * stay as they are: installs store them as their theme_name.
 */
test( 'Settings > Theme lists the default theme first and the legacy theme last', async ( { page } ) => {
  await login( page );
  await page.goto( '/?m[]=theme&p[]=theme_settings' );

  const select = page.locator( 'select[name$="[theme_name]"]' ).first();
  const options = await select.locator( 'option' ).evaluateAll( ( els ) =>
    els.map( ( o ) => ( { value: ( o as HTMLOptionElement ).value, text: ( o.textContent || '' ).trim() } ) )
      .filter( ( o ) => o.value !== '' ) );

  expect( options[ 0 ], 'the default theme should lead the list' )
    .toEqual( { value: 'metis', text: 'Default Theme' } );
  expect( options[ options.length - 1 ], 'the legacy theme should close it' )
    .toEqual( { value: 'pro', text: 'Legacy Theme' } );
  expect( options.map( ( o ) => o.text ).join( ', ' ), 'nothing should still be called Metis' )
    .not.toMatch( /metis/i );
  expect( options.filter( ( o ) => o.text === 'Default Theme' ), 'only one theme can be the default' )
    .toHaveLength( 1 );
} );
