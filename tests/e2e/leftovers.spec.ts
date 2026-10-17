import { test, expect } from '@playwright/test';
import { Fixture, countFixtures } from './db';

/**
 * The suites must not leave their fixtures in the database.
 *
 * This is the guard on the rest of the suite rather than a test of the product. Every
 * other spec either reuses a record or deletes the one it made, and when one of them
 * stops doing that nothing goes red - the row count just creeps up, run after run,
 * until a screen that renders a row per record gets too slow to drive and some
 * unrelated test times out. That is not a failure anyone can read.
 *
 * It had already happened. ucm_subscription held 208 rows named 'CSRF Test
 * Subscription', left by csrf-modules.spec.ts over many runs; every customer and
 * invoice screen renders a checkbox per subscription, so quote-to-invoice.spec.ts
 * timed out at 180s. Deleting the leftovers took that test to 11 seconds. Nothing in
 * the suite had anything to say about it.
 *
 * So this counts the rows whose names the suites use and fails while there are too
 * many. It reads the database directly because no screen in the app gives an honest
 * total - the lists page at twenty rows, which is exactly how the backlog hid.
 *
 * When this fails, the message names the table. Either a spec has stopped cleaning up
 * after itself, or the database is carrying a backlog from before it did:
 *
 *     UCM_CLEANUP=1 npx playwright test tests/e2e/cleanup-leftovers.spec.ts
 *
 * clears the backlog through the app's own delete. Raising the limits is the wrong fix
 * and will only hide the next one.
 */

/**
 * A handful rather than one, deliberately.
 *
 * A test that is interrupted, or that fails before its cleanup runs, leaves its record
 * behind, and a retry leaves another. The suite is allowed to be untidy to that degree
 * without going red; what it is not allowed to do is accumulate, and anything growing
 * once per run crosses this in a few sittings.
 */
const LIMIT = 8;

const FIXTURES: Fixture[] = [
  // The tables the leak actually reached, and the names the suites write into them.
  { table: 'customer', column: 'customer_name', like: 'CSRF %' },
  { table: 'customer', column: 'customer_name', like: 'Fieldset %' },
  { table: 'customer', column: 'customer_name', like: 'Lifecycle %' },
  { table: 'invoice', column: 'name', like: 'CSRF %' },
  { table: 'invoice', column: 'name', like: 'Fieldset %' },
  { table: 'ticket', column: 'subject', like: 'Ticket walk%' },
  { table: 'ticket', column: 'subject', like: 'Ticket priority%' },
  { table: 'ticket', column: 'subject', like: 'Fieldset %' },
  { table: 'subscription', column: 'name', like: 'CSRF %' },
  { table: 'subscription', column: 'name', like: 'Fieldset %' },
  { table: 'security_role', column: 'name', like: 'CSRF %' },
  { table: 'security_role', column: 'name', like: 'Fieldset %' },
  { table: 'user', column: 'name', like: 'CSRF %' },
  { table: 'user', column: 'name', like: 'Fieldset %' },
  { table: 'company', column: 'name', like: 'CSRF %' },
  { table: 'company', column: 'name', like: 'Fieldset %' },
  { table: 'website', column: 'name', like: 'CSRF %' },
  { table: 'website', column: 'name', like: 'Lifecycle %' },
  { table: 'faq', column: 'question', like: 'CSRF %' },
  { table: 'faq', column: 'question', like: 'Fieldset %' },
  { table: 'data_type', column: 'data_type_name', like: 'CSRF %' },
  { table: 'data_type', column: 'data_type_name', like: 'Fieldset %' },
  { table: 'data_type', column: 'data_type_name', like: 'Escaping %' },
  { table: 'group', column: 'name', like: 'CSRF %' },
  { table: 'group', column: 'name', like: 'Fieldset %' },
  // Not in the original list, but these grew the same way and for the same reason.
  { table: 'quote', column: 'name', like: 'CSRF %' },
  { table: 'quote', column: 'name', like: 'Conversion %' },
  { table: 'job', column: 'name', like: 'CSRF %' },
  { table: 'contract', column: 'name', like: 'CSRF %' },
  { table: 'vendor', column: 'vendor_name', like: 'CSRF %' },
  { table: 'vendor', column: 'vendor_name', like: 'Lifecycle %' },
  { table: 'newsletter', column: 'subject', like: 'CSRF %' },
  { table: 'newsletter', column: 'subject', like: 'Fieldset %' },
  { table: 'finance', column: 'name', like: 'CSRF %' },
  { table: 'customer_type', column: 'type_name', like: 'CSRF %' },
  { table: 'ticket_type', column: 'name', like: 'CSRF %' },
  { table: 'product_category', column: 'product_category_name', like: 'CSRF %' },
  { table: 'faq_product', column: 'name', like: 'CSRF %' },
];

test.describe( 'Leftovers', () => {
  test( 'no suite has left a pile of its own fixtures behind', async () => {
    const counts = await countFixtures( FIXTURES );

    const tooMany = FIXTURES
      .map( ( fixture, i ) => ( { ...fixture, count: counts[ i ] } ) )
      .filter( row => row.count > LIMIT )
      .map( row => `  ${ row.count } rows in ${ row.table }.${ row.column } like '${ row.like }'` );

    expect(
      tooMany,
      'Fixtures are piling up in the database. A suite has stopped deleting what it\n'
      + 'makes, or this database is carrying a backlog from before they did:\n\n'
      + tooMany.join( '\n' )
      + '\n\nClear a backlog with:\n'
      + '  UCM_CLEANUP=1 npx playwright test tests/e2e/cleanup-leftovers.spec.ts\n',
    ).toEqual( [] );
  } );
} );
