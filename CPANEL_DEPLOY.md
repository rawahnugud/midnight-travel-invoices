# Run Midnight Travel Invoices on cPanel (Laravel)

Follow these steps to get the app running on your cPanel hosting (e.g. **invoice.midnighttravel.net**).

---

## Step 1: Get the code on the server

### Option A: Clone from GitHub (recommended)

1. In cPanel, open **Git™ Version Control**.
2. Click **Create** or **Clone**.
3. **Repository URL:** `https://github.com/RawahNugud/midnight-travel-invoices.git`
4. **Repository Path:** e.g. `midnight-travel-invoices` (folder will be created in your home directory).
5. **Repository Name:** e.g. `Midnight Travel Invoices`.
6. Create, then click **Pull** / **Update** to get the latest code.

### Option B: Upload via File Manager

Upload the project ZIP (or files) into a folder like `midnight-travel-invoices` in your home directory.

---

## Step 2: Point your domain to the `public` folder

Laravel must run with the **document root** set to the **`public`** folder inside the app.

1. In cPanel go to **Domains** (or **Subdomains**).
2. Find **invoice.midnighttravel.net** (or your domain/subdomain).
3. **Edit** the domain/subdomain.
4. Set **Document Root** to:
   ```text
   midnight-travel-invoices/public
   ```
   (Use the path **relative to your home directory** — e.g. if the app is at `/home/midn5821/midnight-travel-invoices`, the document root is `midnight-travel-invoices/public`.)

5. Save.

---

## Step 3: Install PHP dependencies (Composer)

1. In cPanel open **Terminal** (or use SSH).
2. Go to the app folder (not `public`):
   ```bash
   cd ~/midnight-travel-invoices
   ```
3. Run:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   If `composer` is not in PATH, use the full path your host gives (e.g. `php /path/to/composer.phar install --no-dev --optimize-autoloader`).

4. Wait until it finishes (creates the `vendor` folder).

---

## Step 4: Environment and app key

1. In **File Manager**, go to `midnight-travel-invoices`.
2. Copy `.env.example` to `.env` (or create `.env` with the contents of `.env.example`).
3. Edit `.env` and set at least:
   - `APP_NAME="Midnight Travel Invoices"`
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://invoice.midnighttravel.net` (your real URL)
   - `SETUP_SECRET=your-secret-string-here` (for the one-time seed URL)

4. In Terminal (from the app folder):
   ```bash
   cd ~/midnight-travel-invoices
   php artisan key:generate
   ```
   This writes `APP_KEY` into `.env`.

---

## Step 5: Database (SQLite)

1. Create the SQLite file. In Terminal:
   ```bash
   cd ~/midnight-travel-invoices
   touch database/database.sqlite
   ```
   Or in File Manager: create an empty file `database/database.sqlite`.

2. In `.env` ensure:
   ```text
   DB_CONNECTION=sqlite
   DB_DATABASE="database/database.sqlite"
   ```
   (Or use an absolute path like `/home/midn5821/midnight-travel-invoices/database/database.sqlite` if your host requires it.)

3. Run migrations:
   ```bash
   php artisan migrate --force
   ```

---

## Step 6: Seed data (admin, staff, sample invoices)

**Option A — In the browser (easiest)**  
After the site loads, open:

```text
https://invoice.midnighttravel.net/setup/seed?token=midnight-travel-setup-change-me
```

If you set `SETUP_SECRET` in `.env`, use that value instead of `midnight-travel-setup-change-me`.

**Option B — In Terminal**

```bash
cd ~/midnight-travel-invoices
php artisan db:seed
```

Then log in with **admin** / **admin123**.

---

## Step 7: Writable folders

Laravel needs to write to `storage` and `bootstrap/cache`.

In Terminal:

```bash
cd ~/midnight-travel-invoices
chmod -R 775 storage bootstrap/cache
```

If your host uses a different user/group, they may ask you to use something like `chown` as well.

---

## Step 8: Test the site

1. Open **https://invoice.midnighttravel.net** (or your URL).
2. You should see the **login** page.
3. After seeding, log in with **admin** / **admin123**.

If you see a blank page or 500 error:

- Check **storage/logs/laravel.log** for errors.
- Ensure the **document root** is `midnight-travel-invoices/public` (Step 2).
- Ensure **composer install** and **php artisan key:generate** and **migrate** were run from the app root (the folder that contains `artisan`).

---

## Quick checklist

| Step | What to do |
|------|------------|
| 1 | Clone repo (or upload) into e.g. `midnight-travel-invoices` |
| 2 | Set domain document root to `midnight-travel-invoices/public` |
| 3 | `composer install --no-dev --optimize-autoloader` in app folder |
| 4 | Copy `.env.example` to `.env`, set `APP_URL`, then `php artisan key:generate` |
| 5 | `touch database/database.sqlite` then `php artisan migrate --force` |
| 6 | Visit `/setup/seed?token=...` or run `php artisan db:seed` |
| 7 | `chmod -R 775 storage bootstrap/cache` |
| 8 | Open site and log in |

---

## Updating the app later

1. In **Git Version Control**, select the repo and click **Pull**.
2. In Terminal:
   ```bash
   cd ~/midnight-travel-invoices
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   ```
3. If you use route or config cache: `php artisan config:cache` and `php artisan route:cache` (optional).
