import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  // Two suites are run deliberately rather than as part of a sweep.
  //
  // @screenshots is a documentation chore: slow, and it proves nothing about whether the
  // software works.
  //   npx playwright test tests/e2e/screenshots.spec.ts --grep @screenshots
  //
  // @demo needs the app configured as the public demo, which the rest of the suite is not
  // run against. It also flaked when run alongside the whole suite in demo mode - the
  // themes apply reliably on their own and serially, so it is interference between tests
  // rather than a fault in the demo, but it is not worth a red run to find out which.
  //   ./scripts/demo-mode.sh on
  //   UCM_DEMO=1 npx playwright test tests/e2e/demo-mode.spec.ts
  //
  // @cleanup deletes the fixtures older runs of the suite left behind. It is a chore
  // run against a database that has a backlog, not a test of anything, and it deletes
  // records, so it only goes when it is asked for.
  //   UCM_CLEANUP=1 npx playwright test tests/e2e/cleanup-leftovers.spec.ts
  grepInvert: new RegExp( [
    process.env.UCM_SCREENSHOTS ? null : '@screenshots',
    process.env.UCM_DEMO ? null : '@demo',
    process.env.UCM_CLEANUP ? null : '@cleanup',
  ].filter( Boolean ).join( '|' ) || '(?!)' ),
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  // One worker everywhere, not just in CI.
  //
  // The suites share a small set of named fixtures - one customer, one data type and
  // its fieldset, one company, one customer group - which each suite finds and reuses
  // rather than making its own. That is what keeps the row counts flat, and it is also
  // what makes them unsafe to run side by side: the find-or-create has a window where
  // two workers both find nothing and both create, and several suites edit the shared
  // customer, raise invoices against it and delete them again.
  //
  // Measured rather than assumed, because a local run used to default to parallel and
  // it mostly looked fine. Four runs of the whole suite at six workers against five
  // serial runs of the same code:
  //
  //   serial    5 of 5 green
  //   parallel  2 of 4 red - content-lifecycle could not read back the value it had
  //             just written to its own data record (twice), and the csrf quote form
  //             did not save (once)
  //
  // Both failures are the shared fixtures being used from two places at once, and
  // neither has ever happened serially. Parallel finishes in about a third of the
  // time, which is not worth a suite that is wrong half the time it runs.
  //
  // Making it genuinely parallel-safe means giving each worker its own fixtures, which
  // undoes the sharing that keeps the tables flat; it is a bigger change than this one
  // and wants doing deliberately, not as a side effect.
  workers: 1,
  reporter: 'html',
  use: {
    baseURL: process.env.UCM_BASE_URL || 'http://localhost:8090',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: process.env.CI ? undefined : {
    command: 'echo "Ensure Docker is running: docker-compose up -d"',
    url: 'http://localhost:8090',
    reuseExistingServer: true,
  },
});
