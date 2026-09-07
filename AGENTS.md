# Agents Guide for Sport Center Sampit

## Development Commands

**Setup (fresh install)**
```bash
composer run setup
```
- Runs: composer install, generates app key, runs migrations, npm install, builds assets

**Development Server**
```bash
composer run dev
```
- Runs 3 processes concurrently: PHP artisan serve (port 8000), queue worker, and Vite dev server
- Named processes: 'server', 'queue', 'vite'

**Testing & Quality**
```bash
# Full test suite with lint and type checks
composer run test

# CI check (npm lint + format + types + tests)
composer run ci:check

# PHP linting only
composer run lint

# PHP lint dry-run  
composer run lint:check

# PHP type checking
composer run types:check

# Vue/TS linting
npm run lint

# Format check
npm run format:check

# Vue type checking
npm run types:check
```

## Architecture & Stack

- **Backend**: Laravel 13 + Inertia.js server-side
- **Frontend**: Vue 3 + TypeScript + Vite
- **Testing**: Pest PHP testing framework
- **Database**: MySQL (DB: sport_center, user: dev, pass: root)
- **Session/Queue**: Database-backed
- **Styling**: Tailwind CSS 4.x

## Database Configuration

⚠️ **Important**: .env.example shows SQLite but active .env uses MySQL:
- Connection: DB_CONNECTION=mysql
- Database: sport_center
- Credentials: dev/root

Run migrations after switching databases:
```bash
php artisan migrate --force
```

## Asset Pipeline

- Vite builds to public/build
- Font optimization via Bunny (Instrument Sans weights 400, 500, 600)
- Auto-refresh enabled for development
- SSR build: npm run build:ssr

## Entry Points

- **Web routes**: routes/web.php (Inertia routes only)
- **SPA pages**: resources/js/Pages/*.vue
- **Middleware**: Custom Inertia middleware in app/Http/Middleware/HandleInertiaRequests.php
- **Root template**: resources/views/app.blade.php

## Framework Quirks

- **Inertia SSR**: Root view is 'app' (not 'app.blade.php')
- **Queue retries**: Configured to 1 try (--tries=1)
- **Font loading**: Uses Bunny CDN with specific weight configs
- **Tailwind**: Uses newer v4 API with Vite plugin
- **Vue forms**: Wayfinder integration with form variants

## Environment Requirements

- PHP 8.3+
- Node.js (latest LTS)
- MySQL 5.7+ or equivalent
- Redis (for caching/sessions)
- Ensure database exists before running migrations

## Key Directories

- resources/js/ - Vue application
- resources/views/ - Blade templates
- app/Http/Controllers/ - Controllers
- app/Models/ - Eloquent models
- database/ - Migrations & seeders
- tests/ - Pest tests
