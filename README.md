# Midnight Travel — Invoice Management System (Laravel)

Production-ready invoice web app built with **PHP Laravel 10**. Runs on any host with PHP 8.1+ and Composer (including cPanel shared hosting).

**Project state:** App and mobile UX are in a complete, production-ready state. The only area not yet optimized is the **print page** (invoice print/PDF view). All other pages (dashboard, invoices, users, settings, forms, tables) are mobile-friendly with sidebar, FAB, card layouts, and single-column forms.

## Features

- **Authentication:** Login with username/password, session-based
- **Roles:** Admin (full access), Staff (create & edit own invoices), Viewer (read-only)
- **Dashboard:** Total/paid/pending invoices, revenue, recent list
- **Invoices:** Create, edit, view, delete, print/PDF-ready
- **Users:** Admin can manage users (create, edit, delete)
- **Setup URL:** Seed database via browser: `/setup/seed?token=YOUR_SETUP_SECRET`

## Requirements

- PHP 8.1+
- Composer
- SQLite (default) or MySQL
- Laravel 10

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
# Or use the setup URL in the browser after starting the server (see below)
```

Then start the built-in server:

```bash
php artisan serve
```

Open **http://localhost:8000**. Log in with **admin** / **admin123** (or **staff** / **staff123** if you ran the seeder).

## Deploy to cPanel

1. **Upload or clone** the project into a folder (e.g. `midnight-travel-invoices` or `invoice.midnighttravel.net`).

2. **Set the document root** to the `public` folder:
   - In cPanel → Domains → (your domain/subdomain) → Document Root: e.g. `midnight-travel-invoices/public`
   - Or point the domain to `public` inside the app folder.

3. **Install dependencies** (in the project root, not inside `public`):
   ```bash
   cd /home/youruser/midnight-travel-invoices
   composer install --no-dev --optimize-autoloader
   ```

4. **Environment:**
   - Copy `.env.example` to `.env`
   - Run `php artisan key:generate`
   - Set `APP_ENV=production`, `APP_DEBUG=false`, and a strong `APP_KEY`
   - For SQLite: ensure `DB_DATABASE` points to an absolute path or `database/database.sqlite` (create the file: `touch database/database.sqlite`)

5. **Run migrations:**
   ```bash
   php artisan migrate --force
   ```

6. **Seed data (one-time):** Either run `php artisan db:seed` or visit in the browser:
   ```
   https://your-domain.com/setup/seed?token=midnight-travel-setup-change-me
   ```
   Then set `SETUP_SECRET` in `.env` to a secret value and use that in the URL instead.

7. **Permissions:** Ensure `storage` and `bootstrap/cache` are writable:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

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
| `/setup/seed?token=...` | One-time seed (admin, staff, sample invoices) |

## Default logins (after seed)

- **Admin:** `admin` / `admin123`
- **Staff:** `staff` / `staff123`

Change these in production.

## Project structure (Laravel)

```
app/
  Http/Controllers/   Auth, Dashboard, Invoice, User, Setup
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
