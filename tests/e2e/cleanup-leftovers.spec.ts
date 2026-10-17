import { test, expect, Page } from '@playwright/test';
import { login, deleteRecord, formAuthKey } from './seed';
import { Fixture, countFixtures, idsMatching } from './db';

/**
 * Clears the fixtures earlier runs of the suite left in the database.
 *
 * Not part of a normal run - it is tagged @cleanup and the config filters that out, so
 * it only goes when it is asked for:
 *
 *     UCM_CLEANUP=1 npx playwright test tests/e2e/cleanup-leftovers.spec.ts
 *
 * Every suite now deletes what it makes, so nothing new piles up. This is for the
 * backlog already sitting in a database that was tested against before they did: a few
 * hundred customers, invoices, FAQ items, vendors and quotes, which is enough to make
 * the screens that render a row per record too slow to drive, and enough to keep
 * leftovers.spec.ts red.
 *
 * Two deliberate choices:
 *
 *  - The ids are read from the database, and the delete is posted to the app. Reading
 *    from the screens instead meant a tour of every module's list page, and three of
 *    them (quote_admin_list, job_admin_list, contract_admin_list) die when they are
 *    asked for directly, so the first version of this file reported "removed 0 quotes"
 *    while 134 sat in the table. The newsletter lists publish no id at all.
 *  - The delete still goes through the module, never a DELETE at the table. That is the
 *    whole point: the modules take their invoice items, tasks, addresses, contacts and
 *    notes with them, and several of them only recently started doing that properly.
 */

type Target = {
  /** for the test name and the log */
  what: string;
  /** table, column and the LIKE patterns the suites write */
  table: string;
  column: string;
  terms: string[];
  /** the primary key column, and the field the delete posts it as */
  idColumn: string;
  /** where a delete is posted */
  postUrl: string;
  process: string;
  /** confirm_delete's post key, when the module passes something other than its own */
  confirmKey?: string;
  /** anything else the module's confirm screen asks for */
  extra?: Record<string, string>;
};

const TARGETS: Target[] = [
  // Parents first: deleting a customer takes its invoices, jobs and websites, so the
  // rows left for the later targets are only the ones that belonged to nothing.
  {
    what: 'customers', table: 'customer', column: 'customer_name',
    terms: [ 'CSRF %', 'Fieldset %', 'Lifecycle %', 'Credit Customer %', 'SubRenew %', 'Address Test %' ],
    idColumn: 'customer_id', postUrl: '/?m[]=customer', process: 'save_customer',
    extra: { delete_others: '1' },
  },
  {
    what: 'invoices', table: 'invoice', column: 'name',
    terms: [ 'CSRF %', 'Fieldset %', 'Credit Note Test %', 'Credit spend test', 'Invoice arithmetic test', 'Renewal %', 'PDF print test' ],
    idColumn: 'invoice_id', postUrl: '/?m[]=invoice', process: 'save_invoice',
  },
  {
    what: 'quotes', table: 'quote', column: 'name',
    terms: [ 'CSRF %', 'Conversion %', 'Quote %' ],
    idColumn: 'quote_id', postUrl: '/?m[]=quote', process: 'save_quote',
  },
  {
    what: 'jobs', table: 'job', column: 'name',
    terms: [ 'CSRF %', 'Job %' ],
    idColumn: 'job_id', postUrl: '/?m[]=job', process: 'save_job',
  },
  {
    what: 'tickets', table: 'ticket', column: 'subject',
    terms: [ 'Ticket walk%', 'Ticket priority%', 'Fieldset %' ],
    idColumn: 'ticket_id', postUrl: '/?m[]=ticket', process: 'save_ticket',
  },
  {
    what: 'subscriptions', table: 'subscription', column: 'name',
    terms: [ 'CSRF %', 'Fieldset %' ],
    idColumn: 'subscription_id', postUrl: '/?m[]=subscription', process: 'save_subscription',
  },
  {
    what: 'contracts', table: 'contract', column: 'name',
    terms: [ 'CSRF %' ],
    idColumn: 'contract_id', postUrl: '/?m[]=contract', process: 'save_contract',
  },
  {
    what: 'vendors', table: 'vendor', column: 'vendor_name',
    terms: [ 'CSRF %', 'Lifecycle %', 'Vendor Test %' ],
    idColumn: 'vendor_id', postUrl: '/?m[]=vendor', process: 'save_vendor',
  },
  {
    what: 'websites', table: 'website', column: 'name',
    terms: [ 'CSRF %', 'Lifecycle %', 'Website Test %' ],
    idColumn: 'website_id', postUrl: '/?m[]=website', process: 'save_website',
  },
  {
    what: 'staff and contacts', table: 'user', column: 'name',
    terms: [ 'CSRF %', 'Fieldset %' ],
    idColumn: 'user_id', postUrl: '/?m[]=user', process: 'save_user',
  },
  {
    what: 'security roles', table: 'security_role', column: 'name',
    terms: [ 'CSRF %', 'Fieldset %' ],
    idColumn: 'security_role_id', postUrl: '/?m[]=security', process: 'save_security_role',
  },
  {
    what: 'companies', table: 'company', column: 'name',
    terms: [ 'CSRF %', 'Fieldset %' ],
    idColumn: 'company_id', postUrl: '/?m[]=company', process: 'save_company',
  },
  {
    what: 'data types', table: 'data_type', column: 'data_type_name',
    terms: [ 'CSRF %', 'Fieldset %', 'Escaping %', 'Record Type %', 'Data Type %', 'Search Type %' ],
    idColumn: 'data_type_id', postUrl: '/?m[]=data', process: 'save_data_type',
  },
  {
    what: 'newsletters', table: 'newsletter', column: 'subject',
    terms: [ 'CSRF %', 'Fieldset %', 'Queued Newsletter %', 'Dynamic Fields %', 'Newsletter Test %' ],
    idColumn: 'newsletter_id', postUrl: '/?m[]=newsletter', process: 'save_newsletter',
  },
  {
    what: 'groups', table: 'group', column: 'name',
    terms: [ 'CSRF %' ],
    idColumn: 'group_id', postUrl: '/?m[]=group', process: 'save_group',
  },
  {
    what: 'FAQ items', table: 'faq', column: 'question',
    terms: [ 'CSRF %', 'Fieldset %' ],
    idColumn: 'faq_id', postUrl: '/?m[]=faq', process: 'save_faq',
    // Both FAQ screens hash 'customer_id' rather than their own key.
    confirmKey: 'customer_id',
  },
  {
    what: 'FAQ products', table: 'faq_product', column: 'name',
    terms: [ 'CSRF %' ],
    idColumn: 'faq_product_id', postUrl: '/?m[]=faq', process: 'save_faq_product',
    confirmKey: 'customer_id',
  },
  {
    what: 'customer types', table: 'customer_type', column: 'type_name',
    terms: [ 'CSRF %' ],
    idColumn: 'customer_type_id', postUrl: '/?m[]=customer', process: 'save_customer_type',
  },
  {
    what: 'ticket types', table: 'ticket_type', column: 'name',
    terms: [ 'CSRF %' ],
    idColumn: 'ticket_type_id', postUrl: '/?m[]=ticket', process: 'save_ticket_type',
  },
  {
    what: 'product categories', table: 'product_category', column: 'product_category_name',
    terms: [ 'CSRF %' ],
    idColumn: 'product_category_id', postUrl: '/?m[]=product', process: 'save_product_category',
  },
  // Three that name their fixture in a key column rather than a name column, which is
  // why the first pass of this file walked straight past them.
  {
    what: 'templates', table: 'template', column: 'template_key',
    terms: [ 'csrf_test_template%', 'csrf_seed_template%' ],
    idColumn: 'template_id', postUrl: '/?m[]=template', process: 'save_template',
  },
  {
    what: 'ticket fields', table: 'ticket_data_key', column: 'key',
    terms: [ 'csrf_test_key%', 'csrf_seed_key%' ],
    idColumn: 'ticket_data_key_id', postUrl: '/?m[]=ticket', process: 'save_ticket_data_key',
  },
  {
    what: 'extra fields', table: 'extra_default', column: 'extra_key',
    terms: [ 'csrf_test_field%', 'csrf_seed_field%' ],
    idColumn: 'extra_default_id', postUrl: '/?m[]=extra', process: 'save_extra_default',
  },
  {
    what: 'transactions', table: 'finance', column: 'name',
    terms: [ 'CSRF %' ],
    idColumn: 'finance_id', postUrl: '/?m[]=finance', process: 'save_finance',
  },
];

test.describe( 'Clearing leftovers @cleanup', () => {
  for ( const target of TARGETS ) {
    test( `removes the ${ target.what } earlier runs left behind`, async ( { page } ) => {
      test.setTimeout( 1800000 );
      await login( page );
      // Once, not once per record: the token is good for the session.
      const authKey = await formAuthKey( page );

      let removed = 0;
      for ( const like of target.terms ) {
        const fixture: Fixture = { table: target.table, column: target.column, like };
        // Round and round rather than one pass: deleting a parent can take rows that
        // were on this list with it, so the set is re-read each time. A pass that
        // removes nothing stops the loop - without that, a module whose delete quietly
        // refuses turns this into tens of thousands of pointless posts, and the run
        // looks like it is working rather than stuck.
        let before = Infinity;
        for ( let pass = 0; pass < 50; pass++ ) {
          const ids = await idsMatching( fixture, target.idColumn );
          if ( ! ids.length ) {
            break;
          }
          expect(
            ids.length,
            `deleting ${ target.what } matching '${ like }' is having no effect`
            + ` - ${ ids.length } rows are still there after a full pass`,
          ).toBeLessThan( before );
          before = ids.length;
          for ( const id of ids ) {
            await deleteRecord(
              page, target.postUrl, target.process, target.idColumn, id,
              { confirmKey: target.confirmKey, extra: target.extra, authKey },
            );
            removed++;
          }
        }
      }

      const left = await countFixtures(
        target.terms.map( like => ( { table: target.table, column: target.column, like } ) ),
      );
      const total = left.reduce( ( a, b ) => a + b, 0 );
      // eslint-disable-next-line no-console
      console.log( `removed ${ removed } ${ target.what }, ${ total } left` );
      expect( total, `${ total } ${ target.what } still match this suite's fixture names` ).toBe( 0 );
    } );
  }
} );
