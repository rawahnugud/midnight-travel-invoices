const db = require('../config/database');

function requireAuth(req, res, next) {
  if (!req.session || !req.session.userId) {
    if (req.xhr || req.headers.accept?.includes('application/json')) {
      return res.status(401).json({ error: 'Unauthorized' });
    }
    return res.redirect('/login');
  }
  const user = db.prepare('SELECT id, username, email, role FROM users WHERE id = ?').get(req.session.userId);
  if (!user) {
    req.session.destroy();
    return res.redirect('/login');
  }
  req.user = user;
  res.locals.user = user;
  next();
}

function requireRole(...roles) {
  return (req, res, next) => {
    if (!req.user) return res.redirect('/login');
    if (roles.includes(req.user.role)) return next();
    res.status(403).send('Forbidden');
  };
}

function canEditInvoice(req, invoice) {
  if (!req.user) return false;
  if (req.user.role === 'admin') return true;
  if (req.user.role === 'viewer') return false;
  return invoice.created_by === req.user.id;
}

function canViewInvoice(req, invoice) {
  if (!req.user) return false;
  if (req.user.role === 'admin') return true;
  if (req.user.role === 'viewer') return true;
  return invoice.created_by === req.user.id;
}

module.exports = {
  requireAuth,
  requireRole,
  canEditInvoice,
  canViewInvoice,
};
