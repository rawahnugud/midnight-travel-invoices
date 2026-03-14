const bcrypt = require('bcryptjs');
const path = require('path');
const fs = require('fs');
const Database = require('better-sqlite3');

const dbPath = process.env.DATABASE_PATH || path.join(__dirname, '..', 'data', 'invoices.db');
const schemaPath = path.join(__dirname, '..', 'db', 'schema.sql');

if (!fs.existsSync(dbPath)) {
  const dir = path.dirname(dbPath);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  const db = new Database(dbPath);
  db.exec(fs.readFileSync(schemaPath, 'utf8'));
  db.close();
}

const db = new Database(dbPath);
const adminHash = bcrypt.hashSync('admin123', 10);
const staffHash = bcrypt.hashSync('staff123', 10);

// Insert users if not exist
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
  console.log('Users created: admin / admin123, staff / staff123');
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

  console.log('Sample invoices created.');
}

db.close();
console.log('Seed complete.');
