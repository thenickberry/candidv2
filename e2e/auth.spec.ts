import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test('should show login page', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('h2')).toContainText('Login');
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="username"]', 'invaliduser');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    await expect(page.locator('.flash-error')).toBeVisible();
  });

  test('should login with valid credentials', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'changeme');
    await page.click('button[type="submit"]');

    // Should redirect to home or change password page
    await expect(page).not.toHaveURL('/login');
  });

  test('should redirect to login when accessing protected page', async ({ page }) => {
    await page.goto('/image/add');

    await expect(page).toHaveURL(/\/login/);
  });

  test('should logout successfully', async ({ page }) => {
    // Login first
    await page.goto('/login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'changeme');
    await page.click('button[type="submit"]');

    // Wait for login to complete (may redirect to password change)
    await page.waitForURL(url => !url.pathname.includes('/login'));

    // Navigate directly to logout endpoint
    await page.goto('/logout');

    // Should redirect to home. Check that logout link no longer exists in DOM
    // (profile dropdown shows "Login" when logged out, "Logout" when logged in)
    await expect(page.locator('.profile-menu a[href="/logout"]')).toHaveCount(0);
  });

  test('should redirect registration to login with message', async ({ page }) => {
    await page.goto('/register');

    await expect(page).toHaveURL('/login');
    await expect(page.locator('.flash')).toContainText('invite-only');
  });
});
