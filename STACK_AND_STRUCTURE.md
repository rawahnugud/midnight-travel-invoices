# Midnight Travel Invoice System — Stack & Structure

## Stack

| Layer      | Choice        | Reason |
|-----------|----------------|--------|
| Runtime   | Node.js        | Single runtime, easy local run and deploy |
| Backend   | Express        | Simple, stable, widely used |
| Database  | SQLite         | No separate server, one file, easy backup and deploy |
| Views     | EJS            | Server-rendered HTML, minimal front-end complexity |
| Auth      | express-session + bcrypt | Session-based login, secure passwords |
| Security  | csurf, validation, escape | CSRF, input checks, XSS prevention |

**Why not PHP/MySQL?** Node + SQLite gives one `npm install` and `node server.js`; no Apache/PHP or MySQL setup. You can still deploy to any Node host or run locally without extra services.

---

## Folder Structure

```
Inovice Project/
├── package.json
├── .env.example
├── README.md
├── server.js                 # App entry
├── config/
│   └── database.js           # SQLite connection
├── db/
│   ├── schema.sql            # Tables
│   └── seed.sql              # Admin, staff, sample invoices
├── middleware/
│   ├── auth.js               # Require login, role checks
│   └── csrf.js               # CSRF token
├── routes/
│   ├── index.js              # Mount routes
│   ├── auth.js               # Login, logout
│   ├── dashboard.js          # Dashboard page
│   ├── invoices.js           # CRUD + print
│   └── users.js              # User management (admin)
├── public/
│   ├── css/
│   │   ├── style.css         # Main UI
│   │   └── print.css         # Invoice print
│   ├── js/
│   │   └── app.js            # Client-side (forms, confirm)
│   └── images/
│       └── logo.png          # Placeholder
├── views/
│   ├── layout.ejs            # Main shell (sidebar + content)
│   ├── login.ejs
│   ├── dashboard.ejs
│   ├── invoices/
│   │   ├── list.ejs
│   │   ├── new.ejs
│   │   ├── view.ejs
│   │   └── edit.ejs
│   └── users/
│       └── list.ejs
└── rollback_backup/          # (existing backup)
```

---

## Routes

- `GET  /login`          — Login form
- `POST /login`          — Login
- `POST /logout`         — Logout
- `GET  /dashboard`      — Dashboard (summary + recent invoices)
- `GET  /invoices`       — Invoice list (filtered by role)
- `GET  /invoices/new`   — New invoice form
- `POST /invoices`       — Create invoice
- `GET  /invoices/:id`   — View invoice
- `GET  /invoices/:id/edit` — Edit form
- `PUT  /invoices/:id`   — Update invoice
- `DELETE /invoices/:id` — Delete (with confirm)
- `GET  /invoices/:id/print` — Print view
- `GET  /users`          — User list (admin)
- `POST /users`          — Create user (admin)
- `PUT  /users/:id`      — Update user (admin)
- `DELETE /users/:id`    — Delete user (admin)

Roles: `admin` (full), `staff` (own invoices + create), `viewer` (read-only).
