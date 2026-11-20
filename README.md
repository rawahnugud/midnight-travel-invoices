# Midnight Travel — Invoice Management System (Laravel)

The Laravel application serves English and Arabic invoices. Start here for local development. Use [the cPanel guide](CPANEL_DEPLOY.md) when preparing a release. The Node.js implementation is legacy.

## Features

- **Authentication:** Login with username/password, session-based
- **Roles:** Admin (full access), Staff (create & edit own invoices), Viewer (read-only)
- **Dashboard:** Total/paid/pending invoices, revenue, recent list
- **Invoices:** Create, edit, view, delete, print/PDF-ready
- **Users:** Admin can manage users (create, edit, deactivate). Deactivation keeps the original invoice author and all line items.

## Requirements

- PHP 8.2.30+
- Composer
- SQLite (default) or MySQL
- Laravel 12

## Quick start (local)

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Create SQLite database file (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed users and sample invoices (optional)
php artisan db:seed
```

Then start the built-in server:

```bash
php artisan serve
```

Open **http://localhost:8000**. Log in with **admin** / **admin123** (or **staff** / **staff123** if you ran the seeder).

## Release checks

Run `composer audit` and `vendor/bin/phpunit tests/Feature` from a development checkout. The tests create their own in-memory SQLite database.

Use [the cPanel deployment guide](CPANEL_DEPLOY.md) for hosting and backup instructions.

## Routes

| Route | Description |
|-------|-------------|
| `/login` | Login page |
| `/dashboard` | Dashboard (stats + recent invoices) |
| `/invoices` | Invoice list |
| `/invoices/create` | New invoice |
| `/invoices/{id}` | View invoice |
| `/invoices/{id}/edit` | Edit invoice |
| `/invoices/{id}/print` | Print view |
| `/users` | User management (admin only) |
| `/settings` | Settings page |

## Local demo logins

- **Admin:** `admin` / `admin123`
- **Staff:** `staff` / `staff123`

Use the administrator setup command for production.

## Project structure (Laravel)

```
app/
  Http/Controllers/   Auth, Dashboard, Invoice, User
  Http/Middleware/   EnsureUserHasRole
  Models/            User, Invoice, LineItem
config/
database/migrations/
database/seeders/    DatabaseSeeder
resources/views/     Blade: layout, auth, dashboard, invoices, users
routes/web.php
public/              index.php, css, js (document root)
```

## Security

- Passwords hashed with bcrypt
- CSRF protection on all forms
- Role checks on controllers (admin, staff, viewer)
- Staff can only edit/delete their own invoices; admin can do all

## License

Proprietary — Midnight Travel.
