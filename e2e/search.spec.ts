import { test, expect } from '@playwright/test';

test.describe('Search', () => {
  test('should show search page with form', async ({ page }) => {
    await page.goto('/search');

    await expect(page.locator('h2')).toContainText('Search');
    await expect(page.locator('input[name="keywords"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should show filter options', async ({ page }) => {
    await page.goto('/search');

    // Date range inputs
    await expect(page.locator('input[name="start_date"]')).toBeVisible();
    await expect(page.locator('input[name="end_date"]')).toBeVisible();

    // Category select
    await expect(page.locator('select[name="category_id"]')).toBeVisible();
  });

  test('should perform search with keyword', async ({ page }) => {
    await page.goto('/search');

    await page.fill('input[name="keywords"]', 'test');
    await page.click('button[type="submit"]');

    // Should redirect to search results
    await expect(page).toHaveURL(/\/search/);
  });

  test('should perform search with date range', async ({ page }) => {
    await page.goto('/search');

    await page.fill('input[name="start_date"]', '2020-01-01');
    await page.fill('input[name="end_date"]', '2025-12-31');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/\/search/);
  });

  test('should show no results message when nothing found', async ({ page }) => {
    await page.goto('/search');

    // Use a very unlikely search term
    await page.fill('input[name="keywords"]', 'xyz123impossiblesearchterm789');
    await page.click('button[type="submit"]');

    // Either shows "no results" or empty result set
    const content = await page.textContent('body');
    const hasNoResults = content?.includes('No images found') ||
                         content?.includes('no results') ||
                         (await page.locator('.image-grid').count() === 0);

    expect(hasNoResults).toBeTruthy();
  });
});
