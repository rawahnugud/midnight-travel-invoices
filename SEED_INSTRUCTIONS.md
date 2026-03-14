# How to Load Seed Data (Admin, Staff, Sample Invoices)

The seed script creates:
- **Admin user:** `admin` / `admin123`
- **Staff user:** `staff` / `staff123`
- **2 sample invoices** with line items

Run it **once** in the environment where your app (and database) runs.

---

## On your Windows PC (local)

1. Open a terminal where **Node.js** is available (e.g. Command Prompt, PowerShell, or VS Code terminal with Node in PATH).
2. Go to the project folder and run:

```bash
cd "d:\Web Development Projects\Inovice Project"
npm run seed
```

Or:

```bash
node scripts/seed.js
```

3. You should see: `Users created: admin / admin123, staff / staff123` and `Sample invoices created.` then `Seed complete.`
4. Start the app (`npm start`) and log in at http://localhost:3000 with `admin` / `admin123`.

---

## On cPanel (production server)

After you’ve cloned the repo and set up the Node.js app:

1. **SSH:** If you have SSH access, go to the app directory and run:
   ```bash
   cd ~/midnight-travel-invoices
   # or: cd ~/invoice.midnighttravel.net  (your actual path)
   node scripts/seed.js
   ```

2. **No SSH:** Use cPanel’s **Terminal** (if available) and run the same commands in the app folder.

3. **Application start script:** Some hosts let you run a one-off command when the app is first created; you can run `node scripts/seed.js` there once.

The database file is created in the app’s `data/` folder. Running the seed **once** is enough; it skips creating users/invoices if they already exist.

---

## Summary

| Where   | Command              | When        |
|---------|----------------------|-------------|
| Local   | `npm run seed`       | Once, after first run |
| cPanel  | `node scripts/seed.js` in app folder | Once, after deploy   |

No need to “upload” the database file: the seed script **creates** the data on the machine where it runs (your PC or the server).
