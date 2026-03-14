const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../config/database');
const { csrfProtection } = require('../middleware/csrf');
const { body, validationResult } = require('express-validator');

const router = express.Router();

router.get('/login', (req, res) => {
  if (req.session?.userId) return res.redirect('/dashboard');
  res.render('login', { title: 'Login', csrfToken: req.csrfToken?.(), layout: false });
});

router.post('/login',
  csrfProtection,
  body('username').trim().notEmpty().withMessage('Username required'),
  body('password').notEmpty().withMessage('Password required'),
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.render('login', { title: 'Login', errors: errors.array(), csrfToken: req.csrfToken?.(), layout: false });
    }
    const { username, password } = req.body;
    const user = db.prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?').get(username);
    if (!user || !bcrypt.compareSync(password, user.password_hash)) {
      return res.render('login', { title: 'Login', error: 'Invalid username or password', csrfToken: req.csrfToken?.(), layout: false });
    }
    req.session.userId = user.id;
    req.session.username = user.username;
    req.session.role = user.role;
    res.redirect(req.session.returnTo || '/dashboard');
    delete req.session.returnTo;
  }
);

router.post('/logout', (req, res) => {
  req.session.destroy();
  res.redirect('/login');
});

module.exports = router;
