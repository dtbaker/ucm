import { test, expect, Page } from '@playwright/test';
import { login, createRecord, aCustomer } from './seed';

/**
 * Lifecycle cover for the last four record types that had none: newsletter, calendar
 * entry, file and custom data.
 *
 * Same shape as records-lifecycle.spec.ts - create through the real handler, read the
 * value back off the form the app renders, then edit and read back again. Reading the
 * value back is the whole point: a "saved successfully" message proves the handler ran
 * and nothing more, and this codebase has shipped fields that were dropped on the way in
 * and fields that were never rendered on the way out.
 *
 * Every locator is scoped to its form. With UCM_DEBUG on, the debug panel echoes the page
 * markup, so an unscoped .first() can match a copy of a field that does nothing.
 */

async function fieldValue( page: Page, process: string, name: string ): Promise<string> {
  const form = page.locator( `form:has(input[name="_process"][value="${ process }"])` ).first();
  return form.locator( `[name="${ name }"]` ).first().inputValue();
}

test.describe( 'Content lifecycles', () => {
  test( 'a newsletter keeps its subject and sender, and an edit sticks', async ( { page } ) => {
    await login( page );
    const subject = 'Newsletter Test ' + Date.now();

    const newsletterId = await createRecord( page, '/?m[]=newsletter', {
      _process: 'save_newsletter',
      newsletter_id: 'new',
      subject,
      from_name: 'Lifecycle Sender',
      from_email: 'sender@example.com',
      content: 'Body text for the lifecycle test.',
    }, 'newsletter_id' );

    await page.goto( `/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=${ newsletterId }` );
    expect( await fieldValue( page, 'save_newsletter', 'subject' ), 'the subject should come back as typed' )
      .toBe( subject );
    expect( await fieldValue( page, 'save_newsletter', 'from_name' ), 'the sender name should come back as typed' )
      .toBe( 'Lifecycle Sender' );

    const renamed = subject + ' edited';
    await createRecord( page, '/?m[]=newsletter', {
      _process: 'save_newsletter',
      newsletter_id: newsletterId,
      subject: renamed,
      from_name: 'Lifecycle Sender',
      from_email: 'sender@example.com',
      content: 'Body text for the lifecycle test.',
    }, 'newsletter_id' );

    await page.goto( `/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=${ newsletterId }` );
    expect( await fieldValue( page, 'save_newsletter', 'subject' ), 'the edit should have stuck' )
      .toBe( renamed );
  } );

  /**
   * Calendar entries, file records and custom data groups are not covered here.
   *
   * Each was attempted and each needs more than a save and a read-back, so guessing at it
   * was costing more than it was proving:
   *
   *  - save_calendar_entry does not redirect to the new entry, so there is no id to follow.
   *    The calendar screen is drawn by a script that fetches its data separately, so a
   *    useful test has to go through that feed rather than a form.
   *  - a file record is created by save_file and the id comes back, but file_admin_edit
   *    does not render a form carrying _process=save_file with the file name in it, so the
   *    read-back has to come from somewhere else.
   *  - save_data_type creates a group and returns its id, but admin_data_list_type answers
   *    "No $data_type" for it, so either the page wants a different parameter or the group
   *    needs a field group before it will render.
   *
   * None of that is evidence of a bug - it is evidence that these three need a person to
   * drive the screens once and see how they actually fit together. Left honestly
   * uncovered rather than asserted on a guess.
   */
} );
