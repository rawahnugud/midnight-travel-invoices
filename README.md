# Midnight Travel — Invoice Management System

A production-ready invoice web app with dashboard, CRUD invoices, user management, and print-ready layouts.

## Stack

- **Backend:** Node.js, Express
- **Database:** SQLite (single file, no separate server)
- **Views:** EJS (server-rendered)
- **Auth:** Session-based (express-session), bcrypt for passwords, CSRF protection

## Quick start

### 1. Install dependencies

```bash
npm install
```

### 2. Initialize database and seed data

The database file is created on first run. To create tables and seed users + sample invoices:

```bash
npm run seed
```

This creates:

- **Admin:** `admin` / `admin123`
- **Staff:** `staff` / `staff123`
- Two sample invoices

### 3. Run the app

```bash
npm start
```

Open **http://localhost:3000** and log in with `admin` / `admin123`.

## Environment (optional)

Copy `.env.example` to `.env` and set:

- `SESSION_SECRET` — long random string for session signing
- `PORT` — default 3000
- `DATABASE_PATH` — path to SQLite file (default `./data/invoices.db`)

## Roles

| Role   | Can do |
|--------|--------|
| Admin  | All: manage users, all invoices, create/edit/delete any invoice, print |
| Staff  | Create invoices, edit/delete own invoices, print, view own |
| Viewer | Read-only: view invoices (no create/edit/delete) |

## Routes

- `/login` — Sign in
- `/dashboard` — Summary cards + recent invoices
- `/invoices` — List (filtered by role)
- `/invoices/new` — Create invoice
- `/invoices/:id` — View
- `/invoices/:id/edit` — Edit
- `/invoices/:id/print` — Print view (opens in new tab, browser Print → PDF)
- `/users` — User management (admin only)
- `/settings` — Settings placeholder

## Project structure

```
├── server.js           # Entry, session, CSRF, routes
├── config/database.js  # SQLite connection
├── db/
│   ├── schema.sql      # Tables
│   └── seed.sql        # (Seed logic in scripts/seed.js)
├── middleware/
│   ├── auth.js         # requireAuth, requireRole, canEditInvoice
│   └── csrf.js         # CSRF protection
├── routes/
│   ├── index.js        # Mount all routes
│   ├── auth.js         # Login, logout
│   ├── dashboard.js   # Dashboard stats + recent
│   ├── invoices.js     # Invoice CRUD + print
│   └── users.js        # User CRUD (admin)
├── views/              # EJS templates
├── public/
│   ├── css/style.css   # Main UI
│   ├── css/print.css   # Invoice print
│   └── js/app.js       # Line items, user modal, confirm
└── scripts/
    ├── init-db.js      # Create tables (optional; server also runs schema)
    └── seed.js         # Seed users + sample invoices
```

## Invoice printing

- Use **Print** from the invoice view or list (opens `/invoices/:id/print`).
- In the print window, use **Print** or **Save as PDF**.
- `public/css/print.css` styles the invoice for A4.

## Branding

- **Midnight Travel** name and premium/luxury style are used in layout and invoice print.
- Colors: dark navy, maroon accent, gold accent (see `:root` in `style.css`).

## Security

- Passwords hashed with bcrypt.
- Session-based auth; role checks on every protected route.
- Staff can only edit/delete invoices they created (unless admin).
- CSRF token on all state-changing forms.
- Input validation via express-validator where used.

## License

Private use for Midnight Travel.
