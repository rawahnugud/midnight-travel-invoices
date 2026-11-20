# Midnight Travel — Invoice Management System (Laravel)

Production-ready invoice web app built with **PHP Laravel 10**. Runs on any host with PHP 8.1+ and Composer (including cPanel shared hosting).

## Features

- **Authentication:** Login with username/password, session-based
- **Roles:** Admin (full access), Staff (create & edit own invoices), Viewer (read-only)
- **Dashboard:** Total/paid/pending invoices, revenue, recent list
- **Invoices:** Create, edit, view, delete, print/PDF-ready
- **Users:** Admin can manage users (create, edit, delete)

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
   - Set `APP_URL` to your public URL (e.g. `https://invoice.yourdomain.com`) so links and assets use clean URLs
   - For SQLite: ensure `DB_DATABASE` points to an absolute path or `database/database.sqlite` (create the file: `touch database/database.sqlite`)

5. **Run migrations:**
   ```bash
   php artisan migrate --force
   ```

6. **Create the first administrator** with `php artisan invoices:create-admin admin`. Use a new password at the hidden prompts. Demo seeding is restricted to local and testing environments.

7. **Permissions:** Ensure `storage` and `bootstrap/cache` are writable:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

8. **Performance (recommended for production):** Cache config, routes, and views so the app runs faster:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   Re-run these after changing config, routes, or Blade templates. Use `config:clear`, `route:clear`, or `view:clear` to clear each cache.

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
