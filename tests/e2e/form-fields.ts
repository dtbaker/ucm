import { Page, expect } from '@playwright/test';

/**
 * The controls a form actually renders, as a comparable list.
 *
 * This exists for the move from hand-written <table class="tableclass_form"> layouts to
 * module_form::generate_fieldset(). That change rewrites the markup around every field,
 * and the thing that must not change is the fields themselves: a dropped or renamed
 * control means the value silently stops reaching the database, which is a bug this
 * codebase has shipped before and which a "saved successfully" message will not catch.
 *
 * So each page gets a spec that records its controls and passes BEFORE the conversion.
 * The same spec then has to pass after it. Name and kind are the invariant; values are
 * not, because they depend on whatever record the test happened to open.
 */

export type Field = string;

/** Every named control inside the form that carries `process`, as "name|tag|type". */
export async function formFields( page: Page, url: string, process: string ): Promise<Field[]> {
  await page.goto( url );

  return page.evaluate( ( processValue ) => {
    const marker = document.querySelector( `input[name="_process"][value="${ processValue }"]` );
    const form = marker ? marker.closest( 'form' ) : document.querySelector( 'form' );
    if ( ! form ) {
      return [];
    }

    const seen: string[] = [];
    const names: string[] = [];
    form.querySelectorAll( 'input, select, textarea' ).forEach( ( el ) => {
      const node = el as HTMLInputElement;
      if ( ! node.name ) {
        return;
      }
      // The form token and the dispatcher are plumbing, not fields, and the helper
      // emits them itself - they would show up as a difference that means nothing.
      if ( node.name === 'form_auth_key' || node.name === '_process' ) {
        return;
      }
      names.push( node.name );
      const kind = node.tagName.toLowerCase();
      seen.push( `${ node.name }|${ kind }|${ kind === 'input' ? node.type : '' }` );
    } );

    // generate_form_element() pairs every checkbox with a hidden default_<name>, so the
    // handler can tell "unticked" from "not on this form". That companion is the helper's
    // own plumbing, like the token - but it is only ignored when the real control it
    // belongs to is present, so a rename still shows up as a difference.
    return seen
      .filter( ( entry ) => {
        const name = entry.split( '|' )[ 0 ];
        return ! ( name.startsWith( 'default_' ) && names.indexOf( name.slice( 8 ) ) !== -1 );
      } )
      .sort();
  }, process );
}

/**
 * Assert a page's controls are exactly `expected`.
 *
 * The failure prints both sides, because the useful information when this breaks is
 * which control went missing, not that a count changed.
 */
export async function expectFields( page: Page, url: string, process: string, expected: Field[] ) {
  const actual = await formFields( page, url, process );
  expect( actual, 'the form should render the same controls as before the conversion' )
    .toEqual( [ ...expected ].sort() );
}
