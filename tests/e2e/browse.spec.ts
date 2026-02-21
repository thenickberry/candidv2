import { test, expect } from '@playwright/test';

test.describe('Browse', () => {
  test('should show home page', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('h1 a')).toContainText('CANDIDv2');
    await expect(page.locator('a[href="/browse"]')).toBeVisible();
    // Search is now a modal trigger
    await expect(page.locator('nav a[onclick*="openSearchModal"]')).toBeVisible();
  });

  test('should show browse page with categories', async ({ page }) => {
    await page.goto('/browse');

    await expect(page.locator('h2')).toContainText('Categories');
  });

  test('should navigate to category', async ({ page }) => {
    await page.goto('/browse');

    // Seed data guarantees at least the "Root" category exists
    const categoryLink = page.locator('a[href^="/browse/"]').first();
    await expect(categoryLink).toBeVisible();
    await categoryLink.click();
    await expect(page).toHaveURL(/\/browse\/\d+/);
  });

  test('should show sort dropdown in category view', async ({ page }) => {
    await page.goto('/browse');

    // Seed data guarantees at least the "Root" category exists
    const categoryLink = page.locator('a[href^="/browse/"]').first();
    await expect(categoryLink).toBeVisible();
    await categoryLink.click();
    // Sort dropdown is present on category pages (even with no images)
    await expect(page.locator('select#sort')).toBeVisible();
  });
});

test.describe('Lightbox', () => {
  test('should open lightbox when clicking image', async ({ page }) => {
    await page.goto('/');

    // Find an image card on the page
    const imageCard = page.locator('.image-card img').first();
    const hasImages = await imageCard.count() > 0;

    if (hasImages) {
      await imageCard.click();

      // Lightbox should be visible
      await expect(page.locator('.lightbox-overlay.active')).toBeVisible();
      await expect(page.locator('.lightbox-image')).toBeVisible();
    }
  });

  test('should close lightbox with close button', async ({ page }) => {
    await page.goto('/');

    const imageCard = page.locator('.image-card img').first();
    const hasImages = await imageCard.count() > 0;

    if (hasImages) {
      await imageCard.click();
      await expect(page.locator('.lightbox-overlay.active')).toBeVisible();

      await page.click('.lightbox-close');
      await expect(page.locator('.lightbox-overlay.active')).not.toBeVisible();
    }
  });

  test('should close lightbox with Escape key', async ({ page }) => {
    await page.goto('/');

    const imageCard = page.locator('.image-card img').first();
    const hasImages = await imageCard.count() > 0;

    if (hasImages) {
      await imageCard.click();
      await expect(page.locator('.lightbox-overlay.active')).toBeVisible();

      await page.keyboard.press('Escape');
      await expect(page.locator('.lightbox-overlay.active')).not.toBeVisible();
    }
  });

  test('should navigate with arrow keys', async ({ page }) => {
    await page.goto('/');

    // Need at least 2 images for navigation
    const imageCards = page.locator('.image-card img');
    const imageCount = await imageCards.count();

    if (imageCount >= 2) {
      await imageCards.first().click();
      await expect(page.locator('.lightbox-overlay.active')).toBeVisible();

      const firstSrc = await page.locator('.lightbox-image').getAttribute('src');

      await page.keyboard.press('ArrowRight');
      await page.waitForFunction(
        (src) => document.querySelector('.lightbox-image')?.getAttribute('src') !== src,
        firstSrc
      );

      const secondSrc = await page.locator('.lightbox-image').getAttribute('src');
      expect(secondSrc).not.toEqual(firstSrc);
    }
  });

  test('should show metadata in lightbox footer', async ({ page }) => {
    await page.goto('/');

    const imageCard = page.locator('.image-card img').first();
    const hasImages = await imageCard.count() > 0;

    if (hasImages) {
      await imageCard.click();
      await expect(page.locator('.lightbox-overlay.active')).toBeVisible();

      // Footer with metadata should be visible
      await expect(page.locator('.lightbox-footer')).toBeVisible();
    }
  });
});
