/**
 * One-time setup route: seed the database by visiting a URL.
 * Use: GET /setup/seed?token=YOUR_SETUP_SECRET
 * Set SETUP_SECRET in .env (or leave default for first run, then change it).
 */
const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../config/database');

const router = express.Router();
const SETUP_SECRET = process.env.SETUP_SECRET || 'midnight-travel-setup-change-me';

router.get('/seed', (req, res) => {
  const token = req.query.token;
  if (token !== SETUP_SECRET) {
    return res.status(403).send('<h1>Invalid or missing token</h1><p>Use: /setup/seed?token=YOUR_SETUP_SECRET</p>');
  }

  try {
    const adminHash = bcrypt.hashSync('admin123', 10);
    const staffHash = bcrypt.hashSync('staff123', 10);

    const existingUsers = db.prepare('SELECT COUNT(*) as c FROM users').get();
    if (existingUsers.c === 0) {
      db.prepare(`
        INSERT INTO users (username, email, password_hash, role)
        VALUES (?, ?, ?, ?)
      `).run('admin', 'admin@midnighttravel.net', adminHash, 'admin');
      db.prepare(`
        INSERT INTO users (username, email, password_hash, role)
        VALUES (?, ?, ?, ?)
      `).run('staff', 'staff@midnighttravel.net', staffHash, 'staff');
    }

    const adminId = db.prepare('SELECT id FROM users WHERE username = ?').get('admin')?.id;
    const staffId = db.prepare('SELECT id FROM users WHERE username = ?').get('staff')?.id;
    const invoiceCount = db.prepare('SELECT COUNT(*) as c FROM invoices').get().c;

    if (invoiceCount === 0 && adminId && staffId) {
      const now = new Date().toISOString().slice(0, 10);
      db.prepare(`
        INSERT INTO invoices (invoice_number, status, customer_name, customer_email, customer_phone, customer_address, invoice_date, due_date, currency, tax_rate, discount_amount, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `).run('INV-2024-001', 'paid', 'Acme Corp', 'billing@acme.com', '+1 555 0100', '123 Main St, City', now, now, 'USD', 0, 0, 'Thank you for your business.', adminId);
      const inv1 = db.prepare('SELECT id FROM invoices WHERE invoice_number = ?').get('INV-2024-001');
      db.prepare(`
        INSERT INTO line_items (invoice_id, item_name, description, quantity, unit_price, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
      `).run(inv1.id, 'Travel Package - Desert Safari', 'Full day safari with dinner', 2, 150.00, 0);
      db.prepare(`
        INSERT INTO line_items (invoice_id, item_name, description, quantity, unit_price, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
      `).run(inv1.id, 'Airport Transfer', 'Private transfer', 1, 45.00, 1);

      db.prepare(`
        INSERT INTO invoices (invoice_number, status, customer_name, customer_email, invoice_date, due_date, currency, tax_rate, discount_amount, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `).run('INV-2024-002', 'pending', 'Jane Smith', 'jane@example.com', now, now, 'USD', 0, 0, staffId);
      const inv2 = db.prepare('SELECT id FROM invoices WHERE invoice_number = ?').get('INV-2024-002');
      db.prepare(`
        INSERT INTO line_items (invoice_id, item_name, description, quantity, unit_price, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
      `).run(inv2.id, 'Luxury Hotel Stay', '3 nights - Sea View', 3, 280.00, 0);
    }

    res.send(`
      <!DOCTYPE html>
      <html><head><meta charset="utf-8"><title>Seed complete</title></head>
      <body style="font-family:sans-serif;max-width:500px;margin:2rem auto;padding:1rem;">
        <h1>Seed complete</h1>
        <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
        <p>Staff: <strong>staff</strong> / <strong>staff123</strong></p>
        <p>Sample invoices were created. You can now <a href="/login">log in</a>.</p>
        <p style="color:#666;font-size:0.9rem;">For security, set SETUP_SECRET in .env and do not share this URL.</p>
      </body></html>
    `);
  } catch (err) {
    console.error('Setup seed error:', err);
    res.status(500).send('<h1>Seed error</h1><pre>' + String(err.message) + '</pre>');
  }
});

module.exports = router;
