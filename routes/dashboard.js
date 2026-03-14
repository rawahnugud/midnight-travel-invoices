const express = require('express');
const db = require('../config/database');

const router = express.Router();

router.get('/', (req, res) => {
  const userId = req.user.id;
  const isAdmin = req.user.role === 'admin';

  const totalInvoices = db.prepare(
    isAdmin ? 'SELECT COUNT(*) as c FROM invoices' : 'SELECT COUNT(*) as c FROM invoices WHERE created_by = ?'
  ).get(isAdmin ? [] : [userId]).c;

  const paidCount = db.prepare(
    isAdmin
      ? "SELECT COUNT(*) as c FROM invoices WHERE status = 'paid'"
      : "SELECT COUNT(*) as c FROM invoices WHERE status = 'paid' AND created_by = ?"
  ).get(isAdmin ? [] : [userId]).c;

  const pendingCount = db.prepare(
    isAdmin
      ? "SELECT COUNT(*) as c FROM invoices WHERE status = 'pending'"
      : "SELECT COUNT(*) as c FROM invoices WHERE status = 'pending' AND created_by = ?"
  ).get(isAdmin ? [] : [userId]).c;

  const paidInvoices = db.prepare(
    isAdmin ? "SELECT id, tax_rate, discount_amount FROM invoices WHERE status = 'paid'" : "SELECT id, tax_rate, discount_amount FROM invoices WHERE status = 'paid' AND created_by = ?"
  ).all(isAdmin ? [] : [userId]);
  let totalRevenue = 0;
  paidInvoices.forEach((inv) => {
    const row = db.prepare('SELECT COALESCE(SUM(quantity * unit_price), 0) as st FROM line_items WHERE invoice_id = ?').get(inv.id);
    const st = row?.st ?? 0;
    totalRevenue += st + (inv.tax_rate / 100) * st - (inv.discount_amount || 0);
  });

  const recentSql = isAdmin
    ? `SELECT i.*, u.username as created_by_name,
       (SELECT COALESCE(SUM(li.quantity * li.unit_price), 0) FROM line_items li WHERE li.invoice_id = i.id) as subtotal
       FROM invoices i LEFT JOIN users u ON i.created_by = u.id ORDER BY i.created_at DESC LIMIT 10`
    : `SELECT i.*, u.username as created_by_name,
       (SELECT COALESCE(SUM(li.quantity * li.unit_price), 0) FROM line_items li WHERE li.invoice_id = i.id) as subtotal
       FROM invoices i LEFT JOIN users u ON i.created_by = u.id WHERE i.created_by = ? ORDER BY i.created_at DESC LIMIT 10`;
  const recent = db.prepare(recentSql).all(isAdmin ? [] : [userId]);

  res.render('dashboard', {
    title: 'Dashboard',
    pageTitle: 'Dashboard',
    activePage: 'dashboard',
    totalInvoices,
    paidCount,
    pendingCount,
    totalRevenue,
    recent,
  });
});

module.exports = router;
