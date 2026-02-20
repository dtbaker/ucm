# e2e tests

1. Start the Docker environment

```
docker-compose up -d
```

This spins up the PHP app on http://localhost:8090, MySQL, MailHog, and phpMyAdmin. You'll need the app to be installed/seeded with test data (admin credentials: admin@example.com / password).

2. Install Node dependencies (first time only)

```
npx playwright install
npm installnpx playwright install
```

The npx playwright install step downloads the Chromium browser binary that Playwright needs.

3. Run the tests

```
npm run test:e2e
```

Or with the interactive UI:

```
npm run test:e2e:ui
```

You can also run a specific test file:

```
npx playwright test tests/e2e/login.spec.ts
```

4. Viewing results

```
npx playwright show-report
```
