const express = require('express');
const auth = require('./auth');
const setup = require('./setup');
const dashboard = require('./dashboard');
const invoices = require('./invoices');
const users = require('./users');
const { requireAuth } = require('../middleware/auth');
const { csrfProtection } = require('../middleware/csrf');

const router = express.Router();

// Auth (no requireAuth) - auth router has GET/POST /login, POST /logout
router.use(auth);
// One-time setup: seed DB by visiting /setup/seed?token=SETUP_SECRET (no auth)
router.use('/setup', setup);

// Protected routes
router.use(requireAuth);
router.use(csrfProtection);

router.use('/dashboard', dashboard);
router.use('/invoices', invoices);
router.use('/users', users);

router.get('/', (req, res) => res.redirect('/dashboard'));
router.get('/settings', (req, res) => res.render('settings', { title: 'Settings', pageTitle: 'Settings', activePage: 'settings' }));

module.exports = router;
