import { test, expect } from '@playwright/test';

/**
 * E2E tests for UCM login flow.
 * Run against Dockerized app: docker-compose up -d && npm run test:e2e
 * Test data: admin@example.com / password (from installer seed)
 */
test.describe('Login', () => {
  test('login page loads', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/UCM|Ultimate Client Manager|Login/i);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('login with valid credentials', async ({ page }) => {
    await page.goto('/');
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();

    await expect(page).not.toHaveURL(/\?.*login/);
    await expect(page.locator('body')).not.toContainText('Invalid username or password');
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    await page.goto('/');
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('wrongpassword');
    await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();

    await expect(page.getByText(/invalid|incorrect|wrong/i)).toBeVisible();
  });
});
