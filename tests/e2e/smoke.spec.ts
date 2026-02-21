import { test, expect, Page } from '@playwright/test';

/**
 * Smoke Tests
 *
 * Verify that key pages return HTTP 2xx and contain no PHP errors or warnings.
 * These tests catch fatal bootstrap/configuration errors that functional tests
 * might silently pass through (e.g., if a page returns a PHP error but the
 * test only checks for the absence of a conditional element).
 */

const PHP_ERROR_PATTERNS = [
  'Fatal error',
  'Parse error',
  'Uncaught Error',
  'Warning: require(',
  'Warning: include(',
  'Warning: require_once(',
];

async function assertNoPhpErrors(page: Page): Promise<void> {
  const content = await page.content();
  for (const pattern of PHP_ERROR_PATTERNS) {
    expect(content, `Page must not contain PHP error: "${pattern}"`).not.toContain(pattern);
  }
}

async function login(page: Page): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'changeme');
  await page.click('button[type="submit"]');
  await page.waitForURL(url => !url.pathname.includes('/login'));
}

test.describe('Smoke — public pages', () => {
  const pages: Array<{ path: string; selector: string; text: string }> = [
    { path: '/',       selector: 'h1 a',  text: 'CANDIDv2' },
    { path: '/login',  selector: 'h2',    text: 'Login' },
    { path: '/browse', selector: 'h2',    text: 'Categories' },
  ];

  for (const { path, selector, text } of pages) {
    test(`${path} returns 2xx with no PHP errors`, async ({ page }) => {
      const response = await page.goto(path);
      expect(response?.status(), `${path} should not return a server error`).toBeLessThan(500);
      await assertNoPhpErrors(page);
      await expect(page.locator(selector)).toContainText(text);
    });
  }
});

test.describe('Smoke — authenticated pages', () => {
  const paths = ['/image/add', '/category/add', '/profile/edit', '/admin/users'];

  for (const path of paths) {
    test(`${path} returns 2xx with no PHP errors`, async ({ page }) => {
      await login(page);
      const response = await page.goto(path);
      expect(response?.status(), `${path} should not return a server error`).toBeLessThan(500);
      await assertNoPhpErrors(page);
    });
  }
});
