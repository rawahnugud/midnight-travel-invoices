const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../config/database');
const { requireAuth, requireRole } = require('../middleware/auth');
const { csrfProtection } = require('../middleware/csrf');
const { body, validationResult } = require('express-validator');

const router = express.Router();
router.use(requireAuth);
router.use(requireRole('admin'));

router.get('/', csrfProtection, (req, res) => {
  const users = db.prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id').all();
  res.render('users/list', { title: 'Users', users, activePage: 'users', pageTitle: 'Users', csrfToken: req.csrfToken?.() });
});

router.post('/',
  csrfProtection,
  body('username').trim().notEmpty().isLength({ max: 100 }),
  body('password').isLength({ min: 6 }).withMessage('Password at least 6 characters'),
  body('role').isIn(['admin', 'staff', 'viewer']),
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      const users = db.prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id').all();
      return res.render('users/list', { title: 'Users', users, errors: errors.array(), activePage: 'users', pageTitle: 'Users', csrfToken: req.csrfToken?.() });
    }
    const existing = db.prepare('SELECT id FROM users WHERE username = ?').get(req.body.username);
    if (existing) {
      const users = db.prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id').all();
      return res.render('users/list', { title: 'Users', users, errors: [{ msg: 'Username already exists' }], activePage: 'users', pageTitle: 'Users', csrfToken: req.csrfToken?.() });
    }
    const hash = bcrypt.hashSync(req.body.password, 10);
    db.prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)').run(
      req.body.username,
      req.body.email || null,
      hash,
      req.body.role
    );
    res.redirect('/users');
  }
);

router.put('/:id',
  csrfProtection,
  body('username').trim().notEmpty().isLength({ max: 100 }),
  body('role').isIn(['admin', 'staff', 'viewer']),
  (req, res) => {
    const id = parseInt(req.params.id, 10);
    if (id === req.user.id && req.body.role !== 'admin') {
      const users = db.prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id').all();
      return res.render('users/list', { title: 'Users', users, errors: [{ msg: 'You cannot demote yourself' }], activePage: 'users', pageTitle: 'Users', csrfToken: req.csrfToken?.() });
    }
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      const users = db.prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id').all();
      return res.render('users/list', { title: 'Users', users, errors: errors.array(), activePage: 'users', pageTitle: 'Users', csrfToken: req.csrfToken?.() });
    }
    const existing = db.prepare('SELECT id FROM users WHERE username = ? AND id != ?').get(req.body.username, id);
    if (existing) {
      const users = db.prepare('SELECT id, username, email, role, created_at FROM users ORDER BY id').all();
      return res.render('users/list', { title: 'Users', users, errors: [{ msg: 'Username already exists' }], activePage: 'users', pageTitle: 'Users', csrfToken: req.csrfToken?.() });
    }
    db.prepare('UPDATE users SET username = ?, email = ?, role = ?, updated_at = datetime(\'now\') WHERE id = ?').run(
      req.body.username,
      req.body.email || null,
      req.body.role,
      id
    );
    if (req.body.new_password && req.body.new_password.length >= 6) {
      const hash = bcrypt.hashSync(req.body.new_password, 10);
      db.prepare('UPDATE users SET password_hash = ? WHERE id = ?').run(hash, id);
    }
    res.redirect('/users');
  }
);

router.delete('/:id', csrfProtection, (req, res) => {
  const id = parseInt(req.params.id, 10);
  if (id === req.user.id) return res.status(400).send('Cannot delete yourself');
  db.prepare('DELETE FROM users WHERE id = ?').run(id);
  if (req.xhr || req.headers.accept?.includes('application/json')) return res.json({ success: true });
  res.redirect('/users');
});

module.exports = router;
