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

/** Pick a form by the hidden _process it dispatches on. */
export function byProcess( process: string ): string {
  return `form:has(input[name="_process"][value="${ process }"])`;
}

/**
 * Every named control inside `formSelector`, as "name|tag|type", read off the page as it
 * currently stands. Navigate first - some of these forms are only reachable by posting.
 */
export async function fieldsIn( page: Page, formSelector: string ): Promise<Field[]> {
  return page.evaluate( ( selector ) => {
    const form = document.querySelector( selector );
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
      // A row index is data, not shape: add_to_group[17] and add_to_group[18] are the
      // same control repeated, and how many there are depends on what is in the database.
      const shape = node.name.replace( /\[\d+\]/g, '[*]' );
      const kind = node.tagName.toLowerCase();
      seen.push( `${ shape }|${ kind }|${ kind === 'input' ? node.type : '' }` );
    } );

    // generate_form_element() pairs every checkbox with a hidden default_<name>, so the
    // handler can tell "unticked" from "not on this form". That companion is the helper's
    // own plumbing, like the token - but it is only ignored when the real control it
    // belongs to is present, so a rename still shows up as a difference.
    return [ ...new Set( seen ) ]
      .filter( ( entry ) => {
        const name = entry.split( '|' )[ 0 ];
        return ! ( name.startsWith( 'default_' ) && names.indexOf( name.slice( 8 ) ) !== -1 );
      } )
      .sort();
  }, formSelector );
}

/** Go to `url`, then read the controls of the form that dispatches `process`. */
export async function formFields( page: Page, url: string, process: string ): Promise<Field[]> {
  await page.goto( url );

  return fieldsIn( page, byProcess( process ) );
}

/**
 * Assert the form's controls are exactly `expected`.
 *
 * The failure prints both sides, because the useful information when this breaks is
 * which control went missing, not that a count changed.
 */
export async function expectFieldsIn( page: Page, formSelector: string, expected: Field[] ) {
  const actual = await fieldsIn( page, formSelector );
  expect( actual, 'the form should render the same controls as before the conversion' )
    .toEqual( [ ...expected ].sort() );
}

/** Go to `url` and assert the controls of the form that dispatches `process`. */
export async function expectFields( page: Page, url: string, process: string, expected: Field[] ) {
  await page.goto( url );
  await expectFieldsIn( page, byProcess( process ), expected );
}
