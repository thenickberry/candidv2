# CANDIDv2

[![Tests](https://github.com/thenickberry/candidv2/actions/workflows/tests.yml/badge.svg)](https://github.com/thenickberry/candidv2/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/php-8.3%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A PHP-based image management and cataloging system.

## Overview

CANDIDv2 is an image database application for organizing, searching, and browsing photo collections. Originally written in Perl by Ben Weir, rewritten in modern PHP.

## Features

- **Browse** — Navigate images through hierarchical categories with sort options
- **Search** — Find images by date range, photographer, description, or tagged people
- **Lightbox** — Full-screen carousel with keyboard and touch navigation
- **Bulk Upload** — Upload multiple images at once with automatic EXIF extraction (date taken, camera)
- **Bulk Edit** — Select multiple images to update metadata or rotate in one action
- **People Tagging** — Tag users in photos and filter search results by tagged people
- **Duplicate Detection** — MD5-based duplicate detection on upload
- **Admin Panel** — User management and trash/restore for soft-deleted content

## Requirements

- PHP 8.3+
- MariaDB 11+
- Caddy (or any web server) + PHP-FPM
- PHP extensions: `imagick`, `pdo_mysql`, `exif`, `zip`

## Quick Start (Docker)

```bash
# Clone the repository
git clone https://github.com/thenickberry/candidv2
cd candidv2

# Copy environment configuration
cp .env.example .env

# Start the containers
docker compose up -d

# Access the application
open http://localhost:8080
```

### Default Credentials

- **Username:** `admin`
- **Password:** `changeme`

> **Important:** Change the admin password immediately after first login.

### Docker Commands

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Rebuild image after Dockerfile changes
docker compose build --no-cache && docker compose up -d

# MariaDB CLI
docker compose exec db mariadb -u candid -p candid

# Reset database (destroys all data)
docker compose down -v && docker compose up -d
```

## Manual Installation

### 1. Configure Environment

```bash
cp .env.example .env
# Edit .env with your database credentials and settings
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Create Database

```bash
mariadb -u root -p < database/schema.sql
mariadb -u root -p candid < database/seed.sql
```

### 4. Configure Web Server

Point your document root to the `public/` directory. All requests must be routed through `public/index.php`.

**Caddy example:**
```
root * /path/to/candidv2/public
php_fastcgi unix//run/php/php-fpm.sock
file_server
try_files {path} /index.php?{query}
```

**Nginx example:**
```nginx
root /path/to/candidv2/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { fastcgi_pass unix:/run/php/php-fpm.sock; include fastcgi_params; }
```

### 5. Set Permissions

```bash
chmod -R 755 storage
chown -R www-data:www-data storage
```

## Configuration

All configuration is via environment variables in `.env`:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment (`development` / `production`) | `production` |
| `APP_DEBUG` | Show PHP errors | `false` |
| `DB_HOST` | Database hostname | `localhost` |
| `DB_NAME` | Database name | `candid` |
| `DB_USER` | Database username | `candid` |
| `DB_PASS` | Database password | — |
| `SESSION_LIFETIME` | Session lifetime in seconds | `86400` |

## Project Structure

```
candidv2/
├── bin/              # CLI utility scripts
├── bootstrap/
│   └── app.php       # Application bootstrap
├── config/           # Configuration
├── database/         # Schema and seed SQL
├── docker/           # Dockerfile, Caddyfile, php.ini
├── public/           # Web root (front controller + assets)
├── resources/        # View templates
├── src/              # Application code (PSR-4: App\)
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   ├── Entity/
│   ├── Exception/
│   └── Helper/
├── storage/          # Runtime files (uploads, images, cache)
└── tests/            # PHPUnit suites + Playwright E2E
    └── e2e/
```

## Testing

```bash
# PHPUnit — unit and integration tests (102 tests)
docker exec candidv2-app-1 php vendor/bin/phpunit

# PHPStan — static analysis (level 6)
docker exec candidv2-app-1 vendor/bin/phpstan analyse

# Playwright — E2E tests (40 tests × 4 browsers = 160 total)
npm install && npx playwright install
npx playwright test

# Single browser
npx playwright test --project=chromium
```

Tests run automatically via GitHub Actions on push/PR (PHP 8.3 and 8.4).

## Documentation

- [CHANGELOG.md](CHANGELOG.md) — Log of all changes with rationale
- [PHASES.md](PHASES.md) — History of the modernization effort

## License

MIT — see [LICENSE](LICENSE) for details.
