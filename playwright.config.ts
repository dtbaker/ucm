import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  // Screenshot capture is a documentation chore, not a check - it is slow and it proves
  // nothing about whether the software works. Run it deliberately:
  //   npx playwright test tests/e2e/screenshots.spec.ts --grep @screenshots
  grepInvert: process.env.UCM_SCREENSHOTS ? undefined : /@screenshots/,
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
