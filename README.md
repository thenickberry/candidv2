# CANDIDv2

A PHP-based image management and cataloging system.

## Overview

CANDIDv2 is an image database application that allows users to organize, search, and browse photo collections. Originally written in Perl by Ben Weir, it has been rewritten in PHP.

## Features

- **Search** — Find images by date, photographer, description, or tagged people
- **Browse** — Navigate images through hierarchical categories with sorting options
- **Lightbox** — View images in a carousel with keyboard/touch navigation
- **Bulk Upload** — Upload multiple images with EXIF metadata extraction
- **People Tagging** — Tag users in photos and search by tagged people
- **Duplicate Detection** — Automatic MD5-based duplicate detection on upload

## Requirements

- PHP 8.3+
- MySQL 8.0+
- Apache with mod_rewrite
- PHP Extensions: GD, PDO_MySQL, EXIF, zip

## Quick Start (Docker)

The fastest way to get started is with Docker:

```bash
# Clone the repository
git clone <repository-url>
cd candidv2

# Copy environment configuration
cp .env.example .env

# Start the containers
docker compose up -d

# Access the application
open http://localhost:8080
```

### Default Credentials

- **Username:** admin
- **Password:** changeme

**Important:** Change the admin password immediately after first login.

### Docker Commands

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Rebuild after changes
docker compose build --no-cache
docker compose up -d

# Access MySQL CLI
docker compose exec db mysql -u candid -p candid

# Reset database (warning: destroys data)
docker compose down -v
docker compose up -d
```

## Manual Installation

### 1. Configure Environment

```bash
cp .env.example .env
# Edit .env with your database credentials
```

### 2. Create Database

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p candid < database/seed.sql
```

### 3. Configure Apache

Point your document root to the `public/` directory and ensure mod_rewrite is enabled.

Example virtual host:

```apache
<VirtualHost *:80>
    ServerName candid.local
    DocumentRoot /path/to/candidv2/public

    <Directory /path/to/candidv2/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Set Permissions

```bash
# Make storage directories writable
chmod -R 755 storage
chown -R www-data:www-data storage
```

## Configuration

Configuration is managed through environment variables. See `.env.example` for available options:

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database hostname | `localhost` |
| `DB_NAME` | Database name | `candid` |
| `DB_USER` | Database username | `candid` |
| `DB_PASS` | Database password | — |
| `DEFAULT_THEME` | UI theme | `default` |
| `MAX_UPLOAD_SIZE` | Max upload file size | `50M` |

## Project Structure

```
candidv2/
├── public/           # Web root (front controller)
├── src/              # Application code (PSR-4: App\)
│   ├── Controller/   # Request handlers
│   ├── Service/      # Business logic
│   ├── Exception/    # Custom exceptions
│   └── Helper/       # Utility functions
├── config/           # Configuration files
├── templates/        # View templates
├── database/         # Schema and seeds
├── docker/           # Docker configuration
├── storage/          # Runtime files (uploads, images, cache)
├── scripts/          # CLI utilities
├── tests/            # PHPUnit test suites
└── legacy/           # Deprecated code (reference only)
```

## Testing

```bash
# PHPUnit (79 tests)
docker exec candidv2-app-1 php vendor/bin/phpunit

# PHPStan static analysis
docker exec candidv2-app-1 vendor/bin/phpstan analyse

# Playwright E2E (27 tests) - requires npm install first
npm install && npx playwright install
npx playwright test
```

Tests run automatically via GitHub Actions on push/PR (PHP 8.2, 8.3).

## Documentation

- [PHASES.md](PHASES.md) — Roadmap for ongoing updates
- [CHANGELOG.md](CHANGELOG.md) — Log of all modifications
- [MODERNIZATION_PLAN.md](MODERNIZATION_PLAN.md) — Technical assessment and plan

## License

See LICENSE file for details.
