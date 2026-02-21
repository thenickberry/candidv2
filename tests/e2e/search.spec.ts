import { test, expect } from '@playwright/test';

test.describe('Search Modal', () => {
  test('should open search modal from navigation', async ({ page }) => {
    await page.goto('/');

    // Click search link in navigation
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');

    // Modal should be visible
    await expect(page.locator('#search-modal')).toHaveClass(/active/);
    await expect(page.locator('#search-modal h3')).toContainText('Search');
  });

  test('should show search form with filter options', async ({ page }) => {
    await page.goto('/');

    // Open search modal
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');

    // Wait for form to load
    await expect(page.locator('#search-modal-form')).toBeVisible();

    // Check form fields
    await expect(page.locator('#search-keywords')).toBeVisible();
    await expect(page.locator('#search-start-date')).toBeVisible();
    await expect(page.locator('#search-end-date')).toBeVisible();
    await expect(page.locator('#search-photographer')).toBeVisible();
    await expect(page.locator('#search-category')).toBeVisible();
    await expect(page.locator('#search-people')).toBeVisible();
    await expect(page.locator('#search-sort')).toBeVisible();
  });

  test('should perform search with keyword', async ({ page }) => {
    await page.goto('/');

    // Open search modal
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');
    await expect(page.locator('#search-modal-form')).toBeVisible();

    // Fill in keyword and search
    await page.fill('#search-keywords', 'test');
    await page.click('#search-modal-submit');

    // Should navigate to search results
    await expect(page).toHaveURL(/\/search\/results/);
  });

  test('should perform search with date range', async ({ page }) => {
    await page.goto('/');

    // Open search modal
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');
    await expect(page.locator('#search-modal-form')).toBeVisible();

    // Fill in date range
    await page.fill('#search-start-date', '2020-01-01');
    await page.fill('#search-end-date', '2025-12-31');
    await page.click('#search-modal-submit');

    // Should navigate to search results
    await expect(page).toHaveURL(/\/search\/results/);
  });

  test('should close modal with cancel button', async ({ page }) => {
    await page.goto('/');

    // Open search modal
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');
    await expect(page.locator('#search-modal')).toHaveClass(/active/);

    // Click cancel
    await page.click('#search-modal-cancel');

    // Modal should be hidden
    await expect(page.locator('#search-modal')).not.toHaveClass(/active/);
  });

  test('should close modal with Escape key', async ({ page }) => {
    await page.goto('/');

    // Open search modal
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');
    await expect(page.locator('#search-modal')).toHaveClass(/active/);

    // Press Escape
    await page.keyboard.press('Escape');

    // Modal should be hidden
    await expect(page.locator('#search-modal')).not.toHaveClass(/active/);
  });

  test('should show no results message when nothing found', async ({ page }) => {
    await page.goto('/');

    // Open search modal
    await page.click('nav a[href="#"][onclick*="openSearchModal"]');
    await expect(page.locator('#search-modal-form')).toBeVisible();

    // Use a very unlikely search term
    await page.fill('#search-keywords', 'xyz123impossiblesearchterm789');
    await page.click('#search-modal-submit');

    // Wait for results page
    await expect(page).toHaveURL(/\/search\/results/);

    // Either shows "no results" or empty result set
    const content = await page.textContent('body');
    const hasNoResults = content?.includes('No images found') ||
                         content?.includes('no results') ||
                         (await page.locator('.image-grid').count() === 0);

    expect(hasNoResults).toBeTruthy();
  });
});
