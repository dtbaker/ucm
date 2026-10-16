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
  grepInvert: new RegExp( [
    process.env.UCM_SCREENSHOTS ? null : '@screenshots',
    process.env.UCM_DEMO ? null : '@demo',
  ].filter( Boolean ).join( '|' ) || '(?!)' ),
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
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
