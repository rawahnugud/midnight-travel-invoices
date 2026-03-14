const express = require('express');
const db = require('../config/database');
const { requireAuth, requireRole, canEditInvoice, canViewInvoice } = require('../middleware/auth');
const { csrfProtection } = require('../middleware/csrf');
const { body, validationResult } = require('express-validator');

const router = express.Router();
router.use(requireAuth);

function getNextInvoiceNumber() {
  const row = db.prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE 'INV-%' ORDER BY id DESC LIMIT 1").get();
  if (!row) return 'INV-2024-001';
  const match = row.invoice_number.match(/INV-(\d+)-(\d+)/);
  const year = match ? match[1] : new Date().getFullYear();
  const num = match ? parseInt(match[2], 10) + 1 : 1;
  return `INV-${year}-${String(num).padStart(3, '0')}`;
}

function getInvoiceWithItems(id) {
  const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(id);
  if (!invoice) return null;
  invoice.items = db.prepare('SELECT * FROM line_items WHERE invoice_id = ? ORDER BY sort_order, id').all(id);
  invoice.created_by_name = db.prepare('SELECT username FROM users WHERE id = ?').get(invoice.created_by)?.username || '—';
  return invoice;
}

router.get('/', (req, res) => {
  const isAdmin = req.user.role === 'admin';
  const sql = isAdmin
    ? 'SELECT i.*, u.username as created_by_name, (SELECT COALESCE(SUM(li.quantity * li.unit_price), 0) FROM line_items li WHERE li.invoice_id = i.id) as subtotal FROM invoices i LEFT JOIN users u ON i.created_by = u.id ORDER BY i.created_at DESC'
    : 'SELECT i.*, u.username as created_by_name, (SELECT COALESCE(SUM(li.quantity * li.unit_price), 0) FROM line_items li WHERE li.invoice_id = i.id) as subtotal FROM invoices i LEFT JOIN users u ON i.created_by = u.id WHERE i.created_by = ? ORDER BY i.created_at DESC';
  const invoices = db.prepare(sql).all(isAdmin ? [] : [req.user.id]);
  invoices.forEach(inv => {
    const sub = inv.subtotal || 0;
    inv.total = sub + (inv.tax_rate / 100) * sub - (inv.discount_amount || 0);
  });
  res.render('invoices/list', { title: 'Invoices', invoices, activePage: 'invoices' });
});

router.get('/new', requireRole('admin', 'staff'), csrfProtection, (req, res) => {
  const today = new Date().toISOString().slice(0, 10);
  res.render('invoices/new', {
    title: 'New Invoice',
    pageTitle: 'New Invoice',
    activePage: 'invoices',
    invoice: { invoice_number: getNextInvoiceNumber(), invoice_date: today, due_date: today, currency: 'USD', tax_rate: 0, discount_amount: 0, items: [{ item_name: '', description: '', quantity: 1, unit_price: 0 }] },
    csrfToken: req.csrfToken?.(),
  });
});

const invoiceValidators = [
  body('customer_name').trim().notEmpty().withMessage('Customer name required'),
  body('invoice_date').notEmpty().withMessage('Invoice date required'),
  body('currency').trim().notEmpty(),
  body('tax_rate').isFloat({ min: 0, max: 100 }).toFloat(),
  body('discount_amount').isFloat({ min: 0 }).toFloat(),
];

router.post('/',
  requireRole('admin', 'staff'),
  csrfProtection,
  invoiceValidators,
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      const items = (req.body.items || [{}]).map((it, i) => ({
        item_name: it.item_name || '',
        description: it.description || '',
        quantity: parseFloat(it.quantity) || 1,
        unit_price: parseFloat(it.unit_price) || 0,
      }));
      return res.render('invoices/new', {
        title: 'New Invoice',
        pageTitle: 'New Invoice',
        activePage: 'invoices',
        errors: errors.array(),
        invoice: { ...req.body, items },
        csrfToken: req.csrfToken?.(),
      });
    }
    const inv = req.body;
    const result = db.prepare(`
      INSERT INTO invoices (invoice_number, status, customer_name, customer_email, customer_phone, customer_address, invoice_date, due_date, currency, tax_rate, discount_amount, notes, terms, created_by)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `).run(
      inv.invoice_number || getNextInvoiceNumber(),
      inv.status || 'draft',
      inv.customer_name,
      inv.customer_email || null,
      inv.customer_phone || null,
      inv.customer_address || null,
      inv.invoice_date,
      inv.due_date || null,
      inv.currency || 'USD',
      parseFloat(inv.tax_rate) || 0,
      parseFloat(inv.discount_amount) || 0,
      inv.notes || null,
      inv.terms || null,
      req.user.id
    );
    const invoiceId = result.lastInsertRowid;
    const items = Array.isArray(inv.items) ? inv.items : [];
    const stmt = db.prepare('INSERT INTO line_items (invoice_id, item_name, description, quantity, unit_price, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    items.forEach((it, i) => {
      if (it.item_name || it.quantity || it.unit_price) {
        stmt.run(invoiceId, it.item_name || '', it.description || '', parseFloat(it.quantity) || 1, parseFloat(it.unit_price) || 0, i);
      }
    });
    res.redirect('/invoices/' + invoiceId);
  }
);

router.get('/:id', (req, res) => {
  const invoice = getInvoiceWithItems(req.params.id);
  if (!invoice) return res.status(404).send('Invoice not found');
  if (!canViewInvoice(req, invoice)) return res.status(403).send('Forbidden');
  res.render('invoices/view', { title: 'Invoice ' + invoice.invoice_number, pageTitle: 'Invoice ' + invoice.invoice_number, activePage: 'invoices', invoice });
});

router.get('/:id/print', (req, res) => {
  const invoice = getInvoiceWithItems(req.params.id);
  if (!invoice) return res.status(404).send('Invoice not found');
  if (!canViewInvoice(req, invoice)) return res.status(403).send('Forbidden');
  res.render('invoices/print', { title: 'Invoice ' + invoice.invoice_number, invoice, layout: false });
});

router.get('/:id/edit', requireRole('admin', 'staff'), csrfProtection, (req, res) => {
  const invoice = getInvoiceWithItems(req.params.id);
  if (!invoice) return res.status(404).send('Invoice not found');
  if (!canEditInvoice(req, invoice)) return res.status(403).send('Forbidden');
  if (!invoice.items?.length) invoice.items = [{ item_name: '', description: '', quantity: 1, unit_price: 0 }];
  res.render('invoices/edit', { title: 'Edit Invoice', pageTitle: 'Edit Invoice', activePage: 'invoices', invoice, csrfToken: req.csrfToken?.() });
});

router.put('/:id',
  requireRole('admin', 'staff'),
  csrfProtection,
  invoiceValidators,
  (req, res) => {
    const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(req.params.id);
    if (!invoice) return res.status(404).send('Invoice not found');
    if (!canEditInvoice(req, invoice)) return res.status(403).send('Forbidden');
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      const inv = getInvoiceWithItems(req.params.id);
      inv.items = inv.items?.length ? inv.items : [{ item_name: '', description: '', quantity: 1, unit_price: 0 }];
      return res.render('invoices/edit', { title: 'Edit Invoice', pageTitle: 'Edit Invoice', activePage: 'invoices', invoice: { ...req.body, id: req.params.id, items: req.body.items || inv.items }, errors: errors.array(), csrfToken: req.csrfToken?.() });
    }
    const inv = req.body;
    db.prepare(`
      UPDATE invoices SET status = ?, customer_name = ?, customer_email = ?, customer_phone = ?, customer_address = ?, invoice_date = ?, due_date = ?, currency = ?, tax_rate = ?, discount_amount = ?, notes = ?, terms = ?, updated_at = datetime('now')
      WHERE id = ?
    `).run(inv.status || 'draft', inv.customer_name, inv.customer_email || null, inv.customer_phone || null, inv.customer_address || null, inv.invoice_date, inv.due_date || null, inv.currency || 'USD', parseFloat(inv.tax_rate) || 0, parseFloat(inv.discount_amount) || 0, inv.notes || null, inv.terms || null, req.params.id);
    db.prepare('DELETE FROM line_items WHERE invoice_id = ?').run(req.params.id);
    const items = Array.isArray(inv.items) ? inv.items : [];
    const stmt = db.prepare('INSERT INTO line_items (invoice_id, item_name, description, quantity, unit_price, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    items.forEach((it, i) => {
      if (it.item_name || it.quantity || it.unit_price) {
        stmt.run(req.params.id, it.item_name || '', it.description || '', parseFloat(it.quantity) || 1, parseFloat(it.unit_price) || 0, i);
      }
    });
    res.redirect('/invoices/' + req.params.id);
  }
);

router.delete('/:id', requireRole('admin', 'staff'), csrfProtection, (req, res) => {
  const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(req.params.id);
  if (!invoice) return res.status(404).json({ error: 'Not found' });
  if (!canEditInvoice(req, invoice)) return res.status(403).json({ error: 'Forbidden' });
  db.prepare('DELETE FROM line_items WHERE invoice_id = ?').run(req.params.id);
  db.prepare('DELETE FROM invoices WHERE id = ?').run(req.params.id);
  if (req.xhr || req.headers.accept?.includes('application/json')) {
    return res.json({ success: true });
  }
  res.redirect('/invoices');
});

module.exports = router;
