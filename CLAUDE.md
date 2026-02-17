# CANDIDv2 — Project Context

## What This Is
CANDIDv2 is a modern PHP-based image management application.

## Key Files
- PHASES.md — The roadmap for all changes, with status tracking
- CHANGELOG.md — Running log of all modifications with dates and rationale

## Working Rules
- Log every change to CHANGELOG.md before considering a task complete.
- Update PHASES.md when adding new features or completing tasks.
- Run tests after any code changes (see Testing section below).
- Ask before making architectural or UX-breaking changes.
- Prioritize backward compatibility.
- Target the latest stable PHP version.
- Strive for good test coverage: unit tests, integration tests, end-to-end testing, etc.
- Reduce dependencies where possible; prefer native browser/CSS/PHP solutions.
- When recommending a framework, justify it against the cost of added complexity.

## Testing

Run these after making code changes:

### PHP (required after any PHP changes)
```bash
# Unit and integration tests (102 tests)
docker exec candidv2-app-1 php vendor/bin/phpunit

# Static analysis (level 6)
docker exec candidv2-app-1 vendor/bin/phpstan analyse
```

### E2E (run after significant UI/flow changes)
```bash
# Install dependencies (first time only)
npm install
npx playwright install

# Run all E2E tests (31 tests × 4 browsers = 124 total)
npx playwright test

# Run specific browser only
npx playwright test --project=chromium

# Run with visible browser
npx playwright test --headed

# Debug mode
npx playwright test --debug
```

### CI
All tests run automatically on push/PR via GitHub Actions:
- PHPUnit (PHP 8.3 and 8.4)
- PHPStan
- Playwright E2E (after PHPUnit and lint pass)

## UI Guidelines

### Button Patterns (based on Elastic UI)

**One primary button per context** — Each page, modal, or form should have only one filled/primary button to avoid competing for attention.

**Button types:**
- `.btn` (primary/filled) — Reserved for the main action (Save, Upload, Create)
- `.btn-secondary` — Secondary actions (Cancel, Edit, Select)
- `.btn-danger` — Destructive actions in modals (Delete, Empty Trash)
- `.btn-text-danger` — Less prominent destructive actions (inline Delete links)
- `.action-icon` — Icon-only buttons for table rows and inline actions

**Button placement:**
- **Modals/popovers:** Buttons bottom-right, primary action on far right
- **Forms:** Save/Cancel left, Delete right (using `.btn-group` layout)
- **Page headers:** Action buttons (Upload, Create) positioned top-right
- **Empty states:** Center-align the call-to-action button

**Icon buttons:**
- Use only when the symbol is immediately recognizable (trash, pencil, rotate)
- Never use for primary actions
- Always include `title` attribute for tooltip

### Modal Patterns

- Use `data-confirm` attributes for confirmation dialogs
- Add `data-confirm-danger` for destructive action styling
- Modal CSS is in `layouts/main.php`, JS in `public/assets/js/modal.js`

## Task Completion Checklist

Before considering any task complete, verify:

1. **Tests pass** — Run PHPUnit and PHPStan (see Testing section)
2. **Tests added** — Add unit/integration tests for new functionality
3. **CHANGELOG.md updated** — Log the change with date and description
4. **PHASES.md updated** — Mark tasks complete or add new features to the list
5. **Test counts updated** — If tests were added, update counts in CLAUDE.md and PHASES.md
