# CANDIDv2 Modernization Plan

**Generated:** 2026-02-14
**Codebase Version:** 2.50
**Target PHP Version:** 8.2+

---

## Executive Summary

CANDIDv2 is a PHP-based image management system originally written ~20 years ago. The codebase consists of **38 PHP files** totaling approximately **6,100 lines** of code. It requires comprehensive modernization to run on current PHP versions and address critical security vulnerabilities.

### Key Statistics

| Category | Count |
|----------|-------|
| PHP Files | 38 |
| Include Modules | 12 (2,776 lines) |
| Third-Party Dependencies | 5 |
| Security Vulnerabilities | 150+ |
| Deprecated Pattern Occurrences | 219 |

---

## 1. Project Overview

### Architecture

```
candidv2/
├── config.inc                 # Configuration (hardcoded credentials)
├── htdocs/                    # Web-accessible files
│   ├── index.php              # Homepage
│   ├── main.php               # Primary router (all major operations)
│   ├── login.php, register.php, search.php, browse.php
│   ├── image/                 # Image operations (add, edit, view, slideshow)
│   ├── category/              # Category management
│   ├── profile/               # User profiles
│   ├── comment/               # Comments
│   ├── js/                    # JavaScript (including 3rd-party)
│   └── themes/                # CSS themes (default, classic)
├── includes/                  # PHP library modules
│   ├── db.inc                 # Database (mysql_* functions)
│   ├── auth.inc               # Authentication/sessions
│   ├── template.inc           # HTML generation
│   ├── image.inc              # Image operations (1,014 lines - largest)
│   ├── category.inc           # Category operations
│   ├── query.inc              # Query building
│   └── [6 more modules]
├── scripts/                   # CLI maintenance utilities
└── legacy/                    # Deprecated files
```

### Core Features

- **Image Management** — Upload, edit metadata, tag people, rotate, search
- **Hierarchical Categories** — Tree-based organization with thumbnails
- **User System** — Registration, profiles, access levels (0-5)
- **Search** — By date, photographer, category, people, keywords
- **Comments** — With email notifications
- **Slideshow** — BarelyFitz-based image viewer

### Database Tables (Inferred)

- `user` — Users with access levels, preferences
- `session` — Active sessions
- `image_info` — Image metadata
- `image_file` — Binary image data (BLOB)
- `image_thumb` — Thumbnail data (BLOB)
- `image_people` — People tags
- `image_comment` — Comments
- `image_category` — Image-category links
- `category` — Hierarchical categories
- `history` — Audit log

---

## 2. Third-Party Dependencies

| Dependency | Version | Location | Status |
|------------|---------|----------|--------|
| jscalendar | 0.9.6 | htdocs/js/jscalendar-0.9.6/ | Replace with HTML5 `<input type="date">` |
| Treeview | 4.3 | htdocs/js/Treeview/ | Replace with vanilla JS |
| BarelyFitz Slideshow | 1.16 | htdocs/js/slideshow.js | Replace with vanilla JS |
| Mint Analytics | — | /mint/?js (external) | Remove |
| Smarty | — | Referenced, not bundled | Remove references |

### Files to Move to legacy/

- `htdocs/js/jscalendar-0.9.6/` (entire directory)
- `htdocs/js/Treeview/` (entire directory)
- `htdocs/js/slideshow.js`
- `htdocs/js/ftiens4.js` (duplicate)
- `htdocs/js/ua.js` (duplicate)

---

## 3. Security Vulnerabilities

### Critical (Immediate Action Required)

#### SQL Injection — 100+ instances

All database operations use string concatenation with `addslashes()`. Examples:

| File | Line | Code |
|------|------|------|
| auth.inc | 118 | `"WHERE username='$username' AND pword=OLD_PASSWORD('$password')"` |
| browse.php | 60 | `"WHERE parent=${cat_id}"` |
| theme_change.php | 5 | `"SET theme='${_GET['theme']}'"` |
| query.inc | 61-79 | All query parameters from $_GET |

**Fix:** Replace all mysql_* with PDO prepared statements.

#### Command Injection — 2 instances

| File | Line | Code |
|------|------|------|
| main.php | 147 | `system("rm -rf $destDir")` with user-controlled `$_POST['destDir']` |
| image/add.php | 128 | `shell_exec("find $destDir -print")` |

**Fix:** Use native PHP functions (RecursiveDirectoryIterator, glob).

#### XSS — 30+ instances

User input echoed without escaping:

| File | Line | Code |
|------|------|------|
| ajax.php | 44, 46, 49 | `<?= $_GET['id'] ?>` |
| register.php | 43-83 | Form values from $sql array |

**Fix:** Use `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` on all output.

### High Severity

#### No CSRF Protection

All 15+ forms lack CSRF tokens. Any state-changing operation can be triggered by malicious sites.

**Fix:** Implement token generation and validation.

#### Weak Password Hashing

Uses MySQL `PASSWORD()` and `OLD_PASSWORD()` functions (trivially reversible).

**Fix:** Use `password_hash()` / `password_verify()`.

#### Open Redirects — 8+ instances

| File | Line | Code |
|------|------|------|
| theme_change.php | 8 | `header("Location: ${_SERVER['HTTP_REFERER']}")` |
| main.php | 88 | `header("Location: ${_POST['refer_back']}")` |

**Fix:** Validate redirect URLs against allowed hosts.

#### Insecure Session IDs

```php
// auth.inc:106-109
$sid = rand(1000000000, 9999999999);  // Predictable!
```

**Fix:** Use `bin2hex(random_bytes(32))`.

### Medium Severity

- **Information Disclosure** — db_error() exposes query and error messages
- **Hardcoded URLs** — http://candid.scurvy.net in multiple files
- **No File Upload Validation** — Missing magic byte checking

---

## 4. Deprecated PHP Patterns

### Blocking PHP 7.0+ (Must Fix)

| Pattern | Count | Files | Fix |
|---------|-------|-------|-----|
| mysql_* functions | 107 | 18 | Migrate to PDO |
| split() | 6 | 5 | Use explode() |
| ereg_replace() | 1 | 1 | Use preg_replace() |

### Blocking PHP 8.x

| Pattern | Count | Files | Fix |
|---------|-------|-------|-----|
| $HTTP_POST_FILES | 3 | 1 | Use $_FILES |
| ${var} interpolation | 91 | 24 | Use {$var} or concatenation |
| $$var variable variables | 2 | 2 | Use arrays |
| implode() reversed args | 2 | 2 | Fix argument order |

### Code Quality Issues

- No type declarations (80+ functions)
- No exception handling (7 die()/exit() calls)
- Global state everywhere (global keyword abuse)
- No PSR-4 autoloading
- No test suite

---

## 5. Files Requiring Changes

### High Priority (Security + PHP 7.0 Compatibility)

| File | Lines | Issues |
|------|-------|--------|
| includes/db.inc | 71 | mysql_*, SQL injection |
| includes/auth.inc | 158 | SQL injection, weak hashing, insecure session |
| includes/query.inc | 308 | SQL injection throughout |
| includes/image.inc | 1,014 | mysql_*, potential injection |
| htdocs/main.php | — | Command injection, open redirects |
| htdocs/browse.php | — | 25 mysql_* calls, SQL injection |

### Medium Priority (PHP 8.x Compatibility)

All 38 PHP files need ${var} interpolation fixes.

### Dead Code to Remove

| File | Status |
|------|--------|
| htdocs/new-browse.php | Unused experimental |
| htdocs/search-test.php | Test file |
| htdocs/ajax.phps | Source display |
| htdocs/devel/ | Development utilities |
| *.BAK files | Backups |

---

## 6. Implementation Roadmap

### Phase 2: Docker Environment

- [ ] Dockerfile (PHP 8.2 + Apache + extensions)
- [ ] docker-compose.yml (PHP + MySQL 8.0)
- [ ] .env.example, .gitignore
- [ ] database/schema.sql, database/seed.sql
- [ ] storage/ directories

### Phase 3: PHP Modernization

#### 3a: Database Layer
- [ ] Create Database.php (PDO singleton)
- [ ] Rewrite db.inc with prepared statements
- [ ] Migrate 107 mysql_* calls to PDO
- [ ] Replace PASSWORD() with password_hash()

#### 3b: Security Fixes
- [ ] CSRF token system
- [ ] XSS escaping helper (h() function)
- [ ] File upload validation
- [ ] Remove system()/shell_exec() calls
- [ ] Fix open redirects

#### 3c: Language Modernization
- [ ] Fix all deprecated patterns (219 occurrences)
- [ ] Add type declarations
- [ ] Replace global with dependency injection
- [ ] Add namespaces + PSR-4

#### 3d: Dead Code Cleanup
- [ ] Move unused files to legacy/
- [ ] Remove Mint analytics
- [ ] Fix hardcoded URLs

### Phase 4: Backend Improvements

- [ ] Filesystem-based image storage (replace BLOBs)
- [ ] Invite-only registration
- [ ] Admin user management

### Phase 5-6: Frontend Modernization

- [ ] Replace jscalendar with HTML5 date inputs
- [ ] Replace Treeview with vanilla JS
- [ ] Replace BarelyFitz with vanilla JS carousel
- [ ] CSS Grid layouts, responsive design

### Phase 7: Testing

- [ ] PHPUnit setup
- [ ] Unit tests for core modules
- [ ] Integration tests
- [ ] CI/CD pipeline

---

## 7. Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data loss during migration | High | Backup database before changes |
| Breaking existing functionality | Medium | Thorough testing at each phase |
| Security exposure during transition | High | Complete Phase 3b before deployment |
| Image BLOB migration issues | Medium | Test with sample data first |

---

## 8. Success Criteria

- [ ] Application runs on PHP 8.2+
- [ ] Zero critical security vulnerabilities
- [ ] All deprecated patterns resolved
- [ ] Docker-based development environment
- [ ] Unit test coverage > 60%
- [ ] No third-party JavaScript dependencies
- [ ] Images stored on filesystem (not BLOBs)

---

## Appendix: Vulnerability Summary by File

| File | SQL Injection | XSS | Command Inj. | Other |
|------|--------------|-----|--------------|-------|
| includes/auth.inc | 3 | — | — | Weak crypto, sessions |
| includes/query.inc | 10+ | — | — | — |
| includes/image.inc | 5+ | — | — | — |
| htdocs/browse.php | 10+ | — | — | — |
| htdocs/main.php | 2 | — | 1 | Open redirects |
| htdocs/ajax.php | — | 3 | — | — |
| htdocs/register.php | 2 | 5+ | — | — |
| htdocs/theme_change.php | 1 | — | — | Open redirect |
| htdocs/comment/delete.php | 2 | — | — | — |
