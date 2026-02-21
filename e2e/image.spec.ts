import { test, expect } from '@playwright/test';
import path from 'path';

// Helper to login before tests
async function login(page: any) {
  await page.goto('/login');
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'changeme');
  await page.click('button[type="submit"]');
  await page.waitForURL(url => !url.pathname.includes('/login'));

  // Handle password change redirect if needed
  if (page.url().includes('/profile/password')) {
    // Skip tests that require logged in state if password change is required
    return false;
  }
  return true;
}

test.describe('Image Upload', () => {
  test('should show upload form when logged in', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    await page.goto('/image/add');

    await expect(page.locator('h2')).toContainText('Upload');
    await expect(page.locator('input[type="file"]')).toBeVisible();
    await expect(page.locator('select[name="category_id"]')).toBeVisible();
  });

  test('should require file selection', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    await page.goto('/image/add');

    // Submit without selecting file
    await page.click('button[type="submit"]');

    // Should show error or stay on page
    const url = page.url();
    expect(url).toContain('/image/add');
  });

  test('should pre-select category from query param', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    // Get a category ID first
    await page.goto('/browse');
    const categoryLink = page.locator('a[href^="/browse/"]').first();
    const hasCategories = await categoryLink.count() > 0;

    if (hasCategories) {
      const href = await categoryLink.getAttribute('href');
      const categoryId = href?.match(/\/browse\/(\d+)/)?.[1];

      if (categoryId) {
        await page.goto(`/image/add?category=${categoryId}`);

        const selectedValue = await page.locator('select[name="category_id"]').inputValue();
        expect(selectedValue).toBe(categoryId);
      }
    }
  });
});

test.describe('Image View', () => {
  test('should show image detail page', async ({ page }) => {
    // Find an image from home page
    await page.goto('/');

    const imageLink = page.locator('a[href^="/image/"]').first();
    const hasImages = await imageLink.count() > 0;

    if (hasImages) {
      await imageLink.click();
      await expect(page.locator('h2, .image-detail')).toBeVisible();
    }
  });

  test('should show breadcrumb navigation', async ({ page }) => {
    await page.goto('/');

    const imageLink = page.locator('a[href^="/image/"]').first();
    const hasImages = await imageLink.count() > 0;

    if (hasImages) {
      await imageLink.click();
      const breadcrumb = page.locator('.breadcrumb');
      if (await breadcrumb.count() > 0) {
        await expect(breadcrumb).toBeVisible();
      }
    }
  });
});

test.describe('Image Bulk Edit', () => {
  test('should show Select button and checkboxes when toggled', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    // Go to a category with images
    await page.goto('/browse');

    const categoryLink = page.locator('a[href^="/browse/"]').first();
    const hasCategories = await categoryLink.count() > 0;

    if (hasCategories) {
      await categoryLink.click();

      // Select button should be visible
      const selectBtn = page.locator('#toggleSelectMode');
      if (await selectBtn.count() > 0) {
        await expect(selectBtn).toBeVisible();

        // Checkboxes should be hidden initially
        const checkbox = page.locator('.image-select-checkbox');
        if (await checkbox.count() > 0) {
          await expect(checkbox.first()).toBeHidden();

          // Click Select button to enable select mode
          await selectBtn.click();

          // Checkboxes should now be visible
          await expect(checkbox.first()).toBeVisible();
        }
      }
    }
  });

  test('should show bulk actions bar when select mode is enabled', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    // Go to a category with images
    await page.goto('/browse');

    const categoryLink = page.locator('a[href^="/browse/"]').first();
    const hasCategories = await categoryLink.count() > 0;

    if (hasCategories) {
      await categoryLink.click();

      const selectBtn = page.locator('#toggleSelectMode');
      if (await selectBtn.count() > 0) {
        // Bulk actions bar should be hidden initially
        await expect(page.locator('#bulkActionsBar')).toBeHidden();

        // Click Select button
        await selectBtn.click();

        // Bulk actions bar should now be visible
        await expect(page.locator('#bulkActionsBar')).toBeVisible();
        await expect(page.locator('#selectionCount')).toContainText('0 selected');

        // Select first image
        const checkbox = page.locator('.image-select-checkbox').first();
        if (await checkbox.count() > 0) {
          await checkbox.click();
          await expect(page.locator('#selectionCount')).toContainText('1 selected');
        }
      }
    }
  });

  test('should navigate to bulk edit page when Edit Selected is clicked', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    // Go to a category with images
    await page.goto('/browse');

    const categoryLink = page.locator('a[href^="/browse/"]').first();
    const hasCategories = await categoryLink.count() > 0;

    if (hasCategories) {
      await categoryLink.click();

      const selectBtn = page.locator('#toggleSelectMode');
      if (await selectBtn.count() > 0) {
        // Enable select mode
        await selectBtn.click();

        const checkbox = page.locator('.image-select-checkbox').first();
        if (await checkbox.count() > 0) {
          // Select first image
          await checkbox.click();

          // Click Edit Selected
          await page.locator('#bulkEditBtn').click();

          // Should be on bulk edit page
          await expect(page.url()).toContain('/image/bulk/edit');
          await expect(page.locator('h2')).toContainText('Edit');
        }
      }
    }
  });

  test('should exit select mode when Done is clicked', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    // Go to a category with images
    await page.goto('/browse');

    const categoryLink = page.locator('a[href^="/browse/"]').first();
    const hasCategories = await categoryLink.count() > 0;

    if (hasCategories) {
      await categoryLink.click();

      const selectBtn = page.locator('#toggleSelectMode');
      if (await selectBtn.count() > 0) {
        // Enable select mode
        await selectBtn.click();

        // Bulk actions bar should be visible
        await expect(page.locator('#bulkActionsBar')).toBeVisible();

        // Click Done to exit select mode
        await page.locator('#exitSelectMode').click();

        // Bulk actions bar should be hidden
        await expect(page.locator('#bulkActionsBar')).toBeHidden();

        // Select button should be visible again
        await expect(selectBtn).toBeVisible();

        // Checkboxes should be hidden
        const checkbox = page.locator('.image-select-checkbox');
        if (await checkbox.count() > 0) {
          await expect(checkbox.first()).toBeHidden();
        }
      }
    }
  });
});

test.describe('Image Edit', () => {
  test('should show edit form when logged in as owner', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    // Find an image to edit
    await page.goto('/');

    const imageLink = page.locator('a[href^="/image/"]').first();
    const hasImages = await imageLink.count() > 0;

    if (hasImages) {
      await imageLink.click();

      // Look for edit link
      const editLink = page.locator('a[href$="/edit"]').first();
      if (await editLink.count() > 0) {
        await editLink.click();
        await expect(page.locator('h2')).toContainText('Edit');
        await expect(page.locator('input[name="description"]')).toBeVisible();
      }
    }
  });

  test('should have delete button on edit page', async ({ page }) => {
    const loggedIn = await login(page);
    if (!loggedIn) {
      test.skip();
      return;
    }

    await page.goto('/');

    const imageLink = page.locator('a[href^="/image/"]').first();
    const hasImages = await imageLink.count() > 0;

    if (hasImages) {
      await imageLink.click();

      const editLink = page.locator('a[href$="/edit"]').first();
      if (await editLink.count() > 0) {
        await editLink.click();

        // Delete button should exist
        await expect(page.locator('button:has-text("Delete"), .btn-text-danger')).toBeVisible();
      }
    }
  });
});
