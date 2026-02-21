# Phases

## Phase 1: Assessment & Planning
- [x] Read the full codebase and produce a project overview
- [x] Identify all third-party dependencies (jscalendar 0.9.6, Treeview/treemenu.net, BarelyFitz Slideshow, Mint analytics)
- [x] Catalog security vulnerabilities (SQL injection via `mysql_*`, XSS, CSRF, command injection, open redirects, file upload validation)
- [x] Catalog deprecated PHP patterns (`mysql_*`, short open tags, `split()`, `ereg_replace()`, `$HTTP_POST_FILES`, `$$var['key']`)
- [x] Produce detailed MODERNIZATION_PLAN.md based on all tasks listed

---

## Phase 2: Documentation & Build
- [x] Create Dockerfile (PHP 8.2 + Apache with GD, PDO_MySQL, EXIF, zip extensions)
- [x] Create docker-compose.yml (PHP+Apache and MySQL 8.0, one-command dev environment)
- [x] Create docker/php.ini with upload limits and error reporting overrides
- [x] Create .env.example with environment configuration template
- [x] Create .gitignore for .env, storage files, IDE files, Docker volumes, vendor/
- [x] Create database/schema.sql (canonical schema reconciled from setup.php)
- [x] Create database/seed.sql (default admin user with bcrypt password)
- [x] Create storage/images/.gitkeep and storage/uploads/.gitkeep
- [x] Update README.md with Docker quick-start and production deployment instructions

---

## Phase 3: PHP Modernization

### 3a: Code Restructuring
- [x] Create new directory structure:
  ```
  candidv2/
  ├── public/                  # Web root (rename from htdocs/)
  │   ├── index.php            # Front controller (single entry point)
  │   ├── assets/              # Static files (CSS, JS, images)
  │   │   ├── css/
  │   │   ├── js/
  │   │   └── images/
  │   └── themes/
  ├── src/                     # Application code (PSR-4: App\)
  │   ├── Controller/          # Request handlers
  │   ├── Service/             # Business logic
  │   ├── Repository/          # Database queries
  │   ├── Entity/              # Data models
  │   └── Helper/              # Utility functions
  ├── config/                  # Configuration files
  │   └── config.php
  ├── templates/               # View templates
  ├── storage/                 # Runtime files
  │   ├── uploads/
  │   ├── images/
  │   ├── cache/
  │   └── logs/
  ├── database/                # Schema and migrations
  ├── scripts/                 # CLI utilities
  ├── tests/                   # Test suites
  ├── legacy/                  # Deprecated code
  └── vendor/                  # Composer dependencies
  ```
- [x] Create composer.json with PSR-4 autoloading (namespace: `App\`)
- [x] Create front controller (public/index.php) with router
- [x] Create base Controller class with common functionality
- [x] Move includes/*.inc to src/ as namespaced classes:
  - [x] db.inc → src/Service/Database.php
  - [x] auth.inc → src/Service/Auth.php
  - [x] template.inc → src/Service/Template.php
  - [x] image.inc → (functionality in ImageController)
  - [x] category.inc → src/Service/CategoryService.php
  - [x] query.inc → (replaced by PDO in Database.php)
  - [x] upload.inc → (functionality in ImageController::add)
  - [x] import.inc → (deprecated, see Phase 3c)
  - [x] profile.inc → src/Service/ProfileService.php
  - [x] comment.inc → src/Service/CommentService.php
  - [x] history.inc → src/Service/HistoryService.php
  - [x] misc.inc → (superseded by Controller base class + functions.php)
- [x] Convert page files to Controllers:
  - [x] htdocs/main.php → src/Controller/MainController.php
  - [x] htdocs/login.php → src/Controller/AuthController.php
  - [x] htdocs/register.php → src/Controller/AuthController.php
  - [x] htdocs/search.php → src/Controller/SearchController.php
  - [x] htdocs/browse.php → src/Controller/BrowseController.php
  - [x] htdocs/image/*.php → src/Controller/ImageController.php
  - [x] htdocs/category/*.php → src/Controller/CategoryController.php
  - [x] htdocs/profile/*.php → src/Controller/ProfileController.php
  - [x] htdocs/comment/*.php → src/Controller/CommentController.php
- [x] Move view logic to templates/ directory
- [x] Create src/Helper/functions.php for global helper functions (h(), csrf_token(), etc.)
- [x] Update all internal paths and references
- [x] Move original htdocs/ to legacy/htdocs/ (preserve for reference)

### 3b: Replace mysql_* with PDO Prepared Statements
- [x] Create src/Service/Database.php — PDO wrapper with query(), lastInsertId(), getConnection() (completed in 3a)
- [x] Implement parameterized queries (Database.php provides this directly; separate QueryBuilder not needed)
- [x] Update config/config.php — .env loading, auto-detect base paths (completed in 3a)
- [x] Migrate all `mysql_*` calls to PDO prepared statements (new code uses PDO; legacy in legacy/)
- [x] Replace `PASSWORD()`/`OLD_PASSWORD()` with `password_hash()`/`password_verify()` (Auth.php uses bcrypt)
- [x] Replace `split()` with `explode()` throughout (no split() in new code)
- [x] Remove `addslashes()` calls (no addslashes() in new code)

### 3c: Security Fixes
- [x] Create CSRF token generation and validation (in functions.php: csrf_token(), csrf_field(), verify_csrf())
- [x] Add CSRF protection to all POST forms and POST handlers (validateCsrf() in all controllers)
- [x] Add XSS output escaping (`h()` helper) on all user-supplied output (h() in functions.php, used in all templates)
- [x] Add file upload validation (magic byte detection via finfo, filename sanitization in ImageController)
- [x] Replace all `system()`/`shell_exec()` calls with native PHP equivalents (none exist in src/)
- [x] Fix open redirects with `safe_redirect()` function (redirect() in functions.php validates same-host)
- [x] Remove MMS/email import features (import.inc already in legacy/, not used in src/)

### 3d: PHP Language Modernization
- [x] Fix `ereg_replace()` → `preg_replace()` (none in src/)
- [x] Fix `$HTTP_POST_FILES` → `$_FILES` (none in src/)
- [x] Fix variable-variable `$$var['key']` → `${$var['key']}` (none in src/)
- [x] Fix `implode()` single-argument calls (none in src/, all use correct syntax)
- [x] Fix `displayImage()` float-to-int deprecation (not applicable, new code)
- [x] Fix all short open tags (`<? ` → `<?php `) (none in src/ or templates/)
- [x] Fix deprecated `${var}` string interpolation → `{$var}` (none in src/)
- [x] Fix superglobal function parameters (none in src/)
- [x] Fix illegal `global $_SERVER` / `global $_COOKIE` declarations (none in src/)
- [x] Fix `implode($array, $glue)` argument order (all correct in src/)
- [x] Remove `global` keyword usage — use dependency injection (none in src/)
- [x] Add type declarations to all functions (new code has types)
- [x] Replace `die()`/`exit()` with exception hierarchy (HttpException, ForbiddenException)

### 3e: Dead Code Cleanup
- [x] Move unused .BAK files, dead pages (new-browse.php, search-test.php, ajax.phps, devel/) to legacy/ (already in legacy/htdocs/)
- [x] Remove defunct Mint analytics script include (none in src/ or templates/)
- [x] Fix hardcoded URLs (candid.scurvy.net) to use config-based URLs (none in src/, templates/, or config/)

---

## Phase 4: Backend feature improvements

### 4a: Filesystem-Based Image Storage
- [x] Create ImageStorage.php — hash-based sharding for image files
- [x] Migrate image storage from database BLOBs to filesystem (ImageController updated, migration script created)
- [x] Update ImageController to use ImageStorage service (legacy code in legacy/ unchanged)
- [x] Create migration script: scripts/migrate_images_to_filesystem.php

### 4b: Invite-Only Registration
- [x] Replace open registration with admin-only user creation (registration redirects to login with message)
- [x] Add `must_change_password` column to user table
- [x] Create AdminController with user management (list, create, edit, delete)
- [x] Create UserService for user operations
- [x] Forced password change redirect in Controller::requireAuth()

---

## Phase 5: Dependency Reduction

### 5a: Replace jscalendar
- [x] Replace jscalendar 0.9.6 date picker with native HTML5 `<input type="date">` (new templates use HTML5 date)
- [x] Find all input fields handling date and make sure the type is updated to `date` (done: image/add.php, search/index.php)
- [x] Remove jscalendar script includes from template.inc (not included in new templates)
- [x] Move htdocs/js/jscalendar-0.9.6/ to legacy/ (already in legacy/)

### 5b: Replace Treeview
- [x] Create vanilla JS tree view (not needed - categories use simple HTML lists)
- [x] Move htdocs/js/Treeview/ to legacy/ (already in legacy/)

### 5c: Replace BarelyFitz Slideshow
- [x] Slideshow functionality not included in new code (can be added later if needed)
- [x] Move htdocs/js/slideshow.js to legacy/ (already in legacy/)

---

## Phase 6: CSS Consolidation & Responsive Design

### 6a: CSS Consolidation
- [x] Rewrite CSS with CSS custom properties (templates/layouts/main.php has modern CSS with custom properties)
- [x] Remove obsolete jscalendar CSS rules (not included in new templates)
- [x] Extract inline `style=` attributes into named CSS classes (all templates converted)
- [x] Consolidate to single default theme; move classic theme to legacy/ (already in legacy/)

### 6b: Replace Table Layouts
- [x] Modern CSS Grid and Flexbox layout classes (.grid-2, .flex-between, .flex-center)
- [x] Image grid with CSS Grid (.image-grid with auto-fill)
- [x] Tables use semantic HTML with .table class
- [x] Header nav uses flexbox

### 6c: Responsive Design
- [x] Add viewport meta tag (in templates/layouts/main.php)
- [x] Add responsive media queries (768px and 480px breakpoints)
- [x] Mobile-friendly table styles with .hide-mobile class
- [x] Responsive button and card sizing

---

## Phase 7: Testing

- [x] Add composer.json with PHPUnit 10.5 dev dependency (already configured)
- [x] Create phpunit.xml with unit and integration test suites
- [x] Create tests/bootstrap.php for test environment setup
- [x] Write unit tests
  - HelperFunctionsTest (h(), csrf_*, config(), format_date/datetime())
  - ImageStorageTest (store, retrieve, delete, exists)
  - UserServiceTest (CRUD operations with mocked database)
  - CategoryServiceTest (getAll, getTree, find, getImages, countImages, getBreadcrumb)
- [x] Write integration tests
  - DatabaseTest (connection, query, transactions)
- [x] Create .github/workflows/tests.yml for CI (GitHub Actions)
  - Matrix testing for PHP 8.2 and 8.3
  - MySQL 8.0 service for integration tests
  - PHP syntax linting
- [x] Update README.md with instructions for running the test suite

---

## Phase 8: Framework Evaluation
- [x] Evaluate Slim 4, Flight, and frameworkless approaches
- [x] Document recommendation in FRAMEWORK_EVALUATION.md
- **Recommendation:** Continue frameworkless; migration cost exceeds benefit for this application's scope

---

## New Features

### Feature 1: Image Lightbox with Carousel
- [x] Create lightbox overlay component (vanilla JS)
- [x] Click image thumbnail to open in overlay instead of navigating to detail page
- [x] Add left/right navigation arrows for carousel
- [x] Add keyboard navigation (arrow keys, Escape to close)
- [x] Preload adjacent images for smooth navigation
- [x] Display image metadata (description, date, photographer) in overlay
- [x] Add close button and click-outside-to-close behavior
- [x] Maintain URL state (update URL hash for shareable links)
- [x] Ensure mobile-friendly touch gestures (swipe left/right)
- [x] Enhanced metadata display (date taken, photographer, camera model)
- [x] Action links (Details, Edit) with permission-based visibility
- [x] Data attributes on image cards for metadata extraction

### Feature 2: Duplicate Detection on Upload
- [x] Compute MD5 hash of uploaded image files
- [x] Check for existing images with same hash in the target category
- [x] Skip duplicates and report count in flash message
- [x] Store md5_hash in image_info table for all uploads
- [x] Migration script to backfill MD5 hashes for existing images

### Feature 3: Category Image Sorting
- [x] Add sort dropdown to category view (Date Taken, Date Added, Description)
- [x] Sort selection auto-submits form
- [x] Maintain sort preference via query parameter
- [x] Default sort order per category (stored in sort_by column)
- [x] Category edit page includes sort order dropdown

### Feature 4: Enhanced Testing Infrastructure
- [x] PHPStan static analysis (level 6)
  - [x] Added phpstan/phpstan to composer.json
  - [x] Created phpstan.neon configuration
  - [x] Added to CI workflow
- [x] Additional integration tests
  - [x] AuthServiceTest (login, logout, password hashing, admin checks)
  - [x] UserServiceTest (CRUD with real database)
  - [x] ImageStorageTest (filesystem operations)
- [x] Playwright E2E testing
  - [x] Created package.json with Playwright dependency
  - [x] Created playwright.config.ts (Chrome, Firefox, Safari, mobile)
  - [x] Auth tests (login, logout, registration redirect)
  - [x] Browse tests (categories, lightbox navigation)
  - [x] Search tests (form, filters, results)
  - [x] Image tests (upload, edit, delete)
  - [x] Added E2E job to CI workflow
- [x] Test counts: 102 PHPUnit tests, 31 Playwright E2E tests

### Feature 5: Soft-Delete with Trash Management
- [x] Soft-delete for categories and images
  - [x] Added deleted_at and deleted_by columns to category and image_info tables
  - [x] Created migration 003_add_soft_delete.sql
  - [x] Categories and images marked as deleted instead of permanent removal
  - [x] Deleted items hidden from all browse/search views
- [x] ImageService for image operations
  - [x] softDelete(), restore(), hardDelete(), getDeleted() methods
  - [x] Registered in Container
- [x] CategoryService soft-delete methods
  - [x] softDelete() deletes category tree and orphaned images
  - [x] restore() restores category (moves to root if parent deleted)
  - [x] hardDelete() for permanent removal
  - [x] getDeleted(), countDeleted(), getDeletionStats()
- [x] Admin Trash page
  - [x] View all soft-deleted categories and images
  - [x] Tabbed interface (Categories/Images)
  - [x] Individual restore and permanent delete actions
  - [x] Bulk restore and delete with checkboxes
  - [x] Empty Trash button to purge all
  - [x] Trash link in admin navigation
- [x] Category delete confirmation modal
  - [x] Shows count of subcategories and images affected
  - [x] JSON endpoint /category/{id}/deletion-stats
  - [x] Modal replaces browser alert dialog
- [x] Unit tests for ImageService and CategoryService soft-delete methods

### Feature 6: Upload Improvements
- [x] Upload progress indicator
  - [x] Progress bar with percentage during file upload
  - [x] XMLHttpRequest with upload progress event tracking
  - [x] Visual feedback while processing
- [x] Inline category creation
  - [x] "New" button next to category dropdown on upload page
  - [x] Modal to create category without leaving page
  - [x] JSON endpoint POST /category/add-json
  - [x] New category automatically selected after creation
- [x] Increased upload limits
  - [x] max_file_uploads increased from 20 to 100
  - [x] post_max_size increased from 55M to 500M

### Feature 7: Navigation Improvements
- [x] Profile dropdown menu
  - [x] Profile icon (person silhouette) triggers hover dropdown
  - [x] Contains Profile, Logout links
  - [x] Admin section with Users and Trash (admin only)
  - [x] Login/Register for logged-out users

### Feature 8: Image Page Layouts
- [x] Two-column layout for image view and edit pages
  - [x] Image displayed on left (main content area)
  - [x] View page: metadata and comments in sidebar on right
  - [x] Edit page: form fields in sidebar on right, full-size image with rotate button
  - [x] CSS Grid layout with responsive breakpoint at 900px
  - [x] Stacks vertically on mobile devices

### Feature 9: Configurable Thumbnail Settings
- [x] Thumbnail dimensions and quality in config/config.php
  - [x] thumb_width, thumb_height, thumb_quality settings
  - [x] Environment variable support (THUMB_WIDTH, THUMB_HEIGHT, THUMB_QUALITY)
  - [x] Default: 400x400 at 100% quality
- [x] ImageController uses config values
  - [x] Added config() helper to base Controller class
  - [x] createThumbnail() reads from config instead of hardcoded values
- [x] regenerate_thumbnails.php script uses config values
  - [x] Displays configured dimensions and quality in output

---

## Missed Tasks

Tasks discovered during implementation that were not in the original plan:

- [x] **Image edit functionality** — /image/{id}/edit route, showEdit()/edit() controller methods, and templates/image/edit.php were missing from original plan
- [x] **Route ordering fix** — Specific routes (/image/add) must come before parameterized routes (/image/{id}) in public/index.php to avoid 404 errors
- [x] **Bcrypt hash in seed.sql** — Placeholder hash was invalid; regenerated correct hash for "changeme" password
- [x] **Controller method naming conflict** — ImageController::view() conflicted with parent Controller::view(); renamed to detail()
- [x] **Category add/edit UI links** — Routes and controller existed but browse templates lacked add/edit links; added links and canEdit/canAddCategory flags to BrowseController
- [x] **Test fixtures not matching implementation** — Unit tests used wrong method mocks (query vs fetchAll/fetchOne), wrong path format for ImageStorage, wrong date format, wrong HTML entity encoding; fixed all tests to match actual implementation
- [x] **Test files not mounted in Docker** — phpunit.xml and tests/ directory missing from docker-compose.yml volumes
- [x] **Bulk image upload** — Upload multiple images at once instead of one at a time
- [x] **Default description from filename** — Pre-populate description field with the image filename (minus extension)
- [x] **EXIF date pre-population** — Use EXIF DateTimeOriginal to pre-fill date_taken field on upload
- [x] **User management navigation links** — Admin and Profile links missing from header navigation; functionality existed but wasn't accessible from UI
- [x] **People tagging UI** — Database table existed for tagging users in images but no UI to manage tags; added multi-select on image edit page
- [x] **Search by tagged people** — Added "People Tagged" multi-select filter to search form to find images where any of the selected users are tagged
- [x] **Upload button in category view** — Added Upload button that pre-selects the current category in the upload form
- [x] **Image deletion** — No way to delete images; added delete route, controller method, and button on edit page
- [x] **Return to category after edit/delete** — After editing or deleting an image, redirect back to the category the user was browsing
- [x] **Button styling inconsistency** — Many submit buttons missing `.btn` class; standardized across all 17 templates
- [x] **Button layout standardization** — Shortened button text (Save/Cancel/Create/Delete), consistent `.btn-group` layout with Save/Cancel left and Delete right, delete buttons use `.btn-text-danger` style
- [x] **Image rotation** — Added rotate CW button on image edit page; rotates full image and regenerates thumbnail
- [x] **Edit icon standardization** — Use pencil SVG icon with `.action-icon` class for edit actions (used in admin/users, image view, category breadcrumb)
- [x] **Bulk image editing** — Mass edit metadata for multiple images from search results or category browse:
  - Add/remove people tags
  - Update date taken
  - Update description
  - Rotate images
  - Update photographer
  - Change category
  - Set access level
  - Set private flag
  - UI: checkboxes on image grids, bulk actions bar, bulk edit page
  - Routes: GET/POST /image/bulk/edit, POST /image/bulk/rotate
  - E2E tests: 4 tests for bulk edit functionality
- [x] **Intervention Image migration** — Consolidated all image processing to use Intervention Image v3:
  - Replaced GD extension with Intervention Image using Imagick driver
  - Cleaner thumbnail generation with automatic EXIF orientation
  - Native HEIC format support
  - Updated ImageController with imageManager() and createThumbnail() methods
  - Updated regenerate_thumbnails.php script
  - Updated Dockerfile (removed GD, kept imagick)
  - Updated composer.json (removed ext-gd, added intervention/image)
- [x] **Lazy loading images** — Added native `loading="lazy"` attribute to image thumbnails:
  - Applied to browse/category.php, search/results.php, image/bulk-edit.php
  - Improves initial page load performance
- [x] **Expandable flash message details** — Flash messages can include collapsible details:
  - Upload feedback shows skipped duplicates and failed files
  - Details section is collapsed by default
- [x] **Removed query limits** — Browse and search pages show all results:
  - Category browse: removed LIMIT 50
  - Search results: removed LIMIT 100
- [x] **Full datetime for date_taken** — Store time component from EXIF:
  - Changed date_taken column from DATE to DATETIME
  - EXIF DateTimeOriginal now stored as 'Y-m-d H:i:s'
  - Migration 005_date_taken_datetime.sql
  - Backfill script scripts/backfill_date_times.php
- [x] **Modal dialog system** — Replaced all JavaScript alerts/confirms with modals:
  - Created public/assets/js/modal.js with Modal.alert(), Modal.confirm(), Modal.confirmDanger()
  - Added modal CSS to templates/layouts/main.php
  - Uses data-confirm attributes for declarative form/button confirmation
  - Updated 7 templates: browse/category, search/results, image/edit, image/bulk-edit, image/view, admin/users, admin/trash
- [x] **Upload progress indicator improvements** — Better feedback during file processing:
  - Shimmer animation for processing state after upload completes
  - Descriptive text during thumbnail generation
  - Spinner icon during processing
- [x] **Bulk action UI improvements** — Cleaner interface per Elastic UI guidelines:
  - Replaced Select text button with grid+checkmark icon
  - Replaced Done button with X icon on right side
  - Header buttons hidden when category has no images
- [x] **Removed unused profile fields** — Cleaned up user profile:
  - Removed numrows/numcols form fields from edit page
  - Removed from controller and service update methods
